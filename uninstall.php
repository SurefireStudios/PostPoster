<?php
/**
 * Uninstall script for Post Poster plugin
 *
 * @package PostPoster
 */

// If uninstall not called from WordPress, then exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Clean up plugin data on uninstall
 */
function post_poster_uninstall() {
    global $wpdb;
    
    // Remove plugin options
    delete_option('pp_settings');
    
    // Remove user meta (last settings)
    $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key = 'pp_last_settings'");
    
    // Clear all cached queries
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
    
    // Remove any custom post meta added by the plugin (if any)
    // This is for future extensibility
    $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE 'pp_%'");
    
    // Clear any scheduled events (if any are added in the future)
    wp_clear_scheduled_hook('pp_cleanup_cache');
    
    // Flush rewrite rules one final time
    flush_rewrite_rules();
}

// Execute cleanup
post_poster_uninstall();