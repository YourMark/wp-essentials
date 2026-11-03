<?php
/**
 * Site Health tests.
 *
 * @package YourMark\Essentials
 */

namespace YourMark\Essentials\Hardening;

/**
 * Adds Site Health tests for what PHP cannot fix by itself.
 *
 * Core's readme.html tells anyone the WordPress version, and a server that runs
 * PHP from the uploads folder turns any upload hole into code execution. Both
 * are the server's or the deploy's to fix; these tests make them visible.
 */
final class SiteHealth {

	/**
	 * Hook in.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_filter( 'site_status_tests', [ self::class, 'add_tests' ] );
	}

	/**
	 * Register the tests.
	 *
	 * @param array<string, array<string, mixed>> $tests Tests by type.
	 * @return array<string, array<string, mixed>>
	 */
	public static function add_tests( array $tests ): array {
		$tests['direct']['yourmark_readme'] = [
			'label' => __( 'WordPress readme', 'yourmark-essentials' ),
			'test'  => [ self::class, 'test_readme' ],
		];

		$tests['direct']['yourmark_uploads_php'] = [
			'label' => __( 'PHP in the uploads folder', 'yourmark-essentials' ),
			'test'  => [ self::class, 'test_uploads_php' ],
		];

		return $tests;
	}

	/**
	 * Whether core's readme.html is still in place.
	 *
	 * @return array<string, mixed>
	 */
	public static function test_readme(): array {
		if ( ! file_exists( ABSPATH . 'readme.html' ) ) {
			return self::result( 'yourmark_readme', 'good', __( 'WordPress\'s readme.html has been removed', 'yourmark-essentials' ), '' );
		}

		return self::result(
			'yourmark_readme',
			'recommended',
			__( 'WordPress\'s readme.html is still in place', 'yourmark-essentials' ),
			__( 'It tells anyone which WordPress version runs here, which the rest of the site no longer does. Delete it on every deploy or update, since core puts it back.', 'yourmark-essentials' )
		);
	}

	/**
	 * Whether the server runs a PHP file placed in the uploads folder.
	 *
	 * Writes a file with a random name, requests it and deletes it again.
	 *
	 * @return array<string, mixed>
	 */
	public static function test_uploads_php(): array {
		$uploads = wp_upload_dir( null, false );
		$name    = 'yourmark-essentials-' . wp_generate_password( 12, false ) . '.php';
		$path    = trailingslashit( $uploads['basedir'] ) . $name;
		$marker  = wp_generate_password( 20, false );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- A throwaway probe; WP_Filesystem may ask for FTP credentials.
		if ( $uploads['error'] || ! file_put_contents( $path, "<?php echo '{$marker}';" ) ) {
			return self::result( 'yourmark_uploads_php', 'recommended', __( 'PHP in the uploads folder could not be tested', 'yourmark-essentials' ), __( 'The uploads folder is not writable, so no test file could be placed.', 'yourmark-essentials' ) );
		}

		// A loopback, so the same certificate check as core's own loopback tests.
		$response = wp_remote_get(
			trailingslashit( $uploads['baseurl'] ) . $name,
			[
				'timeout'   => 10,
				'sslverify' => apply_filters( 'https_local_ssl_verify', false ), // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core's hook.
			]
		);
		wp_delete_file( $path );

		if ( is_wp_error( $response ) ) {
			return self::result( 'yourmark_uploads_php', 'recommended', __( 'PHP in the uploads folder could not be tested', 'yourmark-essentials' ), __( 'The site could not request a file from its own uploads folder.', 'yourmark-essentials' ) );
		}

		if ( ! str_contains( wp_remote_retrieve_body( $response ), $marker ) ) {
			return self::result( 'yourmark_uploads_php', 'good', __( 'PHP does not run from the uploads folder', 'yourmark-essentials' ), '' );
		}

		return self::result(
			'yourmark_uploads_php',
			'critical',
			__( 'PHP runs from the uploads folder', 'yourmark-essentials' ),
			__( 'Anyone who gets a file into uploads through a plugin bug can run code on the server. Deny PHP in the uploads folder in the web server, for nginx with a location block that returns 403 for .php under /uploads/.', 'yourmark-essentials' )
		);
	}

	/**
	 * A Site Health result in the Security group.
	 *
	 * @param string $test        Test id.
	 * @param string $status      'good', 'recommended' or 'critical'.
	 * @param string $label       Result headline.
	 * @param string $description Why it matters and what to do.
	 * @return array<string, mixed>
	 */
	private static function result( string $test, string $status, string $label, string $description ): array {
		return [
			'label'       => $label,
			'status'      => $status,
			'badge'       => [
				'label' => __( 'Security' ), // phpcs:ignore WordPress.WP.I18n.MissingArgDomain -- Core's own badge, so it arrives translated.
				'color' => 'blue',
			],
			'description' => $description ? '<p>' . esc_html( $description ) . '</p>' : '',
			'actions'     => '',
			'test'        => $test,
		];
	}
}
