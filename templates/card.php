<?php
/**
 * Template for post card
 *
 * @package PostPoster
 * @var WP_Post $post Current post object (set by setup_postdata)
 * @var array $atts Shortcode attributes
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Ensure we have a post object
global $post;
if (!isset($post) || !is_a($post, 'WP_Post')) {
    return;
}

// Get post data using WordPress functions (they use the global $post)
$post_id = get_the_ID();
$post_title = get_the_title();
$post_permalink = get_the_permalink();
$post_date = get_the_date();
$post_datetime = get_the_date('c');

// Get excerpt
$post_excerpt = '';
if ($atts['show_excerpt']) {
    if (has_excerpt()) {
        $post_excerpt = PP_Helpers::generate_excerpt(get_the_excerpt(), $atts['excerpt_words']);
    } else {
        $post_excerpt = PP_Helpers::generate_excerpt(get_the_content(), $atts['excerpt_words']);
    }
}

// Get categories
$categories = get_the_category();
$category_names = array();
if (!empty($categories)) {
    foreach ($categories as $category) {
        $category_names[] = $category->name;
    }
}

?>
<article class="pp-card" itemscope itemtype="https://schema.org/Article">
    
    <?php if ($atts['show_image']) : ?>
        <div class="pp-card-image">
            <a href="<?php echo esc_url($post_permalink); ?>" aria-hidden="true" tabindex="-1">
                <?php echo PP_Helpers::get_responsive_image(get_the_ID(), $atts['image_ratio'], get_the_title()); ?>
            </a>
        </div>
    <?php endif; ?>
    
    <div class="pp-card-content">
        
        <?php if ($atts['show_title']) : ?>
            <h3 class="pp-card-title" itemprop="headline">
                <a href="<?php echo esc_url($post_permalink); ?>" itemprop="url">
                    <?php echo esc_html($post_title); ?>
                </a>
            </h3>
        <?php endif; ?>
        
        <?php if ($atts['show_excerpt'] && !empty($post_excerpt)) : ?>
            <div class="pp-card-excerpt" itemprop="description">
                <p><?php echo esc_html($post_excerpt); ?></p>
            </div>
        <?php endif; ?>
        
        <a href="<?php echo esc_url($post_permalink); ?>" class="pp-read-more-btn">
            <?php esc_html_e('Read More', 'post-poster'); ?>
        </a>
        
        <?php if ($atts['show_date'] || $atts['show_author'] || ($atts['show_categories'] && !empty($categories))) : ?>
        <div class="pp-card-meta">
            <?php if ($atts['show_date']) : ?>
                <time class="pp-card-date" datetime="<?php echo esc_attr($post_datetime); ?>" itemprop="datePublished">
                    <?php echo esc_html($post_date); ?>
                </time>
            <?php endif; ?>
            
            <?php if ($atts['show_author']) : ?>
                <span class="pp-card-author">
                    <?php esc_html_e('by', 'post-poster'); ?> 
                    <a href="<?php echo esc_url(get_author_posts_url(get_the_author_meta('ID'))); ?>" class="pp-author-link">
                        <?php echo esc_html(get_the_author()); ?>
                    </a>
                </span>
            <?php endif; ?>
            
            <?php if ($atts['show_categories'] && !empty($categories)) : ?>
                <span class="pp-card-categories">
                    <span class="pp-card-categories-label"><?php esc_html_e('in', 'post-poster'); ?></span>
                    <?php 
                    $category_links = array();
                    foreach ($categories as $category) {
                        $category_links[] = '<a href="' . esc_url(get_category_link($category->term_id)) . '" class="pp-category-link">' . esc_html($category->name) . '</a>';
                    }
                    echo implode(', ', $category_links);
                    ?>
                </span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
    </div>
    
    <?php
    /**
     * Hook for additional card content
     *
     * @param WP_Post $post Current post object
     * @param array $atts Shortcode attributes
     */
    do_action('pp_card_content', $post, $atts);
    ?>
    
</article>