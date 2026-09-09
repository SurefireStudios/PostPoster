# Security Policy

Post Poster runs inside WordPress, renders content on the public front end, and exposes an
AJAX endpoint to unauthenticated visitors. Security reports are taken seriously.

## Supported versions

| Version | Status |
| --- | --- |
| `1.1.0` | ✅ Supported |
| `< 1.1.0` | ❌ Superseded — upgrade to `1.1.0` |

Fixes land on the latest release only.

## Reporting a vulnerability

**Please do not open a public issue for an unpatched vulnerability.**

Use either channel:

1. **GitHub Security Advisories** — the
   [Report a vulnerability](https://github.com/SurefireStudios/PostPoster/security/advisories/new)
   form on this repository. This is the preferred route.
2. **Email** — contact [Surefire Studios](https://www.surefirestudios.io) via the details on
   our site, with `Post Poster security` in the subject line.

Please include, where you can:

- The plugin version and your WordPress and PHP versions
- The file and, if known, the line or function involved
- Steps to reproduce, ideally with a minimal proof of concept
- The privilege level required (unauthenticated, subscriber, editor, administrator)
- Your assessment of the impact

### What to expect

- We aim to acknowledge a report within **7 days**.
- We will confirm the issue and share a rough remediation timeline.
- Once a fix ships, we will credit you in the release notes unless you prefer otherwise.

## Scope

**In scope** — everything in this repository: the plugin PHP, the admin and frontend
JavaScript, the block, the templates and the stylesheets.

Findings we are particularly interested in:

- Cross-site scripting through shortcode attributes, block attributes or post content
  rendered into a card
- SQL injection or unintended query manipulation through the `pp_query_args` filter path or
  the `categories` attribute
- Weaknesses in the **`pp_load_more_posts`** AJAX endpoint, which is registered for both
  `wp_ajax_` and `wp_ajax_nopriv_` and therefore reachable by logged-out visitors
- Missing capability checks or nonce verification on the admin screen
- Cache poisoning through the transient keys used for query results

**Out of scope:**

- Vulnerabilities in WordPress core or in other plugins and themes
- Issues that require an already-compromised administrator account
- Missing security headers at the web-server level

## Design notes for reviewers

- The admin screen requires the `manage_options` capability.
- The Load More endpoint verifies the `pp_load_more_nonce` nonce and re-sanitises every
  attribute through `PP_Helpers::sanitize_shortcode_atts()` before querying. It is
  deliberately available to logged-out visitors, since the grid is public.
- Grids only ever show published posts that the query returns; the plugin adds no capability
  bypass of its own.
- Query results are cached in transients keyed by the sanitised attributes.
