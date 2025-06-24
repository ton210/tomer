(function($) {
    'use strict';

    /**
     * SSPUImageRetriever Module - Fixed Version with Enhanced Debugging
     * Handles retrieving images from an Alibaba product page and adding them to the WordPress media library.
     * Enhanced with debugging for variants page functionality
     */
    const SSPUImageRetriever = {
        // --- PROPERTIES ---

        // State variables to track the module's status
        state: {
            retrievedImages: [],
            currentAlibabaUrl: '',
            isInitialized: false,
            isRetrieving: false,
            currentContext: 'unknown' // Track where we're being used
        },

        // Cached jQuery elements for performance
        elements: {},

        // --- INITIALIZATION ---

        /**
         * Initializes the module.
         */
        init: function() {
            console.log('[SSPU Image Retriever] Starting initialization...');
            
            // Run on document ready
            $(() => {
                console.log('[SSPU Image Retriever] Document ready, checking context...');
                
                // Determine context (images tab or variants tab)
                if ($('#tab-images').length > 0) {
                    console.log('[SSPU Image Retriever] Found images tab');
                    this.state.currentContext = 'images';
                } else if ($('#tab-variants').length > 0 || $('#sspu-variants-wrapper').length > 0) {
                    console.log('[SSPU Image Retriever] Found variants tab');
                    this.state.currentContext = 'variants';
                } else if ($('.sspu-variant-row').length > 0) {
                    console.log('[SSPU Image Retriever] Found variant rows (probably variants context)');
                    this.state.currentContext = 'variants';
                }
                
                // Initialize based on context
                if (this.state.currentContext !== 'unknown') {
                    setTimeout(() => {
                        this.initializeWhenReady();
                    }, 500);
                } else {
                    console.warn('[SSPU Image Retriever] No suitable context found, waiting for tabs...');
                    // Set up listener for tab changes
                    this.setupTabListener();
                }
            });
        },

        /**
         * Set up listener for jQuery UI tabs
         */
        setupTabListener: function() {
            console.log('[SSPU Image Retriever] Setting up tab listener...');
            
            // Listen for both jQuery UI tabs and custom tab implementations
            $(document).on('tabsactivate', (event, ui) => {
                console.log('[SSPU Image Retriever] Tab activated:', ui);
                if (ui.newPanel && (ui.newPanel.attr('id') === 'tab-images' || ui.newPanel.attr('id') === 'tab-variants')) {
                    this.state.currentContext = ui.newPanel.attr('id').replace('tab-', '');
                    if (!this.state.isInitialized) {
                        this.initializeWhenReady();
                    }
                }
            });
            
            // Also listen for manual tab clicks
            $(document).on('click', '.ui-tabs-nav a, .nav-tab', (e) => {
                const href = $(e.currentTarget).attr('href');
                console.log('[SSPU Image Retriever] Tab clicked:', href);
                if (href === '#tab-images' || href === '#tab-variants') {
                    this.state.currentContext = href.replace('#tab-', '');
                    if (!this.state.isInitialized) {
                        setTimeout(() => this.initializeWhenReady(), 100);
                    }
                }
            });
        },

        /**
         * Checks if the relevant UI is present and then initializes.
         * This is the main entry point after the document is ready.
         */
        initializeWhenReady: function() {
            if (this.state.isInitialized) {
                console.log('[SSPU Image Retriever] Already initialized, skipping...');
                return;
            }
            
            console.log('[SSPU Image Retriever] Initializing for context:', this.state.currentContext);

            // Only create UI for images tab, not variants tab
            if (this.state.currentContext === 'images') {
                this.createRetrieverUI();
            }
            
            this.cacheDOMElements();
            this.bindEvents();

            this.state.isInitialized = true;
            this.updateAlibabaUrl(); // Perform initial check for a URL
            
            console.log('[SSPU Image Retriever] Initialization complete!');
        },

        /**
         * Caches all necessary DOM elements into the `this.elements` object.
         */
        cacheDOMElements: function() {
            console.log('[SSPU Image Retriever] Caching DOM elements...');
            
            const $container = $('.sspu-image-retriever-section');
            this.elements = {
                $container: $container,
                $tabImages: $('#tab-images'),
                $tabVariants: $('#tab-variants'),
                $manualUrlInput: $('#manual-alibaba-url'),
                $useManualUrlButton: $('#use-manual-url'),
                $retrieveButton: $('#retrieve-alibaba-images'),
                $spinner: $container.find('.spinner'),
                $status: $('#retriever-status'),
                $progress: $('#retrieval-progress'),
                $progressBar: $('#retrieval-progress .progress-fill'),
                $progressText: $('#retrieval-progress .progress-text'),
                $gallery: $('#retrieved-images-gallery'),
                $scrapeVariantsBtn: $('#sspu-scrape-variants-btn')
            };
            
            console.log('[SSPU Image Retriever] Cached elements:', {
                containerFound: $container.length > 0,
                scrapeButtonFound: this.elements.$scrapeVariantsBtn.length > 0,
                manualInputFound: this.elements.$manualUrlInput.length > 0
            });
        },

        /**
         * Binds all event listeners for the module.
         */
        bindEvents: function() {
            console.log('[SSPU Image Retriever] Binding events...');
            
            // Listen for URL assignment from other parts of the application
            $(document).on('sspu:alibaba-url-assigned', (event, url) => {
                console.log('[SSPU Image Retriever] URL assigned via event:', url);
                if (this.elements.$manualUrlInput.length > 0) {
                    this.elements.$manualUrlInput.val(url);
                }
                this.updateAlibabaUrl();
            });

            // Re-check for URL when tabs are activated
            $('#sspu-tabs').on('tabsactivate', (event, ui) => {
                console.log('[SSPU Image Retriever] Tab activated:', ui.newPanel.attr('id'));
                if (ui.newPanel.attr('id') === 'tab-images' || ui.newPanel.attr('id') === 'tab-variants') {
                    if (!this.state.isInitialized) {
                        this.initializeWhenReady();
                    } else {
                        this.updateAlibabaUrl();
                    }
                }
            });
            
            // Use delegated events for controls
            if (this.elements.$container.length > 0) {
                this.elements.$container
                    .on('click', '#retrieve-alibaba-images', this.startImageRetrieval.bind(this))
                    .on('click', '#use-manual-url', this.useManualUrl.bind(this))
                    .on('keypress', '#manual-alibaba-url', (e) => {
                        if (e.which === 13) { // Enter key
                            e.preventDefault();
                            this.useManualUrl();
                        }
                    })
                    .on('input', '#manual-alibaba-url', this.updateAlibabaUrl.bind(this));
            }

            // Bind the scrape variants button (on variants tab)
            $(document).on('click', '#sspu-scrape-variants-btn', (e) => {
                console.log('[SSPU Image Retriever] Scrape variants button clicked!');
                e.preventDefault();
                this.scrapeVariantsFromUrl();
            });

            // Bind events for gallery images if gallery exists
            if (this.elements.$gallery.length > 0) {
                this.elements.$gallery
                    .on('click', '.edit-with-ai', this.openAIEditor)
                    .on('click', '.add-to-main', this.addToMainImage)
                    .on('click', '.add-to-gallery', this.addToGalleryImages);
            }
            
            console.log('[SSPU Image Retriever] Events bound successfully');
        },

        // --- UI & DOM MANIPULATION ---

        /**
         * Creates and prepends the image retriever HTML to the images tab.
         */
        createRetrieverUI: function() {
            console.log('[SSPU Image Retriever] Creating retriever UI...');
            
            if ($('#tab-images .sspu-image-retriever-section').length > 0) {
                console.log('[SSPU Image Retriever] UI already exists, skipping...');
                return;
            }

            const retrieverHtml = `
                <div class="sspu-image-retriever-section">
                    <h3>Alibaba Image Retrieval</h3>
                    <div class="manual-url-section" style="margin-bottom: 15px; padding: 15px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px;">
                        <label for="manual-alibaba-url" style="display: block; margin-bottom: 5px; font-weight: bold;">Alibaba Product URL:</label>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <input type="url" id="manual-alibaba-url" placeholder="https://www.alibaba.com/product-detail/..." style="flex: 1; padding: 8px;" class="regular-text" />
                            <button type="button" id="use-manual-url" class="button">Use This URL</button>
                        </div>
                        <p class="description" style="margin-top: 5px;">Enter an Alibaba or 1688.com URL, or use the one assigned from the queue.</p>
                    </div>
                    <div class="retriever-controls">
                        <button type="button" id="retrieve-alibaba-images" class="button button-primary">Retrieve Images from Alibaba</button>
                        <span class="spinner"></span>
                        <span id="retriever-status" style="margin-left: 10px; font-style: italic;"></span>
                    </div>
                    <div id="retrieval-progress" style="display: none; margin-top: 15px;">
                        <div class="progress-bar" style="background-color: #e0e0e0; border-radius: 4px; overflow: hidden; height: 20px;"><div class="progress-fill" style="width: 0%; height: 100%; background-color: #0073aa; transition: width 0.3s ease;"></div></div>
                        <p class="progress-text" style="text-align: center; margin-top: 5px;"></p>
                    </div>
                    <div id="retrieved-images-gallery" class="retrieved-images-container"></div>
                </div>`;
            $('#tab-images').prepend(retrieverHtml);
            
            console.log('[SSPU Image Retriever] UI created successfully');
        },

        /**
         * Displays the retrieved and downloaded images in a gallery format.
         * @param {Array} images - Array of image objects with id, url, and thumb_url.
         */
        displayRetrievedImages: function(images) {
            console.log('[SSPU Image Retriever] Displaying', images.length, 'images');
            
            const galleryHtml = `
                <h4>Retrieved Images (${images.length})</h4>
                <div class="retrieved-images-grid">
                    ${images.map((img, index) => `
                        <div class="retrieved-image-item" data-image-id="${img.id}" style="animation-delay: ${index * 50}ms">
                            <img src="${img.thumb_url}" alt="Product image ${index + 1}" />
                            <div class="image-actions">
                                <button class="button button-primary edit-with-ai" data-image-id="${img.id}" data-image-url="${img.url}">Edit with AI</button>
                                <button class="button button-small add-to-main" data-image-id="${img.id}">Set as Main</button>
                                <button class="button button-small add-to-gallery" data-image-id="${img.id}">Add to Gallery</button>
                            </div>
                        </div>
                    `).join('')}
                </div>`;
            this.elements.$gallery.html(galleryHtml).show();
        },

        /**
         * Updates the UI state (buttons, status text) based on the current URL.
         */
        updateUI: function() {
            console.log('[SSPU Image Retriever] Updating UI...');
            
            if (this.elements.$retrieveButton.length > 0) {
                if (this.isValidAlibabaUrl(this.state.currentAlibabaUrl)) {
                    this.elements.$retrieveButton.prop('disabled', false).removeClass('button-disabled');
                    this.elements.$status.text('Ready - ' + this.truncateUrl(this.state.currentAlibabaUrl));
                } else {
                    this.elements.$retrieveButton.prop('disabled', true).addClass('button-disabled');
                    this.elements.$status.text('Enter a valid Alibaba or 1688.com URL.');
                }
            }
        },
        
        /**
         * Resets the retriever UI to its initial state after a process completes or fails.
         */
        resetRetrieverUI: function() {
            console.log('[SSPU Image Retriever] Resetting UI...');
            
            this.state.isRetrieving = false;
            if (this.elements.$spinner.length > 0) {
                this.elements.$spinner.removeClass('is-active');
            }
            if (this.elements.$progress.length > 0) {
                this.elements.$progress.fadeOut();
            }
            this.updateUI();
        },

        /**
         * Shows a notification message within the retriever section.
         * @param {string} message - The message to display.
         * @param {string} type - 'success', 'warning', 'error', or 'info'.
         */
        showNotification: function(message, type = 'info') {
            console.log('[SSPU Image Retriever] Notification:', type, '-', message);
            
            // Try to find a container for the notification
            let $container = this.elements.$container;
            if (!$container || $container.length === 0) {
                // Fallback to the main uploader wrapper
                $container = $('#sspu-uploader-wrapper');
            }
            
            if ($container.length === 0) {
                // Last resort - use alert
                alert(message);
                return;
            }
            
            const $notification = $(`<div class="notice notice-${type} is-dismissible" style="margin: 10px 0;"><p>${message}</p></div>`);
            $container.find('.notice').remove(); // Remove old notices
            $container.prepend($notification);

            if (type === 'success' || type === 'info') {
                setTimeout(() => $notification.fadeOut(500, function() { $(this).remove(); }), 5000);
            }
        },

        
        // --- CORE LOGIC ---

        /**
         * Retrieves the URL from various sources, validates it, and updates the state.
         */
        updateAlibabaUrl: function() {
            console.log('[SSPU Image Retriever] Updating Alibaba URL...');
            
            // Priority 1: Manual Input Field
            let url = '';
            if (this.elements.$manualUrlInput && this.elements.$manualUrlInput.length > 0) {
                url = this.elements.$manualUrlInput.val().trim();
                console.log('[SSPU Image Retriever] URL from manual input:', url);
            }

            // Priority 2: Hidden input field (if manual is empty)
            if (!url) {
                const $urlInput = $('#current-alibaba-url');
                if ($urlInput.length > 0) {
                    url = $urlInput.val().trim();
                    console.log('[SSPU Image Retriever] URL from hidden input:', url);
                }
            }
            
            // Priority 3: Global window variable (fallback)
            if (!url && window.sspu_current_alibaba_url) {
                url = window.sspu_current_alibaba_url;
                console.log('[SSPU Image Retriever] URL from window variable:', url);
            }
            
            this.state.currentAlibabaUrl = url;
            console.log('[SSPU Image Retriever] Final URL:', url);
            
            // Sync the input field if it exists
            if (this.elements.$manualUrlInput && this.elements.$manualUrlInput.length > 0) {
                this.elements.$manualUrlInput.val(url);
            }
            
            this.updateUI();
        },

        /**
         * Handles the click on the "Use This URL" button.
         */
        useManualUrl: function() {
            console.log('[SSPU Image Retriever] Use manual URL clicked');
            this.updateAlibabaUrl();
            if (this.isValidAlibabaUrl(this.state.currentAlibabaUrl)) {
                this.showNotification('URL set successfully! Click "Retrieve Images" to start.', 'success');
            } else {
                this.showNotification('Please enter a valid Alibaba or 1688.com URL.', 'error');
            }
        },

        /**
         * Scrape variants from URL - called from variants tab
         */
        scrapeVariantsFromUrl: function() {
            console.log('[SSPU Image Retriever] ===== SCRAPE VARIANTS START =====');
            this.updateAlibabaUrl();

            if (!this.isValidAlibabaUrl(this.state.currentAlibabaUrl)) {
                console.error('[SSPU Image Retriever] Invalid URL:', this.state.currentAlibabaUrl);
                this.showNotification('Please provide a valid Alibaba or 1688.com URL.', 'error');
                return;
            }

            console.log('[SSPU Image Retriever] Scraping from URL:', this.state.currentAlibabaUrl);
            const $button = $('#sspu-scrape-variants-btn');
            const originalText = $button.html();
            $button.prop('disabled', true).html('<span class="spinner is-active" style="float:none; margin-right:5px;"></span>Scraping...');

            $.ajax({
                url: sspu_ajax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'sspu_scrape_alibaba_variants',
                    nonce: sspu_ajax.nonce,
                    url: this.state.currentAlibabaUrl
                }
            })
            .done(response => {
                console.log('[SSPU Image Retriever] Scrape response:', response);
                
                if (response.success && response.data.variants && response.data.variants.length > 0) {
                    const variants = response.data.variants;
                    console.log(`[SSPU Image Retriever] Found ${variants.length} variants:`, variants);
                    
                    if (confirm(`Found ${variants.length} variants. This will clear existing variants and create new ones. Continue?`)) {
                        // Clear existing variants
                        $('#clear-variants-btn').trigger('click');

                        // Process variants after clearing
                        setTimeout(() => {
                            this.processScrapedVariants(variants);
                        }, 500);
                    }
                } else {
                    const errorMsg = response.data && response.data.message ? response.data.message : 'No variants with images were found.';
                    console.error('[SSPU Image Retriever] No variants found or error:', response);
                    this.showNotification(errorMsg, 'error');
                }
            })
            .fail(xhr => {
                console.error('[SSPU Image Retriever] Scrape variants AJAX error:', xhr);
                console.error('[SSPU Image Retriever] Response text:', xhr.responseText);
                this.showNotification('An error occurred during scraping. Please check the console.', 'error');
            })
            .always(() => {
                $button.prop('disabled', false).html(originalText);
            });
        },

        /**
         * Process scraped variants sequentially to ensure proper timing
         */
        processScrapedVariants: function(variants) {
            const self = this;
            let processedCount = 0;
            
            // Process variants one by one to ensure proper timing
            function processNextVariant() {
                if (processedCount >= variants.length) {
                    console.log('[SSPU Image Retriever] All variants processed');
                    self.showNotification(`Created ${variants.length} variants successfully!`, 'success');
                    return;
                }
                
                const variant = variants[processedCount];
                const variantIndex = processedCount;
                
                console.log(`[SSPU Image Retriever] Processing variant ${variantIndex + 1}/${variants.length}:`, variant);
                
                // Add the variant
                $('#sspu-add-variant-btn').trigger('click');
                
                // Wait for the row to be added to DOM
                setTimeout(() => {
                    // Get all rows and find the one we just added
                    const $allRows = $('.sspu-variant-row');
                    const $newRow = $allRows.eq(variantIndex);
                    
                    if ($newRow.length === 0) {
                        console.error(`[SSPU Image Retriever] Could not find row ${variantIndex}`);
                        processedCount++;
                        processNextVariant();
                        return;
                    }
                    
                    console.log(`[SSPU Image Retriever] Populating row ${variantIndex}`);
                    
                    // Set the option name (e.g., "Color")
                    const $optionNameInput = $newRow.find('.sspu-variant-option-name');
                    if ($optionNameInput.length > 0) {
                        $optionNameInput.val(variant.name || 'Color').trigger('change');
                        console.log(`[SSPU Image Retriever] Set option name to: "${variant.name || 'Color'}"`);
                    } else {
                        console.error('[SSPU Image Retriever] Could not find option name input');
                    }
                    
                    // Set the option value (e.g., "Green", "Blue", etc.)
                    const $optionValueInput = $newRow.find('.sspu-variant-option-value');
                    if ($optionValueInput.length > 0) {
                        $optionValueInput.val(variant.value).trigger('change');
                        console.log(`[SSPU Image Retriever] Set option value to: "${variant.value}"`);
                    } else {
                        console.error('[SSPU Image Retriever] Could not find option value input');
                    }
                    
                    // Clean the image URL
                    let cleanedImageUrl = variant.image_url;
                    
                    // Remove any leading "//" and add https:
                    if (cleanedImageUrl.startsWith('//')) {
                        cleanedImageUrl = 'https:' + cleanedImageUrl;
                    }
                    
                    // Remove all duplicate protocol prefixes (handles cases like "https:https://")
                    cleanedImageUrl = cleanedImageUrl.replace(/^(https?:)+(\/\/)?/i, 'https://');
                    
                    // If still no protocol, add https://
                    if (!cleanedImageUrl.match(/^https?:\/\//i)) {
                        cleanedImageUrl = 'https://' + cleanedImageUrl;
                    }
                    
                    console.log(`[SSPU Image Retriever] Cleaned URL from "${variant.image_url}" to "${cleanedImageUrl}"`);
                    console.log(`[SSPU Image Retriever] Downloading image for "${variant.value}" from: ${cleanedImageUrl}`);
                    
                    // Download and set the image
                    self.downloadAndSetVariantImage($newRow, cleanedImageUrl, variant.value);
                    
                    // Move to next variant
                    processedCount++;
                    setTimeout(processNextVariant, 300); // Small delay between variants
                    
                }, 200); // Wait for DOM update
            }
            
            // Start processing
            processNextVariant();
        },
        
        /**
         * Helper function to download an image and set it for a specific variant row.
         * @param {jQuery} $row - The jQuery object for the variant row.
         * @param {string} imageUrl - The URL of the image to download.
         * @param {string} variantValue - The value of the variant for filename.
         */
        downloadAndSetVariantImage: function($row, imageUrl, variantValue) {
            console.log(`[Image Retriever] ===== DOWNLOAD START =====`);
            console.log(`[Image Retriever] Variant: "${variantValue}"`);
            console.log(`[Image Retriever] Row index:`, $row.index());
            console.log(`[Image Retriever] Image URL: ${imageUrl}`);
            
            // Capture the row reference in a closure to ensure we're working with the correct row
            const $targetRow = $row;
            const targetVariantValue = variantValue;
            
            // Add a visual loading indicator
            $targetRow.find('.sspu-variant-image-preview').addClass('loading').html('<div class="spinner is-active"></div>');
            
            const filename = `variant-${variantValue.replace(/\s+/g, '-').toLowerCase()}`;

            $.ajax({
                url: sspu_ajax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'sspu_download_external_image',
                    nonce: sspu_ajax.nonce,
                    image_url: imageUrl,
                    filename: filename
                },
                beforeSend: function(xhr) {
                    console.log(`[Image Retriever] Sending AJAX request for "${targetVariantValue}"...`);
                    console.log(`[Image Retriever] Request data:`, {
                        action: 'sspu_download_external_image',
                        image_url: imageUrl,
                        filename: filename
                    });
                }
            }).done(response => {
                console.log(`[Image Retriever] ===== RESPONSE RECEIVED for "${targetVariantValue}" =====`);
                console.log(`[Image Retriever] Success:`, response.success);
                console.log(`[Image Retriever] Full response:`, response);
                
                if (response.success && response.data) {
                    console.log(`[Image Retriever] Attachment ID: ${response.data.attachment_id}`);
                    console.log(`[Image Retriever] Thumb URL: ${response.data.thumb_url}`);
                    
                    const attachment = {
                        id: response.data.attachment_id,
                        sizes: { thumbnail: { url: response.data.thumb_url } },
                        url: response.data.url,
                        alt: `Image for ${targetVariantValue}`
                    };
                    
                    // Use the captured row reference to ensure we're setting the image on the correct row
                    const $variantValueInput = $targetRow.find('.sspu-variant-option-value');
                    const currentValue = $variantValueInput.val();
                    
                    console.log(`[Image Retriever] Verifying row - expected: "${targetVariantValue}", actual: "${currentValue}"`);
                    
                    if (currentValue === targetVariantValue) {
                        // Use the existing setVariantImage function
                        if (window.SSPU && window.SSPU.variants && typeof window.SSPU.variants.setVariantImage === 'function') {
                            console.log(`[Image Retriever] Setting image on correct row for "${targetVariantValue}"`);
                            window.SSPU.variants.setVariantImage($targetRow, attachment);
                            
                            // Force remove loading state after setting image
                            setTimeout(() => {
                                $targetRow.find('.sspu-variant-image-preview')
                                    .removeClass('loading')
                                    .find('.spinner').remove();
                                // Also remove any processing overlays
                                $targetRow.find('.processing-overlay, .processing').remove();
                            }, 100);
                        } else {
                            console.error('[Image Retriever] SSPU.variants.setVariantImage not found!');
                            // Fallback: manually set the image
                            const $preview = $targetRow.find('.sspu-variant-image-preview');
                            const $idField = $targetRow.find('.sspu-variant-image-id');
                            
                            $preview.removeClass('loading').html(`<img src="${attachment.sizes.thumbnail.url}" alt="${attachment.alt}" data-id="${attachment.id}" />`);
                            $idField.val(attachment.id);
                            $targetRow.find('.sspu-ai-edit-variant-image').show();
                            
                            // Force remove any remaining loading elements
                            $preview.find('.spinner, .processing-overlay, .processing').remove();
                        }
                    } else {
                        console.error(`[Image Retriever] Row mismatch! Expected "${targetVariantValue}" but found "${currentValue}"`);
                        // Remove loading state
                        $targetRow.find('.sspu-variant-image-preview').removeClass('loading');
                        
                        // Try to find the correct row by iterating through all variant rows
                        let correctRowFound = false;
                        $('.sspu-variant-row').each(function() {
                            const $thisRow = $(this);
                            if ($thisRow.find('.sspu-variant-option-value').val() === targetVariantValue) {
                                console.log(`[Image Retriever] Found correct row for "${targetVariantValue}", setting image`);
                                if (window.SSPU && window.SSPU.variants) {
                                    window.SSPU.variants.setVariantImage($thisRow, attachment);
                                    
                                    // Force remove loading state after setting image
                                    setTimeout(() => {
                                        $thisRow.find('.sspu-variant-image-preview')
                                            .removeClass('loading')
                                            .find('.spinner').remove();
                                        // Also remove any processing overlays
                                        $thisRow.find('.processing-overlay, .processing').remove();
                                    }, 100);
                                }
                                correctRowFound = true;
                                return false; // break the loop
                            }
                        });
                        
                        if (!correctRowFound) {
                            console.error(`[Image Retriever] Could not find row for variant "${targetVariantValue}"`);
                        }
                    }
                } else {
                    console.error(`[Image Retriever] Download failed for "${targetVariantValue}":`, response);
                    if (response.data && response.data.message) {
                        console.error(`[Image Retriever] Error message: ${response.data.message}`);
                    }
                    $targetRow.find('.sspu-variant-image-preview').removeClass('loading').html('<span style="color: red;">Failed to load image</span>');
                }
            }).fail((xhr, textStatus, errorThrown) => {
                console.error(`[Image Retriever] ===== AJAX ERROR for "${targetVariantValue}" =====`);
                console.error(`[Image Retriever] Status: ${xhr.status}`);
                console.error(`[Image Retriever] Status Text: ${textStatus}`);
                console.error(`[Image Retriever] Error Thrown: ${errorThrown}`);
                console.error(`[Image Retriever] Response Text:`, xhr.responseText);
                
                // Try to parse error response
                try {
                    const errorResponse = JSON.parse(xhr.responseText);
                    console.error(`[Image Retriever] Parsed error:`, errorResponse);
                } catch (e) {
                    console.error(`[Image Retriever] Could not parse error response`);
                }
                
                $targetRow.find('.sspu-variant-image-preview').removeClass('loading').html('<span style="color: red;">Error loading image</span>');
            }).always(() => {
                console.log(`[Image Retriever] ===== REQUEST COMPLETE for "${targetVariantValue}" =====`);
                // Ensure loading state is removed
                $targetRow.find('.sspu-variant-image-preview').removeClass('loading');
            });
        },
        
        /**
         * Starts the two-step process of retrieving image URLs and then downloading them.
         */
        startImageRetrieval: function() {
            if (this.state.isRetrieving) {
                console.log('[SSPU Image Retriever] Already retrieving, skipping...');
                return;
            }
            this.updateAlibabaUrl(); // Ensure we have the latest URL

            if (!this.isValidAlibabaUrl(this.state.currentAlibabaUrl)) {
                this.showNotification('No valid Alibaba URL found. Please enter one above.', 'error');
                return;
            }

            console.log('[SSPU Image Retriever] Starting retrieval from:', this.state.currentAlibabaUrl);
            this.state.isRetrieving = true;

            // Setup UI for retrieval
            this.elements.$retrieveButton.prop('disabled', true);
            this.elements.$spinner.addClass('is-active');
            this.elements.$progress.show();
            this.elements.$progressBar.css('width', '0%');
            this.elements.$progressText.text('Step 1/2: Fetching image list from Alibaba...');
            this.elements.$status.text('Working...');
            this.elements.$gallery.hide().empty();

            // Step 1: Fetch the list of image URLs from the server
            $.ajax({
                url: sspu_ajax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'sspu_retrieve_alibaba_images',
                    nonce: sspu_ajax.nonce,
                    alibaba_url: this.state.currentAlibabaUrl
                }
            })
            .done(response => {
                console.log('[SSPU Image Retriever] Retrieve response:', response);
                
                if (response.success && response.data && response.data.images && response.data.images.length > 0) {
                    this.elements.$progressBar.css('width', '50%');
                    this.elements.$progressText.text(`Step 2/2: Found ${response.data.images.length} images. Downloading to WordPress...`);
                    this.state.retrievedImages = response.data.images;
                    this.downloadImagesToWordPress(this.state.retrievedImages); // Step 2
                } else {
                    const errorMsg = response.data && response.data.message ? response.data.message : 'No images found or page could not be read.';
                    this.showNotification(errorMsg, 'warning');
                    this.resetRetrieverUI();
                }
            })
            .fail((xhr) => {
                console.error('[SSPU Image Retriever] AJAX Error:', xhr.responseText);
                this.showNotification('An error occurred while fetching the image list. Check the console for details.', 'error');
                this.resetRetrieverUI();
            });
        },

        /**
         * Downloads images to WordPress in batches.
         * @param {Array} imageUrls - The list of URLs to download.
         */
        downloadImagesToWordPress: async function(imageUrls) {
            console.log('[SSPU Image Retriever] Downloading', imageUrls.length, 'images...');
            
            const total = imageUrls.length;
            let downloadedCount = 0;
            const downloadedImages = [];
            
            for (let i = 0; i < total; i++) {
                try {
                    const result = await this.downloadSingleImage(imageUrls[i], i);
                    if (result.success) {
                        downloadedImages.push(result.data);
                        console.log('[SSPU Image Retriever] Downloaded image', i + 1, 'of', total);
                    }
                } catch (error) {
                    console.warn(`[SSPU Image Retriever] Failed to download image ${i + 1}`, error);
                }
                
                downloadedCount++;
                const progress = 50 + (downloadedCount / total) * 50;
                this.elements.$progressBar.css('width', `${progress}%`);
                this.elements.$progressText.text(`Step 2/2: Downloaded ${downloadedCount} of ${total} images...`);
            }

            if (downloadedImages.length > 0) {
                this.showNotification(`Successfully downloaded ${downloadedImages.length} of ${total} images.`, 'success');
                this.displayRetrievedImages(downloadedImages);
            } else {
                this.showNotification('No images could be downloaded. The remote server may be blocking requests.', 'error');
            }

            this.resetRetrieverUI();
        },

        /**
         * Makes an AJAX call to download a single image. Returns a Promise.
         * @param {string} imageUrl - The URL of the image to download.
         * @param {number} index - The index of the image for filename purposes.
         * @returns {Promise}
         */
        downloadSingleImage: function(imageUrl, index) {
            console.log('[SSPU Image Retriever] Downloading image', index + 1, ':', imageUrl);
            
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: sspu_ajax.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'sspu_download_external_image',
                        nonce: sspu_ajax.nonce,
                        image_url: imageUrl,
                        filename: `alibaba-product-${index + 1}`
                    },
                    timeout: 30000 // 30-second timeout per image
                })
                .done(response => {
                    if (response.success) {
                        console.log('[SSPU Image Retriever] Successfully downloaded image', index + 1);
                        resolve({ success: true, data: response.data });
                    } else {
                        console.warn('Server failed to download image:', response.data);
                        resolve({ success: false }); // Resolve with failure so loop continues
                    }
                })
                .fail(error => {
                    console.error('AJAX error downloading image:', error);
                    reject(error); // Reject on network failure
                });
            });
        },


        // --- GALLERY ACTIONS ---

        openAIEditor: function() {
            // 'this' is the clicked button element here
            const imageId = $(this).data('image-id');
            const imageUrl = $(this).data('image-url');
            console.log('[SSPU Image Retriever] Opening AI editor for image:', imageId);
            
            if (window.AIImageEditor && typeof window.AIImageEditor.open === 'function') {
                window.AIImageEditor.open(imageId, imageUrl);
            } else {
                alert('AI Image Editor is not available.');
            }
        },

        addToMainImage: function() {
            const $button = $(this);
            const imageId = $button.data('image-id');
            const imageUrl = $button.closest('.retrieved-image-item').find('img').attr('src');
            
            console.log('[SSPU Image Retriever] Setting as main image:', imageId);
            
            $('#sspu-main-image-id').val(imageId);
            $('#sspu-main-image-preview').html(`<img src="${imageUrl}" alt="" data-id="${imageId}" />`);
            
            $button.text('✓ Set as Main').prop('disabled', true);
            SSPUImageRetriever.showNotification('Main product image updated.', 'success');
        },

        addToGalleryImages: function() {
            const $button = $(this);
            const imageId = $button.data('image-id').toString();
            const $hiddenInput = $('#sspu-additional-image-ids');
            const currentIds = $hiddenInput.val() ? $hiddenInput.val().split(',') : [];

            console.log('[SSPU Image Retriever] Adding to gallery:', imageId);

            if (!currentIds.includes(imageId)) {
                currentIds.push(imageId);
                $hiddenInput.val(currentIds.join(','));
                
                const imageUrl = $button.closest('.retrieved-image-item').find('img').attr('src');
                $('#sspu-additional-images-preview').append(`<img src="${imageUrl}" alt="" data-id="${imageId}" />`);

                $button.text('✓ Added').prop('disabled', true);
                SSPUImageRetriever.showNotification('Image added to gallery.', 'success');
            } else {
                SSPUImageRetriever.showNotification('Image is already in the gallery.', 'info');
                $button.text('✓ Added').prop('disabled', true);
            }
        },

        // --- HELPERS ---

        /**
         * Validates if a string is a plausible Alibaba/1688 URL.
         * @param {string} url - The URL to validate.
         * @returns {boolean}
         */
        isValidAlibabaUrl: function(url) {
            return url && (url.includes('alibaba.com') || url.includes('1688.com')) && url.startsWith('http');
        },

        /**
         * Truncates a URL for display purposes.
         * @param {string} url - The URL to truncate.
         * @returns {string}
         */
        truncateUrl: function(url) {
            if (!url) return '';
            const match = url.match(/\/([^\/]+)\.html/);
            if (match && match[1]) {
                return match[1].substring(0, 35) + '...';
            }
            return url.length > 50 ? url.substring(0, 50) + '...' : url;
        }
    };

    // Kick off the script
    console.log('[SSPU Image Retriever] Script loaded, initializing...');
    SSPUImageRetriever.init();

    // Also make it globally accessible for debugging
    window.SSPUImageRetriever = SSPUImageRetriever;

})(jQuery);