<?php
/**
 * Options that hand over the site.
 *
 * @package YourMark\Essentials
 */

namespace YourMark\Essentials\Hardening;

/**
 * Lets only a full administrator change the options that move, open or hand over the site.
 *
 * A common exploit of a vulnerable plugin calls update_option() without any
 * capability check: it turns registration on with administrator as the new
 * users' role, then signs up. Here such a write keeps the old value unless a
 * full administrator, WP-CLI or the installer makes it, and the default role
 * can never read as administrator at all.
 */
final class Options {

	/**
	 * Options that only a full administrator may change.
	 */
	private const LOCKED = [ 'siteurl', 'home', 'admin_email', 'new_admin_email', 'users_can_register', 'default_role' ];

	/**
	 * Hook in.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_filter( 'option_default_role', [ self::class, 'never_administrator' ] );
		add_action( 'init', [ self::class, 'lock' ] );
		add_action( 'admin_footer-options-general.php', [ self::class, 'disable_fields' ] );
	}

	/**
	 * Read an administrator default role as subscriber.
	 *
	 * @param mixed $role Stored default role.
	 * @return mixed
	 */
	public static function never_administrator( $role ) {
		return $role === 'administrator' ? 'subscriber' : $role;
	}

	/**
	 * Keep each locked option's old value for anyone else.
	 *
	 * @return void
	 */
	public static function lock(): void {
		foreach ( self::locked() as $option ) {
			add_filter( "pre_update_option_{$option}", [ self::class, 'keep_unless_allowed' ], 10, 2 );
		}
	}

	/**
	 * Return the old value unless the change is allowed.
	 *
	 * @param mixed $value     New value.
	 * @param mixed $old_value Current value.
	 * @return mixed
	 */
	public static function keep_unless_allowed( $value, $old_value ) {
		return self::may_change() ? $value : $old_value;
	}

	/**
	 * Grey out the locked fields under Settings > General for whoever cannot change them.
	 *
	 * @return void
	 */
	public static function disable_fields(): void {
		if ( self::may_change() ) {
			return;
		}

		$fields = wp_json_encode( [ '#siteurl', '#home', '#new_admin_email', '#users_can_register', '#default_role' ] );

		printf(
			'<script>%s.forEach((s) => document.querySelector(s)?.setAttribute("disabled", ""));</script>',
			$fields // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- A JSON literal of fixed selectors.
		);
	}

	/**
	 * Whether the current request may change a locked option.
	 *
	 * @return bool
	 */
	public static function may_change(): bool {
		if ( ( defined( 'WP_CLI' ) && WP_CLI ) || wp_installing() ) {
			return true;
		}

		// Not is_super_admin() on a single site: it only checks delete_users, which a client administrator has.
		return in_array( 'administrator', wp_get_current_user()->roles, true ) || ( is_multisite() && is_super_admin() );
	}

	/**
	 * The locked options.
	 *
	 * @return string[]
	 */
	private static function locked(): array {
		/**
		 * Filters the options only a full administrator may change.
		 *
		 * @param string[] $options Option names.
		 */
		return (array) apply_filters( 'yourmark/hardening/locked_options', self::LOCKED );
	}
}
