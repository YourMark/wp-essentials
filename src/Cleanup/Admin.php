<?php
/**
 * Admin background traffic and update mail.
 *
 * @package YourMark\Essentials
 */

namespace YourMark\Essentials\Cleanup;

/**
 * Slows the Heartbeat and mails about automatic updates only when one failed.
 */
final class Admin {

	/**
	 * Seconds between Heartbeat requests; core's 15 means four requests a minute from every open tab.
	 */
	private const HEARTBEAT_INTERVAL = 60;

	/**
	 * Hook in.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_filter( 'heartbeat_settings', [ self::class, 'slow_heartbeat' ] );
		add_filter( 'auto_core_update_send_email', [ self::class, 'mail_core_update' ], 10, 2 );
		add_filter( 'auto_plugin_update_send_email', [ self::class, 'mail_on_failure' ], 10, 2 );
		add_filter( 'auto_theme_update_send_email', [ self::class, 'mail_on_failure' ], 10, 2 );
	}

	/**
	 * Lengthen the Heartbeat interval. Post locks and autosave still run, a little later.
	 *
	 * @param array<string, mixed> $settings Heartbeat settings.
	 * @return array<string, mixed>
	 */
	public static function slow_heartbeat( array $settings ): array {
		$settings['interval'] = self::HEARTBEAT_INTERVAL;

		return $settings;
	}

	/**
	 * Mail about a core update only when it failed or needs attention.
	 *
	 * @param bool   $send Whether core would send it.
	 * @param string $type 'success', 'fail', 'manual' or 'critical'.
	 * @return bool
	 */
	public static function mail_core_update( bool $send, string $type ): bool {
		return $send && $type !== 'success';
	}

	/**
	 * Mail about plugin or theme updates only when one of them failed.
	 *
	 * @param bool                  $send    Whether core would send it.
	 * @param array<int, \stdClass> $results Update results, each with a `result` that is true on success.
	 * @return bool
	 */
	public static function mail_on_failure( bool $send, array $results ): bool {
		foreach ( $results as $result ) {
			if ( ( $result->result ?? null ) !== true ) {
				return $send;
			}
		}

		return false;
	}
}
