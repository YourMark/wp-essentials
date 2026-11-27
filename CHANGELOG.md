# Changelog

## Unreleased

**Hardening**

- No `browsing-topics` in the Permissions-Policy: Chrome dropped the Topics API and warns about the unknown feature.

## 1.1.0

**Hardening**

- `yourmark/hardening/rest_public_users` opens the REST users route to visitors.

**Cleanup**

- No redirect to random content, which WordPress 7.2 adds.

## 1.0.0

First release.

**Hardening**

- XML-RPC, pingbacks and trackbacks off.
- No usernames through `?author=N`, the users sitemap, oEmbed or the REST users route.
- The login and lost password forms confirm no account.
- Five failed logins per address and username within 15 minutes lock that address out of the account; trusted proxies through a filter.
- Sessions last 12 hours, or 7 days with "Remember Me", and only work in the browser that logged in.
- With the Two Factor plugin active, administrators must set up two-factor authentication before using wp-admin, except on `local` and `development` environments.
- Mail when someone becomes an administrator or super admin, and to the user when an application password is created.
- The options that move or open the site change only for a full administrator, and the default role never reads as administrator.
- Application passwords for administrators only.
- Permissions-Policy and a baseline Content-Security-Policy; no WordPress version or `X-Powered-By`; file editing off.
- Site Health tests for core's `readme.html` and for PHP in uploads.

**Cleanup**

- No emoji polyfill or discovery links.
- No jQuery Migrate on the front end; jQuery and Gravity Forms' scripts in the footer.
- robots.txt as one group with Yoast SEO.
- No remote patterns, Openverse or block directory in the editor.
- Ten revisions when the site set no limit, a slower Heartbeat, update mail only on failure.
- Feeds off, on request.

**Client administrator**

- A role that runs the site but cannot change what it runs on.

Dutch translation included.
