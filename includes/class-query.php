<?php
/**
 * Query handling for Post Poster plugin
 *
 * @package PostPoster
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Query class
 */
class PP_Query {
    
    /**
     * Get posts based on shortcode attributes
     *
     * @param array $atts Shortcode attributes
     * @param int $paged Current page number
     * @return array Query results with posts and pagination data
     */
    public static function get_posts($atts, $paged = 1) {
        // Check cache first
        if ($atts['cache_minutes'] > 0) {
            $cache_key = PP_Helpers::get_cache_key($atts, $paged);
            $cached_result = PP_Helpers::get_cached_query($cache_key);
            
            if ($cached_result !== false) {
                return $cached_result;
            }
        }
        
        // Build query arguments
        $query_args = self::build_query_args($atts, $paged);
        
        // Execute query
        $query = new WP_Query($query_args);
        
        // Prepare result
        $result = array(
            'posts' => $query->posts,
            'found_posts' => $query->found_posts,
            'max_num_pages' => $query->max_num_pages,
            'current_page' => $paged,
            'per_page' => $atts['per_page']
        );
        
        // Cache result if caching is enabled
        if ($atts['cache_minutes'] > 0) {
            PP_Helpers::cache_query($cache_key, $result, $atts['cache_minutes']);
        }
        
        // Clean up
        wp_reset_postdata();
        
        return $result;
    }
    
    /**
     * Build WP_Query arguments
     *
     * @param array $atts Shortcode attributes
     * @param int $paged Current page number
     * @return array
     */
    private static function build_query_args($atts, $paged) {
        $args = array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => $atts['per_page'],
            'paged' => $paged,
            'orderby' => $atts['orderby'],
            'order' => $atts['order'],
            'no_found_rows' => false, // Always get found_rows for pagination
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'ignore_sticky_posts' => true
        );
        
        // Add category filter if specified
        if (!empty($atts['categories'])) {
            $categories = PP_Helpers::get_categories_from_string($atts['categories']);
            
            if (!empty($categories)) {
                $category_ids = array();
                foreach ($categories as $category) {
                    $category_ids[] = $category->term_id;
                }
                
                $args['category__in'] = $category_ids;
            }
        }
        
        // Handle different orderby options
        switch ($atts['orderby']) {
            case 'title':
                $args['orderby'] = 'title';
                break;
            case 'modified':
                $args['orderby'] = 'modified';
                break;
            case 'rand':
                $args['orderby'] = 'rand';
                break;
            case 'comment_count':
                $args['orderby'] = 'comment_count';
                break;
            case 'menu_order':
                $args['orderby'] = 'menu_order';
                break;
            default:
                $args['orderby'] = 'date';
        }
        
        // Apply filters for extensibility
        $args = apply_filters('pp_query_args', $args, $atts);
        
