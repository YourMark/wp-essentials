<?php
/**
 * XML-RPC, pingbacks and trackbacks.
 *
 * @package YourMark\Essentials
 */

namespace YourMark\Essentials\Hardening;

/**
 * Turns XML-RPC off and stops advertising it.
 *
 * Nothing current needs it: the block editor, the apps and remote publishing
 * all use the REST API. It remains a favourite for brute force, because one
 * system.multicall request can try hundreds of passwords.
 */
final class XmlRpc {

	/**
	 * Hook in.
	 *
	 * @return void
	 */
	public static function register(): void {
		// xmlrpc_enabled only blocks the authenticated methods; an empty method list also removes pingback.*.
		add_filter( 'xmlrpc_enabled', '__return_false' );
		add_filter( 'xmlrpc_methods', '__return_empty_array' );
		add_filter( 'pings_open', '__return_false', 20 );
		add_filter( 'wp_headers', [ self::class, 'remove_pingback_header' ] );
		add_action( 'init', [ self::class, 'remove_rsd_link' ] );
	}

	/**
	 * Drop the X-Pingback header, which points at xmlrpc.php.
	 *
	 * @param array<string, string> $headers Response headers.
	 * @return array<string, string>
	 */
	public static function remove_pingback_header( array $headers ): array {
		unset( $headers['X-Pingback'] );

		return $headers;
	}

	/**
	 * Drop the Really Simple Discovery link, which only describes XML-RPC.
	 *
	 * @return void
	 */
	public static function remove_rsd_link(): void {
		remove_action( 'wp_head', 'rsd_link' );
	}
}
