<?php
/**
 * JQuery and render-blocking scripts.
 *
 * @package YourMark\Essentials
 */

namespace YourMark\Essentials\Cleanup;

/**
 * Drops jQuery Migrate and prints jQuery and Gravity Forms' scripts in the footer.
 *
 * WordPress loads jQuery on the front end only when a script depends on it, so
 * it cannot simply go; what can go is the blocking. Migrate only shims APIs
 * jQuery removed years ago. Gravity Forms registers its main script for the
 * head and defers it with a filter, which kept jQuery there; its inline init
 * scripts wait for its own loaded event, not for jQuery.
 *
 * Logged-in editors keep core's behaviour, so front-end tools of plugins built
 * for them keep working.
 */
final class Scripts {

	/**
	 * Hook in.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'wp_enqueue_scripts', [ self::class, 'drop_migrate' ], PHP_INT_MAX );
		// As the head scripts print, when every form on the page has enqueued its scripts.
		add_action( 'wp_print_scripts', [ self::class, 'move_to_footer' ] );
	}

	/**
	 * Take jQuery Migrate out of jQuery's dependencies.
	 *
	 * @return void
	 */
	public static function drop_migrate(): void {
		/**
		 * Filters whether jQuery Migrate still loads with jQuery on the front end.
		 *
		 * @param bool $keep Default false.
		 */
		if ( ! self::applies() || apply_filters( 'yourmark/cleanup/jquery_migrate', false ) ) {
			return;
		}

		$jquery = wp_scripts()->query( 'jquery' );

		if ( $jquery instanceof \_WP_Dependency ) {
			$jquery->deps = array_values( array_diff( $jquery->deps, [ 'jquery-migrate' ] ) );
		}
	}

	/**
	 * Put jQuery and the form scripts in the footer group.
	 *
	 * A head script that needs one of them still pulls it into the head, as
	 * WordPress resolves a dependency into its dependent's group.
	 *
	 * @return void
	 */
	public static function move_to_footer(): void {
		/**
		 * Filters whether jQuery and Gravity Forms' scripts move to the footer.
		 *
		 * Return false for a site with inline scripts in its content that call jQuery straight away.
		 *
		 * @param bool $move Default true.
		 */
		if ( ! self::applies() || ! apply_filters( 'yourmark/cleanup/scripts_in_footer', true ) ) {
			return;
		}

		$scripts = wp_scripts();

		foreach ( array_keys( $scripts->registered ) as $handle ) {
			if ( str_starts_with( $handle, 'gform_' ) || in_array( $handle, [ 'jquery', 'jquery-core', 'jquery-migrate' ], true ) ) {
				$scripts->add_data( $handle, 'group', 1 );
			}
		}
	}

	/**
	 * Front end, for anyone but a logged-in editor.
	 *
	 * @return bool
	 */
	private static function applies(): bool {
		return ! is_admin() && ! current_user_can( 'edit_posts' );
	}
}
