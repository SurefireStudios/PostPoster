# Contributing

Thanks for your interest in Post Poster. Issues and pull requests are welcome.

Found a **security vulnerability**? Do not open a public issue — follow
[SECURITY.md](SECURITY.md) instead.

## Good first contributions

- **Cache invalidation on post save**, built into the plugin. Right now the transient cache
  only clears on deactivation, so a grid can serve stale results until it expires. There is a
  working `save_post` snippet in [`examples/usage-examples.php`](examples/usage-examples.php)
  that could become a proper feature (with a setting to turn it off).
- **Custom post type and taxonomy support** — the query is currently posts and categories only.
- **Translations.** The template is at `languages/post-poster.pot`.
- **Testing reports** against current WordPress releases; the header says *Tested up to 6.4*.

## Setting up

There is no build step and no dependencies. Clone the repository straight into a WordPress
install:

```bash
git clone https://github.com/SurefireStudios/PostPoster.git \
  wp-content/plugins/post-poster
```

Activate **Post Poster** under **Plugins**, then work on the files in place. A local
WordPress with `WP_DEBUG` enabled is strongly recommended:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

The Load More handler writes a diagnostic line to the debug log when `WP_DEBUG` is on, which
is useful when working on pagination.

## How the plugin fits together

| File | Responsibility |
| --- | --- |
| `post-poster.php` | Plugin header, bootstrap, asset enqueuing, Load More AJAX handler |
| `includes/class-helpers.php` | Attribute defaults and sanitising, caching, excerpts |
| `includes/class-query.php` | Builds `WP_Query` args, renders pagination |
| `includes/class-shortcode.php` | `[pp_posts]` rendering, template lookup |
| `includes/class-admin.php` | Admin screen and shortcode generator |
| `includes/class-block.php` | Gutenberg block registration |
| `templates/` | `card.php`, `wrapper-start.php`, `wrapper-end.php` — themes can override these |

**`PP_Helpers::sanitize_shortcode_atts()` is the single source of truth for attributes.** If
you add one, add it there — with a default and a sanitiser — and then surface it in the admin
form, the block, and the README table.

## Coding conventions

Match the surrounding code — it follows WordPress plugin conventions:

- **Escape on output**: `esc_html()`, `esc_attr()`, `esc_url()`
- **Sanitize on input**: `sanitize_text_field()`, `sanitize_key()`, `intval()`, and clamp
  numeric ranges the way the existing attributes do
- **Guard admin writes**: `current_user_can('manage_options')` plus nonce verification
- **Query safely**: use `WP_Query` and `$wpdb->prepare()`
- **Make strings translatable** with the `post-poster` text domain
- **Target PHP 7.4** — the plugin declares `Requires PHP: 7.4`, so no nullsafe operator
  (`?->`), no named arguments, no `match`
- **No `console.log` or unconditional `error_log`** in shipped code. Gate diagnostics behind
  `WP_DEBUG`

### After changing strings

Regenerate the translation template so `languages/post-poster.pot` stays in sync:

```bash
wp i18n make-pot . languages/post-poster.pot
```

## Before opening a pull request

1. **Lint.** Every PHP file must parse. CI runs this on PHP 7.4 and 8.3:
   ```bash
   find . -name '*.php' -print0 | xargs -0 -n1 php -l
   ```
   JavaScript is checked too:
   ```bash
   for f in assets/*.js; do node --check "$f"; done
   ```
2. **Test in a real WordPress install.** At minimum: the plugin activates without notices, the
   generator produces a working shortcode, the block previews, and a grid renders with both
   `numeric` and `load_more` pagination.
3. **Describe your change** — what it fixes, plus the WordPress and PHP versions you tested.

Keep one logical change per pull request, and prefer a small, reviewable diff over a reformat.
Note that the source files use CRLF line endings; please don't convert them.

## Reporting a bug

[Open an issue](https://github.com/SurefireStudios/PostPoster/issues) with the plugin version,
your WordPress and PHP versions, the exact shortcode you used, the steps to reproduce, and any
PHP error or debug-log output.

## License

By contributing, you agree that your work is released under the
[GNU General Public License v3.0](LICENSE), the same terms as the rest of this project.
