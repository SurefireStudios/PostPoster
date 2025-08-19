<?php
/**
 * Post Poster Usage Examples
 * 
 * This file contains various examples of how to use the Post Poster plugin
 * programmatically and extend its functionality.
 *
 * @package PostPoster
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * SHORTCODE EXAMPLES
 * ==================
 */

// Basic grid - 3 columns, latest posts
echo do_shortcode('[pp_posts]');

// News category with pagination
echo do_shortcode('[pp_posts categories="news" columns="2" per_page="6" pagination="numeric"]');

// Featured posts with custom styling
echo do_shortcode('[pp_posts categories="featured" columns="4" image_ratio="1x1" gutter="20" class="featured-grid"]');

// Simple text-only list
echo do_shortcode('[pp_posts columns="1" show_image="false" show_excerpt="false" per_page="10"]');

// Random posts showcase
echo do_shortcode('[pp_posts orderby="rand" columns="3" per_page="9" cache_minutes="60"]');


/**
 * PROGRAMMATIC USAGE
 * ==================
 */

// Get posts using the plugin's query class
$atts = array(
    'categories' => 'news,featured',
    'columns' => 3,
    'per_page' => 6,
    'show_image' => true,
    'show_title' => true,
    'show_excerpt' => true,
    'excerpt_words' => 20,
    'orderby' => 'date',
    'order' => 'DESC',
    'image_ratio' => '16x9',
    'gutter' => 16
);

$sanitized_atts = PP_Helpers::sanitize_shortcode_atts($atts);
$query_result = PP_Query::get_posts($sanitized_atts, 1);

if (!empty($query_result['posts'])) {
    echo '<div class="custom-posts-grid">';
    foreach ($query_result['posts'] as $post) {
        echo '<article>';
        echo '<h3><a href="' . get_permalink($post) . '">' . get_the_title($post) . '</a></h3>';
        echo '<p>' . get_the_excerpt($post) . '</p>';
        echo '</article>';
    }
    echo '</div>';
}


/**
 * HOOKS AND FILTERS EXAMPLES
 * ===========================
 */

// Modify query arguments
add_filter('pp_query_args', 'custom_pp_query_args', 10, 2);
function custom_pp_query_args($args, $atts) {
    // Only show posts from the last 30 days
    $args['date_query'] = array(
        array(
            'after' => '30 days ago'
        )
    );
    
    // Exclude certain post IDs
    $args['post__not_in'] = array(123, 456, 789);
    
    return $args;
}

// Add custom content to cards
add_action('pp_card_content', 'custom_pp_card_content', 10, 2);
function custom_pp_card_content($post, $atts) {
    // Add author information
    echo '<div class="pp-card-author">';
    echo 'By ' . get_the_author_meta('display_name', $post->post_author);
    echo '</div>';
    
    // Add reading time estimate
    $word_count = str_word_count(strip_tags($post->post_content));
    $reading_time = ceil($word_count / 200); // Assume 200 words per minute
    echo '<div class="pp-card-reading-time">';
    echo $reading_time . ' min read';
    echo '</div>';
}

// Customize no posts message
add_filter('pp_no_posts_message', 'custom_no_posts_message');
function custom_no_posts_message($message) {
    return 'Sorry, no articles match your criteria. Try browsing our <a href="/archives">archives</a> instead.';
}

// Add content before/after grid
add_action('pp_before_grid', 'custom_before_grid');
function custom_before_grid($atts) {
    if (!empty($atts['categories'])) {
        $categories = PP_Helpers::get_categories_from_string($atts['categories']);
        if (!empty($categories)) {
            echo '<div class="pp-grid-header">';
            echo '<h2>Latest from: ' . implode(', ', array_column($categories, 'name')) . '</h2>';
            echo '</div>';
        }
    }
}

add_action('pp_after_grid', 'custom_after_grid');
function custom_after_grid($atts) {
    echo '<div class="pp-grid-footer">';
    echo '<a href="/blog" class="view-all-button">View All Posts →</a>';
    echo '</div>';
}


/**
 * TEMPLATE OVERRIDE EXAMPLES
 * ===========================
 */

// Example custom card template (save as: wp-content/themes/your-theme/pp/templates/card.php)
/*
<article class="custom-pp-card" itemscope itemtype="https://schema.org/Article">
    
    <?php if ($atts['show_image'] && has_post_thumbnail($post->ID)) : ?>
        <div class="custom-card-image">
            <a href="<?php echo esc_url(get_permalink($post)); ?>">
                <?php echo get_the_post_thumbnail($post->ID, 'medium', array('loading' => 'lazy')); ?>
            </a>
        </div>
    <?php endif; ?>
    
    <div class="custom-card-content">
        
        <?php if ($atts['show_title']) : ?>
            <h3 class="custom-card-title" itemprop="headline">
                <a href="<?php echo esc_url(get_permalink($post)); ?>" itemprop="url">
                    <?php echo esc_html(get_the_title($post)); ?>
                </a>
            </h3>
        <?php endif; ?>
        
        <div class="custom-card-meta">
            <time datetime="<?php echo esc_attr(get_the_date('c', $post)); ?>">
                <?php echo esc_html(get_the_date('F j, Y', $post)); ?>
            </time>
            <span class="custom-card-author">
                by <?php echo esc_html(get_the_author_meta('display_name', $post->post_author)); ?>
            </span>
        </div>
        
        <?php if ($atts['show_excerpt']) : ?>
            <div class="custom-card-excerpt" itemprop="description">
                <?php 
                $excerpt = $post->post_excerpt ?: $post->post_content;
                echo '<p>' . esc_html(PP_Helpers::generate_excerpt($excerpt, $atts['excerpt_words'])) . '</p>';
                ?>
            </div>
        <?php endif; ?>
        
        <div class="custom-card-tags">
            <?php 
            $tags = get_the_tags($post->ID);
            if ($tags) {
                foreach ($tags as $tag) {
                    echo '<span class="tag">' . esc_html($tag->name) . '</span>';
                }
            }
            ?>
        </div>
        
    </div>
    
</article>
*/


