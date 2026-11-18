<?php
/**
 * Session store bound to the browser.
 *
 * @package YourMark\Essentials
 */

namespace YourMark\Essentials\Hardening;

use WP_User_Meta_Session_Tokens;

/**
 * Core's session store, except that a session does not exist for another browser.
 *
 * Core records the user agent of every login and never looks at it again. A
 * cookie that turns up with a different browser or operating system now fails
 * as an unknown session would, everywhere core checks one. The session itself
 * survives, so the real browser stays logged in.
 */
final class BrowserBoundSessionTokens extends WP_User_Meta_Session_Tokens {

	/**
	 * The session, or null when this request comes from another browser.
	 *
	 * @param string $verifier Hashed session token.
	 * @return array<string, mixed>|null
	 */
	protected function get_session( $verifier ): ?array {
		$session = parent::get_session( $verifier );

		// Sessions from before a login recorded its user agent cannot be checked.
		if ( ! is_array( $session ) || empty( $session['ua'] ) ) {
			return $session;
		}

		$current = wp_unslash( $_SERVER['HTTP_USER_AGENT'] ?? '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Only compared, never output or stored.

		return self::browser( (string) $session['ua'] ) === self::browser( (string) $current ) ? $session : null;
	}

	/**
	 * A user agent without its version numbers, so a browser update keeps the session.
	 *
	 * @param string $user_agent User agent.
	 * @return string
	 */
	private static function browser( string $user_agent ): string {
		return (string) preg_replace( '/\d+(?:[._]\d+)*/', '', $user_agent );
	}
}
