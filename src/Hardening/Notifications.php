<?php
/**
 * Mail about changes an attacker makes to keep access.
 *
 * @package YourMark\Essentials
 */

namespace YourMark\Essentials\Hardening;

use WP_User;

/**
 * Mails when someone gains administrator rights or an application password.
 *
 * Both outlast a password change. A plugin hole that creates an administrator,
 * or a stolen session that mints an application password, otherwise goes unseen.
 */
final class Notifications {

	/**
	 * Hook in.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'add_user_role', [ self::class, 'new_administrator' ], 10, 2 );
		add_action( 'grant_super_admin', [ self::class, 'new_super_admin' ] );
		add_action( 'wp_create_application_password', [ self::class, 'new_application_password' ], 10, 2 );
	}

	/**
	 * Tell the site's admin address that a user became an administrator.
	 *
	 * @param int    $user_id User given the role.
	 * @param string $role    Role given.
	 * @return void
	 */
	public static function new_administrator( int $user_id, string $role ): void {
		if ( $role !== 'administrator' || wp_installing() ) {
			return;
		}

		self::send(
			(string) get_option( 'admin_email' ),
			/* translators: %s: username. */
			__( 'New administrator: %s', 'yourmark-essentials' ),
			/* translators: 1: username, 2: email address, 3: site URL. */
			__( '%1$s (%2$s) is now an administrator on %3$s.', 'yourmark-essentials' ),
			$user_id
		);
	}

	/**
	 * Tell the network's admin address that a user became a super admin.
	 *
	 * @param int $user_id User about to be granted super admin rights.
	 * @return void
	 */
	public static function new_super_admin( int $user_id ): void {
		if ( wp_installing() ) {
			return;
		}

		self::send(
			(string) get_site_option( 'admin_email' ),
			/* translators: %s: username. */
			__( 'New super admin: %s', 'yourmark-essentials' ),
			/* translators: 1: username, 2: email address, 3: site URL. */
			__( '%1$s (%2$s) is now a super admin on the network of %3$s.', 'yourmark-essentials' ),
			$user_id
		);
	}

	/**
	 * Tell a user that an application password was created for their account.
	 *
	 * @param int                  $user_id User the password is for.
	 * @param array<string, mixed> $item    The password's details, without the password.
	 * @return void
	 */
	public static function new_application_password( int $user_id, array $item ): void {
		$user = get_userdata( $user_id );

		if ( ! $user instanceof WP_User ) {
			return;
		}

		self::send(
			$user->user_email,
			/* translators: %s: application password name. */
			__( 'New application password: %s', 'yourmark-essentials' ),
			/* translators: 1: username, 2: email address, 3: site URL. */
			__( 'An application password was created for %1$s (%2$s) on %3$s. It keeps working after a password change until it is revoked.', 'yourmark-essentials' ),
			$user_id,
			(string) ( $item['name'] ?? '' )
		);
	}

	/**
	 * Send the mail in the site's language, with who did it and where to undo it.
	 *
	 * @param string $to      Recipient.
	 * @param string $subject Subject format; %s is the subject value.
	 * @param string $message Message format; %1$s username, %2$s email, %3$s site URL.
	 * @param int    $user_id User the change is about.
	 * @param string $subject_value Value for the subject; the username when empty.
	 * @return void
	 */
	private static function send( string $to, string $subject, string $message, int $user_id, string $subject_value = '' ): void {
		$user = get_userdata( $user_id );

		if ( $to === '' || ! $user instanceof WP_User ) {
			return;
		}

		$switched = switch_to_locale( get_locale() );
		$actor    = wp_get_current_user();

		$lines = [
			sprintf( $message, $user->user_login, $user->user_email, home_url() ),
			'',
			$actor->exists()
				/* translators: %s: username. */
				? sprintf( __( 'Done by: %s', 'yourmark-essentials' ), $actor->user_login )
				: __( 'Done by: no logged-in user, so a plugin, a script or WP-CLI.', 'yourmark-essentials' ),
			/* translators: %s: IP address. */
			sprintf( __( 'From IP address: %s', 'yourmark-essentials' ), LoginLimit::client_ip() ),
			'',
			__( 'If this was not you or someone you trust, the site may be compromised. Check the user here:', 'yourmark-essentials' ),
			// Empty when nobody is logged in, as under WP-CLI.
			get_edit_user_link( $user->ID ) !== '' ? get_edit_user_link( $user->ID ) : admin_url( 'users.php' ),
		];

		wp_mail(
			$to,
			sprintf( '[%s] %s', wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ), sprintf( $subject, $subject_value !== '' ? $subject_value : $user->user_login ) ),
			implode( "\n", $lines )
		);

		if ( $switched ) {
			restore_previous_locale();
		}
	}
}