/**
 * CUSTOM CSS EXAMPLES
 * ====================
 */

// Add custom styles to your theme's style.css or custom CSS:
/*

// Custom grid with overlays
.featured-grid .pp-card {
    position: relative;
    overflow: hidden;
}

.featured-grid .pp-card-image::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(180deg, transparent 0%, rgba(0,0,0,0.7) 100%);
}

.featured-grid .pp-card-content {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    color: white;
    z-index: 2;
}

// Masonry-style layout
.masonry-grid .pp-grid {
    columns: 3;
    column-gap: var(--pp-gutter, 16px);
    grid-template-columns: none;
}

.masonry-grid .pp-card {
    break-inside: avoid;
    margin-bottom: var(--pp-gutter, 16px);
}

// Dark theme variant
.dark-theme .pp-card {
    background: #1a1a1a;
    color: #e0e0e0;
    border: 1px solid #333;
}

.dark-theme .pp-card-title a {
    color: #fff;
}

.dark-theme .pp-card-excerpt p {
    color: #ccc;
}

*/


/**
 * GUTENBERG BLOCK EXAMPLES
 * =========================
 */

// Register custom block variation
add_action('init', 'register_custom_pp_block_variation');
function register_custom_pp_block_variation() {
    if (function_exists('register_block_variation')) {
        register_block_variation('post-poster/posts-grid', array(
            'name' => 'featured-posts',
            'title' => 'Featured Posts Grid',
            'description' => 'Display featured posts in a 2-column grid',
            'attributes' => array(
                'categories' => 'featured',
                'columns' => 2,
                'perPage' => 4,
                'imageRatio' => '4x3',
                'gutter' => 24
            ),
            'scope' => array('inserter')
        ));
    }
}


/**
 * HELPER FUNCTION EXAMPLES
 * =========================
 */

// Get related posts using plugin helper
function get_related_posts_widget($post_id, $limit = 3) {
    $related_posts = PP_Query::get_related_posts($post_id, $limit);
    
    if (empty($related_posts)) {
        return '';
    }
    
    $output = '<div class="related-posts-widget">';
    $output .= '<h3>Related Posts</h3>';
    $output .= '<ul>';
    
    foreach ($related_posts as $post) {
        $output .= '<li>';
        $output .= '<a href="' . get_permalink($post) . '">';
        $output .= get_the_title($post);
        $output .= '</a>';
        $output .= '</li>';
    }
    
    $output .= '</ul>';
    $output .= '</div>';
    
    return $output;
}

// Custom cache clearing
function clear_pp_cache_on_post_update($post_id) {
    // Clear plugin cache when posts are updated
    PP_Helpers::clear_all_cache();
}
add_action('save_post', 'clear_pp_cache_on_post_update');

// Get popular posts for sidebar
function get_popular_posts_sidebar() {
    $popular_posts = PP_Query::get_popular_posts(5, 7); // 5 posts from last 7 days
    
    if (empty($popular_posts)) {
        return '';
    }
    
    $output = '<div class="popular-posts-sidebar">';
    $output .= '<h3>Popular This Week</h3>';
    
    foreach ($popular_posts as $post) {
        $output .= '<article class="popular-post-item">';
        
        if (has_post_thumbnail($post->ID)) {
            $output .= '<div class="popular-post-thumb">';
            $output .= '<a href="' . get_permalink($post) . '">';
            $output .= get_the_post_thumbnail($post->ID, 'thumbnail');
            $output .= '</a>';
            $output .= '</div>';
        }
        
        $output .= '<div class="popular-post-content">';
        $output .= '<h4><a href="' . get_permalink($post) . '">' . get_the_title($post) . '</a></h4>';
        $output .= '<time>' . get_the_date('M j', $post) . '</time>';
        $output .= '</div>';
        
        $output .= '</article>';
    }
    
    $output .= '</div>';
    
    return $output;
}


/**
 * PERFORMANCE OPTIMIZATION EXAMPLES
 * ==================================
 */

// Preload critical images
add_action('wp_head', 'preload_pp_grid_images');
function preload_pp_grid_images() {
    if (is_front_page()) {
        // Preload first few images on homepage
        $atts = array('per_page' => 3, 'cache_minutes' => 60);
        $query_result = PP_Query::get_posts(PP_Helpers::sanitize_shortcode_atts($atts), 1);
        
        if (!empty($query_result['posts'])) {
            foreach (array_slice($query_result['posts'], 0, 2) as $post) {
                $image_id = get_post_thumbnail_id($post->ID);
                if ($image_id) {
                    $image_src = wp_get_attachment_image_src($image_id, 'large');
                    if ($image_src) {
                        echo '<link rel="preload" as="image" href="' . esc_url($image_src[0]) . '">';
                    }
                }
            }
        }
    }
}

// Optimize images for grids
add_filter('wp_get_attachment_image_attributes', 'optimize_pp_grid_images', 10, 3);
function optimize_pp_grid_images($attr, $attachment, $size) {
    // Add specific sizes for grid images
    if (isset($attr['class']) && strpos($attr['class'], 'pp-') !== false) {
        $attr['sizes'] = '(max-width: 640px) 100vw, (max-width: 900px) 50vw, 33vw';
    }
    
    return $attr;
}
?>