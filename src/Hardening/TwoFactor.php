<?php
/**
 * Two-factor authentication for administrators.
 *
 * @package YourMark\Essentials
 */

namespace YourMark\Essentials\Hardening;

use Two_Factor_Core;
use WP_User;

/**
 * Sends an administrator without two-factor authentication to set it up before anything else in wp-admin.
 *
 * Only with the Two Factor plugin active; it does the authentication, this only
 * makes it compulsory. The REST API stays open, since setting up an app code runs over it.
 * Not on local and development installs, where a copied database would otherwise
 * ask for production's codes.
 */
final class TwoFactor {

	/**
	 * Hook in.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'admin_init', [ self::class, 'require_setup' ] );
		add_action( 'admin_notices', [ self::class, 'explain' ] );
		add_action( 'network_admin_notices', [ self::class, 'explain' ] );
	}

	/**
	 * Redirect to the profile page until two-factor authentication is on.
	 *
	 * @return void
	 */
	public static function require_setup(): void {
		global $pagenow;

		if ( wp_doing_ajax() || $pagenow === 'profile.php' || ! self::must_set_up( wp_get_current_user() ) ) {
			return;
		}

		wp_safe_redirect( self_admin_url( 'profile.php#two-factor-options' ) );
		exit;
	}

	/**
	 * Say on the profile page why the rest of wp-admin is closed.
	 *
	 * @return void
	 */
	public static function explain(): void {
		if ( ! self::must_set_up( wp_get_current_user() ) ) {
			return;
		}

		wp_admin_notice(
			esc_html__( 'Set up two-factor authentication below before you continue. It is required for your account on this site.', 'yourmark-essentials' ),
			[ 'type' => 'warning' ]
		);
	}

	/**
	 * Whether the user still has to set up two-factor authentication.
	 *
	 * @param WP_User $user User.
	 * @return bool
	 */
	private static function must_set_up( WP_User $user ): bool {
		if ( ! class_exists( Two_Factor_Core::class ) || ! $user->exists() || Two_Factor_Core::is_user_using_two_factor( $user->ID ) ) {
			return false;
		}

		$required = user_can( $user, 'manage_options' ) && ! in_array( wp_get_environment_type(), [ 'local', 'development' ], true );

		/**
		 * Filters whether a user must use two-factor authentication.
		 *
		 * @param bool    $required Default true for users who can manage options, except on a local or development environment.
		 * @param WP_User $user     User.
		 */
		return (bool) apply_filters( 'yourmark/hardening/two_factor_required', $required, $user );
	}
}
