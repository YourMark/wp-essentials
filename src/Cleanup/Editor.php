<?php
/**
 * Remote content in the block editor.
 *
 * @package YourMark\Essentials
 */

namespace YourMark\Essentials\Cleanup;

/**
 * Keeps the block editor to what the site itself provides.
 *
 * Out of the box it fetches patterns from wordpress.org each time it opens,
 * searches Openverse for third-party images of unclear licence, and offers to
 * install blocks as plugins straight from the inserter.
 */
final class Editor {

	/**
	 * Hook in.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_filter( 'should_load_remote_block_patterns', '__return_false' );
		add_filter( 'block_editor_settings_all', [ self::class, 'disable_openverse' ] );
		add_action( 'init', [ self::class, 'remove_block_directory' ] );
	}

	/**
	 * Drop the Openverse category from the inserter's media tab.
	 *
	 * @param array<string, mixed> $settings Editor settings.
	 * @return array<string, mixed>
	 */
	public static function disable_openverse( array $settings ): array {
		$settings['enableOpenverseMediaCategory'] = false;

		return $settings;
	}

	/**
	 * Stop the inserter from offering plugins from the block directory.
	 *
	 * @return void
	 */
	public static function remove_block_directory(): void {
		remove_action( 'enqueue_block_editor_assets', 'wp_enqueue_editor_block_directory_assets' );
	}
}
