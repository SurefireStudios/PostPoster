/**
 * Post Poster Admin JavaScript
 * 
 * @package PostPoster
 */

(function($) {
    'use strict';
    
    // Initialize when document is ready
    $(document).ready(function() {
        PPAdmin.init();
    });
    
    /**
     * Main admin object
     */
    window.PPAdmin = {
        
        /**
         * Initialize admin functionality
         */
        init: function() {
            this.bindEvents();
            this.updateExcerptWordsVisibility();
            this.updateRangeValues();
        },
        
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Form change handlers
            $('#pp-shortcode-form').on('change input', this.handleFormChange.bind(this));
            
            // Button handlers
            $('#pp_generate_btn').on('click', this.generateShortcode.bind(this));
            $('#pp_preview_btn').on('click', this.previewShortcode.bind(this));
            $('#pp_copy_btn').on('click', this.copyShortcode.bind(this));
            
            // Range input handlers
            $('input[type="range"]').on('input', this.updateRangeValues.bind(this));
            
            // Show/hide excerpt words field based on show_excerpt checkbox
            $('input[name="show_excerpt"]').on('change', this.updateExcerptWordsVisibility.bind(this));
        },
        
        /**
         * Handle form changes
         */
        handleFormChange: function() {
            // Save form state
            this.saveFormState();
            
            // Clear previous shortcode
            $('#pp_shortcode_result').val('');
            $('#pp_copy_btn').prop('disabled', true);
            
            // Hide preview if visible
            $('#pp_preview_container').hide();
        },
        
        /**
         * Update range input values display
         */
        updateRangeValues: function() {
            $('input[type="range"]').each(function() {
                var $input = $(this);
                var $valueDisplay = $input.siblings('.pp-range-value');
                if ($valueDisplay.length) {
                    $valueDisplay.text($input.val() + 'px');
                }
            });
        },
        
        /**
         * Update excerpt words field visibility
         */
        updateExcerptWordsVisibility: function() {
            var showExcerpt = $('input[name="show_excerpt"]').is(':checked');
            $('#pp_excerpt_words_row').toggle(showExcerpt);
        },
        
        /**
         * Generate shortcode
         */
        generateShortcode: function(e) {
            e.preventDefault();
            
            var formData = this.getFormData();
            var shortcode = this.buildShortcode(formData);
            
            $('#pp_shortcode_result').val(shortcode);
            $('#pp_copy_btn').prop('disabled', false);
            
            // Save user settings
            this.saveUserSettings(formData);
            
            this.showMessage('Shortcode generated successfully!', 'success', '.pp-shortcode-output');
        },
        
        /**
         * Preview shortcode
         */
        previewShortcode: function(e) {
            e.preventDefault();
            
            var $button = $(e.target);
            var $container = $('#pp_preview_container');
            var $content = $('#pp_preview_content');
            
            // Show loading state
            $button.prop('disabled', true).text('Loading...');
            $content.addClass('pp-loading');
            
            var formData = this.getFormData();
            
            // AJAX request for preview
            $.ajax({
                url: ppAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'pp_preview_shortcode',
                    nonce: ppAdmin.nonce,
                    ...formData
                },
                success: function(response) {
                    if (response.success) {
                        $content.html(response.data.html);
                        $container.show();
                        
                        // Scroll to preview
                        $('html, body').animate({
                            scrollTop: $container.offset().top - 100
                        }, 500);
                    } else {
                        PPAdmin.showMessage('Preview failed: ' + response.data, 'error', '.pp-preview-container');
                    }
                },
                error: function() {
                    PPAdmin.showMessage('Preview request failed. Please try again.', 'error', '.pp-preview-container');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Preview');
                    $content.removeClass('pp-loading');
                }
            });
        },
        
        /**
         * Copy shortcode to clipboard
         */
        copyShortcode: function(e) {
            e.preventDefault();
            
            var $textarea = $('#pp_shortcode_result');
            var shortcode = $textarea.val();
            
            if (!shortcode) {
                this.showMessage('No shortcode to copy. Please generate one first.', 'error');
                return;
            }
            
            // Try to use the modern Clipboard API
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(shortcode).then(function() {
                    PPAdmin.showMessage(ppAdmin.strings.copied, 'success', '.pp-shortcode-output');
                }).catch(function() {
                    PPAdmin.fallbackCopy($textarea[0]);
                });
            } else {
                this.fallbackCopy($textarea[0]);
            }
        },
        
        /**
         * Fallback copy method for older browsers
         */
        fallbackCopy: function(textarea) {
            try {
                textarea.select();
                textarea.setSelectionRange(0, 99999); // For mobile devices
                document.execCommand('copy');
                this.showMessage(ppAdmin.strings.copied, 'success', '.pp-shortcode-output');
            } catch (err) {
                this.showMessage(ppAdmin.strings.error, 'error', '.pp-shortcode-output');
            }
        },
        
        /**
         * Get form data
         */
        getFormData: function() {
            var formData = {};
            
            // Handle each field type explicitly
            $('#pp-shortcode-form').find('input, select, textarea').each(function() {
                var $field = $(this);
                var name = $field.attr('name');
                
                if (!name) return;
                
                var value;
                
                if ($field.is(':checkbox')) {
                    value = $field.is(':checked') ? 'true' : 'false';
                    formData[name] = value;
                } else if ($field.is(':radio')) {
                    // Skip radio buttons here - handle them separately
                    return;
                } else if ($field.is('select[multiple]')) {
                    value = $field.val() ? $field.val().join(',') : '';
                    formData[name] = value;
                } else {
                    value = $field.val();
                    formData[name] = value;
                }
            });
            
            // Handle radio button groups explicitly
            $('input[type="radio"]:checked').each(function() {
                var $radio = $(this);
                var name = $radio.attr('name');
                var value = $radio.val();
                formData[name] = value;
                console.log('Radio button found:', name, '=', value);
            });
            
            // Debug: Log the final form data
            console.log('Final form data:', formData);
            
            return formData;
        },
        
        /**
         * Build shortcode string
         */
        buildShortcode: function(data) {
            console.log('Building shortcode with data:', data);
            
            var shortcode = '[pp_posts';
            var defaults = {
                categories: '',
                columns: '3',
                per_page: '9',
                show_image: 'true',
                show_title: 'true',
                show_excerpt: 'true',
                excerpt_words: '18',
                show_date: 'true',
                show_author: 'false',
                show_categories: 'true',
                orderby: 'date',
                order: 'DESC',
                pagination: 'none',
                image_ratio: '16x9',
                gutter: '16',
                theme: 'auto',
                cache_minutes: '15'
            };
            
            console.log('Using defaults:', defaults);
            
            for (var key in data) {
                if (data.hasOwnProperty(key) && data[key] !== '' && data[key] !== defaults[key]) {
                    console.log('Adding to shortcode:', key, '=', data[key], '(default was:', defaults[key] + ')');
                    shortcode += ' ' + key + '="' + this.escapeAttribute(data[key]) + '"';
                }
            }
            
            shortcode += ']';
            console.log('Final shortcode:', shortcode);
            return shortcode;
        },
        
        /**
         * Escape attribute value
         */
        escapeAttribute: function(value) {
            return String(value).replace(/"/g, '&quot;');
        },
        
        /**
         * Save form state to localStorage
         */
        saveFormState: function() {
            var formData = this.getFormData();
            try {
                localStorage.setItem('pp_form_state', JSON.stringify(formData));
            } catch (e) {
                // localStorage not available
            }
        },
        
        /**
         * Save user settings via AJAX
         */
        saveUserSettings: function(formData) {
            $.ajax({
                url: ppAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'pp_save_settings',
                    nonce: ppAdmin.nonce,
                    ...formData
                },
                success: function(response) {
                    // Settings saved silently
                },
                error: function() {
                    // Fail silently for user settings
                }
            });
        },
        
        /**
         * Show admin message
         */
        showMessage: function(message, type, container) {
            type = type || 'success';
            container = container || '.pp-shortcode-output';
            
            // Check if container exists, fallback to admin container
            var $container = $(container);
            if (!$container.length) {
                $container = $('.pp-admin-container');
                container = '.pp-admin-container';
            }
            
            // Remove existing messages in the container
            $(container + ' .pp-message').remove();
            
            // Create new message
            var $message = $('<div class="pp-message pp-message--' + type + '">' + message + '</div>');
            
            // Insert in the specified container
            $container.append($message);
            
            // Auto-hide after 3 seconds
            setTimeout(function() {
                $message.fadeOut(function() {
                    $message.remove();
                });
            }, 3000);
        }
    };
    
})(jQuery);