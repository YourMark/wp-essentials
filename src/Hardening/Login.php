<?php
/**
 * Login and password reset.
 *
 * @package YourMark\Essentials
 */

namespace YourMark\Essentials\Hardening;

use WP_Error;

/**
 * Stops the login and lost password forms from confirming that an account exists.
 */
final class Login {

	/**
	 * Error codes that tell a wrong username apart from a wrong password.
	 */
	private const CREDENTIAL_ERRORS = [ 'invalid_username', 'invalid_email', 'incorrect_password' ];

	/**
	 * Hook in.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_filter( 'authenticate', [ self::class, 'generic_error' ], 99 );
		add_filter( 'shake_error_codes', [ self::class, 'shake_generic_error' ] );
		add_action( 'lostpassword_post', [ self::class, 'pretend_reset_sent' ], 10, 2 );
	}

	/**
	 * Report a wrong username and a wrong password the same way.
	 *
	 * Other errors, such as an empty field, pass through unchanged.
	 *
	 * @param \WP_User|WP_Error|null $user Authenticated user or error.
	 * @return \WP_User|WP_Error|null
	 */
	public static function generic_error( $user ) {
		if ( ! $user instanceof WP_Error || ! array_intersect( $user->get_error_codes(), self::CREDENTIAL_ERRORS ) ) {
			return $user;
		}

		return new WP_Error(
			'authentication_failed',
			__( '<strong>Error:</strong> Invalid username, email address or incorrect password.' ) // phpcs:ignore WordPress.WP.I18n.MissingArgDomain -- Core's own string, so it arrives translated.
		);
	}

	/**
	 * Shake the form for the generic error, as for the ones it replaces.
	 *
	 * @param string[] $codes Error codes that shake the login form.
	 * @return string[]
	 */
	public static function shake_generic_error( array $codes ): array {
		$codes[] = 'authentication_failed';

		return $codes;
	}

	/**
	 * Answer a reset request for an unknown account as if the email went out.
	 *
	 * Core says "There is no account with that username or email address".
	 * Other errors, such as an empty field, still show.
	 *
	 * @param WP_Error       $errors    Errors so far.
	 * @param \WP_User|false $user_data User the reset is for, false when unknown.
	 * @return void
	 */
	public static function pretend_reset_sent( WP_Error $errors, $user_data ): void {
		if ( $user_data !== false || $errors->has_errors() || is_user_logged_in() ) {
			return;
		}

		wp_safe_redirect( add_query_arg( 'checkemail', 'confirm', wp_login_url() ) );
		exit;
	}
}
