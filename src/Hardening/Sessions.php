<?php
/**
 * Login sessions.
 *
 * @package YourMark\Essentials
 */

namespace YourMark\Essentials\Hardening;

use WP_User_Meta_Session_Tokens;

/**
 * Narrows what a stolen login cookie is worth: shorter sessions, and only in the browser that logged in.
 */
final class Sessions {

	/**
	 * Session length in seconds without "Remember Me". Core gives 2 days.
	 */
	private const LIFETIME = 12 * HOUR_IN_SECONDS;

	/**
	 * Session length in seconds with "Remember Me". Core gives 14 days.
	 */
	private const LIFETIME_REMEMBER = 7 * DAY_IN_SECONDS;

	/**
	 * Hook in.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_filter( 'auth_cookie_expiration', [ self::class, 'lifetime' ], 10, 3 );
		add_filter( 'session_token_manager', [ self::class, 'bind_to_browser' ] );
		add_filter( 'http_request_args', [ self::class, 'loopback_as_browser' ], 10, 2 );
	}

	/**
	 * Session length for a new login.
	 *
	 * @param int   $length   Length core or an earlier filter chose.
	 * @param int   $user_id  User logging in.
	 * @param mixed $remember Whether "Remember Me" was ticked.
	 * @return int
	 */
	public static function lifetime( int $length, int $user_id, $remember ): int {
		$remember = (bool) $remember;

		/**
		 * Filters the session length in seconds.
		 *
		 * @param int  $seconds  Default 12 hours, or 7 days with "Remember Me".
		 * @param bool $remember Whether "Remember Me" was ticked.
		 * @param int  $user_id  User logging in.
		 */
		return (int) apply_filters(
			'yourmark/hardening/session_lifetime',
			$remember ? self::LIFETIME_REMEMBER : self::LIFETIME,
			$remember,
			$user_id
		);
	}

	/**
	 * Swap core's session store for one that checks the browser.
	 *
	 * Only core's own store: a site that brought another one keeps it unchanged.
	 *
	 * @param string $manager Session token class.
	 * @return string
	 */
	public static function bind_to_browser( string $manager ): string {
		/**
		 * Filters whether a session only works in the browser that logged in.
		 *
		 * @param bool $bind Default true.
		 */
		if ( $manager !== WP_User_Meta_Session_Tokens::class || ! apply_filters( 'yourmark/hardening/bind_session_to_browser', true ) ) {
			return $manager;
		}

		require_once __DIR__ . '/BrowserBoundSessionTokens.php';

		return BrowserBoundSessionTokens::class;
	}

	/**
	 * Send the browser's user agent with a loopback that carries its cookies.
	 *
	 * Site Health checks the REST API by calling the site with the user's cookies,
	 * which a bound session would refuse under WordPress's own user agent.
	 *
	 * @param array<string, mixed> $args Request arguments.
	 * @param string               $url  Request URL.
	 * @return array<string, mixed>
	 */
	public static function loopback_as_browser( array $args, string $url ): array {
		$user_agent = sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ?? '' ) );

		if (
			empty( $args['cookies'] )
			|| $user_agent === ''
			|| wp_parse_url( $url, PHP_URL_HOST ) !== wp_parse_url( home_url(), PHP_URL_HOST )
		) {
			return $args;
		}

		$args['user-agent'] = $user_agent;

		return $args;
	}
}
