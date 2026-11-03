<?php
/**
 * Robots.txt with Yoast SEO.
 *
 * @package YourMark\Essentials
 */

namespace YourMark\Essentials\Cleanup;

use Yoast\WP\SEO\Helpers\Robots_Txt_Helper;

/**
 * Keeps robots.txt to one group for all crawlers when Yoast SEO writes it.
 *
 * Yoast removes core's group only when WordPress sits in the web root, so on
 * Bedrock's /wp/ both print a `User-agent: *`. Core's group comes out just
 * before Yoast's filter runs, and its two rules go into Yoast's group.
 */
final class Robots {

	/**
	 * Hook in.
	 *
	 * @return void
	 */
	public static function register(): void {
		// Yoast filters at 99999.
		add_filter( 'robots_txt', [ self::class, 'remove_core_group' ], 99998 );
		add_action( 'Yoast\WP\SEO\register_robots_rules', [ self::class, 'add_core_rules' ] );
	}

	/**
	 * Remove the group core wrote, if Yoast is there to write the rules again.
	 *
	 * @param string $output robots.txt so far.
	 * @return string
	 */
	public static function remove_core_group( string $output ): string {
		if ( ! defined( 'WPSEO_VERSION' ) ) {
			return $output;
		}

		$core = sprintf( "User-agent: *\nDisallow: %s\nAllow: %s\n", self::admin_path(), self::admin_path( 'admin-ajax.php' ) );

		return str_replace( $core, '', $output );
	}

	/**
	 * Put core's rules in Yoast's group for all crawlers.
	 *
	 * @param Robots_Txt_Helper $robots Yoast's rule collector.
	 * @return void
	 */
	public static function add_core_rules( $robots ): void {
		$robots->add_disallow( '*', self::admin_path() );
		$robots->add_allow( '*', self::admin_path( 'admin-ajax.php' ) );
	}

	/**
	 * Path of an admin URL, as core writes it in robots.txt.
	 *
	 * @param string $path Path inside wp-admin.
	 * @return string
	 */
	private static function admin_path( string $path = '' ): string {
		return (string) wp_parse_url( admin_url( $path ), PHP_URL_PATH );
	}
}
