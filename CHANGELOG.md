# Changelog

## 1.0.0

Brings YourMark Hardening 1.1.0 and YourMark Cleanup 1.0.0 together in one package. Their filters keep their names.

**Features**

- XML-RPC off; no usernames through `?author=N`, the users sitemap, oEmbed or REST; login and lost password confirm no account.
- Failed logins are rate limited per address, with trusted proxies through a filter.
- The options that move or open the site change only for a full administrator, and the default role never reads as administrator.
- Application passwords for administrators only, and a dashboard reminder to set up two-step login.
- Permissions-Policy and a baseline Content-Security-Policy; no WordPress version; file editing off.
- Site Health tests for core's `readme.html` and for PHP in uploads.
- No emoji polyfill or discovery links; no jQuery Migrate, with jQuery and Gravity Forms' scripts in the footer; robots.txt as one group with Yoast SEO.
- No remote patterns, Openverse or block directory in the editor; ten revisions when the site set no limit; a slower Heartbeat; update mail only on failure.
- A client administrator role.
- Dutch translation.
