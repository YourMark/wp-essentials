<?php
/**
 * Security headers.
 *
 * @package YourMark\Essentials
 */

namespace YourMark\Essentials\Hardening;

/**
 * Sends a Permissions-Policy and a Content-Security-Policy with every page.
 *
 * Only the two headers hosts rarely set: a header sent by both PHP and nginx
 * arrives twice, and a doubled X-Frame-Options is ignored altogether. Front-end
 * pages only, since wp_headers does not run for wp-admin, the login or REST.
 */
final class Headers {

	/**
	 * Browser features no page of a typical site asks for, so no injected or
	 * embedded script can ask for them either.
	 */
	private const PERMISSIONS_POLICY = [
		'camera'      => '()',
		'microphone'  => '()',
		'geolocation' => '()',
		'payment'     => '()',
		'usb'         => '()',
	];

	/**
	 * Directives that break nothing on any site: no plugins, no <base> pointing
	 * scripts elsewhere, no framing by other sites.
	 */
	private const CONTENT_SECURITY_POLICY = [
		'object-src'      => "'none'",
		'base-uri'        => "'self'",
		'frame-ancestors' => "'self'",
	];

	/**
	 * Hook in.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_filter( 'wp_headers', [ self::class, 'add_headers' ] );
	}

	/**
	 * Add both policies, leaving out any a site filtered down to nothing.
	 *
	 * @param array<string, string> $headers Response headers.
	 * @return array<string, string>
	 */
	public static function add_headers( array $headers ): array {
		/**
		 * Filters the Permissions-Policy, as feature => allowlist.
		 *
		 * @param array<string, string> $policy Feature => allowlist, e.g. 'geolocation' => '(self)'.
		 */
		$permissions = (array) apply_filters( 'yourmark/hardening/permissions_policy', self::PERMISSIONS_POLICY );

		/**
		 * Filters the Content-Security-Policy, as directive => sources.
		 *
		 * @param array<string, string> $policy Directive => sources, e.g. 'img-src' => "'self' data:".
		 */
		$csp = (array) apply_filters( 'yourmark/hardening/content_security_policy', self::CONTENT_SECURITY_POLICY );

		/**
		 * Filters whether the CSP only reports violations to the console instead of blocking them.
		 *
		 * @param bool $report_only Default false.
		 */
		$csp_header = apply_filters( 'yourmark/hardening/content_security_policy_report_only', false )
			? 'Content-Security-Policy-Report-Only'
			: 'Content-Security-Policy';

		if ( $permissions ) {
			$headers['Permissions-Policy'] = self::join( $permissions, '=', ', ' );
		}

		if ( $csp ) {
			$headers[ $csp_header ] = self::join( $csp, ' ', '; ' );
		}

		return $headers;
	}

	/**
	 * Join a policy into a header value.
	 *
	 * @param array<mixed, mixed> $policy    Name => value.
	 * @param string              $glue      Between a name and its value.
	 * @param string              $separator Between entries.
	 * @return string
	 */
	private static function join( array $policy, string $glue, string $separator ): string {
		$entries = [];

		foreach ( $policy as $name => $value ) {
			$entries[] = trim( $name . $glue . $value );
		}

		return implode( $separator, $entries );
	}
}
