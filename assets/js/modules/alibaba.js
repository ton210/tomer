/**
 * SSPU Alibaba Module
 * Handles Alibaba URL management and data fetching
 */
window.SSPU = window.SSPU || {};

(function($, APP) {
    'use strict';

    APP.alibaba = {
        /**
         * Initialize the module
         */
        init() {
            this.bindEvents();
            this.checkCurrentUrl();
            this.addFetchStyles();
            APP.utils.log('Alibaba module initialized');
        },

        /**
         * Bind event handlers
         */
        bindEvents() {
            const $doc = APP.cache.$doc;

            // URL management
            $doc.on('click', '#request-alibaba-url', () => this.requestUrl());
            $doc.on('click', '#complete-alibaba-url', () => this.completeUrl());
            $doc.on('click', '#release-alibaba-url', () => this.releaseUrl());
            $doc.on('click', '#fetch-alibaba-product-name', () => this.fetchProductName());
            
            // New fetch handlers
            $('#fetch-alibaba-moq').on('click', e => {
                e.preventDefault();
                this.fetchAlibabaMOQ();
            });

            $('#fetch-alibaba-description').on('click', e => {
                e.preventDefault();
                this.fetchAlibabaDescription();
            });

            // Image retrieval
            $doc.on('click', '#retrieve-alibaba-images', e => {
                e.preventDefault();
                this.retrieveImages();
            });

            // Variant scraping
            $doc.on('click', '#sspu-scrape-variants-btn', e => {
                e.preventDefault();
                this.scrapeVariants();
            });
        },

        /**
         * Check for current assigned URL on load
         */
        checkCurrentUrl() {
            APP.utils.ajax('get_current_alibaba_url')
                .done(response => {
                    if (response.success && response.data.url) {
                        this.displayAssignedUrl(response.data);
                    } else {
                        this.displayNoUrl();
                    }
                })
                .fail(() => this.displayNoUrl());
        },

        /**
         * Display when no URL is assigned
         */
        displayNoUrl() {
            $('#no-url-assigned').show();
            $('#url-assigned').hide();
        },

        /**
         * Display assigned URL
         */
        displayAssignedUrl(data) {
            $('#current-alibaba-url').val(data.url);
            $('#open-alibaba-url').attr('href', data.url);
            $('#url-assigned-time').text(new Date(data.assigned_at).toLocaleString());
            $('#url-assigned').show();
            $('#no-url-assigned').hide();
            
            // Store current URL in state
            APP.state.currentAlibabaUrl = data.url;
        },

        /**
         * Request new Alibaba URL
         */
        requestUrl() {
            const $spinner = $('#alibaba-url-spinner').addClass('is-active');
            
            APP.utils.ajax('request_alibaba_url')
                .done(response => {
                    if (response.success) {
                        this.displayAssignedUrl(response.data);
                        APP.utils.notify('New Alibaba URL assigned!', 'success');
                    } else {
                        APP.utils.notify(response.data.message || 'Failed to get URL', 'error');
                    }
                })
                .fail(() => APP.utils.notify('Request failed', 'error'))
                .always(() => $spinner.removeClass('is-active'));
        },

        /**
         * Mark current URL as complete
         */
        completeUrl() {
            if (!confirm('Mark this URL as complete? This will remove it from your assignment.')) {
                return;
            }

            const $spinner = $('#alibaba-url-spinner').addClass('is-active');
            
            APP.utils.ajax('complete_alibaba_url')
                .done(response => {
                    if (response.success) {
                        APP.utils.notify('URL marked as complete!', 'success');
                        this.displayNoUrl();
                        APP.state.currentAlibabaUrl = null;
                    } else {
                        APP.utils.notify(response.data.message || 'Failed to complete', 'error');
                    }
                })
                .always(() => $spinner.removeClass('is-active'));
        },

        /**
         * Release URL back to queue
         */
        releaseUrl() {
            if (!confirm('Release this URL back to the queue?')) {
                return;
            }

            const $spinner = $('#alibaba-url-spinner').addClass('is-active');
            
            APP.utils.ajax('release_alibaba_url')
                .done(response => {
                    if (response.success) {
                        APP.utils.notify('URL released back to queue', 'info');
                        this.displayNoUrl();
                        APP.state.currentAlibabaUrl = null;
                    }
                })
                .always(() => $spinner.removeClass('is-active'));
        },

        /**
         * Fetch product name from Alibaba
         */
        fetchProductName() {
            const alibabaUrl = $('#current-alibaba-url').val();
            
            if (!alibabaUrl) {
                APP.utils.notify('No Alibaba URL available', 'warning');
                return;
            }

            const $btn = $('#fetch-alibaba-product-name');
            const $spinner = $('<span class="spinner is-active" style="float: none;"></span>');
            
            $btn.prop('disabled', true).after($spinner);
            
            APP.utils.ajax('fetch_alibaba_product_name', { alibaba_url: alibabaUrl })
                .done(response => {
                    if (response.success && response.data.product_name) {
                        $('#product-name-input').val(response.data.product_name).trigger('input');
                        APP.utils.notify('Product name fetched successfully!', 'success');
                    } else {
                        APP.utils.notify('Could not fetch product name', 'error');
                    }
                })
                .always(() => {
                    $btn.prop('disabled', false);
                    $spinner.remove();
                });
        },

        /**
         * Fetch MOQ from current Alibaba URL
         */
        fetchAlibabaMOQ() {
            const alibabaUrl = $('#current-alibaba-url').val();
            
            if (!alibabaUrl) {
                APP.utils.notify('Please request an Alibaba URL first', 'warning');
                return;
            }
            
            const $btn = $('#fetch-alibaba-moq');
            const $spinner = $btn.find('.spinner').length ? $btn.find('.spinner') : $('<span class="spinner is-active" style="float: none;"></span>');
            
            $btn.prop('disabled', true).append($spinner);
            
            APP.utils.ajax('fetch_alibaba_moq', { alibaba_url: alibabaUrl })
                .done(response => {
                    if (response.success && response.data.moq) {
                        // Set the MOQ value in the metafields tab
                        $('input[name="product_min"]').val(response.data.moq).trigger('change');
                        
                        // Add visual feedback
                        $('input[name="product_min"]').addClass('updated-field');
                        setTimeout(() => $('input[name="product_min"]').removeClass('updated-field'), 2000);
                        
                        APP.utils.notify(`MOQ set to: ${response.data.moq} ${response.data.unit}`, 'success');
                        
                        // If we're on a different tab, switch to metafields tab
                        const $metafieldsTab = $('a[href="#metafields"]');
                        if ($metafieldsTab.length && !$metafieldsTab.parent().hasClass('ui-tabs-active')) {
                            // Flash the tab to indicate where the value was set
                            $metafieldsTab.parent().addClass('highlight-tab');
                            setTimeout(() => $metafieldsTab.parent().removeClass('highlight-tab'), 2000);
                        }
                    } else {
                        APP.utils.notify(response.data.message || 'Could not find MOQ', 'error');
                    }
                })
                .fail((xhr, status, error) => {
                    APP.utils.notify('Failed to fetch MOQ: ' + error, 'error');
                })
                .always(() => {
                    $btn.prop('disabled', false);
                    $spinner.remove();
                });
        },

        /**
         * Fetch product description/attributes from current Alibaba URL
         */
        fetchAlibabaDescription() {
            const alibabaUrl = $('#current-alibaba-url').val();
            
            if (!alibabaUrl) {
                APP.utils.notify('Please request an Alibaba URL first', 'warning');
                return;
            }
            
            const $btn = $('#fetch-alibaba-description');
            const $spinner = $btn.find('.spinner').length ? $btn.find('.spinner') : $('<span class="spinner is-active" style="float: none;"></span>');
            
            $btn.prop('disabled', true).append($spinner);
            
            APP.utils.ajax('fetch_alibaba_description', { alibaba_url: alibabaUrl })
                .done(response => {
                    if (response.success && response.data.html) {
                        // Get current editor content
                        const currentContent = APP.utils.getEditorContent('product_description');
                        
                        // Prepare the new content
                        let newContent = response.data.html;
                        
                        // If there's existing content, append the new content
                        if (currentContent.trim()) {
                            newContent = currentContent + '<br><br><h3>Product Specifications from Alibaba:</h3>' + newContent;
                        }
                        
                        // Set the content in the editor
                        APP.utils.setEditorContent('product_description', newContent);
                        
                        // Flash the description field
                        $('#wp-product_description-wrap').addClass('updated-field');
                        setTimeout(() => $('#wp-product_description-wrap').removeClass('updated-field'), 2000);
                        
                        APP.utils.notify(response.data.message || 'Product details added to description', 'success');
                        
                        // Also populate the AI input text with a summary for AI processing
                        if (response.data.attributes && Object.keys(response.data.attributes).length > 0) {
                            let aiInputText = 'Product specifications:\n';
                            for (const [key, value] of Object.entries(response.data.attributes)) {
                                aiInputText += `${key}: ${value}\n`;
                            }
                            
                            if (response.data.features && response.data.features.length > 0) {
                                aiInputText += '\nKey features:\n';
                                response.data.features.forEach(feature => {
                                    aiInputText += `- ${feature}\n`;
                                });
                            }
                            
                            $('#sspu-ai-input-text').val(aiInputText);
                        }
                    } else {
                        APP.utils.notify(response.data.message || 'Could not find product details', 'error');
                    }
                })
                .fail((xhr, status, error) => {
                    APP.utils.notify('Failed to fetch description: ' + error, 'error');
                })
                .always(() => {
                    $btn.prop('disabled', false);
                    $spinner.remove();
                });
        },

        /**
         * Retrieve images from Alibaba
         */
        retrieveImages() {
            const alibabaUrl = $('#current-alibaba-url').val() || $('#alibaba-url-input').val();
            
            if (!alibabaUrl) {
                APP.utils.notify('Please enter or request an Alibaba URL first', 'warning');
                return;
            }

            const $btn = $('#retrieve-alibaba-images');
            const $spinner = $('<span class="spinner is-active"></span>');
            const $container = $('#alibaba-images-container');
            
            $btn.prop('disabled', true).after($spinner);
            $container.empty();

            APP.utils.ajax('retrieve_alibaba_images', { alibaba_url: alibabaUrl })
                .done(response => {
                    if (response.success && response.data.images) {
                        this.displayRetrievedImages(response.data.images);
                        APP.utils.notify(`Found ${response.data.images.length} images!`, 'success');
                    } else {
                        APP.utils.notify(response.data.message || 'No images found', 'error');
                    }
                })
                .fail(() => APP.utils.notify('Failed to retrieve images', 'error'))
                .always(() => {
                    $btn.prop('disabled', false);
                    $spinner.remove();
                });
        },

        /**
         * Display retrieved images
         */
        displayRetrievedImages(images) {
            const $container = $('#alibaba-images-container');
            $container.empty();

            if (!images.length) {
                $container.html('<p>No images found.</p>');
                return;
            }

            const $grid = $('<div class="alibaba-images-grid"></div>');
            
            images.forEach((imageUrl, index) => {
                const $item = $(`
                    <div class="alibaba-image-item">
                        <img src="${imageUrl}" alt="Product image ${index + 1}">
                        <div class="image-actions">
                            <button type="button" class="button download-image" 
                                    data-url="${imageUrl}" 
                                    data-index="${index}">
                                Download
                            </button>
                            <button type="button" class="button use-as-main" 
                                    data-url="${imageUrl}">
                                Use as Main
                            </button>
                            <button type="button" class="button add-to-gallery" 
                                    data-url="${imageUrl}">
                                Add to Gallery
                            </button>
                        </div>
                        <div class="download-status"></div>
                    </div>
                `);
                
                $grid.append($item);
            });

            $container.append($grid);
            this.bindImageActions();
        },

        /**
         * Bind actions for retrieved images
         */
        bindImageActions() {
            const self = this;

            // Download image
            $('.download-image').on('click', function() {
                const $btn = $(this);
                const imageUrl = $btn.data('url');
                const index = $btn.data('index');
                
                self.downloadImage(imageUrl, index, $btn);
            });

            // Use as main image
            $('.use-as-main').on('click', function() {
                const imageUrl = $(this).data('url');
                self.downloadAndSetAsMain(imageUrl);
            });

            // Add to gallery
            $('.add-to-gallery').on('click', function() {
                const imageUrl = $(this).data('url');
                self.downloadAndAddToGallery(imageUrl);
            });
        },

        /**
         * Download an image from URL
         */
        downloadImage(imageUrl, index, $btn) {
            const $status = $btn.closest('.alibaba-image-item').find('.download-status');
            
            $btn.prop('disabled', true).text('Downloading...');
            $status.html('<span class="spinner is-active"></span> Downloading...');

            APP.utils.ajax('download_external_image', {
                image_url: imageUrl,
                filename: `alibaba-product-${index + 1}`
            })
            .done(response => {
                if (response.success) {
                    $status.html('<span style="color: green;">✓ Downloaded!</span>');
                    $btn.text('Downloaded').addClass('button-primary');
                    
                    // Store the attachment ID
                    $btn.data('attachment-id', response.data.attachment_id);
                    
                    APP.utils.notify('Image downloaded successfully!', 'success');
                }
            })
            .fail(() => {
                $status.html('<span style="color: red;">✗ Download failed</span>');
                $btn.prop('disabled', false).text('Retry');
            });
        },

        /**
         * Download and set as main image
         */
        downloadAndSetAsMain(imageUrl) {
            APP.utils.notify('Downloading and setting as main image...', 'info');

            APP.utils.ajax('download_external_image', {
                image_url: imageUrl,
                filename: 'main-product-image'
            })
            .done(response => {
                if (response.success) {
                    // Trigger the media selection with the new attachment
                    if (window.wp && window.wp.media) {
                        const attachment = wp.media.attachment(response.data.attachment_id);
                        attachment.fetch().done(() => {
                            $('#sspu-main-image-preview').empty().append(
                                $('<img>', {
                                    src: attachment.get('url'),
                                    alt: 'Main product image'
                                })
                            );
                            $('#sspu-main-image-id').val(attachment.id);
                            APP.utils.notify('Main image set!', 'success');
                        });
                    }
                }
            });
        },

        /**
         * Download and add to gallery
         */
        downloadAndAddToGallery(imageUrl) {
            APP.utils.notify('Downloading and adding to gallery...', 'info');

            APP.utils.ajax('download_external_image', {
                image_url: imageUrl,
                filename: 'gallery-image-' + Date.now()
            })
            .done(response => {
                if (response.success) {
                    // Add to additional images
                    const currentIds = $('#sspu-additional-image-ids').val();
                    const newIds = currentIds ? currentIds + ',' + response.data.attachment_id : response.data.attachment_id;
                    
                    $('#sspu-additional-image-ids').val(newIds);
                    
                    // Add preview
                    $('#sspu-additional-images-preview').append(
                        $('<div class="image-preview-item">').append(
                            $('<img>', {
                                src: response.data.thumb_url,
                                'data-id': response.data.attachment_id
                            }),
                            $('<button type="button" class="remove-image">&times;</button>')
                        )
                    );
                    
                    APP.utils.notify('Added to gallery!', 'success');
                }
            });
        },

        /**
         * Scrape variants from Alibaba URL
         */
        scrapeVariants() {
            const alibabaUrl = $('#current-alibaba-url').val() || $('#alibaba-url-input').val();
            
            if (!alibabaUrl) {
                APP.utils.notify('Please enter or request an Alibaba URL first', 'warning');
                return;
            }

            const $btn = $('#sspu-scrape-variants-btn');
            const $spinner = $('<span class="spinner is-active"></span>');
            
            $btn.prop('disabled', true).after($spinner);

            APP.utils.ajax('scrape_alibaba_variants', { url: alibabaUrl })
                .done(response => {
                    if (response.success && response.data.variants) {
                        this.importScrapedVariants(response.data.variants);
                        APP.utils.notify(`Imported ${response.data.variants.length} variants!`, 'success');
                    } else {
                        APP.utils.notify(response.data.message || 'No variants found', 'error');
                    }
                })
                .always(() => {
                    $btn.prop('disabled', false);
                    $spinner.remove();
                });
        },

        /**
         * Import scraped variants
         */
        importScrapedVariants(variants) {
            // Clear existing variants if confirmed
            if ($('.sspu-variant-row').length > 0) {
                if (confirm('This will replace existing variants. Continue?')) {
                    $('#sspu-variants-wrapper').empty();
                    APP.state.variantCounter = 0;
                } else {
                    return;
                }
            }

            // Import each variant
            variants.forEach((variant, index) => {
                // Add variant
                if (APP.variants && APP.variants.add) {
                    APP.variants.add({
                        name: variant.name,
                        value: variant.value,
                        price: ''
                    });

                    // Download and set variant image
                    if (variant.image_url) {
                        this.downloadVariantImage(variant.image_url, index);
                    }
                }
            });
        },

        /**
         * Download image for variant
         */
        downloadVariantImage(imageUrl, variantIndex) {
            APP.utils.ajax('download_external_image', {
                image_url: imageUrl,
                filename: `variant-${variantIndex + 1}`
            })
            .done(response => {
                if (response.success) {
                    const $variantRow = $(`.sspu-variant-row:eq(${variantIndex})`);
                    
                    if ($variantRow.length && APP.variants) {
                        APP.variants.setVariantImage($variantRow, {
                            id: response.data.attachment_id,
                            url: response.data.url,
                            sizes: {
                                thumbnail: {
                                    url: response.data.thumb_url
                                }
                            }
                        });
                    }
                }
            });
        },

        /**
         * Add CSS for visual feedback
         */
        addFetchStyles() {
            if ($('#sspu-fetch-styles').length === 0) {
                $('head').append(`
                    <style id="sspu-fetch-styles">
                        .updated-field {
                            background-color: #d4edda !important;
                            border-color: #28a745 !important;
                            transition: all 0.3s ease;
                        }
                        
                        .highlight-tab {
                            background-color: #fff3cd !important;
                            border-color: #ffc107 !important;
                            animation: pulse 0.5s ease-in-out 3;
                        }
                        
                        @keyframes pulse {
                            0% { transform: scale(1); }
                            50% { transform: scale(1.05); }
                            100% { transform: scale(1); }
                        }
                        
                        #fetch-alibaba-moq .spinner,
                        #fetch-alibaba-description .spinner {
                            margin-left: 5px;
                            vertical-align: middle;
                        }
                        
                        .alibaba-images-grid {
                            display: grid;
                            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                            gap: 15px;
                            margin-top: 20px;
                        }
                        
                        .alibaba-image-item {
                            border: 1px solid #ddd;
                            padding: 10px;
                            text-align: center;
                            background: #f9f9f9;
                        }
                        
                        .alibaba-image-item img {
                            max-width: 100%;
                            height: auto;
                            margin-bottom: 10px;
                        }
                        
                        .image-actions {
                            display: flex;
                            flex-direction: column;
                            gap: 5px;
                        }
                        
                        .image-actions .button {
                            width: 100%;
                            margin: 0;
                        }
                        
                        .download-status {
                            margin-top: 5px;
                            font-size: 12px;
                        }
                        
                        .alibaba-features-info {
                            background: #e8f4fd;
                            border-left: 4px solid #0073aa;
                        }
                    </style>
                `);
            }
        }
    };

})(jQuery, window.SSPU);