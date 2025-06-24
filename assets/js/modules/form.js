/**
 * SSPU Form Management Module - Complete Remake
 *
 * Handles the main product uploader form with comprehensive error handling,
 * modern JavaScript practices, and detailed debugging.
 */
window.SSPU = window.SSPU || {};

(function($, APP) {
    'use strict';

    APP.form = {
        // Configuration
        config: {
            autoSaveInterval: 30000, // 30 seconds
            autoSaveDelay: 5000,     // 5 seconds after last change
            maxRetries: 3,
            retryDelay: 2000
        },

        // State management
        state: {
            isInitialized: false,
            isDirty: false,
            lastSavedData: null,
            retryCount: 0,
            formOpenTime: Date.now(), // Track when form was loaded
            autoSaveTimer: null       // Timer for debounced auto-save
        },

        // Cached DOM elements
        elements: {},

        /**
         * Initialize the form module
         */
        init() {
            // Prevent double initialization
            if (this.state.isInitialized) {
                console.warn('SSPU Form: Already initialized');
                return;
            }

            console.log('SSPU Form: Initializing...');

            // Cache DOM elements
            this.cacheElements();

            // Verify required elements exist
            if (!this.verifyElements()) {
                console.error('SSPU Form: Required elements missing');
                return;
            }

            // Initialize components
            this.initTabs();
            this.initSortables();
            this.initDragDrop();
            this.initSeoCounters();
            this.initAutoSave();
            this.initValidation();

            // Bind events
            this.bindEvents();

            // Mark as initialized
            this.state.isInitialized = true;
            console.log('SSPU Form: Initialization complete');
        },

        /**
         * Cache frequently used DOM elements
         */
        cacheElements() {
            this.elements = {
                form: $('#sspu-product-form'),
                submitButton: $('#sspu-submit-button'),
                submitButtonBottom: $('#sspu-submit-button-bottom'), // Added in new script
                spinner: $('.spinner'),
                statusBox: $('#sspu-status-box'),
                statusHeading: $('#sspu-status-heading'),
                statusLog: $('#sspu-status-log'),
                progressBar: $('#sspu-progress-bar'),
                progressFill: $('#sspu-progress-fill'),
                progressText: $('#sspu-progress-text'),
                tabs: $('#sspu-tabs'),
                variantsWrapper: $('#sspu-variants-wrapper'),
                dropZone: $('#sspu-image-drop-zone'),
                mainImagePreview: $('#sspu-main-image-preview'),
                additionalImagesPreview: $('#sspu-additional-images-preview'),
                collectionSelect: $('#sspu-collection-select'),
                autoSaveStatus: $('.sspu-auto-save-status')
            };
        },

        /**
         * Verify all required elements exist
         */
        verifyElements() {
            const required = ['form', 'submitButton', 'tabs'];

            for (let element of required) {
                if (!this.elements[element] || this.elements[element].length === 0) {
                    console.error(`SSPU Form: Missing required element: ${element}`);
                    return false;
                }
            }

            return true;
        },

        /**
         * Bind all event handlers
         */
        bindEvents() {
            const self = this;

            // Form submission - Handle both submit buttons
            this.elements.form.on('submit', (e) => {
                e.preventDefault();
                self.submit();
            });

            // Handle both submit buttons (top and bottom)
            this.elements.submitButton.on('click', (e) => {
                e.preventDefault();
                self.submit();
            });

            if (this.elements.submitButtonBottom.length) {
                this.elements.submitButtonBottom.on('click', (e) => {
                    e.preventDefault();
                    self.submit();
                });
            }

            // Draft management
            $('#sspu-save-draft').on('click', () => self.saveDraft(false));
            $('#sspu-load-draft').on('click', () => self.loadDraft());

            // Track form changes
            this.elements.form.on('change input', 'input, select, textarea', (e) => {
                // Skip certain fields
                if ($(e.target).hasClass('no-autosave')) return;

                self.state.isDirty = true;
                self.scheduleAutoSave();
            });

            // Image upload buttons
            $(document).on('click', '.sspu-upload-image-btn', (e) => {
                e.preventDefault();
                self.handleImageUpload($(e.currentTarget));
            });

            // Image removal
            $(document).on('click', '.remove-image', (e) => {
                e.preventDefault();
                self.removeImage($(e.currentTarget));
            });

            // Keyboard shortcuts
            $(document).on('keydown', (e) => {
                if ((e.ctrlKey || e.metaKey)) {
                    if (e.key === 's') {
                        e.preventDefault();
                        self.saveDraft(false);
                    }
                    if (e.key === 'Enter' && !APP.state.isSubmitting) {
                        e.preventDefault();
                        self.submit();
                    }
                }
            });

            // Window unload warning
            $(window).on('beforeunload', (e) => {
                if (self.state.isDirty && !APP.state.isSubmitting) {
                    return 'You have unsaved changes. Are you sure you want to leave?';
                }
            });

            // Debug mode toggle
            if (window.location.hash === '#debug') {
                this.enableDebugMode();
            }

            // Setup automatic nonce refresh (added in new script)
            this.setupNonceRefresh();
        },

        /**
         * Setup automatic nonce refresh (Added function)
         */
        setupNonceRefresh() {
            const self = this;

            // Refresh nonce every 10 minutes to prevent expiration
            setInterval(() => {
                self.refreshNonce();
            }, 10 * 60 * 1000); // 10 minutes

            // Also refresh before each submission (explicitly on button click)
            $(document).on('click', '#sspu-submit-button, #sspu-submit-button-bottom', () => {
                // This call in submit() already handles it, but keeps explicit refresh for UX
                self.refreshNonce();
            });
        },

        /**
         * Refresh the security nonce
         */
        /**
         * Refresh the security nonce - FIXED VERSION
         */
        refreshNonce() {
            console.log('SSPU Form: Refreshing security nonce...');

            // Return the AJAX promise so it can be chained
            return $.ajax({
                url: sspu_ajax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'sspu_refresh_nonce',
                    sspu_nonce: sspu_ajax.nonce  // FIXED: Use sspu_nonce instead of nonce
                },
                dataType: 'json'
            }).done((response) => {
                if (response.success && response.data.nonce) {
                    // Update both the hidden field and the global nonce
                    $('#sspu_nonce').val(response.data.nonce);
                    sspu_ajax.nonce = response.data.nonce;  // ADDED: Update global nonce
                    console.log('SSPU Form: Nonce refreshed successfully');
                } else {
                    console.warn('SSPU Form: Failed to refresh nonce:', response);
                }
            }).fail((xhr) => {
                console.error('SSPU Form: Failed to refresh nonce:', xhr);
            });
        },

        /**
         * Initialize jQuery UI tabs
         */
        initTabs() {
            this.elements.tabs.tabs({
                activate: (event, ui) => {
                    ui.newPanel.hide().fadeIn(300);

                    // Trigger resize event for any components that need it
                    $(window).trigger('resize');

                    // Log tab change in debug mode
                    APP.utils.log('Tab changed to: ' + ui.newTab.text());
                }
            });
        },

        /**
         * Initialize sortable functionality
         */
        initSortables() {
            // Image sortables
            $('.sortable-images').sortable({
                tolerance: 'pointer',
                cursor: 'move',
                placeholder: 'sortable-placeholder',
                helper: 'clone',
                update: (event, ui) => {
                    const $container = ui.item.parent();
                    const $idField = $container.siblings('input[type="hidden"]');
                    this.updateImageOrder($container, $idField);
                    this.state.isDirty = true;
                }
            });

            // Variant sortable
            this.elements.variantsWrapper.sortable({
                handle: '.drag-handle',
                tolerance: 'pointer',
                cursor: 'move',
                placeholder: 'sortable-placeholder',
                update: () => {
                    if (APP.variants) {
                        APP.variants.updateNumbers();
                    }
                    this.state.isDirty = true;
                }
            });
        },

        /**
         * Initialize drag and drop
         */
        initDragDrop() {
            const $dropZone = this.elements.dropZone;
            if ($dropZone.length === 0) return;

            // Prevent default drag behaviors
            $(document).on('dragenter dragover', (e) => {
                e.preventDefault();
                e.stopPropagation();
            });

            $dropZone.on({
                'dragenter': (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    $dropZone.addClass('drag-over');
                },
                'dragover': (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                },
                'dragleave': (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    // Ensure dragleave only triggers if leaving the actual dropzone element
                    if (e.target === $dropZone[0]) {
                        $dropZone.removeClass('drag-over');
                    }
                },
                'drop': (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    $dropZone.removeClass('drag-over');

                    const files = e.originalEvent.dataTransfer.files;
                    if (files && files.length > 0) {
                        this.handleFileUpload(files);
                    }
                },
                'click': () => {
                    // Trigger file input click
                    const $fileInput = $('<input type="file" multiple accept="image/*" />');
                    $fileInput.on('change', (e) => {
                        if (e.target.files && e.target.files.length > 0) {
                            this.handleFileUpload(e.target.files);
                        }
                    });
                    $fileInput.click();
                }
            });
        },

        /**
         * Initialize SEO character counters
         */
        initSeoCounters() {
            const counters = [
                { selector: 'input[name="seo_title"]', limit: 60 },
                { selector: 'textarea[name="meta_description"]', limit: 160 }
            ];

            counters.forEach(({ selector, limit }) => {
                $(selector).on('input', function() {
                    const length = this.value.length;
                    const $counter = $(this).siblings('.seo-feedback').find('.char-count');
                    $counter.text(`${length}/${limit}`);

                    if (length > limit) {
                        $counter.addClass('over-limit');
                    } else {
                        $counter.removeClass('over-limit');
                    }
                });
            });

            // Product name length indicator
            $('input[name="product_name"]').on('input', function() {
                const length = this.value.length;
                const $feedback = $(this).siblings('.seo-feedback').find('.title-length');

                if ($feedback.length) {
                    $feedback.text(`Title length: ${length} characters`);
                    $feedback.toggleClass('warning', length > 70);
                }
            });
        },

        /**
         * Initialize auto-save functionality
         */
        initAutoSave() {
            // Periodic auto-save
            setInterval(() => {
                if (this.state.isDirty && !APP.state.isSubmitting) {
                    this.saveDraft(true);
                }
            }, this.config.autoSaveInterval);
        },

        /**
         * Schedule an auto-save after user stops typing
         */
        scheduleAutoSave() {
            clearTimeout(this.state.autoSaveTimer); // Use this.state.autoSaveTimer
            
            this.state.autoSaveTimer = setTimeout(() => { // Assign to this.state
                if (this.state.isDirty && !APP.state.isSubmitting) {
                    this.saveDraft(true);
                }
            }, this.config.autoSaveDelay);
        },

        /**
         * Initialize form validation
         */
        initValidation() {
            // Add HTML5 validation attributes
            $('input[name="product_name"]').attr({
                'required': true,
                'minlength': 3,
                'maxlength': 255
            });

            // Custom validation for variants
            this.elements.form.on('submit', (e) => {
                if (!this.validate()) {
                    e.preventDefault();
                    return false;
                }
            });
        },

        /**
         * Clean up the variant array to ensure proper indexing
         */
        cleanupVariants() {
            console.log('SSPU Form: Cleaning up variants...');

            // Re-index all visible variants
            let index = 0;
            $('.sspu-variant-row:visible').each(function() {
                const $row = $(this);
                const variantValue = $row.find('.sspu-variant-option-value').val();

                if (variantValue && variantValue.trim() !== '') {
                    // Update all input names to use the correct index
                    $row.find('input, select, textarea').each(function() {
                        const name = $(this).attr('name');
                        if (name && name.includes('variant_options[')) {
                            const newName = name.replace(/variant_options\[\d+\]/, `variant_options[${index}]`);
                            $(this).attr('name', newName);
                        }
                    });

                    // Update the variant number display
                    $row.find('.variant-number').text(index + 1);

                    index++;
                } else {
                    // Hide empty variants
                    $row.hide();
                }
            });

            console.log('SSPU Form: Cleanup complete. Valid variants:', index);
        },

        /**
         * Validate form before submission
         */
        validate() {
            console.log('SSPU Form: Validating...');

            const errors = [];

            // Product name validation
            const productName = $('input[name="product_name"]').val().trim();
            if (!productName) {
                errors.push({
                    field: 'product_name',
                    message: 'Product name is required',
                    tab: 0
                });
            } else if (productName.length < 3) {
                errors.push({
                    field: 'product_name',
                    message: 'Product name must be at least 3 characters',
                    tab: 0
                });
            }

            // Variant validation - only check visible variants with values
            const $visibleVariants = $('.sspu-variant-row:visible');
            let validVariantCount = 0;

            $visibleVariants.each(function(index) {
                const $row = $(this);
                const variantValue = $row.find('.sspu-variant-option-value').val();

                // Skip empty variants
                if (!variantValue || variantValue.trim() === '') {
                    console.log(`SSPU Form: Skipping empty variant at index ${index}`);
                    return true; // continue to next iteration
                }

                validVariantCount++;

                // Now validate this variant's price
                const variantPrice = $row.find('.sspu-variant-price').val();
                if (!variantPrice || parseFloat(variantPrice) <= 0) {
                    errors.push({
                        field: `variant_${index}_price`,
                        message: `Variant "${variantValue}" must have a valid price`,
                        tab: 4
                    });
                }
            });

            // Check if we have at least one valid variant
            if (validVariantCount === 0) {
                errors.push({
                    field: 'variants',
                    message: 'At least one variant with a value is required',
                    tab: 4
                });
            }

            // Image validation (optional but recommended)
            const hasMainImage = $('#sspu-main-image-id').val();
            if (!hasMainImage) {
                console.warn('SSPU Form: No main image selected');
            }

            // Display errors
            if (errors.length > 0) {
                console.error('SSPU Form: Validation errors:', errors);

                // Show first error
                const firstError = errors[0];
                APP.utils.notify(firstError.message, 'error');

                // Switch to tab with error
                this.elements.tabs.tabs('option', 'active', firstError.tab);

                // Focus on error field
                if (firstError.field.startsWith('variant_')) {
                    const variantIndex = parseInt(firstError.field.split('_')[1]);
                    $('.sspu-variant-row').eq(variantIndex).find('.error').first().focus();
                } else {
                    $(`[name="${firstError.field}"]`).focus().addClass('error');
                }

                return false;
            }

            console.log('SSPU Form: Validation passed');
            return true;
        },

        /**
         * Collect all form data
         */
        collectFormData() {
            console.log('SSPU Form: Collecting form data...');

            const formData = {
                // Basic info
                product_name: $('input[name="product_name"]').val().trim(),
                product_description: APP.utils.getEditorContent('product_description'),
                product_tags: $('input[name="product_tags"]').val().trim(),

                // Collections
                product_collections: this.elements.collectionSelect.val() || [],

                // SEO
                seo_title: $('input[name="seo_title"]').val().trim(),
                meta_description: $('textarea[name="meta_description"]').val().trim(),
                url_handle: $('input[name="url_handle"]').val().trim(),

                // Images
                main_image_id: $('input[name="main_image_id"]').val(),
                additional_image_ids: $('input[name="additional_image_ids"]').val(),

                // Metafields
                print_methods: $('input[name="print_methods[]"]:checked').map((i, el) => el.value).get(),
                product_min: $('input[name="product_min"]').val() || '',
                product_max: $('input[name="product_max"]').val() || '',

                // Variants
                variant_options: []
            };

            // Collect only valid variant data
            let variantIndex = 0;
            $('.sspu-variant-row:visible').each(function() {
                const $row = $(this);
                const variantValue = $row.find('.sspu-variant-option-value').val();

                // Skip empty variants
                if (!variantValue || variantValue.trim() === '') {
                    console.log(`SSPU Form: Skipping empty variant row at DOM index ${$row.index()}`);
                    return true; // continue to next iteration
                }

                const variantData = {
                    name: $row.find('.sspu-variant-option-name').val().trim() || 'Option',
                    value: variantValue.trim(),
                    price: $row.find('.sspu-variant-price').val() || '0',
                    sku: $row.find('.sspu-variant-sku').val().trim(),
                    weight: $row.find('.sspu-variant-weight').val() || '0',
                    image_id: $row.find('.sspu-variant-image-id').val(),
                    designer_background_url: $row.find('.sspu-designer-background-url').val(),
                    designer_mask_url: $row.find('.sspu-designer-mask-url').val(),
                    tiers: []
                };

                // Collect tier data - using the getTierData method from variants module
                if (APP.variants && APP.variants.getTierData) {
                    variantData.tiers = APP.variants.getTierData($row);
                } else {
                    // Fallback if variants module isn't available
                    $row.find('.volume-tier-row').each(function() {
                        const $tierRow = $(this);
                        const minQty = $tierRow.find('.tier-min-quantity').val();
                        const tierPrice = $tierRow.find('.tier-price').val();

                        if (minQty && tierPrice) {
                            variantData.tiers.push({
                                min_quantity: parseInt(minQty),
                                price: parseFloat(tierPrice)
                            });
                        }
                    });
                }

                formData.variant_options.push(variantData);
                variantIndex++;
            });

            console.log('SSPU Form: Form data collected:', formData);
            console.log('SSPU Form: Valid variants collected:', formData.variant_options.length);

            return formData;
        },

        /**
         * Submit form to Shopify
         */
        submit() {
            console.log('=== SSPU FORM SUBMISSION STARTED ===');
            console.log('Timestamp:', new Date().toISOString());

            // Check if already submitting
            if (APP.state.isSubmitting) {
                console.warn('SSPU Form: Submission already in progress');
                APP.utils.notify('Please wait, submission in progress...', 'warning');
                return;
            }

            // Clean up variants before validation
            this.cleanupVariants();

            // Validate form
            if (!this.validate()) {
                console.error('SSPU Form: Validation failed');
                return;
            }

            // Set submitting state
            APP.state.isSubmitting = true;
            this.state.isDirty = false;

            // UI updates
            this.elements.submitButton.prop('disabled', true).text('Submitting...');
            if (this.elements.submitButtonBottom.length) {
                this.elements.submitButtonBottom.prop('disabled', true).text('Submitting...');
            }
            this.elements.spinner.addClass('is-active');
            this.showProgress();

            // Collect form data
            const formData = this.collectFormData();

            // Always refresh nonce before submission (new script logic)
            // This ensures we always have a fresh nonce for the submission itself
            this.refreshNonce().always(() => {
                this.performSubmission(formData);
            });
        },

        /**
         * Perform the actual form submission
         */
        performSubmission(formData) {
            // Get the current nonce value (which should be fresh after refreshNonce call)
            const nonce = $('#sspu_nonce').val();

            // Prepare AJAX data
            const ajaxData = {
                ...formData,
                action: 'sspu_submit_product',
                sspu_nonce: nonce
            };

            console.log('SSPU Form: Submitting with data:', ajaxData);
            console.log('SSPU Form: Nonce value:', nonce ? nonce.substring(0, 10) + '...' : 'missing');

            // Make AJAX request
            $.ajax({
                url: sspu_ajax.ajaxurl,
                type: 'POST',
                data: ajaxData,
                dataType: 'json',
                timeout: 120000, // 2 minute timeout
                beforeSend: (xhr, settings) => {
                    console.log('SSPU Form: AJAX request initiated');
                    console.log('URL:', settings.url);
                    console.log('Data keys:', Object.keys(ajaxData));
                }
            })
            .done((response) => this.handleSubmissionSuccess(response))
            .fail((xhr, textStatus, errorThrown) => this.handleSubmissionError({
                xhr: xhr,
                textStatus: textStatus,
                errorThrown: errorThrown
            }))
            .always(() => this.resetSubmissionState());
        },

        /**
         * Handle successful submission
         */
        handleSubmissionSuccess(response) {
            console.log('SSPU Form: Submission response received:', response);

            // Update progress
            this.updateProgress(100);

            if (response.success) {
                console.log('SSPU Form: Product created successfully!');
                console.log('Product ID:', response.data.product_id);
                console.log('Product URL:', response.data.product_url);

                // Update UI
                this.elements.statusBox.removeClass('processing error').addClass('success');
                this.elements.statusHeading.html(
                    `Success! <a href="${response.data.product_url}" target="_blank" class="button button-primary">View Product</a>`
                );

                // Display log
                if (response.data.log && Array.isArray(response.data.log)) {
                    this.elements.statusLog.text(response.data.log.join('\n'));
                }

                // Success notification
                APP.utils.notify('Product uploaded successfully!', 'success');

                // Ask to clear form
                setTimeout(() => {
                    if (confirm('Product uploaded! Would you like to clear the form for the next product?')) {
                        this.clear();
                        this.elements.statusBox.fadeOut();
                    }
                }, 2000);

            } else {
                console.error('SSPU Form: Submission failed:', response);

                // Update UI
                this.elements.statusBox.removeClass('processing success').addClass('error');
                this.elements.statusHeading.text('Submission Failed');

                // Build error message
                let errorMessage = response.data?.message || 'An unknown error occurred';

                if (response.data?.log && Array.isArray(response.data.log)) {
                    errorMessage += '\n\nDetails:\n' + response.data.log.join('\n');
                }

                if (response.data?.shopify_errors) {
                    errorMessage += '\n\nShopify Errors:\n' + JSON.stringify(response.data.shopify_errors, null, 2);
                }

                this.elements.statusLog.text(errorMessage);

                // Error notification
                APP.utils.notify('Failed to upload product. See details below.', 'error');
            }
        },

        /**
         * Handle submission error
         */
        handleSubmissionError(error) {
            console.error('SSPU Form: Submission error:', error);

            // Update UI
            this.elements.statusBox.removeClass('processing success').addClass('error');
            this.elements.statusHeading.text('Submission Error');

            let errorMessage = '';

            if (error.message) {
                // Direct error message
                errorMessage = error.message;
            } else if (error.xhr) {
                // AJAX error
                errorMessage = `Network Error: ${error.textStatus}\n`;

                if (error.xhr.status === 0) {
                    errorMessage += 'Could not connect to server. Please check your internet connection.';
                } else if (error.xhr.status === 403) {
                    errorMessage += 'Access denied. Please refresh the page and try again.'; // Changed message slightly
                } else if (error.xhr.status === 500) {
                    errorMessage += 'Server error. Please check the error logs.';
                } else if (error.xhr.status === 504) {
                    errorMessage += 'Request timeout. The server took too long to respond.';
                } else {
                    errorMessage += `HTTP ${error.xhr.status}: ${error.xhr.statusText}`;
                }

                // Try to parse response
                if (error.xhr.responseText) {
                    try {
                        const jsonResponse = JSON.parse(error.xhr.responseText);
                        if (jsonResponse.data?.message) {
                            errorMessage += '\n\n' + jsonResponse.data.message;
                        }
                    } catch (e) {
                        // Show raw response (truncated)
                        errorMessage += '\n\nRaw response:\n' + error.xhr.responseText.substring(0, 500);
                    }
                }
            }

            this.elements.statusLog.text(errorMessage);
            APP.utils.notify('Submission failed. See error details below.', 'error');

            // Log full error for debugging
            console.error('Full error object:', error);
        },

        /**
         * Reset submission state
         */
        resetSubmissionState() {
            console.log('SSPU Form: Resetting submission state');

            APP.state.isSubmitting = false;
            this.elements.submitButton.prop('disabled', false).text('Upload Product to Shopify');
            if (this.elements.submitButtonBottom.length) { // Also reset bottom button
                this.elements.submitButtonBottom.prop('disabled', false).text('Upload Product to Shopify');
            }
            this.elements.spinner.removeClass('is-active');

            // Hide progress after delay
            setTimeout(() => {
                this.elements.progressBar.fadeOut();
            }, 2000);
        },

        /**
         * Show progress indicator
         */
        showProgress() {
            this.elements.statusBox.removeClass('success error').addClass('processing').show();
            this.elements.statusHeading.text('Uploading Product...');
            this.elements.statusLog.text('Preparing product data...');
            this.elements.progressBar.show();
            this.updateProgress(0);

            // Simulate progress
            this.progressInterval = setInterval(() => {
                const currentProgress = parseInt(this.elements.progressFill.css('width')) || 0;
                const maxWidth = this.elements.progressBar.width();
                const currentPercent = (currentProgress / maxWidth) * 100;

                if (currentPercent < 90) {
                    this.updateProgress(currentPercent + Math.random() * 10);
                }
            }, 800);
        },

        /**
         * Update progress bar
         */
        updateProgress(percent) {
            this.elements.progressFill.css('width', percent + '%');
            this.elements.progressText.text(Math.round(percent) + '%');

            if (percent >= 100) {
                clearInterval(this.progressInterval);
            }
        },

        /**
         * Save draft
         */
        saveDraft(isAutoSave = false) {
            console.log(`SSPU Form: ${isAutoSave ? 'Auto-saving' : 'Saving'} draft...`);

            const formData = this.collectFormData();

            // Check if data has changed
            if (this.state.lastSavedData &&
                JSON.stringify(formData) === JSON.stringify(this.state.lastSavedData)) {
                console.log('SSPU Form: No changes to save');
                return;
            }

            const $status = this.elements.autoSaveStatus;
            $status.text(isAutoSave ? 'Auto-saving...' : 'Saving...').show();

            APP.utils.ajax(isAutoSave ? 'auto_save_draft' : 'save_draft', {
                draft_data: formData
            })
            .done((response) => {
                if (response.success) {
                    console.log('SSPU Form: Draft saved successfully');

                    this.state.isDirty = false;
                    this.state.lastSavedData = formData;

                    const time = new Date().toLocaleTimeString();
                    $status.text((isAutoSave ? 'Auto-saved' : 'Saved') + ' at ' + time);

                    if (!isAutoSave) {
                        APP.utils.notify('Draft saved successfully!', 'success');
                    }

                    setTimeout(() => $status.fadeOut(), 3000);
                } else {
                    console.error('SSPU Form: Failed to save draft:', response);
                    $status.text('Save failed').addClass('error');
                }
            })
            .fail((xhr) => {
                console.error('SSPU Form: Draft save error:', xhr);
                $status.text('Save failed').addClass('error');

                if (!isAutoSave) {
                    APP.utils.notify('Failed to save draft', 'error');
                }
            });
        },

        /**
         * Load draft
         */
        loadDraft() {
            console.log('SSPU Form: Loading draft...');

            if (this.state.isDirty) {
                if (!confirm('You have unsaved changes. Loading a draft will overwrite them. Continue?')) {
                    return;
                }
            }

            APP.utils.ajax('load_draft')
            .done((response) => {
                if (response.success && response.data.draft_data) {
                    console.log('SSPU Form: Draft loaded successfully');
                    this.populateFromDraft(response.data.draft_data);
                    this.state.isDirty = false;
                    APP.utils.notify('Draft loaded successfully!', 'success');
                } else {
                    console.log('SSPU Form: No draft found');
                    APP.utils.notify('No draft found', 'info');
                }
            })
            .fail((xhr) => {
                console.error('SSPU Form: Failed to load draft:', xhr);
                APP.utils.notify('Failed to load draft', 'error');
            });
        },

        /**
         * Populate form from draft data
         */
        populateFromDraft(data) {
            console.log('SSPU Form: Populating form from draft data');

            // Basic fields
            $('input[name="product_name"]').val(data.product_name || '').trigger('input');
            $('input[name="product_tags"]').val(data.product_tags || '');
            $('input[name="seo_title"]').val(data.seo_title || '').trigger('input');
            $('textarea[name="meta_description"]').val(data.meta_description || '').trigger('input');
            $('input[name="url_handle"]').val(data.url_handle || '');
            $('input[name="product_min"]').val(data.product_min || '');
            $('input[name="product_max"]').val(data.product_max || '');

            // Description
            if (data.product_description) {
                APP.utils.setEditorContent('product_description', data.product_description);
            }

            // Collections
            if (data.product_collections) {
                const collections = Array.isArray(data.product_collections)
                    ? data.product_collections
                    : [data.product_collections];
                this.elements.collectionSelect.val(collections);
                if (APP.collections && APP.collections.updateCount) {
                    APP.collections.updateCount();
                }
            }

            // Images
            if (data.main_image_id) {
                $('input[name="main_image_id"]').val(data.main_image_id);
                this.loadImagePreviews('main', data.main_image_id, this.elements.mainImagePreview);
            }

            if (data.additional_image_ids) {
                $('input[name="additional_image_ids"]').val(data.additional_image_ids);
                this.loadImagePreviews('additional', data.additional_image_ids, this.elements.additionalImagesPreview);
            }

            // Print methods
            $('input[name="print_methods[]"]').prop('checked', false);
            if (data.print_methods && Array.isArray(data.print_methods)) {
                data.print_methods.forEach(method => {
                    $(`input[name="print_methods[]"][value="${method}"]`).prop('checked', true);
                });
            }

            // Variants
            this.elements.variantsWrapper.empty();
            APP.state.variantCounter = 0; // Ensure variantCounter is reset
            if (APP.variants && APP.variants.initAddButton) { // Re-initialize add variant button if needed
                APP.variants.initAddButton();
            }


            if (data.variant_options && Array.isArray(data.variant_options)) {
                data.variant_options.forEach(variant => {
                    if (APP.variants && APP.variants.add) {
                        APP.variants.add(variant);

                        // Add tiers if present
                        if (variant.tiers && Array.isArray(variant.tiers)) {
                            const $lastRow = $('.sspu-variant-row:last');
                            variant.tiers.forEach(tier => {
                                if (APP.variants.addTier) {
                                    APP.variants.addTier($lastRow, tier.min_quantity, tier.price);
                                }
                            });
                        }

                        // Load variant image if present
                        if (variant.image_id) {
                            const $lastRow = $('.sspu-variant-row:last');
                            this.loadImagePreviews('variant', variant.image_id,
                                $lastRow.find('.sspu-variant-image-preview'));
                        }
                    }
                });
            }
             // After populating, ensure variants are cleaned up and re-indexed
             this.cleanupVariants();
             if (APP.variants && APP.variants.updateNumbers) {
                 APP.variants.updateNumbers();
             }
        },

        /**
         * Load image previews
         */
        loadImagePreviews(type, imageIds, $container) {
            if (!imageIds || !$container || $container.length === 0) return;

            const ids = Array.isArray(imageIds) ? imageIds : imageIds.toString().split(',');
            $container.empty();

            ids.forEach(id => {
                if (!id || !wp.media || !wp.media.attachment) return;

                const attachment = wp.media.attachment(id);
                attachment.fetch().done(() => {
                    const data = attachment.attributes;
                    if (data && data.url) {
                        const thumbUrl = data.sizes?.thumbnail?.url || data.url;
                        const $img = $('<img>', {
                            src: thumbUrl,
                            alt: data.alt || '',
                            'data-id': id,
                            class: 'attachment-thumbnail'
                        });

                        // Add remove button for additional images
                        if (type === 'additional') {
                            const $wrapper = $('<div class="image-preview-item">');
                            $wrapper.append($img);
                            $wrapper.append('<button type="button" class="remove-image" title="Remove">&times;</button>');
                            $container.append($wrapper);
                        } else if (type === 'variant') { // Special handling for variant images
                            const $wrapper = $('<div class="image-preview-item">');
                            $wrapper.append($img);
                            $wrapper.append('<button type="button" class="remove-image" title="Remove">&times;</button>'); // Add remove button
                            $container.append($wrapper);
                            // Hide the AI edit button if the image is removed later or initially
                            $container.closest('.sspu-variant-image-upload').find('.sspu-ai-edit-variant-image').show(); // Ensure AI edit button shows
                        }
                        else {
                            $container.append($img);
                        }
                    }
                }).fail(() => {
                    console.warn(`SSPU Form: Failed to load image preview for ID: ${id}`);
                });
            });
        },

        /**
         * Handle image upload
         */
        handleImageUpload($button) {
            const isMultiple = $button.data('multiple') === true;
            const targetId = $button.data('target-id');
            const targetPreview = $button.data('target-preview');

            // For variant images, find the elements relative to the button
            let $preview, $idField;

            if ($button.data('target-id-class')) {
                // Variant image - find within same row
                const $row = $button.closest('.sspu-variant-row');
                $preview = $row.find('.' + $button.data('target-preview-class'));
                $idField = $row.find('.' + $button.data('target-id-class'));
            } else {
                // Regular image fields
                $preview = $('#' + targetPreview);
                $idField = $('#' + targetId);
            }

            if (!window.wp || !window.wp.media) {
                APP.utils.notify('Media library not available', 'error');
                return;
            }

            const frame = wp.media({
                title: 'Select Image' + (isMultiple ? 's' : ''),
                button: {
                    text: 'Use Image' + (isMultiple ? 's' : '')
                },
                multiple: isMultiple,
                library: {
                    type: 'image'
                }
            });

            frame.on('select', () => {
                const attachments = frame.state().get('selection').toJSON();

                if (!isMultiple) {
                    // Single image
                    $preview.empty(); // Clear existing preview for single image
                    if (attachments[0]) {
                        const attachment = attachments[0];
                        const thumbUrl = attachment.sizes?.thumbnail?.url || attachment.url;

                        $preview.append($('<img>', {
                            src: thumbUrl,
                            alt: attachment.alt || '',
                            'data-id': attachment.id,
                            class: 'attachment-thumbnail' // Ensure consistent class
                        }));

                        $idField.val(attachment.id);

                        // Show AI edit button for variants if a variant image is selected
                        if ($button.data('target-id-class')) {
                            $button.siblings('.sspu-ai-edit-variant-image').show();
                        }
                    } else { // If no image selected (e.g., cleared selection)
                        $idField.val('');
                        if ($button.data('target-id-class')) {
                            $button.siblings('.sspu-ai-edit-variant-image').hide();
                        }
                    }
                } else {
                    // Multiple images
                    const currentIds = $idField.val() ? $idField.val().split(',') : [];

                    attachments.forEach(attachment => {
                        if (!currentIds.includes(attachment.id.toString())) {
                            currentIds.push(attachment.id);

                            const $wrapper = $('<div class="image-preview-item">');
                            const thumbUrl = attachment.sizes?.thumbnail?.url || attachment.url;

                            $wrapper.append($('<img>', {
                                src: thumbUrl,
                                alt: attachment.alt || '',
                                'data-id': attachment.id,
                                class: 'attachment-thumbnail' // Ensure consistent class
                            }));
                            $wrapper.append('<button type="button" class="remove-image" title="Remove">&times;</button>');

                            $preview.append($wrapper);
                        }
                    });

                    $idField.val(currentIds.join(','));
                }

                this.state.isDirty = true;
            });

            frame.open();
        },

        /**
         * Remove image
         */
        removeImage($button) {
            const $item = $button.closest('.image-preview-item');
            const $container = $item.parent();
            const imageId = $item.find('img').data('id');

            // Determine the correct hidden input field based on the container
            let $idField;
            if ($container.attr('id') === 'sspu-main-image-preview') {
                $idField = $('#sspu-main-image-id');
            } else if ($container.attr('id') === 'sspu-additional-images-preview') {
                $idField = $('#sspu-additional-image-ids');
            } else if ($container.hasClass('sspu-variant-image-preview')) { // For variant images
                $idField = $container.siblings('.sspu-variant-image-id');
                // Also hide AI edit button if it's a variant image
                $container.closest('.sspu-variant-image-upload').find('.sspu-ai-edit-variant-image').hide();
            } else {
                console.warn('SSPU Form: Could not determine ID field for image removal.');
                return;
            }

            // Remove from IDs
            const currentIds = $idField.val() ? $idField.val().split(',') : [];
            const newIds = currentIds.filter(id => id !== imageId.toString());
            $idField.val(newIds.join(','));

            // Remove from preview
            $item.fadeOut(200, function() {
                $(this).remove();
            });

            this.state.isDirty = true;
        },

        /**
         * Update image order
         */
        updateImageOrder($container, $idField) {
            const ids = [];
            $container.find('img').each(function() {
                const id = $(this).data('id');
                if (id) ids.push(id);
            });
            $idField.val(ids.join(','));
            this.state.isDirty = true; // Mark form as dirty after reordering
        },

        /**
         * Handle file upload (drag & drop)
         */
        handleFileUpload(files) {
            console.log('SSPU Form: Handling file upload, files:', files.length);

            const allowedTypes = sspu_ajax.allowed_image_types || ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            const maxSize = sspu_ajax.max_file_size || 5 * 1024 * 1024; // 5MB default
            const validFiles = [];
            const errors = [];

            // Validate files
            Array.from(files).forEach(file => {
                const ext = file.name.split('.').pop().toLowerCase();

                if (!allowedTypes.includes(ext)) {
                    errors.push(`${file.name} - Invalid file type (allowed: ${allowedTypes.join(', ')})`);
                } else if (file.size > maxSize) {
                    const sizeMB = (file.size / 1024 / 1024).toFixed(2);
                    const maxSizeMB = (maxSize / 1024 / 1024).toFixed(0);
                    errors.push(`${file.name} - File too large (${sizeMB}MB / max: ${maxSizeMB}MB)`);
                } else {
                    validFiles.push(file);
                }
            });

            // Show errors
            if (errors.length > 0) {
                APP.utils.notify('File validation errors:\n' + errors.join('\n'), 'error');
            }

            // Upload valid files
            if (validFiles.length === 0) return;

            const formData = new FormData();
            validFiles.forEach((file, index) => {
                formData.append(`file_${index}`, file);
            });
            formData.append('action', 'sspu_upload_images');
            formData.append('nonce', sspu_ajax.nonce);

            // Show upload progress
            this.elements.dropZone.addClass('uploading');
            const $progress = $('<div class="upload-progress"><div class="progress-bar"></div></div>');
            this.elements.dropZone.append($progress);

            $.ajax({
                url: sspu_ajax.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                xhr: () => {
                    const xhr = new window.XMLHttpRequest();

                    // Upload progress
                    xhr.upload.addEventListener('progress', (evt) => {
                        if (evt.lengthComputable) {
                            const percentComplete = (evt.loaded / evt.total) * 100;
                            $progress.find('.progress-bar').css('width', percentComplete + '%');
                        }
                    }, false);

                    return xhr;
                }
            })
            .done((response) => {
                if (response.success && response.data.ids) {
                    console.log('SSPU Form: Images uploaded successfully');

                    // Add to additional images
                    const $preview = this.elements.additionalImagesPreview;
                    const $idField = $('#sspu-additional-image-ids');
                    const currentIds = $idField.val() ? $idField.val().split(',') : [];

                    response.data.ids.forEach(id => {
                        if (response.data.urls[id] && !currentIds.includes(id.toString())) {
                            currentIds.push(id.toString());

                            const $wrapper = $('<div class="image-preview-item">');
                            $wrapper.append($('<img>', {
                                src: response.data.urls[id],
                                alt: '',
                                'data-id': id,
                                class: 'attachment-thumbnail' // Ensure consistent class
                            }));
                            $wrapper.append('<button type="button" class="remove-image" title="Remove">&times;</button>');

                            $preview.append($wrapper);
                        }
                    });

                    $idField.val(currentIds.join(','));
                    this.state.isDirty = true;

                    APP.utils.notify(`${response.data.ids.length} images uploaded successfully!`, 'success');
                } else {
                    APP.utils.notify('Upload failed: ' + (response.data?.message || 'Unknown error'), 'error');
                }
            })
            .fail((xhr) => {
                console.error('SSPU Form: Upload failed:', xhr);
                APP.utils.notify('Upload failed. Please try again.', 'error');
            })
            .always(() => {
                this.elements.dropZone.removeClass('uploading');
                $progress.fadeOut(() => $progress.remove());
            });
        },

        /**
         * Clear form
         */
        clear() {
            console.log('SSPU Form: Clearing form');

            // Do not prompt for confirmation if already submitting or if there are no dirty changes
            if (this.state.isDirty && !APP.state.isSubmitting) {
                if (!confirm('Are you sure you want to clear the form? All unsaved changes will be lost.')) {
                    return;
                }
            } else if (APP.state.isSubmitting) {
                console.warn('SSPU Form: Cannot clear form while submission is in progress.');
                APP.utils.notify('Please wait for the current submission to complete before clearing the form.', 'warning');
                return;
            }


            // Reset form
            this.elements.form[0].reset();

            // Clear variants
            this.elements.variantsWrapper.empty();
            APP.state.variantCounter = 0;
            if (APP.variants && APP.variants.initAddButton) { // Re-initialize add variant button if needed
                APP.variants.initAddButton();
            }

            // Clear images
            $('.sspu-image-preview').empty();
            $('input[type="hidden"][name$="_id"], input[type="hidden"][name$="_ids"]').val('');

            // Clear editor content (if TinyMCE is used)
            APP.utils.setEditorContent('product_description', '');

            // Clear AI state (if applicable)
            APP.state.aiImageIds = [];
            $('#sspu-ai-images-preview').empty(); // Assuming an element for AI image previews

            // Reset SEO counters
            $('.seo-feedback .char-count').text('0/60').removeClass('over-limit');
            $('input[name="product_name"]').siblings('.seo-feedback').find('.title-length').text('Title length: 0 characters').removeClass('warning');

            // Reset form state
            this.state.isDirty = false;
            this.state.lastSavedData = null;
            clearTimeout(this.state.autoSaveTimer); // Clear any pending auto-save

            // Clear collections (if using a select2 or similar)
            this.elements.collectionSelect.val(null).trigger('change'); // For select2, need to trigger change
            if (APP.collections && APP.collections.updateCount) {
                APP.collections.updateCount();
            }

            // Hide status box
            this.elements.statusBox.hide();
            this.elements.progressBar.hide();
            clearInterval(this.progressInterval); // Clear any active progress simulation

            console.log('SSPU Form: Form cleared');
            APP.utils.notify('Form cleared successfully!', 'info');
        },

        /**
         * Enable debug mode
         */
        enableDebugMode() {
            console.log('SSPU Form: Debug mode enabled');

            // Add debug panel
            const $debugPanel = $(`
                <div id="sspu-debug-panel" style="position: fixed; bottom: 10px; right: 10px;
                    background: #fff; border: 2px solid #0073aa; padding: 10px;
                    max-width: 300px; max-height: 400px; overflow-y: auto; z-index: 9999;
                    box-shadow: 0 4px 8px rgba(0,0,0,0.2);">
                    <h4 style="margin: 0 0 10px; color: #0073aa;">Debug Panel</h4>
                    <button type="button" id="sspu-test-connection" class="button button-secondary" style="margin-bottom: 5px; width: 100%;">Test Shopify Connection</button>
                    <button type="button" id="sspu-view-form-data" class="button button-secondary" style="margin-bottom: 5px; width: 100%;">View Form Data</button>
                    <button type="button" id="sspu-test-validation" class="button button-secondary" style="margin-bottom: 5px; width: 100%;">Test Validation</button>
                    <button type="button" id="sspu-debug-variants" class="button button-secondary" style="margin-bottom: 5px; width: 100%;">Debug Variants</button>
                    <button type="button" id="sspu-refresh-nonce" class="button button-secondary" style="margin-bottom: 5px; width: 100%;">Refresh Nonce</button>
                    <div id="sspu-debug-output" style="margin-top: 10px; font-family: monospace;
                        font-size: 12px; white-space: pre-wrap; background: #eee; padding: 8px; border-radius: 3px; max-height: 200px; overflow-y: auto;"></div>
                </div>
            `);

            $('body').append($debugPanel);

            // Debug panel actions
            $('#sspu-test-connection').on('click', () => {
                $('#sspu-debug-output').text('Testing Shopify connection...');

                APP.utils.ajax('test_shopify_connection')
                    .done((response) => {
                        $('#sspu-debug-output').text(JSON.stringify(response, null, 2));
                    })
                    .fail((xhr) => {
                        $('#sspu-debug-output').text('Connection test failed:\n' + (xhr.responseText || 'Unknown error'));
                    });
            });

            $('#sspu-view-form-data').on('click', () => {
                const data = this.collectFormData();
                $('#sspu-debug-output').text(JSON.stringify(data, null, 2));
            });

            $('#sspu-test-validation').on('click', () => {
                const isValid = this.validate();
                $('#sspu-debug-output').text('Validation result: ' + (isValid ? 'PASSED' : 'FAILED'));
            });

            $('#sspu-debug-variants').on('click', () => {
                this.debugVariants();
            });

            $('#sspu-refresh-nonce').on('click', () => {
                $('#sspu-debug-output').text('Refreshing nonce...');
                this.refreshNonce().done(() => {
                    $('#sspu-debug-output').text('Nonce refreshed!\nNew nonce: ' + $('#sspu_nonce').val());
                }).fail(() => {
                    $('#sspu-debug-output').text('Failed to refresh nonce');
                });
            });
        },

        /**
         * Debug function to check variant states
         */
        debugVariants() {
            console.log('=== VARIANT DEBUG ===');
            const output = [];
            output.push('Total variant rows: ' + $('.sspu-variant-row').length);
            output.push('Visible variant rows: ' + $('.sspu-variant-row:visible').length);
            output.push('\nVariant Details:');

            $('.sspu-variant-row').each((index, element) => {
                const $row = $(element);
                const isVisible = $row.is(':visible');
                const value = $row.find('.sspu-variant-option-value').val();
                const price = $row.find('.sspu-variant-price').val();
                const classes = $row.attr('class');

                output.push(`\nVariant ${index + 1}:`);
                output.push(`  Visible: ${isVisible}`);
                output.push(`  Value: ${value || '(empty)'}`);
                output.push(`  Price: ${price || '(empty)'}`);
                output.push(`  Classes: ${classes}`);
                output.push(`  Display: ${$row.css('display')}`);
                output.push(`  Image ID: ${$row.find('.sspu-variant-image-id').val() || '(none)'}`); // Added image ID for debug
            });

            output.push('\n=== END DEBUG ===');

            const debugText = output.join('\n');
            console.log(debugText);

            if ($('#sspu-debug-output').length) {
                $('#sspu-debug-output').text(debugText);
            }
        }
    };

})(jQuery, window.SSPU);