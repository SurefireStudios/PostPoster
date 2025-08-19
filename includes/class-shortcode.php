<?php
/**
 * Shortcode handling for Post Poster plugin
 *
 * @package PostPoster
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shortcode class
 */
class PP_Shortcode {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_shortcode('pp_posts', array($this, 'render_shortcode'));
    }
    
    /**
     * Render shortcode
     *
     * @param array $atts Shortcode attributes
     * @param string $content Shortcode content
     * @return string
     */
    public function render_shortcode($atts, $content = '') {
        // Sanitize attributes
        $atts = PP_Helpers::sanitize_shortcode_atts($atts);
        
        // Get current page for pagination
        $paged = max(1, get_query_var('paged', 1));
        
        // Get posts
        $query_result = PP_Query::get_posts($atts, $paged);
        
        // Start output buffering
        ob_start();
        
        // Check if we have posts
        if (empty($query_result['posts'])) {
            echo $this->render_no_posts_message();
            return ob_get_clean();
        }
        
        // Render wrapper start
        echo $this->render_wrapper_start($atts);
        
        // Render posts
        foreach ($query_result['posts'] as $current_post) {
            echo $this->render_post_card($current_post, $atts);
        }
        
        // Render wrapper end
        echo $this->render_wrapper_end($atts);
        
        // Render pagination if enabled
        if ($atts['pagination'] !== 'none') {
            echo PP_Query::get_pagination($query_result, $atts);
        }
        
        return ob_get_clean();
    }
    
    /**
     * Render wrapper start
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    private function render_wrapper_start($atts) {
        $classes = array(
            'pp-grid',
            'pp-cols-' . $atts['columns']
        );
        
        // Add theme class
        if ($atts['theme'] !== 'auto') {
            $classes[] = 'pp-theme-' . $atts['theme'];
        }
        
        // Debug: Always add a data attribute to verify the theme setting
        $classes[] = 'pp-debug-theme-' . $atts['theme'];
        
        if (!empty($atts['class'])) {
            $classes[] = $atts['class'];
        }
        
        $style = '';
        if ($atts['gutter'] > 0) {
            $style = ' style="--pp-gutter: ' . $atts['gutter'] . 'px;"';
        }
        
        // Generate unique ID for load more functionality
        $grid_id = 'pp-grid-' . wp_generate_uuid4();
        
        $wrapper_template = $this->get_template_path('wrapper-start');
        
        if ($wrapper_template) {
            ob_start();
            // Make variables available to template
            $grid_id = $grid_id;
            $classes = $classes;
            $style = $style;
            include $wrapper_template;
            return ob_get_clean();
        }
        
        return '<div id="' . esc_attr($grid_id) . '" class="' . esc_attr(implode(' ', $classes)) . '"' . $style . '>';
    }
    
    /**
     * Render wrapper end
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    private function render_wrapper_end($atts) {
        $wrapper_template = $this->get_template_path('wrapper-end');
        
        if ($wrapper_template) {
            ob_start();
            include $wrapper_template;
            return ob_get_clean();
        }
        
        return '</div>';
    }
    
    /**
     * Render post card
     *
     * @param WP_Post $current_post Post object
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function render_post_card($current_post, $atts) {
        // Store current global post
        global $post;
        $original_post = $post;
        
        // Set up post data for this specific post
        $post = $current_post;
        setup_postdata($post);
        
        $card_template = $this->get_template_path('card');
        
        if ($card_template) {
            ob_start();
            include $card_template;
            $output = ob_get_clean();
        } else {
            // Fallback to built-in rendering
            $output = $this->render_default_card($current_post, $atts);
        }
        
        // Restore original post data
        $post = $original_post;
        if ($original_post) {
            setup_postdata($original_post);
        } else {
            wp_reset_postdata();
        }
        
        return $output;
    }
    
    /**
     * Render default card (fallback)
     *
     * @param WP_Post $current_post Post object
     * @param array $atts Shortcode attributes
     * @return string
     */
    private function render_default_card($current_post, $atts) {
        $html = '<article class="pp-card">';
        
        // Image
        if ($atts['show_image']) {
            $html .= '<div class="pp-card-image">';
            $html .= '<a href="' . esc_url(get_permalink($current_post->ID)) . '" aria-hidden="true" tabindex="-1">';
            $html .= PP_Helpers::get_responsive_image(
                $current_post->ID,
                $atts['image_ratio'],
                $current_post->post_title
            );
            $html .= '</a>';
            $html .= '</div>';
        }
        
        $html .= '<div class="pp-card-content">';
        
        // Title
        if ($atts['show_title']) {
            $html .= '<h3 class="pp-card-title">';
            $html .= '<a href="' . esc_url(get_permalink($current_post->ID)) . '">';
            $html .= esc_html($current_post->post_title);
            $html .= '</a>';
            $html .= '</h3>';
        }
        
        // Excerpt
        if ($atts['show_excerpt']) {
            $excerpt = $this->get_post_excerpt($current_post, $atts['excerpt_words']);
            if (!empty($excerpt)) {
                $html .= '<div class="pp-card-excerpt">';
                $html .= '<p>' . esc_html($excerpt) . '</p>';
                $html .= '</div>';
            }
        }
        
        // Meta
        $html .= '<div class="pp-card-meta">';
        $html .= '<time class="pp-card-date" datetime="' . esc_attr(get_the_date('c', $current_post->ID)) . '">';
        $html .= esc_html(get_the_date('', $current_post->ID));
        $html .= '</time>';
        $html .= '</div>';
        
        $html .= '</div>'; // .pp-card-content
        $html .= '</article>';
        
        return $html;
    }
    
    /**
     * Get post excerpt
     *
     * @param WP_Post $current_post Post object
     * @param int $word_count Number of words
     * @return string
     */
    private function get_post_excerpt($current_post, $word_count) {
        // Check if post has manual excerpt
        if (!empty($current_post->post_excerpt)) {
            return PP_Helpers::generate_excerpt($current_post->post_excerpt, $word_count);
        }
        
        // Generate excerpt from content
        return PP_Helpers::generate_excerpt($current_post->post_content, $word_count);
    }
    
    /**
     * Render no posts message
     *
     * @return string
     */
    private function render_no_posts_message() {
        $message = apply_filters(
            'pp_no_posts_message',
            __('No posts found matching the specified criteria.', 'post-poster')
        );
        
        return '<div class="pp-no-posts">' . esc_html($message) . '</div>';
    }
    
    /**
     * Get template path with theme override support
     *
     * @param string $template Template name
     * @return string|false
     */
    private function get_template_path($template) {
        $template_name = $template . '.php';
        
        // Check for theme override
        $theme_template = get_stylesheet_directory() . '/pp/templates/' . $template_name;
        if (file_exists($theme_template)) {
            return $theme_template;
        }
        
        // Check parent theme
        $parent_template = get_template_directory() . '/pp/templates/' . $template_name;
        if (file_exists($parent_template)) {
            return $parent_template;
        }
        
        // Use plugin template
        $plugin_template = POST_POSTER_PLUGIN_DIR . 'templates/' . $template_name;
        if (file_exists($plugin_template)) {
            return $plugin_template;
        }
        
        return false;
    }
    
    /**
     * Get shortcode preview for admin
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function get_admin_preview($atts) {
        // Sanitize attributes
        $atts = PP_Helpers::sanitize_shortcode_atts($atts);
        
        // Limit posts for preview
        $preview_atts = $atts;
        $preview_atts['per_page'] = min(6, $atts['per_page']);
        $preview_atts['pagination'] = 'none';
        $preview_atts['cache_minutes'] = 0;
        
        // Get posts using the main query function
        $query_result = PP_Query::get_posts($preview_atts, 1);
        
        // Start output buffering
        ob_start();
        
        echo '<div class="pp-admin-preview">';
        echo '<h4>' . __('Preview', 'post-poster') . '</h4>';
        
        // Check if we have posts
        if (empty($query_result['posts'])) {
            echo $this->render_no_posts_message();
        } else {
            // Render wrapper start
            echo $this->render_wrapper_start($preview_atts);
            
            // Render posts
            foreach ($query_result['posts'] as $current_post) {
                echo $this->render_post_card($current_post, $preview_atts);
            }
            
            // Render wrapper end
            echo $this->render_wrapper_end($preview_atts);
        }
        
        echo '</div>';
        
        return ob_get_clean();
    }
    
    /**
     * Generate shortcode string from attributes
     *
     * @param array $atts Attributes
     * @return string
     */
    public static function generate_shortcode_string($atts) {
        $shortcode = '[pp_posts';
        
        foreach ($atts as $key => $value) {
            if ($value !== '' && $value !== null) {
                $shortcode .= ' ' . $key . '="' . esc_attr($value) . '"';
            }
        }
        
        $shortcode .= ']';
        
        return $shortcode;
    }
}