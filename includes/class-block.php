<?php
/**
 * Gutenberg block for Post Poster plugin
 *
 * @package PostPoster
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Block class
 */
class PP_Block {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'register_block'));
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_block_assets'));
    }
    
    /**
     * Register Gutenberg block
     */
    public function register_block() {
        // Check if Gutenberg is available
        if (!function_exists('register_block_type')) {
            return;
        }
        
        register_block_type('post-poster/posts-grid', array(
            'attributes' => $this->get_block_attributes(),
            'render_callback' => array($this, 'render_block'),
            'editor_script' => 'post-poster-block-editor',
            'editor_style' => 'post-poster-block-editor-style',
            'style' => 'post-poster-style'
        ));
    }
    
    /**
     * Get block attributes
     *
     * @return array
     */
    private function get_block_attributes() {
        return array(
            'categories' => array(
                'type' => 'string',
                'default' => ''
            ),
            'columns' => array(
                'type' => 'number',
                'default' => 3
            ),
            'perPage' => array(
                'type' => 'number',
                'default' => 9
            ),
            'showImage' => array(
                'type' => 'boolean',
                'default' => true
            ),
            'showTitle' => array(
                'type' => 'boolean',
                'default' => true
            ),
            'showExcerpt' => array(
                'type' => 'boolean',
                'default' => true
            ),
            'excerptWords' => array(
                'type' => 'number',
                'default' => 18
            ),
            'orderBy' => array(
                'type' => 'string',
                'default' => 'date'
            ),
            'order' => array(
                'type' => 'string',
                'default' => 'DESC'
            ),
            'pagination' => array(
                'type' => 'string',
                'default' => 'none'
            ),
            'imageRatio' => array(
                'type' => 'string',
                'default' => '16x9'
            ),
            'gutter' => array(
                'type' => 'number',
                'default' => 16
            ),
            'className' => array(
                'type' => 'string',
                'default' => ''
            ),
            'cacheMinutes' => array(
                'type' => 'number',
                'default' => 15
            )
        );
    }
    
    /**
     * Render block
     *
     * @param array $attributes Block attributes
     * @return string
     */
    public function render_block($attributes) {
        // Convert block attributes to shortcode format
        $shortcode_atts = array(
            'categories' => $attributes['categories'] ?? '',
            'columns' => $attributes['columns'] ?? 3,
            'per_page' => $attributes['perPage'] ?? 9,
            'show_image' => $attributes['showImage'] ?? true ? 'true' : 'false',
            'show_title' => $attributes['showTitle'] ?? true ? 'true' : 'false',
            'show_excerpt' => $attributes['showExcerpt'] ?? true ? 'true' : 'false',
            'excerpt_words' => $attributes['excerptWords'] ?? 18,
            'orderby' => $attributes['orderBy'] ?? 'date',
            'order' => $attributes['order'] ?? 'DESC',
            'pagination' => $attributes['pagination'] ?? 'none',
            'image_ratio' => $attributes['imageRatio'] ?? '16x9',
            'gutter' => $attributes['gutter'] ?? 16,
            'class' => $attributes['className'] ?? '',
            'cache_minutes' => $attributes['cacheMinutes'] ?? 15
        );
        
        // Use shortcode render function
        $shortcode = new PP_Shortcode();
        return $shortcode->render_shortcode($shortcode_atts);
    }
    
    /**
     * Enqueue block assets
     */
    public function enqueue_block_assets() {
        // Block editor script
        wp_enqueue_script(
            'post-poster-block-editor',
            POST_POSTER_PLUGIN_URL . 'assets/block.js',
            array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n'),
            POST_POSTER_VERSION,
            true
        );
        
        // Block editor styles
        wp_enqueue_style(
            'post-poster-block-editor-style',
            POST_POSTER_PLUGIN_URL . 'assets/block-editor.css',
            array('wp-edit-blocks'),
            POST_POSTER_VERSION
        );
        
        // Localize script with data
        wp_localize_script('post-poster-block-editor', 'ppBlock', array(
            'categories' => PP_Helpers::get_all_categories(),
            'strings' => array(
                'title' => __('Post Poster Grid', 'post-poster'),
                'description' => __('Display posts in a customizable grid layout', 'post-poster'),
                'categories' => __('Categories', 'post-poster'),
                'columns' => __('Columns', 'post-poster'),
                'perPage' => __('Posts per page', 'post-poster'),
                'showImage' => __('Show featured image', 'post-poster'),
                'showTitle' => __('Show post title', 'post-poster'),
                'showExcerpt' => __('Show excerpt', 'post-poster'),
                'excerptWords' => __('Excerpt length (words)', 'post-poster'),
                'orderBy' => __('Order by', 'post-poster'),
                'order' => __('Sort order', 'post-poster'),
                'pagination' => __('Pagination', 'post-poster'),
                'imageRatio' => __('Image ratio', 'post-poster'),
                'gutter' => __('Gutter size', 'post-poster'),
                'cacheMinutes' => __('Cache duration (minutes)', 'post-poster'),
                'noPostsFound' => __('No posts found matching the criteria.', 'post-poster')
            ),
            'options' => array(
                'orderBy' => array(
                    array('value' => 'date', 'label' => __('Date', 'post-poster')),
                    array('value' => 'title', 'label' => __('Title', 'post-poster')),
                    array('value' => 'modified', 'label' => __('Modified', 'post-poster')),
                    array('value' => 'rand', 'label' => __('Random', 'post-poster')),
                    array('value' => 'comment_count', 'label' => __('Comment Count', 'post-poster'))
                ),
                'order' => array(
                    array('value' => 'DESC', 'label' => __('Descending', 'post-poster')),
                    array('value' => 'ASC', 'label' => __('Ascending', 'post-poster'))
                ),
                'pagination' => array(
                    array('value' => 'none', 'label' => __('None', 'post-poster')),
                    array('value' => 'numeric', 'label' => __('Numeric', 'post-poster'))
                ),
                'imageRatio' => array(
                    array('value' => '16x9', 'label' => __('16:9 (Widescreen)', 'post-poster')),
                    array('value' => '4x3', 'label' => __('4:3 (Standard)', 'post-poster')),
                    array('value' => '1x1', 'label' => __('1:1 (Square)', 'post-poster')),
                    array('value' => 'auto', 'label' => __('Auto', 'post-poster'))
                ),
                'cacheMinutes' => array(
                    array('value' => 0, 'label' => __('No cache', 'post-poster')),
                    array('value' => 15, 'label' => __('15 minutes', 'post-poster')),
                    array('value' => 60, 'label' => __('1 hour', 'post-poster')),
                    array('value' => 240, 'label' => __('4 hours', 'post-poster')),
                    array('value' => 1440, 'label' => __('24 hours', 'post-poster'))
                )
            )
        ));
    }
    
    /**
     * Get block preview for editor
     *
     * @param array $attributes Block attributes
     * @return string
     */
    public function get_block_preview($attributes) {
        // Convert to shortcode attributes
        $shortcode_atts = array(
            'categories' => $attributes['categories'] ?? '',
            'columns' => $attributes['columns'] ?? 3,
            'per_page' => min(6, $attributes['perPage'] ?? 9), // Limit for preview
            'show_image' => $attributes['showImage'] ?? true ? 'true' : 'false',
            'show_title' => $attributes['showTitle'] ?? true ? 'true' : 'false',
            'show_excerpt' => $attributes['showExcerpt'] ?? true ? 'true' : 'false',
            'excerpt_words' => $attributes['excerptWords'] ?? 18,
            'orderby' => $attributes['orderBy'] ?? 'date',
            'order' => $attributes['order'] ?? 'DESC',
            'pagination' => 'none', // No pagination in preview
            'image_ratio' => $attributes['imageRatio'] ?? '16x9',
            'gutter' => $attributes['gutter'] ?? 16,
            'cache_minutes' => 0 // No caching for preview
        );
        
        // Use shortcode preview function
        $shortcode = new PP_Shortcode();
        return $shortcode->get_admin_preview($shortcode_atts);
    }
}