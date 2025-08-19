<?php
/**
 * Admin interface for Post Poster plugin
 *
 * @package PostPoster
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin class
 */
class PP_Admin {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_pp_preview_shortcode', array($this, 'ajax_preview_shortcode'));
        add_action('wp_ajax_pp_save_settings', array($this, 'ajax_save_settings'));
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Post Poster', 'post-poster'),           // Page title
            __('Post Poster', 'post-poster'),           // Menu title
            'manage_options',                            // Capability
            'post-poster',                               // Menu slug
            array($this, 'admin_page'),                  // Callback function
            'dashicons-grid-view',                       // Icon
            25                                           // Position (after Comments)
        );
        
        // Add submenu page (same as main page but cleaner URL structure)
        add_submenu_page(
            'post-poster',                               // Parent slug
            __('Shortcode Generator', 'post-poster'),    // Page title
            __('Shortcode Generator', 'post-poster'),    // Menu title
            'manage_options',                            // Capability
            'post-poster',                               // Menu slug (same as parent for main page)
            array($this, 'admin_page')                   // Callback function
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('pp_settings_group', 'pp_settings');
        register_setting('pp_settings_group', 'pp_user_settings');
    }
    
    /**
     * Admin page content
     */
    public function admin_page() {
        if (!PP_Helpers::user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'post-poster'));
        }
        
        // Get current user settings
        $user_id = get_current_user_id();
        $user_settings = get_user_meta($user_id, 'pp_last_settings', true);
        
        if (!is_array($user_settings)) {
            $user_settings = array();
        }
        
        // Default settings
        $defaults = array(
            'categories' => '',
            'columns' => '3',
            'per_page' => '9',
            'show_image' => 'true',
            'show_title' => 'true',
            'show_excerpt' => 'true',
            'excerpt_words' => '18',
            'show_date' => 'true',
            'show_author' => 'false',
            'show_categories' => 'true',
            'orderby' => 'date',
            'order' => 'DESC',
            'pagination' => 'none',
            'image_ratio' => '16x9',
            'gutter' => '16',
            'theme' => 'auto',
            'cache_minutes' => '15'
        );
        
        $settings = array_merge($defaults, $user_settings);
        
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Post Poster - Shortcode Generator', 'post-poster'); ?></h1>
            
            <div class="pp-admin-container">
                <div class="pp-admin-form">
                    <form id="pp-shortcode-form">
                        <?php wp_nonce_field('pp_admin_nonce', 'pp_nonce'); ?>
                        
                        <div class="pp-form-section">
                            <h2><?php esc_html_e('Content Selection', 'post-poster'); ?></h2>
                            
                            <div class="pp-form-row">
                                <label for="pp_categories">
                                    <?php esc_html_e('Categories', 'post-poster'); ?>
                                    <span class="pp-help" title="<?php esc_attr_e('Select one or more categories. Leave empty to show posts from all categories.', 'post-poster'); ?>">?</span>
                                </label>
                                <select id="pp_categories" name="categories" multiple>
                                    <?php
                                    $categories = PP_Helpers::get_all_categories();
                                    $selected_categories = explode(',', $settings['categories']);
                                    
                                    foreach ($categories as $category) {
                                        $selected = in_array($category['value'], $selected_categories) ? 'selected' : '';
                                        echo '<option value="' . esc_attr($category['value']) . '" ' . $selected . '>' . esc_html($category['label']) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <div class="pp-form-row">
                                <label for="pp_per_page">
                                    <?php esc_html_e('Posts per page', 'post-poster'); ?>
                                    <span class="pp-help" title="<?php esc_attr_e('Number of posts to display (1-50)', 'post-poster'); ?>">?</span>
                                </label>
                                <input type="number" id="pp_per_page" name="per_page" value="<?php echo esc_attr($settings['per_page']); ?>" min="1" max="50">
                            </div>
                            
                            <div class="pp-form-row">
                                <label for="pp_orderby">
                                    <?php esc_html_e('Order by', 'post-poster'); ?>
                                </label>
                                <select id="pp_orderby" name="orderby">
                                    <option value="date" <?php selected($settings['orderby'], 'date'); ?>><?php esc_html_e('Date', 'post-poster'); ?></option>
                                    <option value="title" <?php selected($settings['orderby'], 'title'); ?>><?php esc_html_e('Title', 'post-poster'); ?></option>
                                    <option value="modified" <?php selected($settings['orderby'], 'modified'); ?>><?php esc_html_e('Modified', 'post-poster'); ?></option>
                                    <option value="rand" <?php selected($settings['orderby'], 'rand'); ?>><?php esc_html_e('Random', 'post-poster'); ?></option>
                                    <option value="comment_count" <?php selected($settings['orderby'], 'comment_count'); ?>><?php esc_html_e('Comment Count', 'post-poster'); ?></option>
                                </select>
                            </div>
                            
                            <div class="pp-form-row">
                                <label for="pp_order">
                                    <?php esc_html_e('Sort order', 'post-poster'); ?>
                                </label>
                                <select id="pp_order" name="order">
                                    <option value="DESC" <?php selected($settings['order'], 'DESC'); ?>><?php esc_html_e('Descending', 'post-poster'); ?></option>
                                    <option value="ASC" <?php selected($settings['order'], 'ASC'); ?>><?php esc_html_e('Ascending', 'post-poster'); ?></option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="pp-form-section">
                            <h2><?php esc_html_e('Layout Options', 'post-poster'); ?></h2>
                            
                            <div class="pp-form-row">
                                <label><?php esc_html_e('Columns', 'post-poster'); ?></label>
                                <div class="pp-columns-selector">
                                    <?php for ($i = 1; $i <= 4; $i++) : ?>
                                        <label class="pp-column-option">
                                            <input type="radio" name="columns" value="<?php echo $i; ?>" <?php checked($settings['columns'], (string)$i); ?>>
                                            <span class="pp-column-visual pp-cols-<?php echo $i; ?>">
                                                <?php for ($j = 1; $j <= $i; $j++) : ?>
                                                    <div class="pp-column-block"></div>
                                                <?php endfor; ?>
                                            </span>
                                            <span class="pp-column-label"><?php echo $i; ?> <?php echo $i === 1 ? __('Column', 'post-poster') : __('Columns', 'post-poster'); ?></span>
                                        </label>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            
                            <div class="pp-form-row">
                                <label for="pp_gutter">
                                    <?php esc_html_e('Gutter size (px)', 'post-poster'); ?>
                                    <span class="pp-help" title="<?php esc_attr_e('Space between cards in pixels', 'post-poster'); ?>">?</span>
                                </label>
                                <input type="range" id="pp_gutter" name="gutter" value="<?php echo esc_attr($settings['gutter']); ?>" min="0" max="50" step="2">
                                <span class="pp-range-value"><?php echo esc_html($settings['gutter']); ?>px</span>
                            </div>
                            
                            <div class="pp-form-row">
                                <label for="pp_image_ratio">
                                    <?php esc_html_e('Image ratio', 'post-poster'); ?>
                                </label>
                                <select id="pp_image_ratio" name="image_ratio">
                                    <option value="16x9" <?php selected($settings['image_ratio'], '16x9'); ?>>16:9 (<?php esc_html_e('Widescreen', 'post-poster'); ?>)</option>
                                    <option value="4x3" <?php selected($settings['image_ratio'], '4x3'); ?>>4:3 (<?php esc_html_e('Standard', 'post-poster'); ?>)</option>
                                    <option value="1x1" <?php selected($settings['image_ratio'], '1x1'); ?>>1:1 (<?php esc_html_e('Square', 'post-poster'); ?>)</option>
                                    <option value="auto" <?php selected($settings['image_ratio'], 'auto'); ?>><?php esc_html_e('Auto', 'post-poster'); ?></option>
                                </select>
                            </div>
                            
                            <div class="pp-form-row">
                                <label for="pp_theme">
                                    <?php esc_html_e('Theme', 'post-poster'); ?>
                                    <span class="pp-help" title="<?php esc_attr_e('Choose between light and dark card themes', 'post-poster'); ?>">?</span>
                                </label>
                                <select id="pp_theme" name="theme">
                                    <option value="auto" <?php selected($settings['theme'] ?? 'auto', 'auto'); ?>><?php esc_html_e('Auto (Follow site theme)', 'post-poster'); ?></option>
                                    <option value="light" <?php selected($settings['theme'] ?? 'auto', 'light'); ?>><?php esc_html_e('Light', 'post-poster'); ?></option>
                                    <option value="dark" <?php selected($settings['theme'] ?? 'auto', 'dark'); ?>><?php esc_html_e('Dark', 'post-poster'); ?></option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="pp-form-section">
                            <h2><?php esc_html_e('Display Options', 'post-poster'); ?></h2>
                            
                            <div class="pp-form-row pp-checkbox-row">
                                <label>
                                    <input type="checkbox" name="show_image" value="true" <?php checked($settings['show_image'], 'true'); ?>>
                                    <?php esc_html_e('Show featured image', 'post-poster'); ?>
                                </label>
                            </div>
                            
                            <div class="pp-form-row pp-checkbox-row">
                                <label>
                                    <input type="checkbox" name="show_title" value="true" <?php checked($settings['show_title'], 'true'); ?>>
                                    <?php esc_html_e('Show post title', 'post-poster'); ?>
                                </label>
                            </div>
                            
                            <div class="pp-form-row pp-checkbox-row">
                                <label>
                                    <input type="checkbox" name="show_excerpt" value="true" <?php checked($settings['show_excerpt'], 'true'); ?>>
                                    <?php esc_html_e('Show excerpt', 'post-poster'); ?>
                                </label>
                            </div>
                            
                            <div class="pp-form-row" id="pp_excerpt_words_row">
                                <label for="pp_excerpt_words">
                                    <?php esc_html_e('Excerpt length (words)', 'post-poster'); ?>
                                </label>
                                <input type="number" id="pp_excerpt_words" name="excerpt_words" value="<?php echo esc_attr($settings['excerpt_words']); ?>" min="5" max="100">
                            </div>
                            
                            <div class="pp-form-row pp-checkbox-row">
                                <label>
                                    <input type="checkbox" name="show_date" value="true" <?php checked($settings['show_date'] ?? 'true', 'true'); ?>>
                                    <?php esc_html_e('Show post date', 'post-poster'); ?>
                                </label>
                            </div>
                            
                            <div class="pp-form-row pp-checkbox-row">
                                <label>
                                    <input type="checkbox" name="show_author" value="true" <?php checked($settings['show_author'] ?? 'false', 'true'); ?>>
                                    <?php esc_html_e('Show author', 'post-poster'); ?>
                                </label>
                            </div>
                            
                            <div class="pp-form-row pp-checkbox-row">
                                <label>
                                    <input type="checkbox" name="show_categories" value="true" <?php checked($settings['show_categories'] ?? 'true', 'true'); ?>>
                                    <?php esc_html_e('Show categories', 'post-poster'); ?>
                                </label>
                            </div>
                        </div>
                        
                        <div class="pp-form-section">
                            <h2><?php esc_html_e('Advanced Options', 'post-poster'); ?></h2>
                            
                            <div class="pp-form-row">
                                <label for="pp_pagination">
                                    <?php esc_html_e('Pagination', 'post-poster'); ?>
                                </label>
                                <select id="pp_pagination" name="pagination">
                                    <option value="none" <?php selected($settings['pagination'], 'none'); ?>><?php esc_html_e('None', 'post-poster'); ?></option>
                                    <option value="numeric" <?php selected($settings['pagination'], 'numeric'); ?>><?php esc_html_e('Numeric', 'post-poster'); ?></option>
                                    <option value="load_more" <?php selected($settings['pagination'], 'load_more'); ?>><?php esc_html_e('Load More Button', 'post-poster'); ?></option>
                                </select>
                            </div>
                            
                            <div class="pp-form-row">
                                <label for="pp_cache_minutes">
                                    <?php esc_html_e('Cache duration (minutes)', 'post-poster'); ?>
                                    <span class="pp-help" title="<?php esc_attr_e('Cache results for better performance. Set to 0 to disable.', 'post-poster'); ?>">?</span>
                                </label>
                                <select id="pp_cache_minutes" name="cache_minutes">
                                    <option value="0" <?php selected($settings['cache_minutes'], '0'); ?>><?php esc_html_e('No cache', 'post-poster'); ?></option>
                                    <option value="15" <?php selected($settings['cache_minutes'], '15'); ?>>15 <?php esc_html_e('minutes', 'post-poster'); ?></option>
                                    <option value="60" <?php selected($settings['cache_minutes'], '60'); ?>>1 <?php esc_html_e('hour', 'post-poster'); ?></option>
                                    <option value="240" <?php selected($settings['cache_minutes'], '240'); ?>>4 <?php esc_html_e('hours', 'post-poster'); ?></option>
                                    <option value="1440" <?php selected($settings['cache_minutes'], '1440'); ?>>24 <?php esc_html_e('hours', 'post-poster'); ?></option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="pp-form-actions">
                            <button type="button" id="pp_preview_btn" class="button button-secondary">
                                <?php esc_html_e('Preview', 'post-poster'); ?>
                            </button>
                            <button type="button" id="pp_generate_btn" class="button button-primary">
                                <?php esc_html_e('Generate Shortcode', 'post-poster'); ?>
                            </button>
                        </div>
                    </form>
                </div>
                
                <div class="pp-admin-output">
                    <div class="pp-shortcode-output">
                        <h3><?php esc_html_e('Generated Shortcode', 'post-poster'); ?></h3>
                        <div class="pp-shortcode-container">
                            <textarea id="pp_shortcode_result" readonly placeholder="<?php esc_attr_e('Click "Generate Shortcode" to create your shortcode...', 'post-poster'); ?>"></textarea>
                            <button type="button" id="pp_copy_btn" class="button button-secondary" disabled>
                                <?php esc_html_e('Copy', 'post-poster'); ?>
                            </button>
                        </div>
                    </div>
                    
                    <div id="pp_preview_container" class="pp-preview-container" style="display: none;">
                        <h3><?php esc_html_e('Preview', 'post-poster'); ?></h3>
                        <div id="pp_preview_content"></div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * AJAX handler for shortcode preview
     */
    public function ajax_preview_shortcode() {
        // Verify nonce and capabilities
        if (!PP_Helpers::verify_nonce($_POST['nonce'], 'pp_admin_nonce') || !PP_Helpers::user_can('manage_options')) {
            wp_die(__('Security check failed.', 'post-poster'));
        }
        
        // Get and sanitize form data
        $atts = array();
        $form_fields = array(
            'categories', 'columns', 'per_page', 'show_image', 'show_title', 
            'show_excerpt', 'excerpt_words', 'show_date', 'show_author', 
            'show_categories', 'orderby', 'order', 'pagination', 
            'image_ratio', 'gutter', 'theme', 'cache_minutes'
        );
        
        foreach ($form_fields as $field) {
            if (isset($_POST[$field])) {
                $atts[$field] = sanitize_text_field($_POST[$field]);
            }
        }
        
        // Generate preview
        $shortcode = new PP_Shortcode();
        $preview_html = $shortcode->get_admin_preview($atts);
        
        wp_send_json_success(array(
            'html' => $preview_html
        ));
    }
    
    /**
     * AJAX handler for saving user settings
     */
    public function ajax_save_settings() {
        // Verify nonce and capabilities
        if (!PP_Helpers::verify_nonce($_POST['nonce'], 'pp_admin_nonce') || !PP_Helpers::user_can('manage_options')) {
            wp_die(__('Security check failed.', 'post-poster'));
        }
        
        // Get and sanitize form data
        $settings = array();
        $form_fields = array(
            'categories', 'columns', 'per_page', 'show_image', 'show_title', 
            'show_excerpt', 'excerpt_words', 'show_date', 'show_author', 
            'show_categories', 'orderby', 'order', 'pagination', 
            'image_ratio', 'gutter', 'theme', 'cache_minutes'
        );
        
        foreach ($form_fields as $field) {
            if (isset($_POST[$field])) {
                $settings[$field] = sanitize_text_field($_POST[$field]);
            }
        }
        
        // Save user settings
        $user_id = get_current_user_id();
        update_user_meta($user_id, 'pp_last_settings', $settings);
        
        wp_send_json_success(array(
            'message' => __('Settings saved.', 'post-poster')
        ));
    }
}