(function($) {
    'use strict';

    /**
     * SSPUImageRetriever Module
     * Handles retrieving images from an Alibaba product page and adding them to the WordPress media library.
     */
    const SSPUImageRetriever = {
        // --- PROPERTIES ---

        // State variables to track the module's status
        state: {
            retrievedImages: [],
            currentAlibabaUrl: '',
            isInitialized: false,
            isRetrieving: false
        },

        // Cached jQuery elements for performance
        elements: {},

        // --- INITIALIZATION ---

        /**
         * Initializes the module.
         */
        init: function() {
            // Run on document ready
            $(() => {
                // Wait for the main app to potentially assign a URL
                setTimeout(() => {
                    this.initializeWhenReady();
                }, 500);
            });
        },

        /**
         * Checks if the relevant UI tab is present and then initializes.
         * This is the main entry point after the document is ready.
         */
        initializeWhenReady: function() {
            if (this.state.isInitialized || $('#tab-images').length === 0) {
                return;
            }
            console.log('[SSPU Image Retriever] Initializing...');

            this.createRetrieverUI();
            this.cacheDOMElements();
            this.bindEvents();

            this.state.isInitialized = true;
            this.updateAlibabaUrl(); // Perform initial check for a URL
        },

        /**
         * Caches all necessary DOM elements into the `this.elements` object.
         */
        cacheDOMElements: function() {
            const $container = $('.sspu-image-retriever-section');
            this.elements = {
                $container: $container,
                $tabImages: $('#tab-images'),
                $manualUrlInput: $('#manual-alibaba-url'),
                $useManualUrlButton: $('#use-manual-url'),
                $retrieveButton: $('#retrieve-alibaba-images'),
                $spinner: $container.find('.spinner'),
                $status: $('#retriever-status'),
                $progress: $('#retrieval-progress'),
                $progressBar: $('#retrieval-progress .progress-fill'),
                $progressText: $('#retrieval-progress .progress-text'),
                $gallery: $('#retrieved-images-gallery')
            };
        },

        /**
         * Binds all event listeners for the module.
         */
        bindEvents: function() {
            // Listen for URL assignment from other parts of the application
            $(document).on('sspu:alibaba-url-assigned', (event, url) => {
                console.log('[SSPU Image Retriever] URL assigned via event:', url);
                this.elements.$manualUrlInput.val(url);
                this.updateAlibabaUrl();
            });

            // Re-check for URL when the Images tab is activated
            $('#sspu-tabs').on('tabsactivate', (event, ui) => {
                if (ui.newPanel.attr('id') === 'tab-images') {
                    if (!this.state.isInitialized) {
                        this.initializeWhenReady();
                    } else {
                        this.updateAlibabaUrl();
                    }
                }
            });
            
            // Use delegated events for controls inside the dynamically added container
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

            // Bind events for the dynamically created gallery images
            this.elements.$gallery
                .on('click', '.edit-with-ai', this.openAIEditor)
                .on('click', '.add-to-main', this.addToMainImage)
                .on('click', '.add-to-gallery', this.addToGalleryImages);
        },


        // --- UI & DOM MANIPULATION ---

        /**
         * Creates and prepends the image retriever HTML to the images tab.
         */
        createRetrieverUI: function() {
            if ($('#tab-images .sspu-image-retriever-section').length > 0) return;

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
        },

        /**
         * Displays the retrieved and downloaded images in a gallery format.
         * @param {Array} images - Array of image objects with id, url, and thumb_url.
         */
        displayRetrievedImages: function(images) {
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
            if (this.isValidAlibabaUrl(this.state.currentAlibabaUrl)) {
                this.elements.$retrieveButton.prop('disabled', false).removeClass('button-disabled');
                this.elements.$status.text('Ready - ' + this.truncateUrl(this.state.currentAlibabaUrl));
            } else {
                this.elements.$retrieveButton.prop('disabled', true).addClass('button-disabled');
                this.elements.$status.text('Enter a valid Alibaba or 1688.com URL.');
            }
        },
        
        /**
         * Resets the retriever UI to its initial state after a process completes or fails.
         */
        resetRetrieverUI: function() {
            this.state.isRetrieving = false;
            this.elements.$spinner.removeClass('is-active');
            this.elements.$progress.fadeOut();
            this.updateUI();
        },

        /**
         * Shows a notification message within the retriever section.
         * @param {string} message - The message to display.
         * @param {string} type - 'success', 'warning', 'error', or 'info'.
         */
        showNotification: function(message, type = 'info') {
            const $notification = $(`<div class="notice notice-${type} is-dismissible" style="margin: 10px 0;"><p>${message}</p></div>`);
            this.elements.$container.find('.notice').remove(); // Remove old notices
            this.elements.$container.prepend($notification);

            if (type === 'success' || type === 'info') {
                setTimeout(() => $notification.fadeOut(500, function() { $(this).remove(); }), 5000);
            }
        },

        
        // --- CORE LOGIC ---

        /**
         * Retrieves the URL from various sources, validates it, and updates the state.
         */
        updateAlibabaUrl: function() {
            // Priority 1: Manual Input Field
            let url = this.elements.$manualUrlInput.val().trim();

            // Priority 2: Hidden input field (if manual is empty)
            if (!url) {
                const $urlInput = $('#current-alibaba-url');
                if ($urlInput.length > 0) {
                    url = $urlInput.val().trim();
                }
            }
            
            // Priority 3: Global window variable (fallback)
             if (!url && window.sspu_current_alibaba_url) {
                 url = window.sspu_current_alibaba_url;
             }
            
            this.state.currentAlibabaUrl = url;
            this.elements.$manualUrlInput.val(url); // Sync the input field
            
            this.updateUI();
        },

        /**
         * Handles the click on the "Use This URL" button.
         */
        useManualUrl: function() {
            this.updateAlibabaUrl();
            if (this.isValidAlibabaUrl(this.state.currentAlibabaUrl)) {
                this.showNotification('URL set successfully! Click "Retrieve Images" to start.', 'success');
            } else {
                this.showNotification('Please enter a valid Alibaba or 1688.com URL.', 'error');
            }
        },
        
        /**
         * Starts the two-step process of retrieving image URLs and then downloading them.
         */
        startImageRetrieval: function() {
            if (this.state.isRetrieving) return;
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
            const total = imageUrls.length;
            let downloadedCount = 0;
            const downloadedImages = [];
            
            for (let i = 0; i < total; i++) {
                try {
                    const result = await this.downloadSingleImage(imageUrls[i], i);
                    if (result.success) {
                        downloadedImages.push(result.data);
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
    SSPUImageRetriever.init();

})(jQuery);