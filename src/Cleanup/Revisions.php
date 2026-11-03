<?php
/**
 * Post revisions.
 *
 * @package YourMark\Essentials
 */

namespace YourMark\Essentials\Cleanup;

/**
 * Caps revisions per post where the site set no limit of its own.
 *
 * Without WP_POST_REVISIONS every save is kept, so the posts table grows with
 * every edit. Ten covers a day of reworking a page; a site's own constant wins
 * unless it asks for unlimited.
 */
final class Revisions {

	/**
	 * Revisions kept per post.
	 */
	private const LIMIT = 10;

	/**
	 * Hook in.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_filter( 'wp_revisions_to_keep', [ self::class, 'limit' ] );
	}

	/**
	 * Replace "unlimited" with the limit.
	 *
	 * @param int $num Revisions to keep, -1 for all.
	 * @return int
	 */
	public static function limit( int $num ): int {
		if ( $num >= 0 ) {
			return $num;
		}

		/**
		 * Filters how many revisions a post keeps when the site set no limit.
		 *
		 * @param int $limit Default 10.
		 */
		return (int) apply_filters( 'yourmark/cleanup/revisions', self::LIMIT );
	}
}
