<?php
/**
 * Helper functions for Post Poster plugin
 *
 * @package PostPoster
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Helper class
 */
class PP_Helpers {
    
    /**
     * Sanitize shortcode attributes
     *
     * @param array $atts Raw attributes
     * @return array Sanitized attributes
     */
    public static function sanitize_shortcode_atts($atts) {
        $defaults = array(
            'categories' => '',
            'columns' => 3,
            'per_page' => 9,
            'show_image' => 'true',
            'show_title' => 'true',
            'show_excerpt' => 'true',
            'excerpt_words' => 18,
            'show_date' => 'true',
            'show_author' => 'false',
            'show_categories' => 'true',
            'orderby' => 'date',
            'order' => 'DESC',
            'pagination' => 'none',
            'image_ratio' => '16x9',
            'gutter' => 16,
            'theme' => 'auto',
            'class' => '',
            'cache_minutes' => 15
        );
        
        $atts = shortcode_atts($defaults, $atts, 'pp_posts');
        
        // Sanitize each attribute
        $atts['categories'] = sanitize_text_field($atts['categories']);
        $atts['columns'] = max(1, min(4, intval($atts['columns'])));
        $atts['per_page'] = max(1, min(50, intval($atts['per_page'])));
        $atts['show_image'] = self::sanitize_boolean($atts['show_image']);
        $atts['show_title'] = self::sanitize_boolean($atts['show_title']);
        $atts['show_excerpt'] = self::sanitize_boolean($atts['show_excerpt']);
        $atts['excerpt_words'] = max(5, min(100, intval($atts['excerpt_words'])));
        $atts['show_date'] = self::sanitize_boolean($atts['show_date']);
        $atts['show_author'] = self::sanitize_boolean($atts['show_author']);
        $atts['show_categories'] = self::sanitize_boolean($atts['show_categories']);
        $atts['orderby'] = sanitize_key($atts['orderby']);
        $atts['order'] = in_array(strtoupper($atts['order']), array('ASC', 'DESC')) ? strtoupper($atts['order']) : 'DESC';
        $atts['pagination'] = in_array($atts['pagination'], array('none', 'numeric', 'load_more')) ? $atts['pagination'] : 'none';
        $atts['image_ratio'] = in_array($atts['image_ratio'], array('16x9', '1x1', '4x3', 'auto')) ? $atts['image_ratio'] : '16x9';
        $atts['gutter'] = max(0, min(50, intval($atts['gutter'])));
        $atts['theme'] = in_array($atts['theme'], array('auto', 'light', 'dark')) ? $atts['theme'] : 'auto';
        $atts['class'] = sanitize_html_class($atts['class']);
        $atts['cache_minutes'] = max(0, min(1440, intval($atts['cache_minutes'])));
        
        return $atts;
    }
    
    /**
     * Sanitize boolean value
     *
     * @param mixed $value Value to sanitize
     * @return bool
     */
    public static function sanitize_boolean($value) {
        if (is_bool($value)) {
            return $value;
        }
        
        if (is_string($value)) {
            return in_array(strtolower($value), array('true', '1', 'yes', 'on'));
        }
        
        return (bool) $value;
    }
    
    /**
     * Get categories array from string
     *
     * @param string $categories_string Comma-separated category slugs
     * @return array Category term objects
     */
    public static function get_categories_from_string($categories_string) {
        if (empty($categories_string)) {
            return array();
        }
        
        $category_slugs = array_map('trim', explode(',', $categories_string));
        $categories = array();
        
        foreach ($category_slugs as $slug) {
            $term = get_term_by('slug', $slug, 'category');
            if ($term && !is_wp_error($term)) {
                $categories[] = $term;
            }
        }
        
        return $categories;
    }
    
    /**
     * Get cache key for query
     *
     * @param array $atts Shortcode attributes
     * @param int $paged Current page number
     * @return string
     */
    public static function get_cache_key($atts, $paged = 1) {
        $key_data = array_merge($atts, array('paged' => $paged));
        return 'pp_query_' . md5(serialize($key_data));
    }
    
    /**
     * Get cached query results
     *
     * @param string $cache_key Cache key
     * @return mixed False if not cached, otherwise cached data
     */
    public static function get_cached_query($cache_key) {
        return get_transient($cache_key);
    }
    
