/**
 * Post Poster Gutenberg Block
 * 
 * @package PostPoster
 */

(function() {
    'use strict';
    
    const { registerBlockType } = wp.blocks;
    const { InspectorControls } = wp.blockEditor;
    const { PanelBody, SelectControl, RangeControl, ToggleControl, TextControl } = wp.components;
    const { __ } = wp.i18n;
    const { createElement: el, Fragment } = wp.element;
    const { withSelect } = wp.data;
    
    /**
     * Register the Post Poster block
     */
    registerBlockType('post-poster/posts-grid', {
        title: ppBlock.strings.title,
        description: ppBlock.strings.description,
        icon: 'grid-view',
        category: 'widgets',
        keywords: ['posts', 'grid', 'layout', 'blog'],
        
        attributes: {
            categories: {
                type: 'string',
                default: ''
            },
            columns: {
                type: 'number',
                default: 3
            },
            perPage: {
                type: 'number',
                default: 9
            },
            showImage: {
                type: 'boolean',
                default: true
            },
            showTitle: {
                type: 'boolean',
                default: true
            },
            showExcerpt: {
                type: 'boolean',
                default: true
            },
            excerptWords: {
                type: 'number',
                default: 18
            },
            orderBy: {
                type: 'string',
                default: 'date'
            },
            order: {
                type: 'string',
                default: 'DESC'
            },
            pagination: {
                type: 'string',
                default: 'none'
            },
            imageRatio: {
                type: 'string',
                default: '16x9'
            },
            gutter: {
                type: 'number',
                default: 16
            },
            className: {
                type: 'string',
                default: ''
            },
            cacheMinutes: {
                type: 'number',
                default: 15
            }
        },
        
        /**
         * Edit function - renders the block in the editor
         */
        edit: function(props) {
            const { attributes, setAttributes } = props;
            
            // Multi-select for categories
            const categoryOptions = ppBlock.categories.map(cat => ({
                value: cat.value,
                label: cat.label
            }));
            
            // Convert categories string to array for multi-select
            const selectedCategories = attributes.categories ? attributes.categories.split(',') : [];
            
            return el(Fragment, {},
                // Inspector Controls (sidebar)
                el(InspectorControls, {},
                    
                    // Content Selection Panel
                    el(PanelBody, {
                        title: __('Content Selection', 'post-poster'),
                        initialOpen: true
                    },
                        // Categories
                        el('div', { className: 'components-base-control' },
                            el('label', { className: 'components-base-control__label' }, ppBlock.strings.categories),
                            el('select', {
                                multiple: true,
                                value: selectedCategories,
                                onChange: function(e) {
                                    const selected = Array.from(e.target.selectedOptions).map(option => option.value);
                                    setAttributes({ categories: selected.join(',') });
                                },
                                style: { width: '100%', height: '100px' }
                            }, categoryOptions.map(option =>
                                el('option', {
                                    key: option.value,
                                    value: option.value
                                }, option.label)
                            ))
                        ),
                        
                        // Posts per page
                        el(RangeControl, {
                            label: ppBlock.strings.perPage,
                            value: attributes.perPage,
                            onChange: function(value) { setAttributes({ perPage: value }); },
                            min: 1,
                            max: 50
                        }),
                        
                        // Order by
                        el(SelectControl, {
                            label: ppBlock.strings.orderBy,
                            value: attributes.orderBy,
                            options: ppBlock.options.orderBy,
                            onChange: function(value) { setAttributes({ orderBy: value }); }
                        }),
                        
                        // Sort order
                        el(SelectControl, {
                            label: ppBlock.strings.order,
                            value: attributes.order,
                            options: ppBlock.options.order,
                            onChange: function(value) { setAttributes({ order: value }); }
                        })
                    ),
                    
                    // Layout Panel
                    el(PanelBody, {
                        title: __('Layout Options', 'post-poster'),
                        initialOpen: true
                    },
                        // Columns
                        el(RangeControl, {
                            label: ppBlock.strings.columns,
                            value: attributes.columns,
                            onChange: function(value) { setAttributes({ columns: value }); },
                            min: 1,
                            max: 4
                        }),
                        
                        // Gutter
                        el(RangeControl, {
                            label: ppBlock.strings.gutter,
                            value: attributes.gutter,
                            onChange: function(value) { setAttributes({ gutter: value }); },
                            min: 0,
                            max: 50
                        }),
                        
                        // Image ratio
                        el(SelectControl, {
                            label: ppBlock.strings.imageRatio,
                            value: attributes.imageRatio,
                            options: ppBlock.options.imageRatio,
                            onChange: function(value) { setAttributes({ imageRatio: value }); }
                        })
                    ),
                    
                    // Display Options Panel
                    el(PanelBody, {
                        title: __('Display Options', 'post-poster'),
                        initialOpen: false
                    },
                        // Show image
                        el(ToggleControl, {
                            label: ppBlock.strings.showImage,
                            checked: attributes.showImage,
                            onChange: function(value) { setAttributes({ showImage: value }); }
                        }),
                        
                        // Show title
                        el(ToggleControl, {
                            label: ppBlock.strings.showTitle,
                            checked: attributes.showTitle,
                            onChange: function(value) { setAttributes({ showTitle: value }); }
                        }),
                        
                        // Show excerpt
                        el(ToggleControl, {
                            label: ppBlock.strings.showExcerpt,
                            checked: attributes.showExcerpt,
                            onChange: function(value) { setAttributes({ showExcerpt: value }); }
                        }),
                        
                        // Excerpt words (only if show excerpt is enabled)
                        attributes.showExcerpt && el(RangeControl, {
                            label: ppBlock.strings.excerptWords,
                            value: attributes.excerptWords,
                            onChange: function(value) { setAttributes({ excerptWords: value }); },
                            min: 5,
                            max: 100
                        })
                    ),
                    
                    // Advanced Options Panel
                    el(PanelBody, {
                        title: __('Advanced Options', 'post-poster'),
                        initialOpen: false
                    },
                        // Pagination
                        el(SelectControl, {
                            label: ppBlock.strings.pagination,
                            value: attributes.pagination,
                            options: ppBlock.options.pagination,
                            onChange: function(value) { setAttributes({ pagination: value }); }
                        }),
                        
                        // Cache duration
                        el(SelectControl, {
                            label: ppBlock.strings.cacheMinutes,
                            value: attributes.cacheMinutes,
                            options: ppBlock.options.cacheMinutes,
                            onChange: function(value) { setAttributes({ cacheMinutes: parseInt(value) }); }
                        })
                    )
                ),
                
                // Block preview in editor
                el(PostGridPreview, { attributes: attributes })
            );
        },
        
        /**
         * Save function - returns null because we use dynamic rendering
         */
        save: function() {
            return null; // Dynamic block, rendered server-side
        }
    });
    
    /**
     * Preview component for the editor
     */
    const PostGridPreview = withSelect(function(select, props) {
        const { getEntityRecords } = select('core');
        const { attributes } = props;
        
        // Build query parameters
        const queryParams = {
            per_page: Math.min(6, attributes.perPage), // Limit for preview
            orderby: attributes.orderBy,
            order: attributes.order.toLowerCase()
        };
        
        // Add category filter if specified
        if (attributes.categories) {
            const categoryTerms = select('core').getEntityRecords('taxonomy', 'category', {
                slug: attributes.categories.split(','),
                per_page: -1
            });
            
            if (categoryTerms) {
                queryParams.categories = categoryTerms.map(term => term.id);
            }
        }
        
        return {
            posts: getEntityRecords('postType', 'post', queryParams)
        };
    })(function(props) {
        const { attributes, posts } = props;
        
        if (!posts) {
            return el('div', { className: 'wp-block-post-poster-posts-grid' },
                el('p', {}, __('Loading posts...', 'post-poster'))
            );
        }
        
        if (posts.length === 0) {
            return el('div', { className: 'wp-block-post-poster-posts-grid' },
                el('p', {}, ppBlock.strings.noPostsFound)
            );
        }
        
        // Build grid classes
        const gridClasses = [
            'pp-grid',
            'pp-cols-' + attributes.columns
        ];
        
        if (attributes.className) {
            gridClasses.push(attributes.className);
        }
        
        // Grid style
        const gridStyle = {};
        if (attributes.gutter > 0) {
            gridStyle['--pp-gutter'] = attributes.gutter + 'px';
        }
        
        return el('div', {
            className: 'wp-block-post-poster-posts-grid'
        },
            el('div', {
                className: gridClasses.join(' '),
                style: gridStyle
            }, posts.map(function(post) {
                return el(PostCard, {
                    key: post.id,
                    post: post,
                    attributes: attributes
                });
            }))
        );
    });
    
    /**
     * Individual post card component
     */
    function PostCard(props) {
        const { post, attributes } = props;
        
        // Get featured image
        const featuredImageId = post.featured_media;
        const featuredImage = featuredImageId ? wp.data.select('core').getMedia(featuredImageId) : null;
        
        // Get excerpt
        let excerpt = '';
        if (attributes.showExcerpt) {
            if (post.excerpt && post.excerpt.rendered) {
                excerpt = post.excerpt.rendered.replace(/<[^>]*>/g, ''); // Strip HTML
            } else if (post.content && post.content.rendered) {
                excerpt = post.content.rendered.replace(/<[^>]*>/g, '').substring(0, attributes.excerptWords * 6) + '...';
            }
        }
        
        return el('article', { className: 'pp-card' },
            // Featured image
            attributes.showImage && featuredImage && el('div', { className: 'pp-card-image' },
                el('img', {
                    src: featuredImage.source_url,
                    alt: featuredImage.alt_text || post.title.rendered,
                    style: { width: '100%', height: 'auto' }
                })
            ),
            
            // Content
            el('div', { className: 'pp-card-content' },
                // Title
                attributes.showTitle && el('h3', { className: 'pp-card-title' },
                    el('a', {
                        href: post.link,
                        dangerouslySetInnerHTML: { __html: post.title.rendered }
                    })
                ),
                
                // Excerpt
                attributes.showExcerpt && excerpt && el('div', { className: 'pp-card-excerpt' },
                    el('p', {}, excerpt)
                ),
                
                // Meta
                el('div', { className: 'pp-card-meta' },
                    el('time', { className: 'pp-card-date' },
                        new Date(post.date).toLocaleDateString()
                    )
                )
            )
        );
    }
    
})();