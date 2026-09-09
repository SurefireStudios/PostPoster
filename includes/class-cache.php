<?php
/**
 * Cache invalidation for Post Poster plugin
 *
 * Grids are cached in transients, so without this the front end can keep serving a stale
 * grid after a post is published, edited, trashed or deleted. This clears the cached
 * queries whenever the underlying content changes.
 *
 * @package PostPoster
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Cache invalidation class
 */
class PP_Cache {

    /**
     * Option key for the plugin settings array.
     */
    const SETTINGS_OPTION = 'pp_settings';

    /**
     * Whether the cache has already been cleared during this request.
     *
     * Bulk edits and imports fire save_post once per post; without this a 200-post import
     * would run the clear routine 200 times.
     *
     * @var bool
     */
    private static $cleared_this_request = false;

    /**
     * Constructor
     */
    public function __construct() {
        add_action('save_post', array($this, 'on_save_post'), 10, 2);
        add_action('deleted_post', array($this, 'on_post_removed'), 10, 2);
        add_action('trashed_post', array($this, 'on_post_removed'));
        add_action('untrashed_post', array($this, 'on_post_removed'));
    }

    /**
     * Is automatic cache clearing enabled?
     *
     * Defaults to enabled, including for installs that predate the setting.
     *
     * @return bool
     */
    public static function is_enabled() {
        $settings = get_option(self::SETTINGS_OPTION, array());

        if (!is_array($settings) || !array_key_exists('auto_clear_cache', $settings)) {
            return true;
        }

        return (bool) $settings['auto_clear_cache'];
    }

    /**
     * Post types whose changes should invalidate cached grids.
     *
     * Grids currently query the 'post' post type. Filter this if you extend the query.
     *
     * @return array
     */
    public static function get_watched_post_types() {
        return (array) apply_filters('pp_cache_invalidation_post_types', array('post'));
    }

    /**
     * Handle a post being created or updated.
     *
     * @param int     $post_id Post ID
     * @param WP_Post $post    Post object
     */
    public function on_save_post($post_id, $post = null) {
        // Autosaves and revisions are not what the front end renders.
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
            return;
        }

        $post_type = $post instanceof WP_Post ? $post->post_type : get_post_type($post_id);

        if (!in_array($post_type, self::get_watched_post_types(), true)) {
            return;
        }

        self::maybe_clear();
    }

    /**
     * Handle a post being trashed, untrashed or deleted.
     *
     * @param int          $post_id Post ID
     * @param WP_Post|null $post    Post object, when the hook provides one
     */
    public function on_post_removed($post_id, $post = null) {
        $post_type = $post instanceof WP_Post ? $post->post_type : get_post_type($post_id);

        // get_post_type() returns false once a post is fully deleted; clear anyway rather
        // than risk leaving a deleted post visible in a cached grid.
        if ($post_type && !in_array($post_type, self::get_watched_post_types(), true)) {
            return;
        }

        self::maybe_clear();
    }

    /**
     * Clear the cache once per request, if the setting allows it.
     *
     * @return bool Whether the cache was cleared
     */
    public static function maybe_clear() {
        if (self::$cleared_this_request || !self::is_enabled()) {
            return false;
        }

        self::$cleared_this_request = true;

        PP_Helpers::clear_all_cache();

        /**
         * Fires after cached grids are cleared because content changed.
         */
        do_action('pp_cache_cleared');

        return true;
    }

    /**
     * Clear the cache unconditionally, ignoring the setting.
     *
     * Used by the "Clear cache now" button on the admin screen.
     *
     * @return int Number of cached queries removed
     */
    public static function force_clear() {
        self::$cleared_this_request = true;

        $cleared = PP_Helpers::clear_all_cache();

        do_action('pp_cache_cleared');

        return $cleared;
    }
}