        return $args;
    }
    
    /**
     * Get pagination HTML
     *
     * @param array $query_result Query result from get_posts()
     * @param array $atts Shortcode attributes
     * @return string
     */
    public static function get_pagination($query_result, $atts) {
        if ($atts['pagination'] === 'none' || $query_result['max_num_pages'] <= 1) {
            return '';
        }
        
        // Handle load more button
        if ($atts['pagination'] === 'load_more') {
            return self::get_load_more_button($query_result, $atts);
        }
        
        // Handle numeric pagination
        $current_page = max(1, get_query_var('paged', 1));
        
        $pagination_args = array(
            'base' => get_pagenum_link(1) . '%_%',
            'format' => 'page/%#%/',
            'current' => $current_page,
            'total' => $query_result['max_num_pages'],
            'prev_text' => __('&laquo; Previous', 'post-poster'),
            'next_text' => __('Next &raquo;', 'post-poster'),
            'type' => 'list',
            'end_size' => 1,
            'mid_size' => 2
        );
        
        // Handle pretty permalinks
        if (!get_option('permalink_structure')) {
            $pagination_args['base'] = add_query_arg('paged', '%#%');
            $pagination_args['format'] = '';
        }
        
        $pagination_links = paginate_links($pagination_args);
        
        if (!$pagination_links) {
            return '';
        }
        
        $html = '<nav class="pp-pagination" role="navigation" aria-label="' . esc_attr__('Posts navigation', 'post-poster') . '">';
        $html .= $pagination_links;
        $html .= '</nav>';
        
        return $html;
    }
    
    /**
     * Get load more button HTML
     *
     * @param array $query_result Query result from get_posts()
     * @param array $atts Shortcode attributes
     * @return string
     */
    public static function get_load_more_button($query_result, $atts) {
        $current_page = $query_result['current_page'];
        $max_pages = $query_result['max_num_pages'];
        
        if ($current_page >= $max_pages) {
            return '';
        }
        
        // The grid ID should be passed from the shortcode
        // For now, we'll target the closest grid
        
        // Encode attributes for JavaScript
        $js_atts = array(
            'categories' => $atts['categories'],
            'columns' => $atts['columns'],
            'per_page' => $atts['per_page'],
            'show_image' => $atts['show_image'] ? 'true' : 'false',
            'show_title' => $atts['show_title'] ? 'true' : 'false',
            'show_excerpt' => $atts['show_excerpt'] ? 'true' : 'false',
            'excerpt_words' => $atts['excerpt_words'],
            'show_date' => $atts['show_date'] ? 'true' : 'false',
            'show_author' => $atts['show_author'] ? 'true' : 'false',
            'show_categories' => $atts['show_categories'] ? 'true' : 'false',
            'orderby' => $atts['orderby'],
            'order' => $atts['order'],
            'image_ratio' => $atts['image_ratio'],
            'gutter' => $atts['gutter'],
            'theme' => $atts['theme'],
            'cache_minutes' => $atts['cache_minutes']
        );
        
        $html = '<div class="pp-load-more-container">';
        $html .= '<button type="button" class="pp-load-more-btn" ';
        $html .= 'data-page="' . esc_attr($current_page + 1) . '" ';
        $html .= 'data-max-pages="' . esc_attr($max_pages) . '" ';
        $html .= 'data-atts="' . esc_attr(wp_json_encode($js_atts)) . '">';
        $html .= '<span class="pp-load-more-text">' . __('Load More Posts', 'post-poster') . '</span>';
        $html .= '<span class="pp-load-more-loading" style="display: none;">' . __('Loading...', 'post-poster') . '</span>';
        $html .= '</button>';
        $html .= '</div>';
        
        return $html;
    }
    

    
    /**
     * Get related posts by category
     *
     * @param int $post_id Current post ID
     * @param int $limit Number of posts to return
     * @return array
     */
    public static function get_related_posts($post_id, $limit = 3) {
        $categories = get_the_category($post_id);
        
        if (empty($categories)) {
            return array();
        }
        
        $category_ids = array();
        foreach ($categories as $category) {
            $category_ids[] = $category->term_id;
        }
        
        $args = array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'post__not_in' => array($post_id),
            'category__in' => $category_ids,
            'orderby' => 'rand',
            'no_found_rows' => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false
        );
        
        $query = new WP_Query($args);
        $posts = $query->posts;
        
        wp_reset_postdata();
        
        return $posts;
    }
    
    /**
     * Get popular posts
     *
     * @param int $limit Number of posts to return
     * @param int $days Number of days to look back
     * @return array
     */
    public static function get_popular_posts($limit = 5, $days = 30) {
        $args = array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'orderby' => 'comment_count',
            'order' => 'DESC',
            'date_query' => array(
                array(
                    'after' => $days . ' days ago'
                )
            ),
            'no_found_rows' => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false
        );
        
        $query = new WP_Query($args);
        $posts = $query->posts;
        
        wp_reset_postdata();
        
        return $posts;
    }
    
    /**
     * Search posts
     *
     * @param string $search_term Search term
     * @param array $atts Additional attributes
     * @return array
     */
    public static function search_posts($search_term, $atts = array()) {
        $default_atts = array(
            'per_page' => 10,
            'orderby' => 'relevance',
            'order' => 'DESC'
        );
        
        $atts = array_merge($default_atts, $atts);
        
        $args = array(
            'post_type' => 'post',
            'post_status' => 'publish',
            's' => sanitize_text_field($search_term),
            'posts_per_page' => $atts['per_page'],
            'orderby' => $atts['orderby'] === 'relevance' ? 'relevance' : $atts['orderby'],
            'order' => $atts['order'],
            'no_found_rows' => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false
        );
        
        // Add category filter if specified
        if (!empty($atts['categories'])) {
            $categories = PP_Helpers::get_categories_from_string($atts['categories']);
            
            if (!empty($categories)) {
                $category_ids = array();
                foreach ($categories as $category) {
                    $category_ids[] = $category->term_id;
                }
                
                $args['category__in'] = $category_ids;
            }
        }
        
        $query = new WP_Query($args);
        $posts = $query->posts;
        
        wp_reset_postdata();
        
        return $posts;
    }
}