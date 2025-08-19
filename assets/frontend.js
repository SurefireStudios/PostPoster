/**
 * Post Poster Frontend JavaScript
 * 
 * @package PostPoster
 */

(function($) {
    'use strict';
    
    // Initialize when document is ready
    $(document).ready(function() {
        PPFrontend.init();
    });
    
    /**
     * Frontend functionality object
     */
    window.PPFrontend = {
        
        /**
         * Initialize frontend functionality
         */
        init: function() {
            this.bindEvents();
        },
        
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Handle load more button clicks
            $(document).on('click', '.pp-load-more-btn', this.handleLoadMore.bind(this));
        },
        
        /**
         * Handle load more button click
         */
        handleLoadMore: function(e) {
            e.preventDefault();
            
            var $button = $(e.currentTarget);
            var $container = $button.closest('.pp-load-more-container');
            var $grid = $button.closest('.pp-grid').length ? 
                        $button.closest('.pp-grid') : 
                        $button.parent().prev('.pp-grid');
            
            // Get button data
            var page = parseInt($button.data('page'));
            var maxPages = parseInt($button.data('max-pages'));
            var atts = $button.data('atts');
            
            // Validate data
            if (!page || !atts || !$grid.length) {
                console.error('Post Poster: Invalid load more data');
                return;
            }
            
            // Show loading state
            this.setLoadingState($button, true);
            
            // Make AJAX request
            $.ajax({
                url: ppFrontend.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'pp_load_more_posts',
                    nonce: ppFrontend.nonce,
                    page: page,
                    atts: JSON.stringify(atts)
                },
                success: function(response) {
                    if (response.success) {
                        PPFrontend.handleLoadMoreSuccess(response.data, $button, $grid);
                    } else {
                        PPFrontend.handleLoadMoreError(response.data || ppFrontend.strings.error, $button);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Post Poster AJAX Error:', error);
                    PPFrontend.handleLoadMoreError(ppFrontend.strings.error, $button);
                },
                complete: function() {
                    PPFrontend.setLoadingState($button, false);
                }
            });
        },
        
        /**
         * Handle successful load more response
         */
        handleLoadMoreSuccess: function(data, $button, $grid) {
            // Debug logging
            console.log('Load More Success:', {
                page: data.page,
                max_pages: data.max_pages,
                has_more: data.has_more,
                posts_loaded: $(data.html).length
            });
            
            // Append new posts to grid
            var $newPosts = $(data.html);
            
            // Add fade-in animation
            $newPosts.css('opacity', '0');
            $grid.append($newPosts);
            
            // Animate in new posts
            $newPosts.animate({ opacity: 1 }, 300);
            
            // Update button data for next page
            $button.data('page', data.page + 1);
            
            // Hide button if no more posts available
            if (!data.has_more) {
                console.log('Hiding button - no more posts available');
                this.hideLoadMoreButton($button);
            } else {
                console.log('Button remaining - more posts available');
            }
            
            // Trigger custom event
            $(document).trigger('ppPostsLoaded', {
                posts: $newPosts,
                page: data.page,
                hasMore: data.has_more
            });
        },
        
        /**
         * Handle load more error
         */
        handleLoadMoreError: function(message, $button) {
            // Show error message
            var $error = $('<div class="pp-load-more-error">' + message + '</div>');
            $button.parent().append($error);
            
            // Hide error after 5 seconds
            setTimeout(function() {
                $error.fadeOut(function() {
                    $error.remove();
                });
            }, 5000);
            
            console.error('Post Poster Load More Error:', message);
        },
        
        /**
         * Set loading state for button
         */
        setLoadingState: function($button, loading) {
            var $text = $button.find('.pp-load-more-text');
            var $loading = $button.find('.pp-load-more-loading');
            
            if (loading) {
                $button.prop('disabled', true).addClass('loading');
                $text.hide();
                $loading.show();
            } else {
                $button.prop('disabled', false).removeClass('loading');
                $text.show();
                $loading.hide();
            }
        },
        
        /**
         * Hide load more button when no more posts
         */
        hideLoadMoreButton: function($button) {
            var $text = $button.find('.pp-load-more-text');
            
            // Change button text and disable
            $text.text(ppFrontend.strings.noMore);
            $button.prop('disabled', true).addClass('no-more');
            
            // Optionally hide completely after a delay
            setTimeout(function() {
                $button.parent().fadeOut();
            }, 2000);
        }
    };
    
})(jQuery);