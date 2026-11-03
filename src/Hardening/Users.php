<?php
/**
 * User enumeration.
 *
 * @package YourMark\Essentials
 */

namespace YourMark\Essentials\Hardening;

/**
 * Closes the places where WordPress hands out usernames.
 *
 * A login name is half a credential. The REST users route is closed in Rest.
 */
final class Users {

	/**
	 * Hook in.
	 *
	 * @return void
	 */
	public static function register(): void {
		// At 9 on wp, ahead of SEO plugins that redirect disabled author archives themselves.
		add_action( 'wp', [ self::class, 'block_author_query' ], 9 );
		add_filter( 'wp_sitemaps_add_provider', [ self::class, 'remove_users_sitemap' ], 10, 2 );
		add_filter( 'oembed_response_data', [ self::class, 'anonymise_oembed' ] );
	}

	/**
	 * Send ?author=N home instead of to /author/{login}/.
	 *
	 * 302, so a browser does not keep the redirect for a later logged-in visit.
	 *
	 * @return void
	 */
	public static function block_author_query(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only checks presence; the value is never used.
		if ( is_user_logged_in() || ! isset( $_GET['author'] ) ) {
			return;
		}

		wp_safe_redirect( home_url(), 302 );
		exit;
	}

	/**
	 * Keep author archives out of the core sitemap; they list every author's login slug.
	 *
	 * @param \WP_Sitemaps_Provider|false $provider Sitemap provider.
	 * @param string                      $name     Provider name.
	 * @return \WP_Sitemaps_Provider|false
	 */
	public static function remove_users_sitemap( $provider, string $name ) {
		return $name === 'users' ? false : $provider;
	}

	/**
	 * Credit the site in oEmbed responses, as core does for a post without an author.
	 *
	 * Core puts the author's display name and archive URL, with the login slug, in
	 * every embed of a post.
	 *
	 * @param array<string, mixed> $data oEmbed response data.
	 * @return array<string, mixed>
	 */
	public static function anonymise_oembed( array $data ): array {
		$data['author_name'] = get_bloginfo( 'name' );
		$data['author_url']  = get_home_url();

		return $data;
	}
}
