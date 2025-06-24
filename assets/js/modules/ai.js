/**
 * SSPU AI Module
 *
 * Manages all AI-powered features, including content generation for
 * descriptions and SEO, product name formatting, and pricing/weight suggestions.
 */
window.SSPU = window.SSPU || {};

(function($, APP) {
    'use strict';

    APP.ai = {
        /**
         * Initializes the module by binding events to AI-related buttons.
         */
        init() {
            this.bindEvents();
        },

        /**
         * Binds all event listeners for the AI features.
         */
        bindEvents() {
            // Product name formatting
            $('#format-product-name').on('click', e => {
                e.preventDefault();
                this.formatProductName();
            });

            // AI content generation from the Basic Info tab
            $('#sspu-generate-description').on('click', () => this.generateContent('description'));
            $('#sspu-generate-tags').on('click', () => this.generateContent('tags'));
            $('#sspu-suggest-price').on('click', () => this.generateContent('price'));

            // SEO content generation from the SEO tab
            $('#sspu-generate-seo-title').on('click', e => {
                e.preventDefault();
                this.generateSeo('title');
            });
            $('#sspu-generate-meta-desc').on('click', e => {
                e.preventDefault();
                this.generateSeo('meta');
            });

            // Bulk AI actions from the Variants tab
            $('#apply-price-to-all').on('click', () => this.suggestAllPricing());
            $('#apply-tiers-to-all').on('click', () => this.estimateWeight());
            $('#auto-generate-all-skus').on('click', () => this.autoGenerateAllSKUs());
            $('#ai-suggest-all-pricing').on('click', () => this.suggestAllPricing());
            $('#ai-estimate-weight').on('click', () => this.estimateWeight());

            // AI image upload
            $('#sspu-upload-ai-images').on('click', e => {
                e.preventDefault();
                this.uploadAiImages();
            });
        },

        /**
         * Handles the AI formatting of a long product name.
         */
        formatProductName() {
            if (!sspu_ajax.openai_configured) {
                APP.utils.notify(sspu_ajax.strings.no_openai_key, 'warning');
                return;
            }

            const productName = $('#product-name-input').val().trim();
            if (!productName) {
                APP.utils.notify('Please enter a product name first.', 'warning');
                return;
            }

            const $btn = $('#format-product-name').prop('disabled', true);
            $('#format-name-spinner').addClass('is-active');

            APP.utils.ajax('format_product_name', { product_name: productName })
                .done(response => {
                    if (response.success) {
                        $('#product-name-input').val(response.data.formatted_name).trigger('input');
                        APP.utils.notify('Product name formatted successfully!', 'success');
                    } else {
                        APP.utils.notify('Error: ' + (response.data.message || 'Formatting failed'), 'error');
                    }
                })
                .always(() => {
                    $btn.prop('disabled', false);
                    $('#format-name-spinner').removeClass('is-active');
                });
        },

        /**
         * Generic handler for generating AI content like descriptions, tags, or price.
         * @param {string} type - The type of content to generate ('description', 'tags', 'price').
         */
        generateContent(type) {
            if (!sspu_ajax.openai_configured) {
                APP.utils.notify(sspu_ajax.strings.no_openai_key, 'warning');
                return;
            }

            const inputText = $('#sspu-ai-input-text').val().trim();
            const imageUrls = $('input[name="ai_image_urls[]"]').map(function() { return $(this).val().trim(); }).get().filter(url => url);

            if (!inputText && APP.state.aiImageIds.length === 0 && imageUrls.length === 0) {
                APP.utils.notify('Please provide some text or upload/link images for AI analysis.', 'warning');
                return;
            }

            $('#sspu-ai-spinner').addClass('is-active');
            
            const $statusBox = $('#sspu-status-box').removeClass('success error').addClass('processing').show();
            const $statusLog = $('#sspu-status-log');
            const $statusHeading = $('#sspu-status-heading');
            
            $statusHeading.text(`Generating ${type}...`);
            $statusLog.text('Sending request to AI. This may take up to 60 seconds...');
            
            APP.utils.ajax('generate_description', { 
                input_text: inputText, 
                image_ids: APP.state.aiImageIds,
                image_urls: imageUrls,
                type: type 
            }).done(response => {
                if (response.success) {
                    $statusBox.removeClass('processing').addClass('success');
                    $statusHeading.text('Success!');
                    $statusLog.text(`AI ${type} generated successfully.`);
                    setTimeout(() => $statusBox.fadeOut(), 5000);

                    switch(type) {
                        case 'description':
                            APP.utils.setEditorContent('product_description', response.data.description);
                            APP.utils.notify('Description generated successfully!', 'success');
                            break;
                        case 'tags':
                            $('input[name="product_tags"]').val(response.data.tags).addClass('updated-field');
                            APP.utils.notify('Tags generated successfully!', 'success');
                            break;
                        case 'price':
                            const $firstVariant = $('.sspu-variant-row:first');
                            if ($firstVariant.length) {
                                $firstVariant.find('.sspu-variant-price').val(response.data.price).addClass('updated-field');
                                APP.utils.notify('Price suggested successfully!', 'success');
                            } else {
                                APP.utils.notify(`Suggested price: ${response.data.price}`, 'info');
                            }
                            break;
                    }
                    setTimeout(() => $('.updated-field').removeClass('updated-field'), 1000);
                } else {
                    $statusBox.removeClass('processing').addClass('error');
                    $statusHeading.text('AI Generation Failed');
                    $statusLog.html(response.data.message || 'An unknown error occurred.');
                }
            }).fail((xhr, textStatus) => {
                $statusBox.removeClass('processing').addClass('error');
                $statusHeading.text('Request Failed');
                let errorMsg = `A network error occurred: ${textStatus}.`;
                if (textStatus === 'timeout') {
                    errorMsg = 'The request timed out. The server is taking too long to respond. Please try again later.';
                }
                $statusLog.text(errorMsg);
            }).always(() => {
                $('#sspu-ai-spinner').removeClass('is-active');
            });
        },

        /**
         * Handles the generation of SEO title or meta description.
         * @param {string} type - 'title' or 'meta'.
         */
        generateSeo(type) {
            if (!sspu_ajax.openai_configured) {
                APP.utils.notify(sspu_ajax.strings.no_openai_key, 'warning');
                return;
            }

            const productName = $('input[name="product_name"]').val();
            const description = APP.utils.getEditorContent('product_description');

            if (!productName) {
                APP.utils.notify('Please enter a product name first.', 'warning');
                return;
            }

            const btnId = `#sspu-generate-${type === 'title' ? 'seo-title' : 'meta-desc'}`;
            const $btn = $(btnId).prop('disabled', true);

            APP.utils.ajax('generate_seo', { 
                product_name: productName, 
                description: description, 
                type: type 
            }).done(response => {
                if (response.success) {
                    const selector = type === 'title' ? 'input[name="seo_title"]' : 'textarea[name="meta_description"]';
                    $(selector).val(response.data.content).trigger('input').addClass('updated-field');
                    APP.utils.notify(`${type === 'title' ? 'SEO title' : 'Meta description'} generated successfully!`, 'success');
                    setTimeout(() => $('.updated-field').removeClass('updated-field'), 1000);
                } else {
                    APP.utils.notify('Error: ' + (response.data.message || 'SEO generation failed.'), 'error');
                }
            }).always(() => $btn.prop('disabled', false));
        },

        /**
         * Suggests pricing for all variants based on AI analysis
         */
        suggestAllPricing() {
            const productName = $('#product-name-input').val();
            const mainImageId = $('#sspu-main-image-id').val();
            const minQuantity = $('input[name="product_min"]').val() || 25;

            if (!productName || !mainImageId) {
                APP.utils.notify('Please enter a product name and select a main image first.', 'warning');
                return;
            }

            const $btn = $('#ai-suggest-all-pricing');
            const $spinner = $btn.find('.spinner');
            $btn.prop('disabled', true);
            $spinner.addClass('is-active');

            APP.utils.ajax('ai_suggest_all_pricing', {
                product_name: productName,
                main_image_id: mainImageId,
                min_quantity: minQuantity
            })
            .done(response => {
                if (response.success) {
                    // Set base price for all variants
                    $('.sspu-variant-price').val(response.data.base_price).addClass('updated-field');
                    
                    // If we have tiers, apply them to all variants
                    if (response.data.tiers && response.data.tiers.length > 0) {
                        $('.sspu-variant-row').each(function() {
                            const $row = $(this);
                            const $tiersBody = $row.find('.volume-tiers-body');
                            
                            // Clear existing tiers
                            $tiersBody.empty();
                            
                            // Add new tiers
                            response.data.tiers.forEach((tier, index) => {
                                if (APP.variants && APP.variants.addTier) {
                                    APP.variants.addTier($row, tier.min_quantity, tier.price);
                                }
                            });
                        });
                        
                        APP.utils.notify(`AI suggested base price: $${response.data.base_price} with ${response.data.tiers.length} volume tiers`, 'success');
                    } else {
                        APP.utils.notify(`AI suggested base price: $${response.data.base_price}`, 'success');
                    }
                    
                    setTimeout(() => $('.updated-field').removeClass('updated-field'), 2000);
                } else {
                    APP.utils.notify('Error: ' + (response.data.message || 'Pricing suggestion failed'), 'error');
                }
            })
            .fail(() => {
                APP.utils.notify('Network error while suggesting pricing', 'error');
            })
            .always(() => {
                $btn.prop('disabled', false);
                $spinner.removeClass('is-active');
            });
        },

        /**
         * Estimates weight for all variants based on AI analysis
         */
        estimateWeight() {
            const productName = $('#product-name-input').val();
            const mainImageId = $('#sspu-main-image-id').val();

            if (!productName) {
                APP.utils.notify('Please enter a product name first.', 'warning');
                return;
            }

            if (!mainImageId) {
                APP.utils.notify('Please select a main product image first.', 'warning');
                return;
            }

            const $btn = $('#ai-estimate-weight');
            const $spinner = $btn.find('.spinner');
            $btn.prop('disabled', true);
            $spinner.addClass('is-active');

            APP.utils.ajax('estimate_weight', {
                product_name: productName,
                main_image_id: mainImageId
            })
            .done(response => {
                if (response.success) {
                    // Set weight for all variants
                    $('.sspu-variant-weight').val(response.data.weight).addClass('updated-field');
                    
                    APP.utils.notify(`AI estimated weight: ${response.data.weight} lbs`, 'success');
                    
                    setTimeout(() => $('.updated-field').removeClass('updated-field'), 2000);
                } else {
                    APP.utils.notify('Error: ' + (response.data.message || 'Weight estimation failed'), 'error');
                }
            })
            .fail(() => {
                APP.utils.notify('Network error while estimating weight', 'error');
            })
            .always(() => {
                $btn.prop('disabled', false);
                $spinner.removeClass('is-active');
            });
        },

        /**
         * Auto-generates SKUs for all variants
         */
        autoGenerateAllSKUs() {
            const productName = $('#product-name-input').val();
            
            if (!productName) {
                APP.utils.notify('Please enter a product name first.', 'warning');
                return;
            }

            let generatedCount = 0;
            
            $('.sspu-variant-row').each(function() {
                const $row = $(this);
                const variantName = $row.find('.sspu-variant-option-name').val();
                const variantValue = $row.find('.sspu-variant-option-value').val();
                const $skuField = $row.find('.sspu-variant-sku');
                
                if (variantValue && !$skuField.val()) {
                    // Only generate if SKU is empty
                    APP.utils.ajax('generate_sku', {
                        product_name: productName,
                        variant_name: variantName,
                        variant_value: variantValue
                    }).done(response => {
                        if (response.success) {
                            $skuField.val(response.data.sku).addClass('updated-field');
                            generatedCount++;
                            
                            if (generatedCount === $('.sspu-variant-row').length) {
                                APP.utils.notify('All SKUs generated successfully!', 'success');
                                setTimeout(() => $('.updated-field').removeClass('updated-field'), 2000);
                            }
                        }
                    });
                }
            });
            
            if (generatedCount === 0) {
                APP.utils.notify('All variants already have SKUs.', 'info');
            }
        },

        /**
         * Handles AI image uploads
         */
        uploadAiImages() {
            if (!window.wp || !window.wp.media) {
                APP.utils.notify('WordPress media uploader not available', 'error');
                return;
            }

            const frame = wp.media({
                title: 'Select Images for AI Analysis',
                button: { text: 'Use for AI Analysis' },
                multiple: true,
                library: { type: 'image' }
            });

            frame.on('select', () => {
                const attachments = frame.state().get('selection').toJSON();
                const $preview = $('#sspu-ai-images-preview');
                
                $preview.empty();
                APP.state.aiImageIds = [];

                attachments.forEach(attachment => {
                    APP.state.aiImageIds.push(attachment.id);
                    $('<img>', {
                        src: attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url,
                        alt: attachment.alt,
                        'data-id': attachment.id,
                        css: {
                            maxWidth: '100px',
                            maxHeight: '100px',
                            margin: '5px',
                            border: '1px solid #ddd',
                            borderRadius: '4px'
                        }
                    }).appendTo($preview);
                });

                APP.utils.notify(`Selected ${attachments.length} images for AI analysis`, 'info');
            });

            frame.open();
        }
    };

})(jQuery, window.SSPU);