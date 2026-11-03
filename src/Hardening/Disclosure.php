<?php
/**
 * Version disclosure and file editing.
 *
 * @package YourMark\Essentials
 */

namespace YourMark\Essentials\Hardening;

/**
 * Hides the WordPress version and turns off the admin's code editors.
 *
 * The version stays in core's readme.html, a static file; remove it on deploy.
 */
final class Disclosure {

	/**
	 * Hook in.
	 *
	 * @return void
	 */
	public static function register(): void {
		// One filter covers the head meta tag and every feed format.
		add_filter( 'the_generator', '__return_empty_string' );
		add_filter( 'script_loader_src', [ self::class, 'mask_version' ] );
		add_filter( 'style_loader_src', [ self::class, 'mask_version' ] );

		// An admin account taken over should not be one click from running PHP. A site that set it keeps its own value.
		if ( ! defined( 'DISALLOW_FILE_EDIT' ) ) {
			define( 'DISALLOW_FILE_EDIT', true );
		}
	}

	/**
	 * Swap a core asset's ?ver=, which is the WordPress version, for a hash of it.
	 *
	 * The URL still changes with every update, so caches and CDNs fetch the new file.
	 *
	 * @param string $src Asset URL.
	 * @return string
	 */
	public static function mask_version( string $src ): string {
		$version = get_bloginfo( 'version' );

		if ( ! str_contains( $src, 'ver=' . $version ) ) {
			return $src;
		}

		return add_query_arg( 'ver', substr( wp_hash( $version ), 0, 8 ), $src );
	}
}
