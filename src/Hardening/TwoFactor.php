<?php
/**
 * Two-step login reminder.
 *
 * @package YourMark\Essentials
 */

namespace YourMark\Essentials\Hardening;

use Two_Factor_Core;

/**
 * Reminds an administrator on the dashboard to set up two-step login.
 *
 * Only with the Two Factor plugin active. A reminder rather than a requirement:
 * a client locked out of their own site is a support call, not a safer site.
 */
final class TwoFactor {

	/**
	 * Hook in.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'admin_notices', [ self::class, 'remind' ] );
	}

	/**
	 * Show the reminder to an administrator without a second step.
	 *
	 * @return void
	 */
	public static function remind(): void {
		if ( ! class_exists( Two_Factor_Core::class ) || get_current_screen()?->id !== 'dashboard' || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( Two_Factor_Core::is_user_using_two_factor( get_current_user_id() ) ) {
			return;
		}

		wp_admin_notice(
			sprintf(
				/* translators: %s: URL of the two-step login settings on the profile page */
				__( 'Your account can change everything on this site, so protect it with a second step. <a href="%s">Set up two-step login</a>.', 'yourmark-essentials' ),
				esc_url( admin_url( 'profile.php#two-factor-options' ) )
			),
			[ 'type' => 'warning' ]
		);
	}
}
