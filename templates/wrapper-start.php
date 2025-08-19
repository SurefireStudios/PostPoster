<?php
/**
 * Template for grid wrapper start
 *
 * @package PostPoster
 * @var array $classes CSS classes for the wrapper
 * @var string $style Inline styles for the wrapper
 * @var array $atts Shortcode attributes
 * @var string $grid_id Unique grid ID
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

?>
<div id="<?php echo esc_attr($grid_id); ?>" class="<?php echo esc_attr(implode(' ', $classes)); ?>"<?php echo $style; ?>><?php
/**
 * Hook for content before grid starts
 *
 * @param array $atts Shortcode attributes
 */
do_action('pp_before_grid', $atts);
?>