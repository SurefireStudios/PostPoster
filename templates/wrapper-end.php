<?php
/**
 * Template for grid wrapper end
 *
 * @package PostPoster
 * @var array $atts Shortcode attributes
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

?><?php
/**
 * Hook for content after grid ends
 *
 * @param array $atts Shortcode attributes
 */
do_action('pp_after_grid', $atts);
?></div>