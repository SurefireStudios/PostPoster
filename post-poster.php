<?php
/**
 * Plugin Name: Post Poster
 * Plugin URI: https://github.com/SurefireStudios/PostPoster
 * Description: A powerful WordPress plugin to create layout grids from existing blog posts with customizable shortcodes and grid options.
 * Version: 1.0.0
 * Author: Surefire Studios
 * Author URI: https://www.surefirestudios.io
 * Text Domain: post-poster
 * Domain Path: /languages
 * Requires at least: 6.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package PostPoster
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('POST_POSTER_VERSION', '1.0.0');
define('POST_POSTER_PLUGIN_FILE', __FILE__);
define('POST_POSTER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('POST_POSTER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('POST_POSTER_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main plugin class
 */
class PostPoster {
    
    /**
     * Plugin instance
     *
     * @var PostPoster
     */
    private static $instance = null;
    
    /**
     * Get plugin instance
     *
     * @return PostPoster
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init();
    }
    
    /**
     * Initialize plugin
     */
    private function init() {
        // Load text domain
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        
        // Include required files
        $this->includes();
        
        // Initialize components
        add_action('init', array($this, 'init_components'));
        
        // Enqueue assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Activation/deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Include required files
     */
    private function includes() {
        require_once POST_POSTER_PLUGIN_DIR . 'includes/class-helpers.php';
        require_once POST_POSTER_PLUGIN_DIR . 'includes/class-query.php';
        require_once POST_POSTER_PLUGIN_DIR . 'includes/class-shortcode.php';
        require_once POST_POSTER_PLUGIN_DIR . 'includes/class-admin.php';
        require_once POST_POSTER_PLUGIN_DIR . 'includes/class-block.php';
    }
    
    /**
     * Initialize components
     */
    public function init_components() {
        // Initialize admin interface
        if (is_admin()) {
            new PP_Admin();
        }
        
        // Initialize shortcode
        new PP_Shortcode();
        
        // Initialize Gutenberg block
        new PP_Block();
        
        // Initialize AJAX handlers
        add_action('wp_ajax_pp_load_more_posts', array($this, 'ajax_load_more_posts'));
        add_action('wp_ajax_nopriv_pp_load_more_posts', array($this, 'ajax_load_more_posts'));
    }
    
    /**
     * Load plugin text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'post-poster',
            false,
            dirname(POST_POSTER_PLUGIN_BASENAME) . '/languages'
        );
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        wp_enqueue_style(
            'post-poster-style',
            POST_POSTER_PLUGIN_URL . 'assets/pp.css',
            array(),
            POST_POSTER_VERSION
        );
        
        // Only enqueue JS if there might be load more buttons on the page
        if (has_shortcode(get_post()->post_content ?? '', 'pp_posts') || is_archive() || is_home()) {
            wp_enqueue_script(
                'post-poster-frontend',
                POST_POSTER_PLUGIN_URL . 'assets/frontend.js',
                array('jquery'),
                POST_POSTER_VERSION,
                true
            );
            
            wp_localize_script('post-poster-frontend', 'ppFrontend', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('pp_load_more_nonce'),
                'strings' => array(
                    'loadMore' => __('Load More Posts', 'post-poster'),
                    'loading' => __('Loading...', 'post-poster'),
                    'noMore' => __('No more posts to load', 'post-poster'),
                    'error' => __('Error loading posts', 'post-poster')
                )
            ));
        }
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Only load on our admin page
        if ('toplevel_page_post-poster' !== $hook) {
            return;
        }
        
        wp_enqueue_style(
            'post-poster-admin-style',
            POST_POSTER_PLUGIN_URL . 'assets/admin.css',
            array(),
            POST_POSTER_VERSION
        );
        
        wp_enqueue_script(
            'post-poster-admin-script',
            POST_POSTER_PLUGIN_URL . 'assets/admin.js',
            array('jquery'),
            POST_POSTER_VERSION,
            true
        );
        
        // Localize script for AJAX
        wp_localize_script('post-poster-admin-script', 'ppAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pp_admin_nonce'),
            'strings' => array(
                'copied' => __('Shortcode copied to clipboard!', 'post-poster'),
                'error' => __('Error copying shortcode', 'post-poster')
            )
        ));
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Set default options
        add_option('pp_settings', array(
            'cache_minutes' => 15,
            'default_columns' => 3,
            'default_per_page' => 9,
            'default_image_ratio' => '16x9',
            'default_gutter' => 16
        ));
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clean up transients
        PP_Helpers::clear_all_cache();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * AJAX handler for load more posts
     */
    public function ajax_load_more_posts() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'pp_load_more_nonce')) {
            wp_die(__('Security check failed.', 'post-poster'));
        }
        
        // Get and sanitize form data
        $page = intval($_POST['page'] ?? 1);
        $atts_json = sanitize_text_field($_POST['atts'] ?? '');
        
        if (empty($atts_json)) {
            wp_send_json_error(__('Invalid request.', 'post-poster'));
        }
        
        // Decode attributes
        $atts = json_decode(stripslashes($atts_json), true);
        if (!is_array($atts)) {
            wp_send_json_error(__('Invalid attributes.', 'post-poster'));
        }
        
        // Sanitize attributes
        $atts = PP_Helpers::sanitize_shortcode_atts($atts);
        
        // Get posts for the requested page
        $query_result = PP_Query::get_posts($atts, $page);
        
        if (empty($query_result['posts'])) {
            wp_send_json_error(__('No more posts found.', 'post-poster'));
        }
        
        // Render the posts
        $shortcode = new PP_Shortcode();
        $html = '';
        
        foreach ($query_result['posts'] as $current_post) {
            $html .= $shortcode->render_post_card($current_post, $atts);
        }
        
        // Check if there are more pages
        $has_more = $page < $query_result['max_num_pages'];
        
        // Debug logging
        error_log('PP Load More Debug: ' . wp_json_encode(array(
            'page' => $page,
            'max_pages' => $query_result['max_num_pages'],
            'found_posts' => $query_result['found_posts'],
            'posts_returned' => count($query_result['posts']),
            'has_more' => $has_more,
            'per_page' => $atts['per_page']
        )));
        
        wp_send_json_success(array(
            'html' => $html,
            'page' => $page,
            'has_more' => $has_more,
            'max_pages' => $query_result['max_num_pages'],
            'found_posts' => $query_result['found_posts'],
            'posts_returned' => count($query_result['posts'])
        ));
    }
}

/**
 * Initialize plugin
 */
function post_poster_init() {
    PostPoster::get_instance();
}
add_action('plugins_loaded', 'post_poster_init');

/**
 * Helper function to get plugin instance
 */
function post_poster() {
    return PostPoster::get_instance();
}