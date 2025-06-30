/**
 * SSPU Variants Module
 * Handles product variant management including volume tiers and mimic functionality
 */
(function($, APP) {
    'use strict';

    APP.variants = {
        /**
         * Initializes the variants module.
         */
        init: function() {
            this.bindEvents();
            this.initSortable();
            this.addMimicStyles();

            // Ensure detect all colors button is properly bound
            if ($('#detect-all-colors-btn').length === 0) {
                console.error('SSPU Variants: Detect All Colors button not found in DOM');
            } else {
                console.log('SSPU Variants: Detect All Colors button found and ready');
            }

            // Additional check for upload custom mask functionality
            setTimeout(() => {
                const $uploadButtons = $('.upload-custom-mask');
                console.log('SSPU Variants: Upload custom mask buttons found:', $uploadButtons.length);

                // If buttons exist but don't have click handlers, bind them directly
                $uploadButtons.each(function() {
                    const $btn = $(this);
                    if (!$btn.data('sspu-bound')) {
                        $btn.data('sspu-bound', true);
                        $btn.on('click', function(e) {
                            e.preventDefault();
                            console.log('Direct click handler for upload custom mask');
                            APP.variants.uploadCustomMask($(this));
                        });
                    }
                });
            }, 1000);

            APP.utils.log('Variants module initialized');
        },

        /**
         * Binds all event listeners for variant management.
         */
        bindEvents: function() {
            const self = this;
            const $doc = APP.cache.$doc;

            // Use body as the delegate for all dynamic content to ensure events work
            $('body').on('click', '.upload-custom-mask', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Upload custom mask button clicked (body delegate)');
                self.uploadCustomMask($(this));
            });

            // Add this new button to the events
            $('body').on('click', '#copy-design-area-to-all', function(e) {
                e.preventDefault();
                self.copyDesignAreaToAll();
            });


            // Add individual variant button
            $doc.on('click', '#sspu-add-variant-btn', function(e) {
                e.preventDefault();
                self.addVariant();
            });

            // Remove variant button from a row
            $doc.on('click', '.sspu-remove-variant-btn', function(e) {
                e.preventDefault();
                self.removeVariant($(this).closest('.sspu-variant-row'));
            });

            // Generate variants from generator section
            $doc.on('click', '#generate-variants-btn', function(e) {
                e.preventDefault();
                self.generateVariantsFromInputs();
            });

            // Clear all variants button
            $doc.on('click', '#clear-variants-btn', function(e) {
                e.preventDefault();
                if (confirm('Are you sure you want to remove all variants?')) {
                    $('#sspu-variants-wrapper').empty();
                    APP.state.variantCounter = 0;
                    APP.utils.notify('All variants have been cleared.', 'info');
                }
            });

            // Option name change
            $doc.on('change', '.sspu-variant-option-name', function() {
                self.updateVariantLabels();
            });

            // Image selection for a variant
            $doc.on('click', '.sspu-upload-image-btn[data-target-id-class="sspu-variant-image-id"]', function(e) {
                e.preventDefault();
                self.selectVariantImage($(this));
            });

            // AI Edit variant image
            $doc.on('click', '.sspu-ai-edit-variant-image', function(e) {
                e.preventDefault();
                const $row = $(this).closest('.sspu-variant-row');
                const imageId = $row.find('.sspu-variant-image-id').val();

                if (!imageId) {
                    APP.utils.notify('Please select a variant image first.', 'warning');
                    return;
                }

                wp.media.attachment(imageId).fetch().done(() => {
                    const imageUrl = wp.media.attachment(imageId).get('url');
                    if (imageUrl && window.AIImageEditor) {
                        window.AIImageEditor.open(imageId, imageUrl, {
                            fromVariants: true,
                            variantRow: $row[0]
                        });
                    } else {
                        APP.utils.notify('AI Image Editor not available.', 'error');
                    }
                });
            });

            // Generate SKU button
            $doc.on('click', '.generate-sku', function(e) {
                e.preventDefault();
                self.generateSKU($(this));
            });

            // Volume tier management - Add
            $doc.on('click', '.add-volume-tier', function(e) {
                e.preventDefault();
                self.addVolumeTier($(this));
            });

            // Volume tier management - Remove
            $doc.on('click', '.remove-volume-tier', function(e) {
                e.preventDefault();
                $(this).closest('.volume-tier-row').remove();
            });

            // Auto-calculate volume tiers
            $doc.on('click', '.auto-calculate-tiers', function(e) {
                e.preventDefault();
                self.autoCalculateVolumeTiers($(this));
            });

            // Apply price to all
            $doc.on('click', '#apply-price-to-all', function(e) {
                e.preventDefault();
                self.applyPriceToAll();
            });

            // Apply tiers to all
            $doc.on('click', '#apply-tiers-to-all', function(e) {
                e.preventDefault();
                self.applyTiersToAll();
            });

            // Apply weight to all
            $doc.on('click', '#apply-weight-to-all', function(e) {
                e.preventDefault();
                self.applyWeightToAll();
            });

            // Auto-generate all SKUs
            $doc.on('click', '#auto-generate-all-skus', function(e) {
                e.preventDefault();
                self.autoGenerateAllSKUs();
            });

            // Mimic All Variants button
            $doc.on('click', '#mimic-all-variants', function(e) {
                e.preventDefault();
                self.mimicAllVariants($(this));
            });

            // Smart Rotate All Variants button
            $doc.on('click', '#smart-rotate-all-variants', function(e) {
                e.preventDefault();
                self.smartRotateAllVariants($(this));
            });

            // Detect Color button
            $doc.on('click', '.detect-color', function(e) {
                e.preventDefault();
                self.detectColor($(this));
            });

            // Detect All Colors button - FIXED
            $doc.on('click', '#detect-all-colors-btn', function(e) {
                e.preventDefault();
                self.detectAllColors();
            });

            // Apply Design Mask to All button - FIXED
            $doc.on('click', '#apply-design-mask-to-all', function(e) {
                e.preventDefault();
                self.applyDesignMaskToAll();
            });

            // Create Design Files button
            $doc.on('click', '.sspu-create-design-tool-files', function(e) {
                e.preventDefault();
                self.createDesignFiles($(this));
            });

            // Copy Design button
            $doc.on('click', '.copy-design-mask', function(e) {
                e.preventDefault();
                self.copyDesignMask($(this));
            });

            // Paste Design button
            $doc.on('click', '.paste-design-mask:not(:disabled)', function(e) {
                e.preventDefault();
                self.pasteDesignMask($(this));
            });

            // Track variant changes for auto-save
            $doc.on('change', '.sspu-variant-row input, .sspu-variant-row select', function() {
                if (APP.state.autoSaveTimer) {
                    clearTimeout(APP.state.autoSaveTimer);
                }
                APP.state.autoSaveTimer = setTimeout(() => {
                    if (APP.drafts && APP.drafts.autoSave) {
                        APP.drafts.autoSave();
                    }
                }, 30000);
            });

            // Test if events are properly bound
            console.log('SSPU Variants: Event binding complete. Testing upload-custom-mask binding...');
            setTimeout(() => {
                const testButton = $('.upload-custom-mask').first();
                if (testButton.length > 0) {
                    const events = $._data(testButton[0], 'events');
                    console.log('SSPU Variants: Events bound to first upload-custom-mask button:', events);
                }
            }, 500);
        },

        /**
         * Create design tool files (mask and background)
         * @param {jQuery} $button - The button that triggered the creation
         */
        createDesignFiles: function($button) {
            const self = this;
            const $row = $button.closest('.sspu-variant-row');
            const imageId = $row.find('.sspu-variant-image-id').val();

            if (!imageId) {
                APP.utils.notify('Please select a variant image first.', 'warning');
                return;
            }

            if (typeof Cropper === 'undefined') {
                APP.utils.notify('Cropper library not loaded. Please refresh the page.', 'error');
                return;
            }

            const modalHtml = `
                <div id="design-mask-modal" class="sspu-lightbox-overlay" style="display:none;">
                    <div class="sspu-lightbox-content">
                        <span class="sspu-lightbox-close">&times;</span>
                        <h2>Select Design Area</h2>
                        <p>Draw a rectangle around the area where designs should be placed, or upload a custom mask.</p>
                        <div id="mask-image-container" style="max-height:60vh; overflow:hidden; margin: 20px 0;">
                            <img id="mask-source-image" src="" style="max-width:100%; height:auto; display:block; margin:0 auto;">
                        </div>
                        <div style="margin-top:20px; text-align:center;">
                            <button type="button" id="confirm-mask" class="button button-primary">Create Design Files</button>
                            <button type="button" id="upload-variant-mask" class="button">Upload Custom Mask</button>
                            <button type="button" id="cancel-mask" class="button">Cancel</button>
                        </div>
                    </div>
                </div>
            `;

            $('#design-mask-modal').remove();
            $('body').append(modalHtml);

            wp.media.attachment(imageId).fetch().done(() => {
                const imageUrl = wp.media.attachment(imageId).get('url');
                const $modal = $('#design-mask-modal');
                const $sourceImg = $('#mask-source-image');
                const $confirmBtn = $('#confirm-mask');
                const $uploadBtn = $('#upload-variant-mask');

                $sourceImg.attr('src', imageUrl);
                $modal.fadeIn();

                const cropper = new Cropper($sourceImg[0], {
                    aspectRatio: NaN,
                    viewMode: 1,
                    dragMode: 'crop',
                    autoCropArea: 0.5,
                    restore: false,
                    guides: true,
                    center: true,
                    highlight: true,
                    cropBoxMovable: true,
                    cropBoxResizable: true,
                    toggleDragModeOnDblclick: false,
                });

                // Handle Upload Custom Mask button inside the modal
                $uploadBtn.on('click', function() {
                    const frame = wp.media({
                        title: 'Select Custom Mask',
                        button: { text: 'Use this mask' },
                        multiple: false,
                        library: { type: 'image' }
                    });

                    frame.on('select', function() {
                        const attachment = frame.state().get('selection').first().toJSON();
                        const maskUrl = attachment.url;

                        // ** FIX START: Disable other buttons and set loading state **
                        $confirmBtn.prop('disabled', true);
                        $uploadBtn.prop('disabled', true).text('Processing...');
                        // ** FIX END **

                        // Use the correct AJAX action for custom masks
                        APP.utils.ajax('create_masked_image_with_custom_mask', {
                            image_id: imageId,
                            custom_mask_url: maskUrl
                        }).done(response => {
                            if (response.success) {
                                $row.find('.sspu-designer-background-url').val(response.data.background_url);
                                $row.find('.sspu-designer-mask-url').val(response.data.mask_url);
                                $row.find('.sspu-design-files-status').html('✓ Design files created with custom mask').show();
                                APP.utils.notify('Design files created with custom mask successfully!', 'success');

                                APP.state.copiedDesignMask = {
                                    background_url: response.data.background_url,
                                    mask_url: response.data.mask_url
                                };
                                $('.paste-design-mask').prop('disabled', false);
                            } else {
                                APP.utils.notify('Failed to create design files with custom mask: ' + response.data.message, 'error');
                            }
                        }).fail(() => {
                            APP.utils.notify('Error creating design files with custom mask.', 'error');
                        }).always(() => {
                            $button.prop('disabled', false).text('Create Design Files');
                            cropper.destroy();
                            $modal.remove(); // Close modal on completion
                        });
                    });

                    frame.open();
                });

                // Handle confirm (for drawing a mask)
                $confirmBtn.on('click', function() {
                    // ** FIX START: Disable other buttons to prevent conflict **
                    $uploadBtn.prop('disabled', true);
                    $confirmBtn.prop('disabled', true).text('Creating...');
                    // ** FIX END **
                    
                    const cropData = cropper.getData();

                    // Use the correct AJAX action for drawn masks
                    APP.utils.ajax('create_masked_image', {
                        image_id: imageId,
                        mask_coordinates: {
                            x: Math.round(cropData.x),
                            y: Math.round(cropData.y),
                            width: Math.round(cropData.width),
                            height: Math.round(cropData.height)
                        }
                    }).done(response => {
                        if (response.success) {
                            $row.find('.sspu-designer-background-url').val(response.data.background_url);
                            $row.find('.sspu-designer-mask-url').val(response.data.mask_url);
                            $row.find('.sspu-design-files-status').html('✓ Design files created').show();
                            APP.utils.notify('Design files created successfully!', 'success');

                            APP.state.copiedDesignMask = {
                                background_url: response.data.background_url,
                                mask_url: response.data.mask_url
                            };
                            $('.paste-design-mask').prop('disabled', false);
                        } else {
                            APP.utils.notify('Failed to create design files: ' + response.data.message, 'error');
                        }
                    }).fail(() => {
                        APP.utils.notify('Error creating design files.', 'error');
                    }).always(() => {
                        $button.prop('disabled', false).text('Create Design Files');
                        cropper.destroy();
                        $modal.remove(); // Close modal on completion
                    });
                });
                
                // General close/cancel handler
                const closeModal = () => {
                    cropper.destroy();
                    $modal.fadeOut(() => $modal.remove());
                    $(document).off('keydown.sspu-lightbox');
                };

                $('#cancel-mask, .sspu-lightbox-close').on('click', closeModal);
                $modal.on('click', e => { if (e.target === e.currentTarget) closeModal(); });
                $(document).on('keydown.sspu-lightbox', e => { if (e.key === 'Escape') closeModal(); });
            });
        },

        /**
         * Upload custom mask for a specific variant
         * @param {jQuery} $button - The button that triggered the upload
         */
        uploadCustomMask: function($button) {
            const self = this;
            const $row = $button.closest('.sspu-variant-row');
            const imageId = $row.find('.sspu-variant-image-id').val();

            console.log('uploadCustomMask method called for variant');

            if (!imageId) {
                APP.utils.notify('Please select a variant image first.', 'warning');
                return;
            }

            // Check if wp.media is available
            if (typeof wp === 'undefined' || typeof wp.media === 'undefined') {
                console.error('WordPress media library not loaded');
                APP.utils.notify('Media library not available. Please refresh the page.', 'error');
                return;
            }

            const frame = wp.media({
                title: 'Select Custom Mask Image',
                button: {
                    text: 'Use this mask'
                },
                multiple: false,
                library: {
                    type: 'image'
                }
            });

            frame.on('select', function() {
                console.log('Custom mask selected');
                const attachment = frame.state().get('selection').first().toJSON();
                const maskUrl = attachment.url;
                console.log('Selected mask URL:', maskUrl);

                // Get the original image URL
                wp.media.attachment(imageId).fetch().done(() => {
                    const imageUrl = wp.media.attachment(imageId).get('url');

                    // **THE FIX IS HERE:**
                    // Instead of making an immediate AJAX call, this opens the preview/adjustment modal.
                    self.showMaskPreviewModal($row, imageId, imageUrl, maskUrl);
                });
            });

            frame.on('open', function() {
                console.log('Media library opened for custom mask');
            });

            frame.open();
        },

        /**
         * Show mask preview modal with adjustment controls.
         * This function gives you the chance to adjust the mask.
         */
        showMaskPreviewModal: function($row, imageId, imageUrl, maskUrl) {
            const self = this;

            // Create modal HTML
            const modalHtml = `
                <div id="mask-preview-modal" class="sspu-lightbox-overlay" style="display:none;">
                    <div class="sspu-lightbox-content" style="max-width: 1200px;">
                        <span class="sspu-lightbox-close">&times;</span>
                        <h2>Adjust Design Area</h2>
                        <p>Position and scale the mask to define where designs will be placed. The white/light area shows where designs will appear.</p>
                        
                        <div style="display: flex; gap: 20px; margin-top: 20px;">
                            <div style="flex: 1;">
                                <div id="mask-preview-container" style="position: relative; display: inline-block; border: 2px solid #ccc;">
                                    <img id="preview-base-image" src="${imageUrl}" style="max-width: 600px; height: auto; display: block;">
                                    <div id="mask-overlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none;">
                                        <img id="preview-mask-image" src="${maskUrl}" style="position: absolute; opacity: 0.7; mix-blend-mode: screen;">
                                    </div>
                                </div>
                            </div>
                            
                            <div style="width: 300px;">
                                <h3>Mask Controls</h3>
                                <div style="margin-bottom: 15px;">
                                    <label>Size: <span id="size-value">100</span>%</label>
                                    <input type="range" id="mask-size" min="10" max="200" value="100" style="width: 100%;">
                                </div>
                                <div style="margin-bottom: 15px;">
                                    <label>Horizontal Position: <span id="x-value">50</span>%</label>
                                    <input type="range" id="mask-x" min="0" max="100" value="50" style="width: 100%;">
                                </div>
                                <div style="margin-bottom: 15px;">
                                    <label>Vertical Position: <span id="y-value">50</span>%</label>
                                    <input type="range" id="mask-y" min="0" max="100" value="50" style="width: 100%;">
                                </div>
                                <div style="margin-bottom: 15px;">
                                    <label>Opacity: <span id="opacity-value">70</span>%</label>
                                    <input type="range" id="mask-opacity" min="0" max="100" value="70" style="width: 100%;">
                                </div>
                                <button type="button" id="reset-mask" class="button">Reset Position</button>
                            </div>
                        </div>
                        
                        <div style="margin-top: 20px; text-align: center;">
                            <button type="button" id="apply-custom-mask" class="button button-primary">Apply Custom Mask</button>
                            <button type="button" id="cancel-custom-mask" class="button">Cancel</button>
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(modalHtml);
            const $modal = $('#mask-preview-modal');
            const $maskImg = $('#preview-mask-image');

            // Update mask transform based on controls
            const updateMaskTransform = () => {
                const size = $('#mask-size').val();
                const x = $('#mask-x').val();
                const y = $('#mask-y').val();
                const opacity = $('#mask-opacity').val() / 100;
                const scale = size / 100;
                const translateX = (x - 50) + '%';
                const translateY = (y - 50) + '%';
                $maskImg.css({
                    transform: `translate(-50%, -50%) translate(${translateX}, ${translateY}) scale(${scale})`,
                    transformOrigin: 'center center',
                    opacity: opacity,
                    position: 'absolute',
                    top: '50%',
                    left: '50%',
                    maxWidth: 'none'
                });
                $('#size-value').text(size);
                $('#x-value').text(Math.round(x));
                $('#y-value').text(Math.round(y));
                $('#opacity-value').text(opacity * 100);
            };

            // Bind control events
            $('#mask-size, #mask-x, #mask-y, #mask-opacity').on('input', updateMaskTransform);
            $('#reset-mask').on('click', function() {
                $('#mask-size').val(100);
                $('#mask-x').val(50);
                $('#mask-y').val(50);
                $('#mask-opacity').val(70);
                updateMaskTransform();
            });

            // Initial position update
            updateMaskTransform();

            // Apply mask button click handler (This now triggers the final step)
            $('#apply-custom-mask').on('click', function() {
                const maskData = {
                    size: $('#mask-size').val(),
                    x: $('#mask-x').val(),
                    y: $('#mask-y').val()
                };
                $modal.fadeOut(() => $modal.remove());
                self.applyCustomMaskWithAdjustments($row, imageId, maskUrl, maskData);
            });

            const closeModal = () => $modal.fadeOut(() => $modal.remove());
            $('#cancel-custom-mask, .sspu-lightbox-close').on('click', closeModal);
            $modal.on('click', e => { if (e.target === e.currentTarget) closeModal(); });
            $(document).on('keydown.maskPreview', e => { if (e.key === 'Escape') closeModal(); });
            
            $modal.fadeIn();
        },

        /**
         * Apply custom mask with adjustments from the modal
         */
        applyCustomMaskWithAdjustments: function($row, imageId, maskUrl, maskData) {
            const $button = $row.find('.upload-custom-mask');
            $button.prop('disabled', true).text('Applying custom mask...');

            APP.utils.ajax('create_masked_image_with_custom_mask', {
                image_id: imageId,
                custom_mask_url: maskUrl,
                mask_adjustments: maskData
            }).done(response => {
                if (response.success) {
                    $row.find('.sspu-designer-background-url').val(response.data.background_url);
                    $row.find('.sspu-designer-mask-url').val(response.data.mask_url);
                    $row.find('.sspu-design-files-status').html('✓ Design files created').show();
                    APP.utils.notify('Design files created with custom mask successfully!', 'success');

                    APP.state.copiedDesignMask = {
                        background_url: response.data.background_url,
                        mask_url: response.data.mask_url
                    };
                    $('.paste-design-mask').prop('disabled', false);
                } else {
                    APP.utils.notify('Failed to create design files: ' + response.data.message, 'error');
                }
            }).fail(() => {
                APP.utils.notify('Error creating design files.', 'error');
            }).always(() => {
                $button.prop('disabled', false).text('Upload Custom Mask');
            });
        },

        /**
         * Copy the design area from one variant and apply it to all others.
         */
        copyDesignAreaToAll: function() {
            const self = this;
            const $sourceRow = $('.sspu-variant-row').first();
            const maskUrl = $sourceRow.find('.sspu-designer-mask-url').val();
            const sourceImageId = $sourceRow.find('.sspu-variant-image-id').val();

            if (!maskUrl || !sourceImageId) {
                APP.utils.notify('Please create or upload a design mask on the first variant to copy it.', 'warning');
                return;
            }

            self.extractMaskCoordinatesFromImage(maskUrl, sourceImageId)
                .then(maskCoordinates => {
                    if (!maskCoordinates) {
                        APP.utils.notify('Could not extract mask coordinates from the source variant.', 'error');
                        return;
                    }

                    const $otherVariants = $('.sspu-variant-row:not(:first)');
                    let successCount = 0;

                    $otherVariants.each(function() {
                        const $row = $(this);
                        const imageId = $row.find('.sspu-variant-image-id').val();

                        if (imageId) {
                            APP.utils.ajax('create_masked_image', {
                                image_id: imageId,
                                mask_coordinates: maskCoordinates
                            }).done(response => {
                                if (response.success) {
                                    $row.find('.sspu-designer-background-url').val(response.data.background_url);
                                    $row.find('.sspu-designer-mask-url').val(response.data.mask_url);
                                    $row.find('.sspu-design-files-status').html('✓ Design files created');
                                    successCount++;
                                }
                            });
                        }
                    });

                    APP.utils.notify(`Copied design area to ${successCount} other variants.`, 'success');
                })
                .catch(error => {
                    APP.utils.notify('Error copying design area: ' + error.message, 'error');
                });
        },

        /**
         * Initializes the jQuery UI sortable functionality for variant rows.
         */
        initSortable: function() {
            if ($.fn.sortable) {
                $('#sspu-variants-wrapper').sortable({
                    handle: '.drag-handle',
                    items: '.sspu-variant-row',
                    axis: 'y',
                    opacity: 0.7,
                    placeholder: 'sortable-placeholder',
                    update: (event, ui) => {
                        this.updateNumbers();
                        APP.utils.log('Variants reordered');
                    }
                });
            }
        },

        /**
         * Adds CSS styles required for mimic functionality and sortable placeholder.
         */
        addMimicStyles: function() {
            if ($('#sspu-mimic-styles').length === 0) {
                $('head').append(`
                    <style id="sspu-mimic-styles">
                        .sspu-variant-row.variant-updated {
                            background-color: #d4edda !important;
                            border-left: 4px solid #00a32a;
                            transition: background-color 0.5s, border-left 0.5s;
                        }
                        .sspu-variant-row.variant-updated .sspu-variant-image-preview img {
                            box-shadow: 0 0 0 3px #00a32a;
                            transition: box-shadow 0.5s;
                        }
                        .sortable-placeholder {
                            border: 2px dashed #0073aa;
                            background: #f0f8ff;
                            height: 150px;
                            visibility: visible !important;
                        }
                        
                        /* Lightbox styles for design mask modal */
                        .sspu-lightbox-overlay {
                            position: fixed;
                            top: 0;
                            left: 0;
                            width: 100%;
                            height: 100%;
                            background-color: rgba(0, 0, 0, 0.8);
                            z-index: 100000;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                        }
                        
                        .sspu-lightbox-content {
                            background: #fff;
                            padding: 30px;
                            max-width: 90%;
                            max-height: 90vh;
                            overflow-y: auto;
                            position: relative;
                            border-radius: 8px;
                            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
                        }
                        
                        .sspu-lightbox-close {
                            position: absolute;
                            top: 10px;
                            right: 15px;
                            font-size: 30px;
                            font-weight: bold;
                            color: #999;
                            cursor: pointer;
                            transition: color 0.3s;
                        }
                        
                        .sspu-lightbox-close:hover,
                        .sspu-lightbox-close:focus {
                            color: #333;
                            text-decoration: none;
                        }
                        
                        #mask-image-container {
                            display: flex;
                            justify-content: center;
                            align-items: center;
                        }
                    </style>
                `);
            }
        },

        /**
         * Generates the HTML for a new variant row.
         * @param {number} index - The index of the variant.
         * @param {string} optionName - The name of the option.
         * @param {string} [optionValue=''] - The value of the option.
         * @param {string} [price=''] - The price of the variant.
         * @param {string} [sku=''] - The SKU of the variant.
         * @returns {jQuery} The jQuery object representing the variant row HTML.
         */
        getVariantRowHTML: function(index, optionName, optionValue = '', price = '', sku = '') {
            const template = $('#sspu-variant-template').html();
            let variantHTML = template.replace(/\[0\]/g, `[${index}]`);
            variantHTML = $(variantHTML);

            variantHTML.find('.variant-number').text(index + 1);
            variantHTML.find('.sspu-variant-option-name').val(optionName);
            variantHTML.find('.sspu-variant-option-value').val(optionValue);
            variantHTML.find('.sspu-variant-price').val(price);
            variantHTML.find('.sspu-variant-sku').val(sku);

            return variantHTML;
        },

        /**
         * Adds a new variant row to the display.
         * @param {object} [data={}] - Optional data to pre-fill variant fields (value, price, sku).
         */
        addVariant: function(data = {}) {
            const $container = $('#sspu-variants-wrapper');
            const variantCount = APP.state.variantCounter++;
            const optionName = $('.sspu-variant-option-name').last().val() || 'Option';
            const variantHTML = this.getVariantRowHTML(variantCount, optionName, data.value, data.price, data.sku);

            $container.append(variantHTML);
            this.updateNumbers();
            APP.utils.log('Added variant #' + variantCount);
        },

        /**
         * Generates multiple variants based on user input in the generator section.
         */
        generateVariantsFromInputs: function() {
            const optionName = $('#variant-option-name').val().trim();
            const optionValuesStr = $('#variant-option-values').val().trim();
            const basePrice = $('#variant-base-price').val();

            if (!optionName || !optionValuesStr) {
                APP.utils.notify('Please provide an option name and at least one value.', 'warning');
                return;
            }

            const values = optionValuesStr.split(/[\n,]/).map(v => v.trim()).filter(v => v);

            if (values.length === 0) {
                APP.utils.notify('No valid option values found.', 'warning');
                return;
            }

            values.forEach(value => {
                this.addVariant({
                    name: optionName,
                    value: value,
                    price: basePrice
                });
            });

            APP.utils.notify(`${values.length} variants generated successfully!`, 'success');
        },

        /**
         * Removes a specified variant row.
         * @param {jQuery} $row - The jQuery object of the variant row to remove.
         */
        removeVariant: function($row) {
            if ($('.sspu-variant-row').length > 1) {
                $row.fadeOut(300, function() {
                    $(this).remove();
                    this.updateNumbers();
                }.bind(this));
                APP.utils.notify('Variant removed', 'info');
            } else {
                APP.utils.notify('At least one variant is required.', 'warning');
            }
        },

        /**
         * Updates the labels for variant options based on the first option name.
         */
        updateVariantLabels: function() {
            const optionName = $('.sspu-variant-option-name').first().val() || 'Option';
            $('.sspu-variant-label').text(optionName + ':');
        },

        /**
         * Updates the numerical labels and input names for all variant rows after reordering or removal.
         */
        updateNumbers: function() {
            $('.sspu-variant-row').each(function(index) {
                $(this).find('.variant-number').text(index + 1);
                $(this).find('input, select').each(function() {
                    const name = $(this).attr('name');
                    if (name) {
                        const newName = name.replace(/\[\d+\]/, `[${index}]`);
                        $(this).attr('name', newName);
                    }
                });
            });
        },

        /**
         * Opens the WordPress media uploader to select an image for a variant.
         * @param {jQuery} $button - The button that triggered the image selection.
         */
        selectVariantImage: function($button) {
            const self = this;
            const $row = $button.closest('.sspu-variant-row');

            if (wp.media) {
                const frame = wp.media({
                    title: 'Select Variant Image',
                    button: {
                        text: 'Use this image'
                    },
                    multiple: false
                });

                frame.on('select', function() {
                    const attachment = frame.state().get('selection').first().toJSON();
                    self.setVariantImage($row, attachment);
                });

                frame.open();
            }
        },

        /**
         * Sets the selected image for a specific variant row.
         * @param {jQuery} $row - The jQuery object for the variant row.
         * @param {object} attachment - The WordPress media attachment object.
         */
        setVariantImage: function($row, attachment) {
            const $preview = $row.find('.sspu-variant-image-preview');
            const $idField = $row.find('.sspu-variant-image-id');

            console.log(`[Variants Module] Setting image for variant. ID: ${attachment.id}`, $row);

            $preview.empty().append(
                $('<img>', {
                    src: attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url,
                    alt: attachment.alt || 'Variant image',
                    'data-id': attachment.id
                })
            );

            $idField.val(attachment.id);
            $row.find('.sspu-ai-edit-variant-image').show();

            APP.utils.log('Set variant image: ' + attachment.id);
        },

        /**
         * Gathers tier data from a specific variant row.
         * @param {jQuery} $row - The jQuery object for the variant row.
         * @returns {Array} An array of tier objects, each with min_quantity and price.
         */
        getTierData: function($row) {
            console.log('[SSPU Variants] === GET TIER DATA START ===');
            console.log('[SSPU Variants] Row provided:', $row.length > 0);

            const tiers = [];
            const $tierRows = $row.find('.volume-tier-row');
            console.log('[SSPU Variants] Found tier rows:', $tierRows.length);

            $tierRows.each(function(index) {
                console.log(`[SSPU Variants] Processing tier row ${index}`);

                const $tierRow = $(this);
                console.log('[SSPU Variants] Tier row element:', $tierRow[0]);

                const $minQtyInput = $tierRow.find('input[name*="[min_quantity]"]');
                const $priceInput = $tierRow.find('input[name*="[price]"]');

                console.log(`[SSPU Variants] Tier ${index} - Min qty input found:`, $minQtyInput.length > 0);
                console.log(`[SSPU Variants] Tier ${index} - Price input found:`, $priceInput.length > 0);

                const min_quantity = $minQtyInput.val();
                const price = $priceInput.val();

                console.log(`[SSPU Variants] Tier ${index} - Min qty value: "${min_quantity}"`);
                console.log(`[SSPU Variants] Tier ${index} - Price value: "${price}"`);

                if (min_quantity && price) {
                    const tierData = {
                        min_quantity: parseInt(min_quantity, 10),
                        price: parseFloat(price)
                    };
                    console.log(`[SSPU Variants] Tier ${index} - Valid tier data:`, tierData);
                    tiers.push(tierData);
                } else {
                    console.warn(`[SSPU Variants] Tier ${index} - Skipping due to missing data`);
                }
            });

            console.log('[SSPU Variants] Total valid tiers collected:', tiers.length);
            console.log('[SSPU Variants] Tier data:', tiers);
            console.log('[SSPU Variants] === GET TIER DATA END ===');

            return tiers;
        },

        /**
         * Adds a volume tier row to a variant.
         * @param {jQuery} $button - The button that triggered adding the tier.
         */
        addVolumeTier: function($button) {
            console.log('[SSPU Variants] === ADD VOLUME TIER START ===');

            const $row = $button.closest('.sspu-variant-row');
            console.log('[SSPU Variants] Row found:', $row.length > 0);

            const $tiersBody = $row.find('.volume-tiers-body');
            console.log('[SSPU Variants] Tiers body found:', $tiersBody.length > 0);
            console.log('[SSPU Variants] Tiers body element:', $tiersBody[0]);

            const variantIndex = $row.index();
            const tierIndex = $tiersBody.find('.volume-tier-row').length;
            console.log('[SSPU Variants] Variant index:', variantIndex);
            console.log('[SSPU Variants] New tier index:', tierIndex);

            const $tierTemplate = $('#sspu-tier-template');
            console.log('[SSPU Variants] Tier template found:', $tierTemplate.length > 0);

            if ($tierTemplate.length === 0) {
                console.error('[SSPU Variants] ERROR: Tier template not found!');
                APP.utils.notify('Error: Tier template not found. Please refresh the page.', 'error');
                return;
            }

            const templateHtml = $tierTemplate.html();
            console.log('[SSPU Variants] Template HTML:', templateHtml);

            const tierHtml = templateHtml
                .replace(/variant_options\[0\]/g, `variant_options[${variantIndex}]`)
                .replace(/\[tiers\]\[0\]/g, `[tiers][${tierIndex}]`);

            console.log('[SSPU Variants] Processed tier HTML:', tierHtml);

            // Parse and append properly
            const $newTier = $($.parseHTML(tierHtml.trim()));
            $tiersBody.append($newTier);

            const newTierCount = $tiersBody.find('.volume-tier-row').length;
            console.log('[SSPU Variants] New tier count after append:', newTierCount);
            console.log('[SSPU Variants] === ADD VOLUME TIER END ===');
        },

        /**
         * Auto-calculates and populates volume tiers for a variant based on its base price.
         * @param {jQuery} $button - The button that triggered the auto-calculation.
         */
        autoCalculateVolumeTiers: function($button) {
            console.log('[SSPU Variants] === AUTO-CALCULATE VOLUME TIERS START ===');

            const $row = $button.closest('.sspu-variant-row');
            console.log('[SSPU Variants] Row found:', $row.length > 0);
            console.log('[SSPU Variants] Row index:', $row.index());
            console.log('[SSPU Variants] Row HTML:', $row[0]);

            const $priceInput = $row.find('.sspu-variant-price');
            console.log('[SSPU Variants] Price input found:', $priceInput.length > 0);
            console.log('[SSPU Variants] Price input value:', $priceInput.val());

            const basePrice = parseFloat($priceInput.val());
            console.log('[SSPU Variants] Parsed base price:', basePrice);

            if (!basePrice || basePrice <= 0) {
                console.error('[SSPU Variants] Invalid base price:', basePrice);
                APP.utils.notify('Please enter a base price first.', 'warning');
                return;
            }

            console.log('[SSPU Variants] Disabling button and making AJAX call...');
            $button.prop('disabled', true);

            APP.utils.ajax('calculate_volume_tiers', {
                    base_price: basePrice
                })
                .done(response => {
                    console.log('[SSPU Variants] AJAX Response received:', response);

                    if (response.success) {
                        console.log('[SSPU Variants] Response successful, tiers data:', response.data.tiers);

                        const $tiersBody = $row.find('.volume-tiers-body');
                        console.log('[SSPU Variants] Tiers body found:', $tiersBody.length > 0);
                        console.log('[SSPU Variants] Tiers body element:', $tiersBody[0]);
                        console.log('[SSPU Variants] Current tiers body content before empty:', $tiersBody.html());

                        $tiersBody.empty();
                        console.log('[SSPU Variants] Tiers body cleared');

                        // Check if tier template exists
                        const $tierTemplate = $('#sspu-tier-template');
                        console.log('[SSPU Variants] Tier template found:', $tierTemplate.length > 0);
                        console.log('[SSPU Variants] Tier template HTML:', $tierTemplate.html());

                        if ($tierTemplate.length === 0) {
                            console.error('[SSPU Variants] ERROR: Tier template not found! Looking for #sspu-tier-template');
                            APP.utils.notify('Error: Tier template not found. Please refresh the page.', 'error');
                            return;
                        }

                        response.data.tiers.forEach((tier, index) => {
                            console.log(`[SSPU Variants] Processing tier ${index}:`, tier);

                            const variantIndex = $row.index();
                            console.log(`[SSPU Variants] Variant index: ${variantIndex}, Tier index: ${index}`);

                            const templateHtml = $tierTemplate.html();
                            console.log('[SSPU Variants] Template HTML before replacement:', templateHtml);

                            const tierHtml = templateHtml
                                .replace(/variant_options\[0\]/g, `variant_options[${variantIndex}]`)
                                .replace(/\[tiers\]\[0\]/g, `[tiers][${index}]`);

                            console.log('[SSPU Variants] Tier HTML after replacement:', tierHtml);

                            // Parse as HTML properly
                            const $tierRow = $($.parseHTML(tierHtml.trim()));
                            console.log('[SSPU Variants] Created tier row jQuery object:', $tierRow.length > 0);

                            // Find and set min quantity - use more flexible selectors
                            const $minQtyInput = $tierRow.find('input[class*="tier-min-quantity"], input[name*="min_quantity"]').first();
                            console.log('[SSPU Variants] Min quantity input found:', $minQtyInput.length > 0);
                            if ($minQtyInput.length > 0) {
                                $minQtyInput.val(tier.min_quantity);
                                console.log('[SSPU Variants] Set min quantity to:', tier.min_quantity);
                            } else {
                                console.error('[SSPU Variants] ERROR: Could not find min quantity input!');
                                console.error('[SSPU Variants] Tier row HTML:', $tierRow.html());
                            }

                            // Find and set price - use more flexible selectors
                            const $priceInput = $tierRow.find('input[class*="tier-price"], input[name*="[price]"]').first();
                            console.log('[SSPU Variants] Price input found:', $priceInput.length > 0);
                            if ($priceInput.length > 0) {
                                $priceInput.val(tier.price);
                                console.log('[SSPU Variants] Set price to:', tier.price);
                            } else {
                                console.error('[SSPU Variants] ERROR: Could not find price input!');
                            }

                            // Append to tiers body
                            console.log('[SSPU Variants] Appending tier row to body...');
                            $tiersBody.append($tierRow);

                            // Verify it was added
                            const addedRows = $tiersBody.find('.volume-tier-row').length;
                            console.log(`[SSPU Variants] Total tier rows after append: ${addedRows}`);
                        });

                        // Final verification
                        const finalTierCount = $tiersBody.find('.volume-tier-row').length;
                        console.log('[SSPU Variants] Final tier count:', finalTierCount);
                        console.log('[SSPU Variants] Final tiers body HTML:', $tiersBody.html());

                        if (finalTierCount === 0) {
                            console.error('[SSPU Variants] ERROR: No tier rows were added despite successful processing!');
                            console.error('[SSPU Variants] Tiers body parent:', $tiersBody.parent());
                            console.error('[SSPU Variants] Row structure:', $row.html());
                        }

                        APP.utils.notify('Volume tiers calculated successfully!', 'success');
                    } else {
                        console.error('[SSPU Variants] AJAX request failed:', response);
                        APP.utils.notify('Failed to calculate volume tiers.', 'error');
                    }
                })
                .fail((xhr, status, error) => {
                    console.error('[SSPU Variants] AJAX Error:', {
                        status: status,
                        error: error,
                        xhr: xhr,
                        responseText: xhr.responseText
                    });
                    APP.utils.notify('Network error while calculating tiers.', 'error');
                })
                .always(() => {
                    console.log('[SSPU Variants] Re-enabling button...');
                    $button.prop('disabled', false);
                    console.log('[SSPU Variants] === AUTO-CALCULATE VOLUME TIERS END ===');
                });
        },

        /**
         * Apply the first variant's price to all variants
         */
        applyPriceToAll: function() {
            const firstPrice = $('.sspu-variant-row:first .sspu-variant-price').val();
            if (!firstPrice) {
                APP.utils.notify('First variant must have a price to apply to all.', 'warning');
                return;
            }

            $('.sspu-variant-price').val(firstPrice).addClass('updated-field');
            APP.utils.notify(`Applied price $${firstPrice} to all variants.`, 'success');
            setTimeout(() => $('.updated-field').removeClass('updated-field'), 1000);
        },

        /**
         * Apply the first variant's tiers to all variants
         */
        applyTiersToAll: function() {
            console.log('[SSPU Variants] === APPLY TIERS TO ALL START ===');

            const $firstRow = $('.sspu-variant-row:first');
            console.log('[SSPU Variants] First row found:', $firstRow.length > 0);

            const firstTiers = this.getTierData($firstRow);
            console.log('[SSPU Variants] First variant tiers:', firstTiers);
            console.log('[SSPU Variants] Number of tiers to copy:', firstTiers.length);

            if (firstTiers.length === 0) {
                console.warn('[SSPU Variants] No tiers found in first variant');
                APP.utils.notify('First variant must have volume tiers to apply to all.', 'warning');
                return;
            }

            const $allRows = $('.sspu-variant-row');
            console.log('[SSPU Variants] Total variant rows:', $allRows.length);

            // Check if tier template exists
            const $tierTemplate = $('#sspu-tier-template');
            console.log('[SSPU Variants] Tier template found:', $tierTemplate.length > 0);

            if ($tierTemplate.length === 0) {
                console.error('[SSPU Variants] ERROR: Tier template not found!');
                APP.utils.notify('Error: Tier template not found. Please refresh the page.', 'error');
                return;
            }

            let successCount = 0;
            let errorCount = 0;

            $allRows.each((index, element) => {
                if (index === 0) {
                    console.log('[SSPU Variants] Skipping first row (source)');
                    return; // Skip first row
                }

                console.log(`[SSPU Variants] Processing variant row ${index}`);

                const $row = $(element);
                const $tiersBody = $row.find('.volume-tiers-body');
                console.log(`[SSPU Variants] Row ${index} - Tiers body found:`, $tiersBody.length > 0);

                if ($tiersBody.length === 0) {
                    console.error(`[SSPU Variants] ERROR: No tiers body found for row ${index}`);
                    errorCount++;
                    return;
                }

                // Clear existing tiers
                const existingTiers = $tiersBody.find('.volume-tier-row').length;
                console.log(`[SSPU Variants] Row ${index} - Existing tiers: ${existingTiers}`);
                $tiersBody.empty();
                console.log(`[SSPU Variants] Row ${index} - Cleared existing tiers`);

                // Add new tiers
                firstTiers.forEach((tier, tierIndex) => {
                    console.log(`[SSPU Variants] Row ${index} - Adding tier ${tierIndex}:`, tier);

                    const variantIndex = $row.index();
                    const templateHtml = $tierTemplate.html();

                    const tierHtml = templateHtml
                        .replace(/variant_options\[0\]/g, `variant_options[${variantIndex}]`)
                        .replace(/\[tiers\]\[0\]/g, `[tiers][${tierIndex}]`);

                    // Parse as HTML properly
                    const $tierRow = $($.parseHTML(tierHtml.trim()));

                    // Set values with flexible selectors
                    const $minQtyInput = $tierRow.find('input[class*="tier-min-quantity"], input[name*="min_quantity"]').first();
                    const $priceInput = $tierRow.find('input[class*="tier-price"], input[name*="[price]"]').first();

                    if ($minQtyInput.length > 0) {
                        $minQtyInput.val(tier.min_quantity);
                        console.log(`[SSPU Variants] Row ${index}, Tier ${tierIndex} - Set min qty: ${tier.min_quantity}`);
                    } else {
                        console.error(`[SSPU Variants] ERROR: Row ${index}, Tier ${tierIndex} - No min qty input found`);
                    }

                    if ($priceInput.length > 0) {
                        $priceInput.val(tier.price);
                        console.log(`[SSPU Variants] Row ${index}, Tier ${tierIndex} - Set price: ${tier.price}`);
                    } else {
                        console.error(`[SSPU Variants] ERROR: Row ${index}, Tier ${tierIndex} - No price input found`);
                    }

                    $tiersBody.append($tierRow);
                });

                // Verify tiers were added
                const finalTierCount = $tiersBody.find('.volume-tier-row').length;
                console.log(`[SSPU Variants] Row ${index} - Final tier count: ${finalTierCount}`);

                if (finalTierCount === firstTiers.length) {
                    successCount++;
                    console.log(`[SSPU Variants] Row ${index} - SUCCESS`);
                } else {
                    errorCount++;
                    console.error(`[SSPU Variants] Row ${index} - ERROR: Expected ${firstTiers.length} tiers, got ${finalTierCount}`);
                }
            });

            console.log('[SSPU Variants] === APPLY TIERS TO ALL SUMMARY ===');
            console.log(`[SSPU Variants] Successfully updated: ${successCount} variants`);
            console.log(`[SSPU Variants] Errors: ${errorCount} variants`);
            console.log('[SSPU Variants] === APPLY TIERS TO ALL END ===');

            APP.utils.notify(`Applied ${firstTiers.length} volume tiers to ${successCount} variants.`, 'success');
        },

        /**
         * Apply the first variant's weight to all variants
         */
        applyWeightToAll: function() {
            const firstWeight = $('.sspu-variant-row:first .sspu-variant-weight').val();
            if (!firstWeight) {
                APP.utils.notify('First variant must have a weight to apply to all.', 'warning');
                return;
            }

            $('.sspu-variant-weight').val(firstWeight).addClass('updated-field');
            APP.utils.notify(`Applied weight ${firstWeight} lbs to all variants.`, 'success');
            setTimeout(() => $('.updated-field').removeClass('updated-field'), 1000);
        },

        /**
         * Auto-generate SKUs for all variants
         */
        autoGenerateAllSKUs: function() {
            const productName = $('#product-name-input').val();

            if (!productName) {
                APP.utils.notify('Please enter a product name first.', 'warning');
                return;
            }

            let skuCount = 0;

            $('.sspu-variant-row').each(function() {
                const $row = $(this);
                const variantName = $row.find('.sspu-variant-option-name').val();
                const variantValue = $row.find('.sspu-variant-option-value').val();
                const $skuField = $row.find('.sspu-variant-sku');

                if (variantValue && !$skuField.val()) {
                    APP.utils.ajax('generate_sku', {
                        product_name: productName,
                        variant_name: variantName,
                        variant_value: variantValue
                    }).done(response => {
                        if (response.success) {
                            $skuField.val(response.data.sku).addClass('updated-field');
                            skuCount++;
                        }
                    });
                }
            });

            setTimeout(() => {
                $('.updated-field').removeClass('updated-field');
                APP.utils.notify(`Generated ${skuCount} SKUs.`, 'success');
            }, 1000);
        },

        /**
         * Public method to add a variant, essentially a wrapper for addVariant.
         * @param {object} data - Data to pre-fill variant fields.
         */
        add: function(data) {
            this.addVariant(data);
        },

        /**
         * Adds a specific volume tier to a variant row.
         * @param {jQuery} $variantRow - The jQuery object for the variant row to which to add the tier.
         * @param {number} minQuantity - The minimum quantity for the tier.
         * @param {number} price - The price for the tier.
         */
        addTier: function($variantRow, minQuantity, price) {
            console.log('[SSPU Variants] === ADD TIER (AI) START ===');
            console.log('[SSPU Variants] Adding tier with:', {
                minQuantity: minQuantity,
                price: price
            });
            console.log('[SSPU Variants] Variant row provided:', $variantRow.length > 0);

            const $tiersBody = $variantRow.find('.volume-tiers-body');
            console.log('[SSPU Variants] Tiers body found:', $tiersBody.length > 0);

            if ($tiersBody.length === 0) {
                console.error('[SSPU Variants] ERROR: No tiers body found in variant row!');
                console.error('[SSPU Variants] Variant row structure:', $variantRow.html());
                return;
            }

            const variantIndex = $variantRow.index();
            const tierIndex = $tiersBody.find('.volume-tier-row').length;
            console.log('[SSPU Variants] Variant index:', variantIndex);
            console.log('[SSPU Variants] New tier will be at index:', tierIndex);

            const $tierTemplate = $('#sspu-tier-template');
            console.log('[SSPU Variants] Tier template found:', $tierTemplate.length > 0);

            if ($tierTemplate.length === 0) {
                console.error('[SSPU Variants] ERROR: Tier template not found!');
                return;
            }

            const templateHtml = $tierTemplate.html();
            const tierHtml = templateHtml
                .replace(/variant_options\[0\]/g, `variant_options[${variantIndex}]`)
                .replace(/\[tiers\]\[0\]/g, `[tiers][${tierIndex}]`);

            // Parse as HTML properly
            const $tierRow = $($.parseHTML(tierHtml.trim()));
            console.log('[SSPU Variants] Created tier row:', $tierRow.length > 0);

            // Set values with flexible selectors
            const $minQtyInput = $tierRow.find('input[class*="tier-min-quantity"], input[name*="min_quantity"]').first();
            const $priceInput = $tierRow.find('input[class*="tier-price"], input[name*="[price]"]').first();

            console.log('[SSPU Variants] Min qty input found:', $minQtyInput.length > 0);
            console.log('[SSPU Variants] Price input found:', $priceInput.length > 0);

            if ($minQtyInput.length > 0) {
                $minQtyInput.val(minQuantity);
                console.log('[SSPU Variants] Set min quantity to:', minQuantity);
            } else {
                console.error('[SSPU Variants] ERROR: Could not find min quantity input!');
                console.error('[SSPU Variants] Tier row HTML:', $tierRow.html());
            }

            if ($priceInput.length > 0) {
                $priceInput.val(price);
                console.log('[SSPU Variants] Set price to:', price);
            } else {
                console.error('[SSPU Variants] ERROR: Could not find price input!');
            }

            $tiersBody.append($tierRow);

            const finalTierCount = $tiersBody.find('.volume-tier-row').length;
            console.log('[SSPU Variants] Final tier count:', finalTierCount);
            console.log('[SSPU Variants] === ADD TIER (AI) END ===');
        },

        /**
         * Initiates the "Mimic All Variants" process, applying the style of one image to others.
         * @param {jQuery} $button - The button that triggered the mimic process.
         */
        mimicAllVariants: function($button) {
            const self = this;
            APP.utils.log('Starting Mimic All process...');

            const $allVariantRows = $('.sspu-variant-row');
            let $sourceRow = null;
            let sourceImageId = null;
            let sourceVariantName = 'the first variant';

            // Find the first variant with a valid image to use as the source
            $allVariantRows.each(function() {
                const $currentRow = $(this);
                const imageId = $currentRow.find('.sspu-variant-image-id').val();
                if (imageId && imageId !== '0') {
                    $sourceRow = $currentRow;
                    sourceImageId = imageId;
                    sourceVariantName = $currentRow.find('.sspu-variant-option-value').val() || sourceVariantName;
                    return false; // Break the loop
                }
            });

            if (!$sourceRow) {
                APP.utils.notify('A source variant with an image is required to mimic. Please add or edit an image for at least one variant.', 'error');
                return;
            }

            const otherVariantImages = [];
            $allVariantRows.each(function() {
                const $currentRow = $(this);
                if ($currentRow.is($sourceRow)) return; // Skip source

                const imageId = $currentRow.find('.sspu-variant-image-id').val();
                if (imageId && imageId !== '0') {
                    otherVariantImages.push(imageId);
                }
            });

            if (otherVariantImages.length === 0) {
                APP.utils.notify('No other variants with images were found to apply the style to.', 'warning');
                return;
            }

            const confirmMsg = `This will use the style from the "${sourceVariantName}" variant and apply it to ${otherVariantImages.length} other variant(s).\n\nThis process can take several minutes and cannot be undone. Continue?`;
            if (!confirm(confirmMsg)) return;

            const originalText = $button.html();
            $button.prop('disabled', true).html('<span class="spinner is-active" style="vertical-align: middle; margin-right: 5px;"></span> Processing...');
            APP.utils.notify(`Starting mimic process for ${otherVariantImages.length} images. Please be patient.`, 'info');

            // Log what is being sent
            console.log('Sending mimic request with:', {
                first_variant_image: sourceImageId,
                other_variant_images: otherVariantImages
            });

            $.ajax({
                    url: sspu_ajax.ajaxurl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'sspu_mimic_all_variants',
                        nonce: sspu_ajax.nonce,
                        first_variant_image: sourceImageId,
                        other_variant_images: otherVariantImages,
                        model: 'gemini-2.0-flash-preview-image-generation'
                    }
                })
                .done(function(response) {
                    console.log('Mimic All Response:', response);

                    if (response.success) {
                        let successCount = 0;
                        let failCount = 0;

                        if (response.data.results && response.data.results.length > 0) {
                            response.data.results.forEach(function(result) {
                                if (result.success && result.new_image_id) {
                                    // Find ALL variant rows that have this original image ID
                                    const $targetInputs = $(`.sspu-variant-image-id[value="${result.original_id}"]`);
                                    console.log(`Found ${$targetInputs.length} variant(s) with image ID ${result.original_id}`);

                                    $targetInputs.each(function() {
                                        const $targetRow = $(this).closest('.sspu-variant-row');
                                        const variantName = $targetRow.find('.sspu-variant-option-value').val();
                                        console.log(`Updating variant "${variantName}" with new image ID: ${result.new_image_id}`);

                                        // Update the variant with the new mimicked image
                                        self.setVariantImage($targetRow, {
                                            id: result.new_image_id,
                                            sizes: {
                                                thumbnail: {
                                                    url: result.new_image_url
                                                }
                                            },
                                            url: result.new_image_url,
                                            alt: 'Mimic-styled variant'
                                        });

                                        // Add visual feedback
                                        $targetRow.addClass('variant-updated');

                                        // Remove highlight after 3 seconds
                                        setTimeout(() => $targetRow.removeClass('variant-updated'), 3000);
                                    });

                                    successCount++;
                                } else {
                                    failCount++;
                                    console.error(`Failed to mimic image ${result.original_id}:`, result.error || 'Unknown error');
                                }
                            });
                        }

                        // Handle both old and new response formats
                        const processed = response.data.processed || successCount;
                        const failed = response.data.failed || failCount;

                        const message = response.data.message || `Mimic process complete! ${processed} of ${otherVariantImages.length} variants were updated successfully.`;
                        APP.utils.notify(message, processed > 0 ? 'success' : 'warning');

                        // Log final stats
                        console.log(`Mimic All Complete - Success: ${processed}, Failed: ${failed}`);
                    } else {
                        APP.utils.notify('Mimic process failed: ' + (response.data.message || 'An unknown error occurred.'), 'error');
                        console.error('Mimic process failed:', response);
                    }
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    APP.utils.notify('A network error occurred. Check the console for details.', 'error');
                    console.error('Mimic AJAX Error:', {
                        status: jqXHR.status,
                        statusText: jqXHR.statusText,
                        responseText: jqXHR.responseText,
                        textStatus: textStatus,
                        errorThrown: errorThrown
                    });
                })
                .always(function() {
                    $button.prop('disabled', false).html(originalText);
                });
        },

        /**
         * Smart Rotate All Variants - Apply smart rotate to all variant images
         * @param {jQuery} $button - The button that triggered the process
         */
        smartRotateAllVariants: function($button) {
            const self = this;
            APP.utils.log('Starting Smart Rotate All process...');

            const $allVariantRows = $('.sspu-variant-row');
            const variantsWithImages = [];

            // Collect all variants with images
            $allVariantRows.each(function() {
                const $row = $(this);
                const imageId = $row.find('.sspu-variant-image-id').val();
                const variantName = $row.find('.sspu-variant-option-value').val() || 'Variant';

                if (imageId && imageId !== '0') {
                    variantsWithImages.push({
                        row: $row,
                        imageId: imageId,
                        variantName: variantName
                    });
                }
            });

            if (variantsWithImages.length === 0) {
                APP.utils.notify('No variants with images found. Please add images to variants first.', 'warning');
                return;
            }

            const confirmMsg = `This will apply Smart Rotate to ${variantsWithImages.length} variant image(s).\n\nThis will:\n• Extract each product\n• Apply white background\n• Center and align products\n• Add professional shadows\n\nThis process may take several minutes. Continue?`;

            if (!confirm(confirmMsg)) return;

            const originalText = $button.html();
            $button.prop('disabled', true).html('<span class="spinner is-active" style="vertical-align: middle; margin-right: 5px;"></span> Processing...');
            APP.utils.notify(`Starting Smart Rotate for ${variantsWithImages.length} images. Please be patient.`, 'info');

            let processedCount = 0;
            let failedCount = 0;
            const results = [];

            // Process each variant sequentially
            function processNextVariant(index) {
                if (index >= variantsWithImages.length) {
                    // All done
                    const message = `Smart Rotate complete! Successfully processed ${processedCount} images, ${failedCount} failed.`;
                    APP.utils.notify(message, processedCount > 0 ? 'success' : 'warning');
                    $button.prop('disabled', false).html(originalText);
                    return;
                }

                const variant = variantsWithImages[index];
                const $row = variant.row;

                // Get image URL from WordPress
                wp.media.attachment(variant.imageId).fetch().done(() => {
                    const imageUrl = wp.media.attachment(variant.imageId).get('url');

                    if (!imageUrl) {
                        failedCount++;
                        processNextVariant(index + 1);
                        return;
                    }

                    // Convert image to base64
                    const img = new Image();
                    img.crossOrigin = 'anonymous';
                    img.onload = function() {
                        const canvas = document.createElement('canvas');
                        canvas.width = img.width;
                        canvas.height = img.height;
                        const ctx = canvas.getContext('2d');
                        ctx.drawImage(img, 0, 0);
                        const imageData = canvas.toDataURL('image/jpeg');

                        // Smart rotate prompt
                        const prompt = "Extract the main product and create a professional e-commerce image:\n\n" +
                            "SPECS:\n" +
                            "• Pure white background (#FFFFFF)\n" +
                            "• Center product perfectly at 0° angle (no tilt/rotation)\n" +
                            "• Product fills 80% of image area with 10% margins all sides\n" +
                            "• Subtle drop shadow: 15% opacity, soft blur, directly below\n" +
                            "• Even studio lighting, maintain original colors\n" +
                            "• Sharp focus, clean edges, no background remnants\n\n" +
                            "CRITICAL: Output must be identical each time - same size, position, margins, and shadow for consistent results.";

                        // Make API call
                        $.ajax({
                            url: sspu_ajax.ajaxurl,
                            type: "POST",
                            data: {
                                action: "sspu_ai_edit_image",
                                nonce: sspu_ajax.nonce,
                                image_data: imageData,
                                prompt: prompt,
                                model: 'gemini-2.0-flash-preview-image-generation',
                                session_id: 'smart_rotate_all_' + Date.now()
                            },
                            success: function(response) {
                                if (response.success && response.data.edited_image) {
                                    // Save the new image
                                    $.ajax({
                                        url: sspu_ajax.ajaxurl,
                                        type: "POST",
                                        data: {
                                            action: "sspu_save_edited_image",
                                            nonce: sspu_ajax.nonce,
                                            image_data: response.data.edited_image,
                                            filename: "smart-rotated-" + variant.variantName
                                        },
                                        success: function(saveResponse) {
                                            if (saveResponse.success) {
                                                // Update variant with new image
                                                self.setVariantImage($row, {
                                                    id: saveResponse.data.attachment_id,
                                                    sizes: {
                                                        thumbnail: {
                                                            url: saveResponse.data.url
                                                        }
                                                    },
                                                    url: saveResponse.data.url,
                                                    alt: 'Smart rotated variant'
                                                });

                                                // Add visual feedback
                                                $row.addClass('variant-updated');
                                                setTimeout(() => $row.removeClass('variant-updated'), 3000);

                                                processedCount++;
                                            } else {
                                                failedCount++;
                                            }

                                            // Process next after a delay
                                            setTimeout(() => processNextVariant(index + 1), 2000);
                                        },
                                        error: function() {
                                            failedCount++;
                                            setTimeout(() => processNextVariant(index + 1), 2000);
                                        }
                                    });
                                } else {
                                    failedCount++;
                                    setTimeout(() => processNextVariant(index + 1), 2000);
                                }
                            },
                            error: function() {
                                failedCount++;
                                setTimeout(() => processNextVariant(index + 1), 2000);
                            }
                        });
                    };

                    img.onerror = function() {
                        failedCount++;
                        processNextVariant(index + 1);
                    };

                    img.src = imageUrl;
                }).fail(() => {
                    failedCount++;
                    processNextVariant(index + 1);
                });
            }

            // Start processing
            processNextVariant(0);
        },

        /**
         * Generates an SKU for a variant based on product and variant details.
         * @param {jQuery} $button - The button that triggered the SKU generation.
         */
        generateSKU: function($button) {
            const $row = $button.closest('.sspu-variant-row');
            const productName = $('#product-name-input').val();
            const variantName = $row.find('.sspu-variant-option-name').val();
            const variantValue = $row.find('.sspu-variant-option-value').val();

            if (!productName || !variantValue) {
                APP.utils.notify('Please enter product name and variant value first.', 'warning');
                return;
            }

            $button.prop('disabled', true);

            APP.utils.ajax('generate_sku', {
                product_name: productName,
                variant_name: variantName,
                variant_value: variantValue
            }).done(response => {
                if (response.success) {
                    $row.find('.sspu-variant-sku').val(response.data.sku).addClass('updated-field');
                    setTimeout(() => $('.updated-field').removeClass('updated-field'), 1000);
                } else {
                    APP.utils.notify('Failed to generate SKU: ' + response.data.message, 'error');
                }
            }).always(() => $button.prop('disabled', false));
        },

        /**
         * Detect color using AI for a variant image
         * @param {jQuery} $button - The button that triggered the detection
         */
        detectColor: function($button) {
            const self = this;
            const $row = $button.closest('.sspu-variant-row');
            const imageId = $row.find('.sspu-variant-image-id').val();

            if (!imageId) {
                APP.utils.notify('Please select a variant image first.', 'warning');
                return;
            }

            // Get the image URL directly from WordPress
            $button.prop('disabled', true).text('Detecting...');

            // Get image from WordPress
            wp.media.attachment(imageId).fetch().done(() => {
                const attachment = wp.media.attachment(imageId);
                const imageUrl = attachment.get('url');

                if (!imageUrl) {
                    APP.utils.notify('Could not get image URL.', 'error');
                    $button.prop('disabled', false).text('🎨 Detect Color');
                    return;
                }

                // Convert image to base64
                const img = new Image();
                img.crossOrigin = 'anonymous';
                img.onload = function() {
                    const canvas = document.createElement('canvas');
                    canvas.width = img.width;
                    canvas.height = img.height;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0);
                    const imageData = canvas.toDataURL('image/jpeg');

                    // Call the detect color endpoint
                    APP.utils.ajax('detect_color', {
                        image_data: imageData
                    }).done(response => {
                        if (response.success && response.data.color) {
                            $row.find('.sspu-variant-option-value').val(response.data.color).addClass('updated-field');
                            APP.utils.notify(`Color detected: ${response.data.color}`, 'success');
                            setTimeout(() => $('.updated-field').removeClass('updated-field'), 1000);
                        } else {
                            APP.utils.notify('Failed to detect color.', 'error');
                        }
                    }).fail(() => {
                        APP.utils.notify('Error detecting color.', 'error');
                    }).always(() => {
                        $button.prop('disabled', false).text('🎨 Detect Color');
                    });
                };

                img.onerror = function() {
                    APP.utils.notify('Failed to load image for color detection.', 'error');
                    $button.prop('disabled', false).text('🎨 Detect Color');
                };

                img.src = imageUrl;
            }).fail(() => {
                APP.utils.notify('Could not load image from WordPress.', 'error');
                $button.prop('disabled', false).text('🎨 Detect Color');
            });
        },

        /**
         * Detect colors for all variants with images - COMPLETE VERSION
         */
        detectAllColors: function() {
            const self = this;
            console.log('Detect All Colors clicked'); // Debug log

            const $variantsWithImages = $('.sspu-variant-row').filter(function() {
                return $(this).find('.sspu-variant-image-id').val() !== '';
            });

            if ($variantsWithImages.length === 0) {
                APP.utils.notify('No variants with images found. Please add images to variants first.', 'warning');
                return;
            }

            const $btn = $('#detect-all-colors-btn');
            const originalText = $btn.text();

            $btn.prop('disabled', true).text('Detecting Colors...');

            let processedCount = 0;
            let successCount = 0;

            APP.utils.notify(`Starting color detection for ${$variantsWithImages.length} variants...`, 'info');

            // Process each variant sequentially to avoid overwhelming the API
            function processNext(index) {
                if (index >= $variantsWithImages.length) {
                    // All done
                    $btn.prop('disabled', false).text(originalText);
                    APP.utils.notify(`Color detection complete! Successfully detected ${successCount} out of ${$variantsWithImages.length} colors.`, 'success');
                    return;
                }

                const $row = $variantsWithImages.eq(index);
                const imageId = $row.find('.sspu-variant-image-id').val();
                const currentValue = $row.find('.sspu-variant-option-value').val();

                // Skip if variant already has a value
                if (currentValue && currentValue.trim() !== '') {
                    console.log(`Skipping variant ${index + 1} - already has value: ${currentValue}`);
                    processedCount++;
                    setTimeout(() => processNext(index + 1), 100); // Small delay
                    return;
                }

                // Get image and detect color
                wp.media.attachment(imageId).fetch().done(() => {
                    const attachment = wp.media.attachment(imageId);
                    const imageUrl = attachment.get('url');

                    if (!imageUrl) {
                        processedCount++;
                        setTimeout(() => processNext(index + 1), 100);
                        return;
                    }

                    // Convert image to base64
                    const img = new Image();
                    img.crossOrigin = 'anonymous';
                    img.onload = function() {
                        const canvas = document.createElement('canvas');
                        canvas.width = img.width;
                        canvas.height = img.height;
                        const ctx = canvas.getContext('2d');
                        ctx.drawImage(img, 0, 0);
                        const imageData = canvas.toDataURL('image/jpeg');

                        // Detect color
                        APP.utils.ajax('detect_color', {
                            image_data: imageData
                        }).done(response => {
                            if (response.success && response.data.color) {
                                $row.find('.sspu-variant-option-value').val(response.data.color).addClass('updated-field');
                                successCount++;
                                console.log(`Variant ${index + 1}: Detected color - ${response.data.color}`);
                            }
                        }).fail(() => {
                            console.log(`Variant ${index + 1}: Color detection failed`);
                        }).always(() => {
                            processedCount++;
                            // Add delay between requests to avoid rate limiting
                            setTimeout(() => processNext(index + 1), 1000);
                        });
                    };

                    img.onerror = function() {
                        processedCount++;
                        setTimeout(() => processNext(index + 1), 100);
                    };

                    img.src = imageUrl;
                }).fail(() => {
                    processedCount++;
                    setTimeout(() => processNext(index + 1), 100);
                });
            }

            // Start processing
            processNext(0);
        },

        /**
         * Apply design mask to all variants - FIXED VERSION
         */
        applyDesignMaskToAll: function() {
            const self = this;

            // Find the first variant with design files
            const $firstVariantWithDesign = $('.sspu-variant-row').filter(function() {
                const $row = $(this);
                const backgroundUrl = $row.find('.sspu-designer-background-url').val();
                const maskUrl = $row.find('.sspu-designer-mask-url').val();
                return backgroundUrl && maskUrl;
            }).first();

            if ($firstVariantWithDesign.length === 0) {
                APP.utils.notify('No variant with design files found. Please create design files for at least one variant first.', 'warning');
                return;
            }

            // Get the source image ID and mask URLs from the first variant
            const sourceImageId = $firstVariantWithDesign.find('.sspu-variant-image-id').val();
            const sourceBackgroundUrl = $firstVariantWithDesign.find('.sspu-designer-background-url').val();
            const sourceMaskUrl = $firstVariantWithDesign.find('.sspu-designer-mask-url').val();

            if (!sourceImageId) {
                APP.utils.notify('Source variant must have an image.', 'warning');
                return;
            }

            // Extract mask coordinates from the source mask image
            // We'll analyze the source mask to determine the crop area
            this.extractMaskCoordinatesFromImage(sourceMaskUrl, sourceImageId)
                .then(maskCoordinates => {
                    if (!maskCoordinates) {
                        APP.utils.notify('Could not extract mask coordinates from source variant.', 'error');
                        return;
                    }

                    // Get all other variants with images
                    const $otherVariants = $('.sspu-variant-row').filter(function() {
                        const $row = $(this);
                        const imageId = $row.find('.sspu-variant-image-id').val();
                        const isNotSource = !$row.is($firstVariantWithDesign);
                        return imageId && isNotSource;
                    });

                    if ($otherVariants.length === 0) {
                        APP.utils.notify('No other variants with images found.', 'warning');
                        return;
                    }

                    const confirmMsg = `This will create design files for ${$otherVariants.length} variants using the same mask area as the first variant. Continue?`;
                    if (!confirm(confirmMsg)) {
                        return;
                    }

                    const $btn = $('#apply-design-mask-to-all');
                    const originalText = $btn.text();
                    $btn.prop('disabled', true).text('Creating Design Files...');

                    let processedCount = 0;
                    let successCount = 0;

                    APP.utils.notify(`Creating design files for ${$otherVariants.length} variants...`, 'info');

                    // Process each variant with the extracted mask coordinates
                    function processNext(index) {
                        if (index >= $otherVariants.length) {
                            // All done
                            $btn.prop('disabled', false).text(originalText);
                            APP.utils.notify(`Design files created for ${successCount} out of ${$otherVariants.length} variants.`, 'success');

                            // Enable paste button for other variants
                            APP.state.copiedDesignMask = {
                                background_url: sourceBackgroundUrl,
                                mask_url: sourceMaskUrl,
                                mask_coordinates: maskCoordinates
                            };
                            $('.paste-design-mask').prop('disabled', false);

                            return;
                        }

                        const $row = $otherVariants.eq(index);
                        const imageId = $row.find('.sspu-variant-image-id').val();
                        const variantValue = $row.find('.sspu-variant-option-value').val() || `variant-${index + 1}`;

                        // Use the extracted mask coordinates to create design files for this variant
                        APP.utils.ajax('create_masked_image', {
                            image_id: imageId,
                            mask_coordinates: maskCoordinates
                        }).done(response => {
                            if (response.success) {
                                $row.find('.sspu-designer-background-url').val(response.data.background_url);
                                $row.find('.sspu-designer-mask-url').val(response.data.mask_url);
                                $row.find('.sspu-design-files-status').html('✓ Design files created');
                                successCount++;
                                console.log(`Variant ${index + 1} (${variantValue}): Design files created`);
                            } else {
                                console.log(`Variant ${index + 1} (${variantValue}): Failed - ${response.data.message}`);
                            }
                        }).fail(() => {
                            console.log(`Variant ${index + 1} (${variantValue}): Request failed`);
                        }).always(() => {
                            processedCount++;
                            // Add delay between requests
                            setTimeout(() => processNext(index + 1), 500);
                        });
                    }

                    // Start processing
                    processNext(0);
                })
                .catch(error => {
                    APP.utils.notify('Error extracting mask coordinates: ' + error.message, 'error');
                });
        },

        /**
         * Extract mask coordinates from a mask image by analyzing it
         */
        extractMaskCoordinatesFromImage: function(maskUrl, originalImageId) {
            return new Promise((resolve, reject) => {
                // Create a canvas to analyze the mask image
                const img = new Image();
                img.crossOrigin = 'anonymous';

                img.onload = function() {
                    const canvas = document.createElement('canvas');
                    canvas.width = img.width;
                    canvas.height = img.height;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0);

                    try {
                        const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                        const data = imageData.data;

                        // Find the transparent rectangle (the mask area)
                        let minX = canvas.width,
                            minY = canvas.height;
                        let maxX = 0,
                            maxY = 0;
                        let foundTransparent = false;

                        for (let y = 0; y < canvas.height; y++) {
                            for (let x = 0; x < canvas.width; x++) {
                                const index = (y * canvas.width + x) * 4;
                                const alpha = data[index + 3]; // Alpha channel

                                // If pixel is transparent (alpha < 128), it's part of the mask
                                if (alpha < 128) {
                                    foundTransparent = true;
                                    minX = Math.min(minX, x);
                                    minY = Math.min(minY, y);
                                    maxX = Math.max(maxX, x);
                                    maxY = Math.max(maxY, y);
                                }
                            }
                        }

                        if (!foundTransparent) {
                            reject(new Error('No transparent area found in mask image'));
                            return;
                        }

                        // Calculate the coordinates
                        const coordinates = {
                            x: minX,
                            y: minY,
                            width: maxX - minX + 1,
                            height: maxY - minY + 1
                        };

                        console.log('Extracted mask coordinates:', coordinates);
                        resolve(coordinates);

                    } catch (error) {
                        reject(new Error('Failed to analyze mask image: ' + error.message));
                    }
                };

                img.onerror = function() {
                    reject(new Error('Failed to load mask image'));
                };

                img.src = maskUrl;
            });
        },

        /**
         * Copy design mask URLs for pasting to other variants
         * @param {jQuery} $button - The button that triggered the copy
         */
        copyDesignMask: function($button) {
            const $row = $button.closest('.sspu-variant-row');
            const backgroundUrl = $row.find('.sspu-designer-background-url').val();
            const maskUrl = $row.find('.sspu-designer-mask-url').val();

            if (!backgroundUrl || !maskUrl) {
                APP.utils.notify('No design files found. Create them first.', 'warning');
                return;
            }

            APP.state.copiedDesignMask = {
                background_url: backgroundUrl,
                mask_url: maskUrl
            };

            $('.paste-design-mask').prop('disabled', false);
            APP.utils.notify('Design files copied! You can now paste to other variants.', 'success');
        },

        /**
         * Paste design mask URLs to a variant
         * @param {jQuery} $button - The button that triggered the paste
         */
        pasteDesignMask: function($button) {
            if (!APP.state.copiedDesignMask) {
                APP.utils.notify('No design files copied.', 'warning');
                return;
            }

            const $row = $button.closest('.sspu-variant-row');
            $row.find('.sspu-designer-background-url').val(APP.state.copiedDesignMask.background_url);
            $row.find('.sspu-designer-mask-url').val(APP.state.copiedDesignMask.mask_url);
            $row.find('.sspu-design-files-status').html('✓ Design files pasted');

            APP.utils.notify('Design files pasted successfully!', 'success');
        }
    };

})(jQuery, window.SSPU);