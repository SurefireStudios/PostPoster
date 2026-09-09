<div align="center">

# Post Poster

**Turn the posts you already have into responsive grid layouts — with a shortcode, a Gutenberg block, or a point-and-click generator in the WordPress admin.**

[![License](https://img.shields.io/github/license/SurefireStudios/PostPoster?color=blue)](LICENSE)
[![Lint](https://github.com/SurefireStudios/PostPoster/actions/workflows/lint.yml/badge.svg)](https://github.com/SurefireStudios/PostPoster/actions/workflows/lint.yml)
[![Version 1.0.1](https://img.shields.io/badge/version-1.0.1-0d9488)](post-poster.php)
[![WordPress 6.0+](https://img.shields.io/badge/WordPress-6.0%2B-21759b?logo=wordpress&logoColor=white)](https://wordpress.org)
[![PHP 7.4+](https://img.shields.io/badge/PHP-7.4%2B-777bb4?logo=php&logoColor=white)](https://www.php.net)
[![Stars](https://img.shields.io/github/stars/SurefireStudios/PostPoster?style=flat)](https://github.com/SurefireStudios/PostPoster/stargazers)

Built by **[Surefire Studios](https://www.surefirestudios.io)**

</div>

---

## What it does

Point Post Poster at some categories, pick a column count, and drop the shortcode on a page.
You get a responsive card grid of your existing posts — featured image, title, excerpt, date,
author and categories, each toggleable — with numeric pagination or an AJAX *Load More*
button.

Nothing is duplicated: the grid is a `WP_Query` over your existing posts, cached in a
transient, rendered through templates your theme can override.

---

## ✨ Features

- **Flexible grid layouts** — 1–4 responsive columns with a configurable gutter
- **Smart content selection** — filter by category, order by date, title, modified, random, comment count or menu order
- **Customisable cards** — toggle image, title, excerpt, date, author and categories independently, with adjustable excerpt length
- **Multiple image ratios** — `16x9`, `4x3`, `1x1`, or `auto`
- **Three pagination modes** — none, numeric links, or an AJAX **Load More** button
- **Light, dark or automatic** — a `theme` option that can follow the visitor's `prefers-color-scheme`
- **Shortcode generator** — build a shortcode from a form in the admin, with a live preview and copy-to-clipboard
- **Gutenberg block** — *Post Poster Grid*, with a live preview and sidebar controls
- **Built-in caching** — query results stored in transients, per-shortcode duration
- **Theme integration** — override any template from your theme; scoped `pp-` CSS classes
- **Accessibility** — semantic markup, ARIA labels, keyboard navigation
- **Developer friendly** — actions and filters, plus a file of worked examples
- **Translation-ready** — `languages/post-poster.pot` included
- **Clean uninstall** — removes its options, user meta and cached queries on delete

---

## 📦 Requirements

- WordPress **6.0** or higher (tested up to 6.4)
- PHP **7.4** or higher
- A theme with CSS Grid support (any modern theme)

---

## 🚀 Installation

1. Download this repository as a ZIP (**Code → Download ZIP**), or clone it:
   ```bash
   git clone https://github.com/SurefireStudios/PostPoster.git post-poster
   ```
2. Place the folder in `wp-content/plugins/` so you end up with
   `wp-content/plugins/post-poster/post-poster.php`.
3. Activate **Post Poster** under **Plugins** in the WordPress admin.
4. A **Post Poster** item appears in the admin sidebar.

Uploading the GitHub ZIP through **Plugins → Add New → Upload Plugin** also works — WordPress
just names the folder `PostPoster-main`, which is harmless.

---

## 📖 Usage

### Using the shortcode generator

1. Open **Post Poster** in the WordPress admin sidebar (it's a top-level menu, below Comments).
2. Configure what you want: categories, columns, posts per page, display toggles, image ratio,
   gutter, pagination and cache duration.
3. Click **Preview** to render the grid right there, or **Generate Shortcode**.
4. Hit **Copy** and paste the shortcode into any page or post.

### Using the Gutenberg block

1. In the block editor, add the **Post Poster Grid** block.
2. Configure it in the block sidebar — Content Selection, Layout, Display and Advanced.
3. The editor shows a live preview as you change settings.

### Using the shortcode directly

```
[pp_posts]
```

A fuller example:

```
[pp_posts categories="news,features" columns="3" per_page="9" show_image="true" show_title="true" show_excerpt="true" excerpt_words="22" orderby="date" order="DESC" pagination="numeric" image_ratio="16x9" gutter="16" cache_minutes="60"]
```

---

## ⚙️ Shortcode attributes

| Attribute | Type | Default | Description |
|-----------|------|---------|-------------|
| `categories` | string | `""` | Comma-separated category slugs (e.g. `news,design`). Empty means all categories |
| `columns` | number | `3` | Number of columns, clamped to 1–4 |
| `per_page` | number | `9` | Posts per page, clamped to 1–50 |
| `show_image` | boolean | `true` | Display featured images |
| `show_title` | boolean | `true` | Display post titles |
| `show_excerpt` | boolean | `true` | Display post excerpts |
| `excerpt_words` | number | `18` | Excerpt length in words, clamped to 5–100 |
| `show_date` | boolean | `true` | Display the published date |
| `show_author` | boolean | `false` | Display the author, linked to their archive |
| `show_categories` | boolean | `true` | Display the post's categories |
| `orderby` | string | `date` | `date`, `title`, `modified`, `rand`, `comment_count` or `menu_order`. Anything else falls back to `date` |
| `order` | string | `DESC` | `ASC` or `DESC` |
| `pagination` | string | `none` | `none`, `numeric`, or `load_more` for an AJAX button |
| `image_ratio` | string | `16x9` | `16x9`, `4x3`, `1x1` or `auto` |
| `gutter` | number | `16` | Space between cards in pixels, clamped to 0–50 |
| `theme` | string | `auto` | `auto` (follows `prefers-color-scheme`), `light` or `dark` |
| `class` | string | `""` | Extra CSS class on the grid wrapper |
| `cache_minutes` | number | `15` | Cache duration in minutes, clamped to 0–1440. `0` disables caching |

Booleans accept `true`, `1`, `yes` or `on`; anything else is false.

### Examples

**News section**
```
[pp_posts categories="news" columns="3" per_page="6" show_excerpt="true" excerpt_words="25"]
```

**Featured posts grid**
```
[pp_posts categories="featured" columns="2" per_page="4" image_ratio="4x3" gutter="24"]
```

**Simple title list**
```
[pp_posts columns="1" show_image="false" show_excerpt="false" per_page="10"]
```

**Random showcase, square images**
```
[pp_posts orderby="rand" columns="4" per_page="8" image_ratio="1x1" gutter="12"]
```

**Infinite-style browsing with a Load More button**
```
[pp_posts columns="3" per_page="9" pagination="load_more" show_author="true"]
```

**Forced dark cards**
```
[pp_posts columns="3" theme="dark"]
```

---

## 🎨 Styling and customisation

### CSS classes

The grid uses scoped `pp-` classes that are safe to target:

| Class | Element |
| --- | --- |
| `.pp-grid` | Grid container |
| `.pp-cols-{1-4}` | Column count |
| `.pp-theme-{auto\|light\|dark}` | Colour scheme |
| `.pp-card` | A single post card |
| `.pp-card-image` | Image container |
| `.pp-card-content` | Text content area |
| `.pp-card-title` | Post title |
| `.pp-card-excerpt` | Excerpt text |
| `.pp-card-meta` | Date, author and category area |
| `.pp-card-date` / `.pp-card-author` / `.pp-card-categories` | Individual meta items |
| `.pp-read-more-btn` | Read-more link |
| `.pp-no-posts` | Empty-result message |

The gutter is exposed as a custom property on the wrapper, so you can override it in CSS:

```css
.pp-grid {
    --pp-gutter: 20px;
}

.pp-card {
    border: 1px solid #e0e0e0;
    border-radius: 12px;
}

.pp-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}
```

### Template overrides

Copy any template into your theme to take control of the markup. Child themes are checked
first, then the parent theme:

```
wp-content/themes/your-theme/pp/templates/card.php
wp-content/themes/your-theme/pp/templates/wrapper-start.php
wp-content/themes/your-theme/pp/templates/wrapper-end.php
```

---

## 🧑‍💻 Developer hooks

### Actions

```php
// Before the grid opens
do_action('pp_before_grid', $atts);

// After the grid closes
do_action('pp_after_grid', $atts);

// Extra content inside each card
do_action('pp_card_content', $post, $atts);
```

### Filters

```php
// Modify the WP_Query arguments
add_filter('pp_query_args', function ($args, $atts) {
    return $args;
}, 10, 2);

// Customise the "no posts" message
add_filter('pp_no_posts_message', function ($message) {
    return 'Nothing here yet.';
});
```

📄 **[`examples/usage-examples.php`](examples/usage-examples.php)** contains worked versions of
all of these, plus a block variation, a related-posts helper, image preloading, and a snippet
to clear the cache whenever a post is saved.

---

## ⚡ Performance

### Caching

Query results are cached in WordPress transients, with the duration set per shortcode via
`cache_minutes` (default 15, max 1440). Set `cache_minutes="0"` to disable it.

> [!NOTE]
> The cache is **not** invalidated automatically when a post is published or edited — a grid
> can serve stale results until the transient expires. Deactivating the plugin clears every
> cached query. To clear it on every post save, use the `save_post` snippet in
> [`examples/usage-examples.php`](examples/usage-examples.php).

### Optimisations

- `WP_Query` with post meta and term caches disabled
- Images lazy-loaded with `loading="lazy"` and responsive `srcset` / `sizes`
- Minimal, scoped CSS to avoid theme conflicts
- Admin JavaScript is only enqueued on the Post Poster screen; the frontend script only loads
  where a `[pp_posts]` shortcode is likely present

---

## 🩺 Troubleshooting

| Symptom | Try this |
| --- | --- |
| No posts displayed | Check the categories contain published posts, and that you used category **slugs**, not names |
| Changes don't show up | The transient cache is still warm — set `cache_minutes="0"` while you work |
| Cards look wrong | Check for theme CSS conflicts with developer tools; try `theme="light"` or `theme="dark"` to pin the palette |
| Grid doesn't lay out in columns | Your theme's container may be constraining it, or the browser predates CSS Grid |
| Load More does nothing | Confirm `pagination="load_more"` and check the browser console for a JavaScript error |

Still stuck? [Open an issue](https://github.com/SurefireStudios/PostPoster/issues) with your
WordPress version, theme name, and the exact shortcode you used.

---

## 🧰 Tech stack

| Layer | Used |
| --- | --- |
| Platform | WordPress plugin — plain PHP, no build step, no dependencies |
| Backend | `WP_Query`, Shortcode API, Transients API, AJAX (`admin-ajax.php`) |
| Editor | Gutenberg block registered as `post-poster/posts-grid` |
| Frontend | CSS Grid, vanilla CSS, jQuery for Load More |
| i18n | `post-poster` text domain with a bundled `.pot` |

### Project structure

```
PostPoster/
├── post-poster.php            # Plugin header, bootstrap, asset loading, Load More AJAX
├── uninstall.php              # Removes options, user meta and cached queries on delete
├── includes/
│   ├── class-helpers.php      # Attribute sanitising, caching, excerpts
│   ├── class-query.php        # WP_Query building and pagination markup
│   ├── class-shortcode.php    # [pp_posts] rendering and template loading
│   ├── class-admin.php        # Admin screen and shortcode generator
│   └── class-block.php        # Gutenberg block registration
├── templates/                 # card.php, wrapper-start.php, wrapper-end.php (overridable)
├── assets/                    # pp.css, frontend.js, admin.css/js, block.js/css
├── examples/usage-examples.php
├── languages/post-poster.pot
├── .github/                   # Lint CI and the debug-leftover check
├── CONTRIBUTING.md
├── SECURITY.md
├── LICENSE
└── README.md
```

---

## 📝 Changelog

### `1.0.1`

**Fixes**

- Removed debug logging that ran on **every** Load More request and wrote to the site's PHP error log. The diagnostic is still there, but only when `WP_DEBUG` is enabled.
- Removed nine `console.log` calls that shipped in the admin and frontend JavaScript.
- Removed a leftover `pp-debug-theme-*` class that was added to every grid alongside the real `pp-theme-*` class.
- Guarded a `get_post()` property read that raised *"Attempt to read property on null"* on PHP 8 for any page without a queried post (404s and some archives).
- Plugin header licence now matches the bundled `LICENSE` (GPLv3 or later).

### `1.0.0`

- Initial release
- `[pp_posts]` shortcode with 18 attributes
- Admin shortcode generator with live preview
- Gutenberg block with live preview
- Numeric and AJAX Load More pagination
- Template override system
- Transient caching, lazy loading, responsive images
- Light / dark / automatic colour schemes
- Accessibility features and translation support

---

## 🤝 Contributing

Issues and pull requests are welcome. Useful contributions:

- Cache invalidation on post save, built into the plugin
- Custom post type and taxonomy support
- Translations — the template is at [`languages/post-poster.pot`](languages/post-poster.pot)
- Testing reports against current WordPress releases

**[CONTRIBUTING.md](CONTRIBUTING.md)** covers local setup, how the classes fit together, the
coding conventions, and how to run the same checks CI does. Every push and pull request is
syntax-checked on PHP 7.4 and 8.3, plus JavaScript, and is rejected if `console.log` or an
ungated `error_log` reaches shipped code.

> [!CAUTION]
> Found a security vulnerability? **Don't open a public issue** — follow [SECURITY.md](SECURITY.md) to report it privately.

---

## 📄 License

Released under the **GNU General Public License v3.0** — see [LICENSE](LICENSE) for the full text.

---

## 🔗 Links

- 🌐 **Surefire Studios** — <https://www.surefirestudios.io>
- 🐛 **Issues** — <https://github.com/SurefireStudios/PostPoster/issues>

<div align="center">
<sub>Built by <a href="https://www.surefirestudios.io">Surefire Studios</a> for the WordPress community.</sub>
</div>
