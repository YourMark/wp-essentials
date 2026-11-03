<?php
/**
 * Login rate limiting.
 *
 * @package YourMark\Essentials
 */

namespace YourMark\Essentials\Hardening;

use WP_Error;
use WP_User;

/**
 * Locks an IP address out of logging in after too many failed attempts.
 *
 * Ten failures within the window lock that address out for it, a correct
 * password included, so a guess that lands during the lockout gives nothing
 * away. Generous on purpose: behind a proxy nobody configured, every visitor
 * shares the proxy's address, and a lockout then hits real users too.
 *
 * The address is REMOTE_ADDR. Only when that is a proxy listed through the
 * trusted_proxies filter does X-Forwarded-For count, since anyone can send
 * that header.
 */
final class LoginLimit {

	/**
	 * Failed attempts allowed per address within the window.
	 */
	private const ATTEMPTS = 10;

	/**
	 * Seconds the window, and so the lockout, lasts.
	 */
	private const WINDOW = 15 * MINUTE_IN_SECONDS;

	/**
	 * Error code of a locked-out attempt.
	 */
	private const ERROR = 'too_many_attempts';

	/**
	 * Hook in.
	 *
	 * @return void
	 */
	public static function register(): void {
		// After core and Login's generic error, so nothing replaces the lockout.
		add_filter( 'authenticate', [ self::class, 'refuse_when_locked' ], 100 );
		add_action( 'wp_login_failed', [ self::class, 'count_failure' ], 10, 2 );
		add_action( 'wp_login', [ self::class, 'reset' ] );
		add_filter( 'shake_error_codes', [ self::class, 'shake' ] );
	}

	/**
	 * Refuse the attempt while the address is locked out.
	 *
	 * @param WP_User|WP_Error|null $user Result so far.
	 * @return WP_User|WP_Error|null
	 */
	public static function refuse_when_locked( $user ) {
		if ( self::failures() < self::attempts() ) {
			return $user;
		}

		return new WP_Error(
			self::ERROR,
			sprintf(
				/* translators: %d: minutes until the next attempt is allowed */
				__( '<strong>Error:</strong> Too many failed login attempts. Try again in %d minutes.', 'yourmark-essentials' ),
				(int) ceil( self::window() / MINUTE_IN_SECONDS )
			)
		);
	}

	/**
	 * Count a failed attempt, but not one refused for the lockout itself.
	 *
	 * @param string        $username Username or email address tried.
	 * @param WP_Error|null $error    Why it failed.
	 * @return void
	 */
	public static function count_failure( string $username, $error = null ): void {
		if ( $error instanceof WP_Error && $error->get_error_code() === self::ERROR ) {
			return;
		}

		set_site_transient( self::key(), self::failures() + 1, self::window() );
	}

	/**
	 * Clear the count once the address logs in.
	 *
	 * @return void
	 */
	public static function reset(): void {
		delete_site_transient( self::key() );
	}

	/**
	 * Shake the form for a lockout, as for a wrong password.
	 *
	 * @param string[] $codes Error codes that shake the login form.
	 * @return string[]
	 */
	public static function shake( array $codes ): array {
		$codes[] = self::ERROR;

		return $codes;
	}

	/**
	 * Failed attempts from this address within the window.
	 *
	 * @return int
	 */
	private static function failures(): int {
		return (int) get_site_transient( self::key() );
	}

	/**
	 * Transient key for this address. Site-wide on a network, which shares one login.
	 *
	 * @return string
	 */
	private static function key(): string {
		return 'yourmark_login_' . md5( self::client_ip() );
	}

	/**
	 * Attempts allowed per window.
	 *
	 * @return int
	 */
	private static function attempts(): int {
		/**
		 * Filters how many failed logins an address may make within the window.
		 *
		 * @param int $attempts Default 10.
		 */
		return max( 1, (int) apply_filters( 'yourmark/hardening/login_attempts', self::ATTEMPTS ) );
	}

	/**
	 * Length of the window and the lockout, in seconds.
	 *
	 * @return int
	 */
	private static function window(): int {
		/**
		 * Filters how long failed logins count, and so how long a lockout lasts, in seconds.
		 *
		 * @param int $seconds Default 900.
		 */
		return max( MINUTE_IN_SECONDS, (int) apply_filters( 'yourmark/hardening/login_window', self::WINDOW ) );
	}

	/**
	 * The visitor's address, read through trusted proxies only.
	 *
	 * Walks X-Forwarded-For from the right, the end the nearest proxy wrote,
	 * and stops at the first address that is not a trusted proxy.
	 *
	 * @return string
	 */
	public static function client_ip(): string {
		$remote = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

		/**
		 * Filters the proxies whose X-Forwarded-For header is believed, as IPs or CIDR ranges.
		 *
		 * @param string[] $proxies Default none.
		 */
		$proxies = (array) apply_filters( 'yourmark/hardening/trusted_proxies', [] );

		if ( ! $proxies || ! self::in_ranges( $remote, $proxies ) || empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			return $remote;
		}

		$chain = array_map( 'trim', explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) ) );

		foreach ( array_reverse( $chain ) as $address ) {
			if ( ! filter_var( $address, FILTER_VALIDATE_IP ) ) {
				break;
			}

			if ( ! self::in_ranges( $address, $proxies ) ) {
				return $address;
			}
		}

		return $remote;
	}

	/**
	 * Whether an address falls in any of the ranges.
	 *
	 * @param string   $address IPv4 or IPv6 address.
	 * @param string[] $ranges  IPs or CIDR ranges.
	 * @return bool
	 */
	private static function in_ranges( string $address, array $ranges ): bool {
		$packed = @inet_pton( $address ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Returns false for an invalid address, with a warning.

		if ( $packed === false ) {
			return false;
		}

		foreach ( $ranges as $range ) {
			[ $subnet, $bits ] = array_pad( explode( '/', (string) $range, 2 ), 2, null );
			$network           = @inet_pton( $subnet ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- As above.

			if ( $network === false || strlen( $network ) !== strlen( $packed ) ) {
				continue;
			}

			$bits  = $bits === null ? strlen( $packed ) * 8 : (int) $bits;
			$bytes = intdiv( $bits, 8 );
			$rest  = $bits % 8;

			if ( substr( $packed, 0, $bytes ) !== substr( $network, 0, $bytes ) ) {
				continue;
			}

			$mask = $rest ? ( 0xFF << ( 8 - $rest ) ) & 0xFF : 0;

			if ( ! $rest || ( ord( $packed[ $bytes ] ) & $mask ) === ( ord( $network[ $bytes ] ) & $mask ) ) {
				return true;
			}
		}

		return false;
	}
}