    /**
     * Cache query results
     *
     * @param string $cache_key Cache key
     * @param mixed $data Data to cache
     * @param int $cache_minutes Cache duration in minutes
     */
    public static function cache_query($cache_key, $data, $cache_minutes) {
        if ($cache_minutes > 0) {
            set_transient($cache_key, $data, $cache_minutes * MINUTE_IN_SECONDS);
        }
    }
    
    /**
     * Clear all plugin cache
     */
    public static function clear_all_cache() {
        global $wpdb;
        
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_pp_query_%'
            )
        );
        
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_timeout_pp_query_%'
            )
        );
    }
    
    /**
     * Generate excerpt from content
     *
     * @param string $content Post content
     * @param int $word_count Number of words
     * @return string
     */
    public static function generate_excerpt($content, $word_count = 18) {
        $content = strip_shortcodes($content);
        $content = wp_strip_all_tags($content);
        $words = explode(' ', $content);
        
        if (count($words) > $word_count) {
            $words = array_slice($words, 0, $word_count);
            $content = implode(' ', $words) . '...';
        }
        
        return $content;
    }
    
    /**
     * Get responsive image HTML
     *
     * @param int $post_id Post ID
     * @param string $ratio Image ratio
     * @param string $alt Alt text
     * @return string
     */
    public static function get_responsive_image($post_id, $ratio = '16x9', $alt = '') {
        $image_id = get_post_thumbnail_id($post_id);
        
        if (!$image_id) {
            return self::get_placeholder_image($ratio, $alt);
        }
        
        $image_sizes = array(
            'mobile' => 'medium',
            'tablet' => 'large',
            'desktop' => 'full'
        );
        
        $image_src = wp_get_attachment_image_src($image_id, $image_sizes['desktop']);
        $image_srcset = wp_get_attachment_image_srcset($image_id, $image_sizes['desktop']);
        $image_sizes_attr = wp_get_attachment_image_sizes($image_id, $image_sizes['desktop']);
        
        if (!$image_src) {
            return self::get_placeholder_image($ratio, $alt);
        }
        
        $ratio_class = $ratio !== 'auto' ? 'pp-ratio-' . $ratio : '';
        
        $html = '<div class="pp-image-wrapper ' . esc_attr($ratio_class) . '">';
        $html .= '<img';
        $html .= ' src="' . esc_url($image_src[0]) . '"';
        
        if ($image_srcset) {
            $html .= ' srcset="' . esc_attr($image_srcset) . '"';
        }
        
        if ($image_sizes_attr) {
            $html .= ' sizes="' . esc_attr($image_sizes_attr) . '"';
        }
        
        $html .= ' alt="' . esc_attr($alt) . '"';
        $html .= ' loading="lazy"';
        $html .= ' decoding="async"';
        $html .= '>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Get placeholder image
     *
     * @param string $ratio Image ratio
     * @param string $alt Alt text
     * @return string
     */
    public static function get_placeholder_image($ratio = '16x9', $alt = '') {
        $ratio_class = $ratio !== 'auto' ? 'pp-ratio-' . $ratio : '';
        
        $html = '<div class="pp-image-wrapper pp-placeholder ' . esc_attr($ratio_class) . '">';
        $html .= '<div class="pp-placeholder-content">';
        $html .= '<span class="pp-placeholder-icon">📄</span>';
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Get all available categories for admin interface
     *
     * @return array
     */
    public static function get_all_categories() {
        $categories = get_categories(array(
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC'
        ));
        
        $options = array();
        foreach ($categories as $category) {
            $options[] = array(
                'value' => $category->slug,
                'label' => $category->name . ' (' . $category->count . ')'
            );
        }
        
        return $options;
    }
    
    /**
     * Validate user capabilities
     *
     * @param string $capability Required capability
     * @return bool
     */
    public static function user_can($capability = 'manage_options') {
        return current_user_can($capability);
    }
    
    /**
     * Verify nonce
     *
     * @param string $nonce Nonce value
     * @param string $action Nonce action
     * @return bool
     */
    public static function verify_nonce($nonce, $action) {
        return wp_verify_nonce($nonce, $action);
    }
}