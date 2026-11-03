<?php
/**
 * Application passwords.
 *
 * @package YourMark\Essentials
 */

namespace YourMark\Essentials\Hardening;

use WP_User;

/**
 * Offers application passwords only to the roles that need them.
 *
 * An application password skips two-step login by design, since it is meant for
 * scripts and integrations. Every account that can make one is a way in that a
 * second factor does not guard.
 */
final class ApplicationPasswords {

	/**
	 * Hook in.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_filter( 'wp_is_application_passwords_available_for_user', [ self::class, 'available_for' ], 10, 2 );
	}

	/**
	 * Allow application passwords for the listed roles only.
	 *
	 * @param bool    $available Whether core would allow them.
	 * @param WP_User $user      The user.
	 * @return bool
	 */
	public static function available_for( bool $available, WP_User $user ): bool {
		/**
		 * Filters the roles that may create and use application passwords.
		 *
		 * @param string[] $roles Default administrator.
		 */
		$roles = (array) apply_filters( 'yourmark/hardening/application_password_roles', [ 'administrator' ] );

		return $available && ( array_intersect( $user->roles, $roles ) || ( is_multisite() && is_super_admin( $user->ID ) ) );
	}
}
