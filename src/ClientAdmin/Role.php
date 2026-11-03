<?php
/**
 * Client administrator role.
 *
 * @package YourMark\Essentials
 */

namespace YourMark\Essentials\ClientAdmin;

use WP_User;
use WP_User_Query;

/**
 * An administrator for the client: runs the site, cannot change what it runs on.
 *
 * The role stores only `read`. Every capability check grants what the
 * administrator role has at that moment, minus a deny list, so capabilities
 * that plugins add to administrators (Yoast's, Gravity Forms') follow without
 * a stored list drifting behind. Remove the package and these users keep only
 * `read`: it fails closed.
 *
 * A client administrator cannot see, edit, delete or create full
 * administrators. The options that hand over the site are locked in
 * Hardening\Options.
 */
final class Role {

	/**
	 * Role slug.
	 */
	public const ROLE = 'client_admin';

	/**
	 * Capabilities a client administrator never gets.
	 */
	private const DENIED = [
		'install_plugins',
		'activate_plugins',
		'update_plugins',
		'delete_plugins',
		'edit_plugins',
		'install_themes',
		'switch_themes',
		'update_themes',
		'delete_themes',
		'edit_themes',
		'update_core',
		'edit_files',
		'unfiltered_upload',
		'import',
		'gravityforms_uninstall',
		'gravityforms_view_updates',
		'gravityforms_view_addons',
		'gravityforms_edit_settings',
		'gravityforms_api_settings',
		'gravityforms_logging',
		'gravityforms_system_status',
	];

	/**
	 * Meta capabilities that act on another user.
	 */
	private const USER_CAPS = [ 'edit_user', 'delete_user', 'remove_user', 'promote_user' ];

	/**
	 * Hook in.
	 *
	 * @return void
	 */
	public static function register(): void {
		// After the text domain loads at init 0.
		add_action( 'init', [ self::class, 'add_role' ], 1 );
		add_filter( 'user_has_cap', [ self::class, 'grant' ], 10, 4 );
		add_filter( 'map_meta_cap', [ self::class, 'protect_administrators' ], 10, 4 );
		add_filter( 'editable_roles', [ self::class, 'editable_roles' ] );
		add_action( 'pre_get_users', [ self::class, 'hide_administrators' ] );
		add_filter( 'views_users', [ self::class, 'hide_administrator_view' ] );
	}

	/**
	 * Create the role once, and show its name in the admin's language.
	 *
	 * @return void
	 */
	public static function add_role(): void {
		$name = __( 'Client administrator', 'yourmark-essentials' );

		if ( ! get_role( self::ROLE ) ) {
			add_role( self::ROLE, $name, [ 'read' => true ] );
		}

		wp_roles()->role_names[ self::ROLE ] = $name;
	}

	/**
	 * Give a client administrator the administrator's capabilities, minus the denied ones.
	 *
	 * @param array<string, bool> $allcaps The user's capabilities.
	 * @param string[]            $caps    Primitive capabilities being checked.
	 * @param array<int, mixed>   $args    The capability check's arguments.
	 * @param WP_User             $user    The user.
	 * @return array<string, bool>
	 */
	public static function grant( array $allcaps, array $caps, array $args, WP_User $user ): array {
		if ( ! in_array( self::ROLE, $user->roles, true ) ) {
			return $allcaps;
		}

		$administrator = get_role( 'administrator' );
		$granted       = [ ...array_keys( array_filter( $administrator->capabilities ?? [] ) ), ...self::plugin_caps() ];
		$denied        = self::denied();

		foreach ( array_diff( $granted, $denied ) as $cap ) {
			$allcaps[ $cap ] = true;
		}

		foreach ( $denied as $cap ) {
			$allcaps[ $cap ] = false;
		}

		return $allcaps;
	}

	/**
	 * Refuse a client administrator any action on a full administrator.
	 *
	 * @param string[]          $caps    Primitive capabilities required.
	 * @param string            $cap     Capability being checked.
	 * @param int               $user_id User being checked.
	 * @param array<int, mixed> $args    The target user's ID first.
	 * @return string[]
	 */
	public static function protect_administrators( array $caps, string $cap, int $user_id, array $args ): array {
		if ( ! in_array( $cap, self::USER_CAPS, true ) || empty( $args[0] ) || ! self::is_client_admin( $user_id ) ) {
			return $caps;
		}

		$target = get_userdata( (int) $args[0] );

		if ( $target && ( in_array( 'administrator', $target->roles, true ) || ( is_multisite() && is_super_admin( $target->ID ) ) ) ) {
			$caps[] = 'do_not_allow';
		}

		return $caps;
	}

	/**
	 * Leave the administrator role out of the roles a client administrator may assign.
	 *
	 * @param array<string, mixed> $roles Roles by slug.
	 * @return array<string, mixed>
	 */
	public static function editable_roles( array $roles ): array {
		if ( self::is_client_admin() ) {
			unset( $roles['administrator'] );
		}

		return $roles;
	}

	/**
	 * Leave full administrators out of the user lists a client administrator sees in the admin.
	 *
	 * @param WP_User_Query $query The query.
	 * @return void
	 */
	public static function hide_administrators( WP_User_Query $query ): void {
		if ( ! is_admin() || ! self::is_client_admin() ) {
			return;
		}

		$excluded                          = (array) ( $query->query_vars['role__not_in'] ?? [] );
		$query->query_vars['role__not_in'] = [ ...$excluded, 'administrator' ];
	}

	/**
	 * Drop the Administrator count above the users list.
	 *
	 * @param array<string, string> $views Links by role.
	 * @return array<string, string>
	 */
	public static function hide_administrator_view( array $views ): array {
		if ( self::is_client_admin() ) {
			unset( $views['administrator'] );
		}

		return $views;
	}

	/**
	 * Whether a user, the current one by default, is a client administrator.
	 *
	 * @param int $user_id User ID, 0 for the current user.
	 * @return bool
	 */
	public static function is_client_admin( int $user_id = 0 ): bool {
		$user = $user_id ? get_userdata( $user_id ) : wp_get_current_user();

		return $user && in_array( self::ROLE, $user->roles, true );
	}

	/**
	 * Capabilities that plugins grant administrators by their role name rather than through the role.
	 *
	 * Gravity Forms gives full access to whoever passes user_can( 'administrator' ),
	 * which a client administrator by design does not; its separate capabilities
	 * are granted here instead, minus the denied ones.
	 *
	 * @return string[]
	 */
	private static function plugin_caps(): array {
		$caps = class_exists( \GFCommon::class ) ? \GFCommon::all_caps() : [];

		/**
		 * Filters capabilities a client administrator gets beyond the administrator role's own.
		 *
		 * @param string[] $caps Capability names. The denied capabilities still win.
		 */
		return (array) apply_filters( 'yourmark/client_admin/extra_caps', $caps );
	}

	/**
	 * The denied capabilities, with unfiltered_html unless a site allows it.
	 *
	 * @return string[]
	 */
	private static function denied(): array {
		/**
		 * Filters whether a client administrator may post unfiltered HTML, such as embed codes with scripts.
		 *
		 * Off by default: with it, a taken-over client account can put scripts on every page.
		 *
		 * @param bool $allowed Default false.
		 */
		$denied = apply_filters( 'yourmark/client_admin/unfiltered_html', false ) ? self::DENIED : [ ...self::DENIED, 'unfiltered_html' ];

		/**
		 * Filters the capabilities a client administrator never gets.
		 *
		 * @param string[] $denied Capability names.
		 */
		return (array) apply_filters( 'yourmark/client_admin/denied_caps', $denied );
	}
}
