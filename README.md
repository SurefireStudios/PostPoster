# Post Poster WordPress Plugin

A powerful WordPress plugin that lets you create layout grids from existing blog posts with customizable shortcodes and grid options. Display your posts in beautiful, responsive grid layouts with full control over appearance and content.

## Features

- **Flexible Grid Layouts**: 1-4 column responsive grids
- **Smart Content Selection**: Filter by categories, order by date/title/popularity
- **Customizable Display**: Toggle images, titles, excerpts with adjustable length
- **Multiple Image Ratios**: 16:9, 4:3, 1:1, or auto
- **Performance Optimized**: Built-in caching, lazy loading, efficient queries
- **Gutenberg Block**: Native block editor support with live preview
- **Theme Integration**: Template override support, theme-agnostic styling
- **Accessibility**: Semantic markup, proper ARIA labels, keyboard navigation
- **Developer Friendly**: Hooks, filters, and extensible architecture

## Installation

1. Upload the `post-poster` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to **Settings → Post Poster** to configure and generate shortcodes

## Basic Usage

### Using the Admin Interface

1. Navigate to **Settings → Post Poster** in your WordPress admin
2. Configure your desired settings:
   - Select categories to include
   - Choose number of columns (1-4)
   - Set posts per page
   - Toggle display options (image, title, excerpt)
   - Adjust layout settings (gutter, image ratio)
3. Click **"Generate Shortcode"** to create your shortcode
4. Copy and paste the shortcode into any page or post

### Using the Gutenberg Block

1. In the block editor, add the **"Post Poster Grid"** block
2. Configure settings in the block sidebar
3. See live preview in the editor
4. Publish your page

## Shortcode Reference

### Basic Shortcode

```
[pp_posts]
```

### Full Example

```
[pp_posts categories="news,features" columns="3" per_page="9" show_image="true" show_title="true" show_excerpt="true" excerpt_words="22" orderby="date" order="DESC" pagination="numeric" image_ratio="16x9" gutter="16" cache_minutes="60"]
```

### Shortcode Attributes

| Attribute | Type | Default | Description |
|-----------|------|---------|-------------|
| `categories` | string | `""` | Comma-separated category slugs (e.g., "news,design") |
| `columns` | number | `3` | Number of columns (1-4) |
| `per_page` | number | `9` | Posts per page (1-50) |
| `show_image` | boolean | `true` | Display featured images |
| `show_title` | boolean | `true` | Display post titles |
| `show_excerpt` | boolean | `true` | Display post excerpts |
| `excerpt_words` | number | `18` | Excerpt length in words (5-100) |
| `orderby` | string | `date` | Order posts by: `date`, `title`, `modified`, `rand`, `comment_count` |
| `order` | string | `DESC` | Sort direction: `ASC` or `DESC` |
| `pagination` | string | `none` | Pagination type: `none` or `numeric` |
| `image_ratio` | string | `16x9` | Image aspect ratio: `16x9`, `4x3`, `1x1`, `auto` |
| `gutter` | number | `16` | Space between cards in pixels (0-50) |
| `class` | string | `""` | Additional CSS class for the grid wrapper |
| `cache_minutes` | number | `15` | Cache duration in minutes (0 to disable) |

## Examples

### News Section
```
[pp_posts categories="news" columns="3" per_page="6" show_excerpt="true" excerpt_words="25"]
```

### Featured Posts Grid
```
[pp_posts categories="featured" columns="2" per_page="4" image_ratio="4x3" gutter="24"]
```

### Simple Title List
```
[pp_posts columns="1" show_image="false" show_excerpt="false" per_page="10"]
```

### Random Posts Showcase
```
[pp_posts orderby="rand" columns="4" per_page="8" image_ratio="1x1" gutter="12"]
```

## Styling and Customization

### CSS Classes

The plugin uses scoped CSS classes that are safe to customize:

- `.pp-grid` - Main grid container
- `.pp-cols-{1-4}` - Column layout classes
- `.pp-card` - Individual post card
- `.pp-card-image` - Image container
- `.pp-card-content` - Text content area
- `.pp-card-title` - Post title
- `.pp-card-excerpt` - Excerpt text
- `.pp-card-meta` - Date and metadata

### Template Overrides

You can override plugin templates by copying them to your theme:

```
wp-content/themes/your-theme/pp/templates/card.php
wp-content/themes/your-theme/pp/templates/wrapper-start.php
wp-content/themes/your-theme/pp/templates/wrapper-end.php
```

### Custom CSS

Add custom styles in your theme's CSS:

```css
.pp-grid {
    --pp-gutter: 20px; /* Custom gutter size */
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

## Developer Hooks

### Actions

```php
// Before grid starts
do_action('pp_before_grid', $atts);

// After grid ends  
do_action('pp_after_grid', $atts);

// Additional card content
do_action('pp_card_content', $post, $atts);
```

### Filters

```php
// Modify query arguments
add_filter('pp_query_args', function($args, $atts) {
    // Customize WP_Query arguments
    return $args;
}, 10, 2);

// Customize no posts message
add_filter('pp_no_posts_message', function($message) {
    return 'Custom no posts message';
});
```

## Performance

### Caching

The plugin includes built-in caching to improve performance:

- Query results are cached using WordPress transients
- Cache duration is configurable per shortcode
- Cache is automatically cleared when posts are updated
- Set `cache_minutes="0"` to disable caching

### Optimization Features

- Efficient `WP_Query` with minimal meta/term cache
- Lazy loading images with `loading="lazy"`
- Responsive images with `srcset` and `sizes`
- Minimal, scoped CSS to avoid conflicts
- JavaScript only loaded on admin pages

## Browser Support

- Modern browsers with CSS Grid support
- Graceful fallback for older browsers
- Mobile-responsive design
- Accessibility compliant (WCAG 2.1 AA)

## Requirements

- WordPress 6.0 or higher
- PHP 7.4 or higher
- Modern theme with CSS Grid support (recommended)

## Troubleshooting

### No Posts Displayed

1. Check that the selected categories contain published posts
2. Verify category slugs are correct (not names)
3. Ensure posts have featured images if `show_image="true"`
4. Clear cache by setting `cache_minutes="0"` temporarily

### Styling Issues

1. Check for theme CSS conflicts
2. Ensure your theme supports CSS Grid
3. Use browser developer tools to inspect CSS
4. Try adding `!important` to custom CSS rules

### Performance Issues

1. Enable caching with `cache_minutes="60"`
2. Limit posts per page (`per_page="6"`)
3. Optimize images in WordPress Media Library
4. Consider using a caching plugin

## Support

For support and feature requests, please:

1. Check the documentation and examples above
2. Search existing issues on GitHub
3. Create a new issue with detailed information
4. Include WordPress version, theme name, and plugin settings

## Changelog

### 1.0.0
- Initial release
- Core shortcode functionality
- Admin interface
- Gutenberg block support
- Template override system
- Performance optimizations
- Accessibility features

## License

GPL v2 or later - see [LICENSE](LICENSE) file for details.

## Credits

Developed with ❤️ for the WordPress community. Built using WordPress coding standards and best practices.
