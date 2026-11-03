# YourMark Essentials

Hardening, cleanup and a client administrator role for every client site.
Everything is on as soon as it is installed; what differs per site is a filter.

## Hardening

| Measure | Why |
|---|---|
| XML-RPC, pingbacks and trackbacks off, with their header and discovery link | Nothing current needs it, and one `system.multicall` request tries hundreds of passwords |
| `?author=N`, the users sitemap, oEmbed and REST `wp/v2/users` no longer hand out usernames | A login name is half a credential. Logged-in users keep the REST route; the editor needs it |
| One login error for a wrong username and a wrong password; lost password answers an unknown account as if the mail went out | Core tells them apart, which confirms that an account exists |
| Ten failed logins per address within 15 minutes lock that address out for the rest of it | Brute force goes from thousands of guesses a minute to forty an hour. See [Behind a proxy](#behind-a-proxy) |
| The options that move or open the site change only for a full administrator, WP-CLI or the installer; the default role never reads as administrator | A common plugin exploit turns registration on with administrator as the new users' role, then signs up |
| Application passwords for administrators only | They skip two-step login by design |
| A dashboard reminder to administrators without two-step login | With the Two Factor plugin active. A reminder, not a requirement: a locked-out client is a support call |
| `Permissions-Policy` and a baseline `Content-Security-Policy` | No camera, microphone, location, payment, USB or Topics; no `<object>`, no foreign `<base>`, no framing by other sites |
| No WordPress version in the head, feeds or core asset URLs; `DISALLOW_FILE_EDIT` | `?ver=` becomes a hash, so caches still refresh on update. A site's own `DISALLOW_FILE_EDIT` wins |
| Site Health tests for core's `readme.html` and for PHP running from uploads | Both are the server's or the deploy's to fix, so they are reported, not patched |

The REST API stays open apart from the users route, so OAuth handshakes,
oEmbed, forms and analytics keep working.

## Cleanup

| Measure | Why |
|---|---|
| No emoji script, styles or TinyMCE plugin | The inline script can be a quarter of a logged-out page, to polyfill what browsers render natively |
| No REST, oEmbed or shortlink discovery links, in the head or as `Link` headers | They point crawlers at duplicate URLs. The APIs themselves keep working |
| No jQuery Migrate on the front end; jQuery and Gravity Forms' scripts in the footer | jQuery loads only where a script needs it, but blocked the first paint where it did. Logged-in editors keep core's behaviour |
| robots.txt as one group with Yoast SEO | Yoast removes core's group only when WordPress sits in the web root |
| No remote patterns, Openverse or block directory in the editor | Fewer requests on every editor load, no third-party images of unclear licence, no plugins installed from the inserter |
| Ten revisions per post when the site set no limit | Unlimited revisions grow the posts table with every save |
| Heartbeat every 60 seconds instead of 15 | Four requests a minute from every open admin tab is mostly noise |
| Mail about automatic updates only when one failed | Clients get mail they cannot act on |
| Feeds off, on request | For a site without a blog every feed is an empty page |

## Client administrator

A role for the client: runs the site, cannot change what it runs on.

It has every capability the administrator role has at that moment, plugins'
own included, except installing, activating, updating, deleting or editing
plugins and themes, updating core, editing files, unfiltered uploads, import
and unfiltered HTML. It cannot see, edit, delete or create full
administrators, and the locked options above stay locked for it.

The role stores only `read`. Remove the package and these users can do nothing
more than a subscriber: it fails closed.

On a network, a site's administrators already cannot touch plugins or themes;
there the role mostly adds the user and option protection.

## Install

### Composer

The repository is private. Give Composer a GitHub token with read access, then:

```json
{
  "repositories": [
    { "type": "vcs", "url": "https://github.com/YourMark/wp-essentials" }
  ],
  "require": {
    "yourmark/wp-essentials": "^1.0"
  }
}
```

It is a `wordpress-muplugin` and installs into `mu-plugins/yourmark-essentials/`.
Bedrock's autoloader loads it from there; without Bedrock, add the loader from
the mu-plugin zip below.

### Download

Every release has two zips:

- `yourmark-essentials.zip` is a regular plugin: upload it under Plugins > Add New.
- `yourmark-essentials-mu.zip` holds the folder and a loader. Unzip it into
  `wp-content/mu-plugins/`, since WordPress loads no mu-plugin from a
  subdirectory by itself and wp-admin cannot upload mu-plugins.

Installed both ways, it loads once. A zip install does not update itself.

## Filters

### REST API

```php
// Close more routes to visitors. A route closes everything below it.
add_filter( 'yourmark/hardening/rest_private_routes', function ( array $routes ): array {
	$routes[] = '/wp/v2/media';
	return $routes;
} );

// Or allow visitors only the namespaces the site uses; everything else, the index included, answers 401.
add_filter( 'yourmark/hardening/rest_public_namespaces', fn (): array => [ 'oembed/1.0', 'gf/v2' ] );
```

### Headers

```php
add_filter( 'yourmark/hardening/permissions_policy', function ( array $policy ): array {
	$policy['geolocation'] = '(self)';
	return $policy;
} );

// Close the CSP to the sources the site uses.
add_filter( 'yourmark/hardening/content_security_policy', fn ( array $policy ): array => [
	...$policy,
	'default-src' => "'self'",
	'script-src'  => "'self' 'unsafe-inline'",
	'img-src'     => "'self' data:",
] );

// While trying a policy out: report to the console instead of blocking.
add_filter( 'yourmark/hardening/content_security_policy_report_only', '__return_true' );
```

`'unsafe-inline'` is hard to avoid on WordPress, which prints inline scripts,
and a nonce cannot work behind a page cache. Return an empty array from either
policy filter to send no header, for instance when the host sends one.

### Login

```php
add_filter( 'yourmark/hardening/login_attempts', fn (): int => 5 );
add_filter( 'yourmark/hardening/login_window', fn (): int => HOUR_IN_SECONDS );
```

#### Behind a proxy

Behind a CDN or load balancer every request comes from the proxy's address, so
without configuration all visitors share one count. List the proxy's ranges and
the client's address is read from `X-Forwarded-For`, walking from the proxy's
end, and from nowhere else, since anyone can send that header:

```php
add_filter( 'yourmark/hardening/trusted_proxies', fn (): array => [ '10.0.0.0/8', '2001:db8::/32' ] );
```

### Options and application passwords

```php
add_filter( 'yourmark/hardening/locked_options', function ( array $options ): array {
	$options[] = 'blogname';
	return $options;
} );

add_filter( 'yourmark/hardening/application_password_roles', fn (): array => [ 'administrator', 'shop_manager' ] );
```

### Scripts

```php
// Load jQuery Migrate again, for an old plugin that still needs it.
add_filter( 'yourmark/cleanup/jquery_migrate', '__return_true' );

// Keep jQuery in the head, for content with inline scripts that call it straight away.
add_filter( 'yourmark/cleanup/scripts_in_footer', '__return_false' );
```

### Feeds and revisions

```php
add_filter( 'yourmark/cleanup/disable_feeds', '__return_true' );
add_filter( 'yourmark/cleanup/revisions', fn (): int => 20 );
```

### Client administrator

```php
// Let client administrators paste embed codes with scripts.
add_filter( 'yourmark/client_admin/unfiltered_html', '__return_true' );

// Withhold or return capabilities.
add_filter( 'yourmark/client_admin/denied_caps', function ( array $caps ): array {
	$caps[] = 'export';
	return $caps;
} );
```

## Development

```bash
composer install
composer qa     # PHPCS (YourMark standard) and PHPStan level 7
composer lint
```

A tag `v1.2.3` builds both zips and attaches them to a GitHub release.
