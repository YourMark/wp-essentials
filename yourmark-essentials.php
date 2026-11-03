<?php
/**
 * Plugin Name: YourMark Essentials
 * Description: Hardening, cleanup and a client administrator role for every client site.
 * Version: 1.0.0
 * Requires at least: 6.7
 * Requires PHP: 8.2
 * Author: YourMark
 * Author URI: https://yourmark.nl
 * License: GPL-2.0-or-later
 * Text Domain: yourmark-essentials
 * Update URI: false
 *
 * Works as a must-use plugin and as a regular plugin. Everything is on; what
 * differs per site is set through the filters in the README.
 *
 * @package YourMark\Essentials
 */

namespace YourMark\Essentials;

defined( 'ABSPATH' ) || exit;

// Installed both as a mu-plugin and as a plugin, it would hook in twice.
if ( defined( 'YOURMARK_ESSENTIALS_VERSION' ) ) {
	return;
}

define( 'YOURMARK_ESSENTIALS_VERSION', '1.0.0' );

foreach ( [
	'Hardening/XmlRpc',
	'Hardening/Users',
	'Hardening/Rest',
	'Hardening/Login',
	'Hardening/LoginLimit',
	'Hardening/Disclosure',
	'Hardening/Headers',
	'Hardening/Options',
	'Hardening/ApplicationPasswords',
	'Hardening/TwoFactor',
	'Hardening/SiteHealth',
	'Cleanup/Emoji',
	'Cleanup/Head',
	'Cleanup/Robots',
	'Cleanup/Scripts',
	'Cleanup/Feeds',
	'Cleanup/Editor',
	'Cleanup/Revisions',
	'Cleanup/Admin',
	'ClientAdmin/Role',
] as $class ) {
	require_once __DIR__ . "/src/{$class}.php";

	( __NAMESPACE__ . '\\' . str_replace( '/', '\\', $class ) )::register();
}

/**
 * Load the translations shipped in languages/, as a .l10n.php file where there is one.
 *
 * At init 0, ahead of the role name and before anything prints.
 */
add_action(
	'init',
	static function (): void {
		load_textdomain( 'yourmark-essentials', __DIR__ . '/languages/yourmark-essentials-' . determine_locale() . '.mo' );
	},
	0
);
