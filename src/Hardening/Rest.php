<?php
/**
 * REST API access for visitors.
 *
 * @package YourMark\Essentials
 */

namespace YourMark\Essentials\Hardening;

use WP_Error;
use WP_REST_Request;
use WP_REST_Server;

/**
 * Keeps private REST routes from visitors who are not logged in.
 *
 * Targeted by default: only routes that leak something are closed, so OAuth
 * handshakes, oEmbed, forms and analytics keep working. A site that needs no
 * public API at all can allow just the namespaces it uses.
 */
final class Rest {

	/**
	 * Routes closed to visitors by default, with everything below them.
	 *
	 * The users route lists every author with their login slug.
	 */
	private const PRIVATE_ROUTES = [ '/wp/v2/users' ];

	/**
	 * Hook in.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_filter( 'rest_pre_dispatch', [ self::class, 'restrict' ], 10, 3 );
	}

	/**
	 * Refuse a closed route to a visitor before it runs.
	 *
	 * Only real REST requests: a server-side rest_do_request() while rendering a
	 * page is the site talking to itself, not a visitor.
	 *
	 * @param mixed           $result  Response to send instead of dispatching; null to dispatch.
	 * @param WP_REST_Server  $server  REST server.
	 * @param WP_REST_Request $request Request.
	 * @return mixed
	 */
	public static function restrict( $result, WP_REST_Server $server, WP_REST_Request $request ) {
		if ( $result !== null || is_user_logged_in() || ! wp_is_serving_rest_request() ) {
			return $result;
		}

		$route = $request->get_route();

		if ( ! self::is_private( $route ) && self::is_public( $route ) ) {
			return $result;
		}

		return new WP_Error(
			'rest_forbidden',
			__( 'Sorry, you are not allowed to do that.' ), // phpcs:ignore WordPress.WP.I18n.MissingArgDomain -- Core's own string, so it arrives translated.
			[ 'status' => rest_authorization_required_code() ]
		);
	}

	/**
	 * Whether a route is closed to visitors.
	 *
	 * @param string $route Route, such as /wp/v2/users/1.
	 * @return bool
	 */
	private static function is_private( string $route ): bool {
		/**
		 * Filters the routes closed to visitors. Each closes everything below it too.
		 *
		 * @param string[] $routes Routes, such as /wp/v2/users.
		 */
		$routes = (array) apply_filters( 'yourmark/hardening/rest_private_routes', self::PRIVATE_ROUTES );

		foreach ( $routes as $private ) {
			if ( self::within( $route, (string) $private ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Whether a route lies in a namespace visitors may use.
	 *
	 * @param string $route Route.
	 * @return bool
	 */
	private static function is_public( string $route ): bool {
		/**
		 * Filters the namespaces visitors may use; null leaves every namespace open.
		 *
		 * Set a list to close the rest of the API, the index included, for example
		 * [ 'oembed/1.0', 'albert/v1' ].
		 *
		 * @param string[]|null $namespaces Namespaces, or null for all.
		 */
		$namespaces = apply_filters( 'yourmark/hardening/rest_public_namespaces', null );

		if ( ! is_array( $namespaces ) ) {
			return true;
		}

		foreach ( $namespaces as $namespace ) {
			if ( self::within( $route, '/' . trim( (string) $namespace, '/' ) ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Whether a route is a prefix itself or lies below it.
	 *
	 * Case-blind, as core's route matching is: /wp/v2/Users is the users route too.
	 *
	 * @param string $route  Route.
	 * @param string $prefix Route or namespace with a leading slash.
	 * @return bool
	 */
	private static function within( string $route, string $prefix ): bool {
		$route  = strtolower( $route );
		$prefix = strtolower( untrailingslashit( $prefix ) );

		return $route === $prefix || str_starts_with( $route, $prefix . '/' );
	}
}
