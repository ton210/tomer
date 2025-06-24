(function($) {
    'use strict';

    // Debug mode flag
    const DEBUG_MODE = sspu_ajax.debug_mode || false;

    // Debug logging helper
    function debugLog(message, data = null) {
        if (DEBUG_MODE || window.location.hash === '#debug') {
            console.log('[SSPU Debug] ' + message, data || '');
        }
    }

    // Show visual notification
    function showNotification(message, type = 'info') {
        debugLog('Notification: ' + type, message);

        // Remove any existing notifications
        $('.sspu-notification').remove();

        const $notification = $(`
            <div class="sspu-notification notice notice-${type} is-dismissible">
                <p>${message.replace(/\n/g, '<br>')}</p>
            </div>
        `);

        $('#sspu-uploader-wrapper').prepend($notification);
        $notification.hide().slideDown();

        // Auto-dismiss after 5 seconds for success messages
        if (type === 'success') {
            setTimeout(function() {
                $notification.slideUp(function() {
                    $(this).remove();
                });
            }, 5000);
        }

        // Make dismissible
        $notification.on('click', '.notice-dismiss', function() {
            $notification.slideUp(function() {
                $(this).remove();
            });
        });
    }

    // Check if required settings are configured
    function checkConfiguration() {
        if (!sspu_ajax.openai_configured) {
            debugLog('OpenAI not configured - showing warnings instead of disabling');
            // Instead of disabling, we'll show warnings when buttons are clicked
        }

        if (!sspu_ajax.shopify_configured) {
            debugLog('Shopify not configured');
            $('#sspu-submit-button').prop('disabled', true).attr('title', sspu_ajax.strings.no_shopify_creds);
            showNotification(sspu_ajax.strings.no_shopify_creds, 'warning');
        }
    }

    // Alibaba URL Management
    function initializeAlibabaUrlFeature() {
        debugLog('Initializing Alibaba URL feature');
        
        // Check for existing assignment on page load
        checkAlibabaUrlAssignment();
        
        // Request new URL
        $('#request-alibaba-url').on('click', function() {
            debugLog('Request Alibaba URL clicked');
            
            const $button = $(this);
            const $spinner = $('#alibaba-url-spinner');
            
            $button.prop('disabled', true);
            $spinner.addClass('is-active');
            
            $.ajax({
                url: sspu_ajax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'sspu_request_alibaba_url',
                    nonce: sspu_ajax.nonce
                },
                dataType: 'json',
                success: function(response) {
                    debugLog('Request URL response', response);
                    
                    if (response.success) {
                        displayAssignedUrl(response.data);
                        showNotification('Alibaba URL assigned successfully!', 'success');
                    } else {
                        showNotification('Error: ' + response.data.message, 'error');
                    }
                },
                error: function(xhr) {
                    debugLog('Request URL error', xhr.responseText);
                    showNotification('Failed to request URL. Please try again.', 'error');
                },
                complete: function() {
                    $button.prop('disabled', false);
                    $spinner.removeClass('is-active');
                }
            });
        });
        
        // Complete URL
        $('#complete-alibaba-url').on('click', function() {
            debugLog('Complete Alibaba URL clicked');
            
            if (!confirm('Are you sure you want to mark this URL as complete? It will be removed from the queue.')) {
                return;
            }
            
            const $button = $(this);
            const $spinner = $('#alibaba-url-spinner');
            const queueId = $button.data('queue-id');
            
            $button.prop('disabled', true);
            $spinner.addClass('is-active');
            
            $.ajax({
                url: sspu_ajax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'sspu_complete_alibaba_url',
                    nonce: sspu_ajax.nonce,
                    queue_id: queueId
                },
                dataType: 'json',
                success: function(response) {
                    debugLog('Complete URL response', response);
                    
                    if (response.success) {
                        hideAssignedUrl();
                        showNotification('URL marked as complete!', 'success');
                    } else {
                        showNotification('Error: ' + response.data.message, 'error');
                    }
                },
                error: function(xhr) {
                    debugLog('Complete URL error', xhr.responseText);
                    showNotification('Failed to complete URL. Please try again.', 'error');
                },
                complete: function() {
                    $button.prop('disabled', false);
                    $spinner.removeClass('is-active');
                }
            });
        });
        
        // Release URL
        $('#release-alibaba-url').on('click', function() {
            debugLog('Release Alibaba URL clicked');
            
            if (!confirm('Are you sure you want to release this URL back to the queue?')) {
                return;
            }
            
            const $button = $(this);
            const $spinner = $('#alibaba-url-spinner');
            const queueId = $button.data('queue-id');
            
            $button.prop('disabled', true);
            $spinner.addClass('is-active');
            
            $.ajax({
                url: sspu_ajax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'sspu_release_alibaba_url',
                    nonce: sspu_ajax.nonce,
                    queue_id: queueId
                },
                dataType: 'json',
                success: function(response) {
                    debugLog('Release URL response', response);
                    
                    if (response.success) {
                        hideAssignedUrl();
                        showNotification('URL released back to queue!', 'info');
                    } else {
                        showNotification('Error: ' + response.data.message, 'error');
                    }
                },
                error: function(xhr) {
                    debugLog('Release URL error', xhr.responseText);
                    showNotification('Failed to release URL. Please try again.', 'error');
                },
                complete: function() {
                    $button.prop('disabled', false);
                    $spinner.removeClass('is-active');
                }
            });
        });
    }

    // Check for existing Alibaba URL assignment
    function checkAlibabaUrlAssignment() {
        debugLog('Checking for existing Alibaba URL assignment');
        
        $.ajax({
            url: sspu_ajax.ajaxurl,
            type: 'POST',
            data: {
                action: 'sspu_request_alibaba_url',
                nonce: sspu_ajax.nonce
            },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.data.url) {
                    displayAssignedUrl(response.data);
                } else {
                    hideAssignedUrl();
                }
            },
            error: function() {
                hideAssignedUrl();
            }
        });
    }

function displayAssignedUrl(data) {
    // --- FIX: Check for valid data and URL to prevent errors ---
    if (!data || !data.url) {
        console.error('Error: Invalid data or URL received.', data);
        return;
    }

    debugLog('Displaying assigned URL', data);

    // Hide the 'no URL' message and show the 'URL assigned' section
    $('#no-url-assigned').hide();
    $('#url-assigned').show();

    // Populate the UI elements with the new data
    $('#current-alibaba-url').val(data.url);
    $('#open-alibaba-url').attr('href', data.url);
    $('#url-assigned-time').text(formatDateTime(data.assigned_at));

    // Store the queue ID on the action buttons
    $('#complete-alibaba-url, #release-alibaba-url').data('queue-id', data.queue_id);

    // --- MODIFICATION: Store the URL globally as requested ---
    window.sspu_current_alibaba_url = data.url;
    
    // --- ADD: Trigger a custom event to notify other scripts ---
    $(document).trigger('sspu:alibaba-url-assigned', [data.url]);
}

    // Hide assigned URL
    function hideAssignedUrl() {
        debugLog('Hiding assigned URL');
        
        $('#url-assigned').hide();
        $('#no-url-assigned').show();
        
        $('#current-alibaba-url').val('');
        $('#open-alibaba-url').attr('href', '#');
        $('#url-assigned-time').text('');
    }

    // Helper function to format date/time
    function formatDateTime(dateTimeStr) {
        const date = new Date(dateTimeStr);
        return date.toLocaleString();
    }

    $(document).ready(function() {
        debugLog('SSPU Admin Script loaded', {
            ajaxurl: sspu_ajax.ajaxurl,
            nonce: sspu_ajax.nonce,
            openai_configured: sspu_ajax.openai_configured,
            shopify_configured: sspu_ajax.shopify_configured
        });

        // Check configuration
        checkConfiguration();

        // Initialize Alibaba URL feature if on uploader page
        if ($('#sspu-uploader-wrapper').length) {
            initializeAlibabaUrlFeature();
        }

        let variantCounter = 0; // Use a counter instead of index
        let autoSaveTimer;
        let aiImageIds = [];
        let isSubmitting = false;

        // Initialize tabs with animation
        $('#sspu-tabs').tabs({
            activate: function(event, ui) {
                ui.newPanel.fadeIn();
            }
        });

        // Initialize sortables
        initializeSortables();

        // Initialize Select2 for better multi-select experience (optional)
        function initializeCollectionSelector() {
            // Collection search functionality
            $('#sspu-collection-search').on('input', function() {
                const searchTerm = $(this).val().toLowerCase();
                filterCollections(searchTerm);
            });

            // Update selected count when selection changes
            $('#sspu-collection-select').on('change', function() {
                updateSelectedCollectionsCount();
            });
        }

        // Filter collections based on search term
        function filterCollections(searchTerm) {
            const $select = $('#sspu-collection-select');
            const $options = $select.find('option');
            
            if (!searchTerm || searchTerm.length === 0) {
                // Show all options if no search term
                $options.show();
                return;
            }
            
            // Hide/show options based on search
            $options.each(function() {
                const $option = $(this);
                const text = $option.text().toLowerCase();
                
                if (text.includes(searchTerm)) {
                    $option.show();
                } else {
                    $option.hide();
                }
            });
        }

        // Update the selected collections count display
        function updateSelectedCollectionsCount() {
            const selectedCount = $('#sspu-collection-select').val() ? $('#sspu-collection-select').val().length : 0;
            $('#selected-collections-count').text(selectedCount);
        }

        // Select all collections (including filtered ones)
        $('#sspu-select-all-collections').on('click', function() {
            debugLog('Select all collections clicked');
            
            // Only select visible options
            $('#sspu-collection-select option:visible').prop('selected', true);
            $('#sspu-collection-select').trigger('change');
            updateSelectedCollectionsCount();
            
            const selectedCount = $('#sspu-collection-select').val() ? $('#sspu-collection-select').val().length : 0;
            showNotification(`${selectedCount} collections selected.`, 'success');
        });

        // Clear collection selection
        $('#sspu-clear-collections').on('click', function() {
            debugLog('Clear collections clicked');
            $('#sspu-collection-select').val([]);
            $('#sspu-collection-select').trigger('change');
            updateSelectedCollectionsCount();
            showNotification('Collection selection cleared.', 'info');
        });

        // Initialize collections on page load
        initializeCollectionSelector();
        loadCollections();

        // Initialize auto-save
        initializeAutoSave();

        // Initialize drag and drop
        initializeDragAndDrop();

        // SEO character counters
        initializeSeoCounters();

        // Initialize keyboard shortcuts
        initializeKeyboardShortcuts();

        // Collection management
        $('#sspu-refresh-collections').on('click', function() {
            debugLog('Refresh collections clicked');
            const $button = $(this);
            $button.prop('disabled', true).addClass('loading');
            
            // Clear search field
            $('#sspu-collection-search').val('');
            
            loadCollections(function() {
                $button.prop('disabled', false).removeClass('loading');
                showNotification('Collections refreshed successfully!', 'success');
            });
        });

        $('#sspu-create-collection').on('click', function() {
            $('#sspu-new-collection').slideDown();
            $('#sspu-new-collection-name').focus();
        });

        $('#sspu-cancel-collection').on('click', function() {
            $('#sspu-new-collection').slideUp();
            $('#sspu-new-collection-name').val('');
        });

        $('#sspu-save-collection').on('click', function() {
            const collectionName = $('#sspu-new-collection-name').val().trim();
            if (!collectionName) {
                showNotification('Please enter a collection name.', 'error');
                $('#sspu-new-collection-name').focus();
                return;
            }

            const $button = $(this);
            $button.prop('disabled', true).addClass('loading');

            debugLog('Creating collection', collectionName);

            $.post(sspu_ajax.ajaxurl, {
                action: 'sspu_create_collection',
                nonce: sspu_ajax.nonce,
                collection_name: collectionName
            })
            .done(function(response) {
                debugLog('Create collection response', response);
                if (response.success) {
                    const collection = response.data;

                    // Add to select and select it
                    $('#sspu-collection-select').append(`<option value="${collection.id}" selected>${collection.title}</option>`);

                    // Update the selection
                    const currentSelections = $('#sspu-collection-select').val() || [];
                    currentSelections.push(collection.id.toString());
                    $('#sspu-collection-select').val(currentSelections).trigger('change');

                    $('#sspu-new-collection').slideUp();
                    $('#sspu-new-collection-name').val('');

                    updateSelectedCollectionsCount();
                    showNotification('Collection created and selected!', 'success');
                } else {
                    showNotification('Error: ' + (response.data?.message || 'Unknown error'), 'error');
                }
            })
            .fail(function(xhr) {
                debugLog('Create collection error', xhr.responseText);
                showNotification('Failed to create collection. Please try again.', 'error');
            })
            .always(function() {
                $button.prop('disabled', false).removeClass('loading');
            });
        });

        // ===== FIXED AI BUTTON HANDLERS =====

        // Product Name Formatting - Using direct binding since element exists at page load
        $('#format-product-name').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            debugLog('Format product name clicked');

            if (!sspu_ajax.openai_configured) {
                showNotification(sspu_ajax.strings.no_openai_key, 'warning');
                return;
            }

            const productName = $('#product-name-input').val().trim();
            if (!productName) {
                showNotification('Please enter a product name first.', 'warning');
                $('#product-name-input').focus();
                return;
            }

            const $button = $(this);
            const $spinner = $('#format-name-spinner');

            $button.prop('disabled', true);
            $spinner.addClass('is-active');

            debugLog('Sending format request', productName);

            $.ajax({
                url: sspu_ajax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'sspu_format_product_name',
                    nonce: sspu_ajax.nonce,
                    product_name: productName
                },
                dataType: 'json',
                success: function(response) {
                    debugLog('Format name response', response);
                    if (response.success && response.data && response.data.formatted_name) {
                        $('#product-name-input').val(response.data.formatted_name);
                        $('#product-name-input').trigger('input');
                        showNotification('Product name formatted successfully!', 'success');
                    } else {
                        const errorMsg = response.data && response.data.message ? response.data.message : 'Failed to format name';
                        showNotification('Error: ' + errorMsg, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    debugLog('Format name AJAX error', {
                        status: xhr.status,
                        error: error,
                        responseText: xhr.responseText
                    });
                    showNotification('Failed to format product name. Check console for details.', 'error');
                },
                complete: function() {
                    $button.prop('disabled', false);
                    $spinner.removeClass('is-active');
                }
            });
        });

        // AI Description Generation
        $('#sspu-generate-description').on('click', function(e) {
            e.preventDefault();
            debugLog('Generate description clicked');
            generateAIContent('description');
        });

        // AI Tags Generation
        $('#sspu-generate-tags').on('click', function(e) {
            e.preventDefault();
            debugLog('Generate tags clicked');
            generateAIContent('tags');
        });

        // AI Price Suggestion
        $('#sspu-suggest-price').on('click', function(e) {
            e.preventDefault();
            debugLog('Suggest price clicked');
            generateAIContent('price');
        });

        // SEO Title Generation
        $('#sspu-generate-seo-title').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            debugLog('Generate SEO title clicked');

            if (!sspu_ajax.openai_configured) {
                showNotification(sspu_ajax.strings.no_openai_key, 'warning');
                return;
            }

            const productName = $('input[name="product_name"]').val();
            const description = getEditorContent('product_description');

            if (!productName) {
                showNotification('Please enter a product name first.', 'warning');
                $('input[name="product_name"]').focus();
                return;
            }

            const $button = $(this);
            $button.prop('disabled', true);

            debugLog('Sending SEO title request', { productName, descriptionLength: description.length });

            $.ajax({
                url: sspu_ajax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'sspu_generate_seo',
                    nonce: sspu_ajax.nonce,
                    product_name: productName,
                    description: description,
                    type: 'title'
                },
                dataType: 'json',
                success: function(response) {
                    debugLog('SEO title response', response);
                    if (response.success && response.data && response.data.content) {
                        $('input[name="seo_title"]').val(response.data.content).trigger('input').addClass('updated-field');
                        showNotification('SEO title generated successfully!', 'success');
                        setTimeout(function() {
                            $('.updated-field').removeClass('updated-field');
                        }, 1000);
                    } else {
                        showNotification('Error: ' + (response.data?.message || 'Failed to generate SEO title'), 'error');
                    }
                },
                error: function(xhr) {
                    debugLog('SEO title error', xhr.responseText);
                    showNotification('Failed to generate SEO title.', 'error');
                },
                complete: function() {
                    $button.prop('disabled', false);
                }
            });
        });

        // Meta Description Generation
        $('#sspu-generate-meta-desc').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            debugLog('Generate meta description clicked');

            if (!sspu_ajax.openai_configured) {
                showNotification(sspu_ajax.strings.no_openai_key, 'warning');
                return;
            }

            const productName = $('input[name="product_name"]').val();
            const description = getEditorContent('product_description');

            if (!productName) {
                showNotification('Please enter a product name first.', 'warning');
                $('input[name="product_name"]').focus();
                return;
            }

            const $button = $(this);
            $button.prop('disabled', true);

            debugLog('Sending meta description request', { productName, descriptionLength: description.length });

            $.ajax({
                url: sspu_ajax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'sspu_generate_seo',
                    nonce: sspu_ajax.nonce,
                    product_name: productName,
                    description: description,
                    type: 'meta'
                },
                dataType: 'json',
                success: function(response) {
                    debugLog('Meta description response', response);
                    if (response.success && response.data && response.data.content) {
                        $('textarea[name="meta_description"]').val(response.data.content).trigger('input').addClass('updated-field');
                        showNotification('Meta description generated successfully!', 'success');
                        setTimeout(function() {
                            $('.updated-field').removeClass('updated-field');
                        }, 1000);
                    } else {
                        showNotification('Error: ' + (response.data?.message || 'Failed to generate meta description'), 'error');
                    }
                },
                error: function(xhr) {
                    debugLog('Meta description error', xhr.responseText);
                    showNotification('Failed to generate meta description.', 'error');
                },
                complete: function() {
                    $button.prop('disabled', false);
                }
            });
        });

        // Variant Price Suggestion (for suggest buttons in variants)
        $(document).on('click', '.suggest-price', function(e) {
            e.preventDefault();
            debugLog('Variant price suggestion clicked');

            if (!sspu_ajax.openai_configured) {
                showNotification(sspu_ajax.strings.no_openai_key, 'warning');
                return;
            }

            const $button = $(this);
            const $variantRow = $button.closest('.sspu-variant-row');
            const productName = $('input[name="product_name"]').val();
            const description = getEditorContent('product_description');
            const variantInfo = $variantRow.find('.sspu-variant-option-value').val();

            if (!productName && !description) {
                showNotification('Please enter a product name or description first.', 'warning');
                return;
            }

            $button.prop('disabled', true).addClass('loading');

            debugLog('Sending price suggestion request', { productName, variantInfo });

            $.ajax({
                url: sspu_ajax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'sspu_suggest_price',
                    nonce: sspu_ajax.nonce,
                    product_name: productName,
                    description: description,
                    variant_info: variantInfo
                },
                dataType: 'json',
                success: function(response) {
                    debugLog('Variant price response', response);
                    if (response.success && response.data && response.data.price) {
                        $variantRow.find('.sspu-variant-price').val(response.data.price).addClass('updated-field');
                        showNotification('Price suggested successfully!', 'success');
                        setTimeout(function() {
                            $('.updated-field').removeClass('updated-field');
                        }, 1000);
                    } else {
                        showNotification('Error: ' + (response.data?.message || 'Failed to suggest price'), 'error');
                    }
                },
                error: function(xhr) {
                    debugLog('Variant price error', xhr.responseText);
                    showNotification('Failed to suggest price.', 'error');
                },
                complete: function() {
                    $button.prop('disabled', false).removeClass('loading');
                }
            });
        });

        // ===== VARIANT GENERATION =====

        // Variant Generation
        $('#generate-variants-btn').on('click', function() {
            debugLog('Generate variants clicked');

            const optionName = $('#variant-option-name').val().trim();
            const optionValues = $('#variant-option-values').val().trim();
            const basePrice = parseFloat($('#variant-base-price').val()) || 0;

            if (!optionName || !optionValues) {
                showNotification('Please enter option name and values.', 'warning');
                return;
            }

            const $button = $(this);
            $button.prop('disabled', true).addClass('loading');

            debugLog('Sending generate variants request', { optionName, optionValues, basePrice });

            $.post(sspu_ajax.ajaxurl, {
                action: 'sspu_generate_variants',
                nonce: sspu_ajax.nonce,
                option_name: optionName,
                option_values: optionValues,
                base_price: basePrice
            })
            .done(function(response) {
                debugLog('Generate variants response', response);
                if (response.success) {
                    // Clear existing variants if any
                    if ($('#sspu-variants-wrapper .sspu-variant-row').length > 0) {
                        if (!confirm('This will replace existing variants. Continue?')) {
                            return;
                        }
                        $('#sspu-variants-wrapper').empty();
                        variantCounter = 0;
                    }

                    // Generate variants with animation
                    response.data.variants.forEach(function(variant, index) {
                        setTimeout(function() {
                            addVariant(variant);
                        }, index * 100); // Stagger the animation
                    });

                    // Clear the generator form
                    $('#variant-option-name').val('');
                    $('#variant-option-values').val('');
                    $('#variant-base-price').val('');

                    showNotification(`Generated ${response.data.variants.length} variants successfully!`, 'success');
                } else {
                    showNotification('Error: ' + (response.data?.message || 'Failed to generate variants'), 'error');
                }
            })
            .fail(function(xhr) {
                debugLog('Generate variants error', xhr.responseText);
                showNotification('Failed to generate variants.', 'error');
            })
            .always(function() {
                $button.prop('disabled', false).removeClass('loading');
            });
        });

        // Clear all variants
        $('#clear-variants-btn').on('click', function() {
            debugLog('Clear variants clicked');

            if ($('#sspu-variants-wrapper .sspu-variant-row').length === 0) {
                showNotification('No variants to clear.', 'info');
                return;
            }

            if (confirm('Are you sure you want to clear all variants?')) {
                $('#sspu-variants-wrapper').fadeOut(300, function() {
                    $(this).empty().fadeIn();
                    variantCounter = 0;
                    showNotification('All variants cleared.', 'success');
                });
            }
        });

        // Bulk pricing actions
        $('#apply-price-to-all').on('click', function() {
            debugLog('Apply price to all clicked');

            const $firstVariant = $('.sspu-variant-row:first');
            if ($firstVariant.length === 0) {
                showNotification('No variants found.', 'warning');
                return;
            }

            const firstPrice = $firstVariant.find('.sspu-variant-price').val();
            if (!firstPrice) {
                showNotification('Please set a price in the first variant.', 'warning');
                $firstVariant.find('.sspu-variant-price').focus();
                return;
            }

            $('.sspu-variant-row:visible').each(function (index) {
                if (index > 0) { // Skip first variant
                    $(this).find('.sspu-variant-price').val(firstPrice).addClass('updated-field');
                }
            });

            showNotification('Price applied to all variants.', 'success');
            setTimeout(function() {
                $('.updated-field').removeClass('updated-field');
            }, 1000);
        });

        // Enhanced Tier Management Functions

        // Store tier data as JSON for easy copying
        function getTierDataAsJSON($variantRow) {
            const tiers = [];
            $variantRow.find('.volume-tier-row').each(function() {
                const $row = $(this);
                const minQty = $row.find('.tier-min-quantity').val();
                const price = $row.find('.tier-price').val();

                if (minQty && price) {
                    tiers.push({
                        min_quantity: parseInt(minQty),
                        price: parseFloat(price)
                    });
                }
            });

            return JSON.stringify(tiers);
        }

        // Apply tier data from JSON
        function applyTierDataFromJSON($variantRow, jsonData) {
            try {
                const tiers = JSON.parse(jsonData);
                const $tiersBody = $variantRow.find('.volume-tiers-body');

                // Clear existing tiers
                $tiersBody.empty();

                // Add new tiers
                tiers.forEach(function(tier) {
                    addVolumeTier($variantRow, tier.min_quantity, tier.price);
                });

                return true;
            } catch (e) {
                debugLog('Error parsing tier data', e);
                return false;
            }
        }

        // NEW: Copy/Paste functionality for tiers
        function initializeTierCopyPaste() {
            let copiedTierData = null;

            // Add copy/paste buttons to each variant
            $('.sspu-variant-row').each(function() {
                const $variantRow = $(this);
                const $controls = $variantRow.find('.volume-pricing-controls');

                // Add copy/paste buttons if they don't exist
                if ($controls.find('.copy-tiers').length === 0) {
                    $controls.append(`
                        <button type="button" class="button copy-tiers" title="Copy tier structure">Copy Tiers</button>
                        <button type="button" class="button paste-tiers" title="Paste tier structure" disabled>Paste Tiers</button>
                    `);
                }
            });

            // Copy handler
            $(document).on('click', '.copy-tiers', function() {
                const $variantRow = $(this).closest('.sspu-variant-row');
                copiedTierData = getTierDataAsJSON($variantRow);

                // Enable all paste buttons
                $('.paste-tiers').prop('disabled', false);

                // Visual feedback
                $(this).text('Copied!').addClass('button-primary');
                setTimeout(() => {
                    $(this).text('Copy Tiers').removeClass('button-primary');
                }, 2000);

                debugLog('Copied tier data', copiedTierData);
                showNotification('Tier structure copied! You can now paste it to other variants.', 'info');
            });

            // Paste handler
            $(document).on('click', '.paste-tiers', function() {
                if (!copiedTierData) {
                    showNotification('No tier data to paste. Copy tiers from a variant first.', 'warning');
                    return;
                }

                const $variantRow = $(this).closest('.sspu-variant-row');
                if (applyTierDataFromJSON($variantRow, copiedTierData)) {
                    showNotification('Tiers pasted successfully!', 'success');
                    $(this).text('Pasted!').addClass('button-primary');
                    setTimeout(() => {
                        $(this).text('Paste Tiers').removeClass('button-primary');
                    }, 2000);
                } else {
                    showNotification('Failed to paste tiers.', 'error');
                }
            });
        }

        // IMPROVED: Apply tiers to all with better error handling
        $('#apply-tiers-to-all').off('click').on('click', function() {
            debugLog('Apply tiers to all clicked (improved version)');

            const $firstVariant = $('.sspu-variant-row:visible:first');
            if ($firstVariant.length === 0) {
                showNotification('No visible variants found.', 'warning');
                return;
            }

            // Get tier data as JSON for reliable copying
            const tierDataJSON = getTierDataAsJSON($firstVariant);
            const tierCount = JSON.parse(tierDataJSON).length;

            if (tierCount === 0) {
                showNotification('No volume tiers found in the first variant.', 'warning');
                return;
            }

            // Confirm action
            if (!confirm(`Apply ${tierCount} tiers from the first variant to all other visible variants?`)) {
                return;
            }

            let successCount = 0;
            let failCount = 0;

            $('.sspu-variant-row:visible').each(function(index) {
                if (index > 0) { // Skip first variant
                    const $variantRow = $(this);
                    const variantId = $variantRow.attr('data-variant-id');

                    if (!variantId) {
                        debugLog('Skipping variant without ID');
                        failCount++;
                        return;
                    }

                    if (applyTierDataFromJSON($variantRow, tierDataJSON)) {
                        successCount++;
                    } else {
                        failCount++;
                    }
                }
            });

            if (failCount > 0) {
                showNotification(`Applied ${tierCount} tiers to ${successCount} variants. ${failCount} failed.`, 'warning');
            } else {
                showNotification(`Successfully applied ${tierCount} tiers to ${successCount} other variants!`, 'success');
            }
        });

        // NEW: Tier Templates
        const TIER_TEMPLATES = {
            standard: [
                { min_quantity: 50, discount: 5 },
                { min_quantity: 100, discount: 10 },
                { min_quantity: 250, discount: 15 },
                { min_quantity: 500, discount: 20 },
                { min_quantity: 1000, discount: 25 },
                { min_quantity: 2500, discount: 30 },
                { min_quantity: 5000, discount: 35 }
            ],
            aggressive: [
                { min_quantity: 50, discount: 10 },
                { min_quantity: 100, discount: 15 },
                { min_quantity: 250, discount: 20 },
                { min_quantity: 500, discount: 25 },
                { min_quantity: 1000, discount: 30 },
                { min_quantity: 2500, discount: 35 },
                { min_quantity: 5000, discount: 40 }
            ],
            minimal: [
                { min_quantity: 100, discount: 5 },
                { min_quantity: 500, discount: 10 },
                { min_quantity: 1000, discount: 15 }
            ]
        };

        // Add template selector to variants
        function addTierTemplateSelector($variantRow) {
            const $controls = $variantRow.find('.volume-pricing-controls');

            if ($controls.find('.tier-template-selector').length === 0) {
                $controls.prepend(`
                    <select class="tier-template-selector">
                        <option value="">-- Apply Template --</option>
                        <option value="standard">Standard (5-35% discount)</option>
                        <option value="aggressive">Aggressive (10-40% discount)</option>
                        <option value="minimal">Minimal (3 tiers)</option>
                    </select>
                `);
            }
        }

        // Template change handler
        $(document).on('change', '.tier-template-selector', function() {
            const templateName = $(this).val();
            if (!templateName) return;

            const $variantRow = $(this).closest('.sspu-variant-row');
            const basePrice = parseFloat($variantRow.find('.sspu-variant-price').val());

            if (!basePrice || basePrice <= 0) {
                showNotification('Please enter a base price first.', 'warning');
                $(this).val(''); // Reset selector
                return;
            }

            const template = TIER_TEMPLATES[templateName];
            const $tiersBody = $variantRow.find('.volume-tiers-body');

            // Clear existing tiers
            $tiersBody.empty();

            // Apply template
            template.forEach(function(tier) {
                const tierPrice = (basePrice * (1 - tier.discount / 100)).toFixed(2);
                addVolumeTier($variantRow, tier.min_quantity, tierPrice);
            });

            showNotification(`Applied ${templateName} pricing template.`, 'success');
            $(this).val(''); // Reset selector
        });

        // Initialize everything when variants are added
        function initializeVariantTierFeatures($variantRow) {
            addTierTemplateSelector($variantRow);
            initializeTierCopyPaste();
        }

        $('#auto-generate-all-skus').on('click', function() {
            debugLog('Auto generate all SKUs clicked');

            const productName = $('input[name="product_name"]').val();
            if (!productName) {
                showNotification('Please enter a product name first.', 'warning');
                $('input[name="product_name"]').focus();
                return;
            }

            let skusGenerated = 0;
            const $button = $(this);
            $button.prop('disabled', true).addClass('loading');

            const skuPromises = [];

            $('.sspu-variant-row').each(function() {
                const $row = $(this);
                const $skuField = $row.find('.sspu-variant-sku');

                if (!$skuField.val()) { // Only generate if SKU is empty
                    const variantName = $row.find('.sspu-variant-option-name').val();
                    const variantValue = $row.find('.sspu-variant-option-value').val();

                    const promise = $.post(sspu_ajax.ajaxurl, {
                        action: 'sspu_generate_sku',
                        nonce: sspu_ajax.nonce,
                        product_name: productName,
                        variant_name: variantName,
                        variant_value: variantValue
                    })
                    .done(function(response) {
                        if (response.success) {
                            $skuField.val(response.data.sku).addClass('updated-field');
                            skusGenerated++;
                        }
                    });

                    skuPromises.push(promise);
                }
            });

            $.when.apply($, skuPromises).always(function() {
                $button.prop('disabled', false).removeClass('loading');
                showNotification(`Generated ${skusGenerated} SKUs.`, 'success');
                setTimeout(function() {
                    $('.updated-field').removeClass('updated-field');
                }, 1000);
            });
        });

        // AI Image Upload
        $('#sspu-upload-ai-images').on('click', function(e) {
            e.preventDefault();
            debugLog('Upload AI images clicked');

            const mediaUploader = wp.media.frames.file_frame = wp.media({
                title: 'Select Images for AI Analysis',
                button: { text: 'Use these images' },
                multiple: true
            });

            mediaUploader.on('select', function() {
                const attachments = mediaUploader.state().get('selection').toJSON();
                aiImageIds = [];
                $('#sspu-ai-images-preview').html('');

                attachments.forEach(function(attachment) {
                    aiImageIds.push(attachment.id);
                    $('#sspu-ai-images-preview').append(`
                        <img src="${attachment.sizes.thumbnail.url}" 
                             alt="${attachment.alt}" 
                             style="max-width: 100px; margin: 5px;"
                             class="ai-preview-image"/>
                    `);
                });

                $('.ai-preview-image').fadeIn();
                debugLog('AI images selected', aiImageIds);
            });

            mediaUploader.open();
        });

        // SKU Generation
        $(document).on('click', '.generate-sku', function() {
            debugLog('Generate SKU clicked');

            const $button = $(this);
            const $variantRow = $button.closest('.sspu-variant-row');

            const productName = $('input[name="product_name"]').val();
            const variantName = $variantRow.find('.sspu-variant-option-name').val();
            const variantValue = $variantRow.find('.sspu-variant-option-value').val();

            if (!productName) {
                showNotification('Please enter a product name first.', 'warning');
                $('input[name="product_name"]').focus();
                return;
            }

            $button.prop('disabled', true).addClass('loading');

            $.post(sspu_ajax.ajaxurl, {
                action: 'sspu_generate_sku',
                nonce: sspu_ajax.nonce,
                product_name: productName,
                variant_name: variantName,
                variant_value: variantValue
            })
            .done(function(response) {
                debugLog('Generate SKU response', response);
                if (response.success) {
                    $variantRow.find('.sspu-variant-sku').val(response.data.sku).addClass('updated-field');
                    setTimeout(function() {
                        $('.updated-field').removeClass('updated-field');
                    }, 1000);
                } else {
                    showNotification('Error: ' + (response.data?.message || 'Failed to generate SKU'), 'error');
                }
            })
            .fail(function(xhr) {
                debugLog('Generate SKU error', xhr.responseText);
                showNotification('Failed to generate SKU.', 'error');
            })
            .always(function() {
                $button.prop('disabled', false).removeClass('loading');
            });
        });

        // Volume Tier Auto-calculation - FIXED VERSION
        $(document).on('click', '.auto-calculate-tiers', function() {
            debugLog('Auto calculate tiers clicked');

            const $button = $(this);
            const $variantRow = $button.closest('.sspu-variant-row');
            const basePrice = parseFloat($variantRow.find('.sspu-variant-price').val());

            if (!basePrice || basePrice <= 0) {
                showNotification('Please enter a base price first.', 'warning');
                $variantRow.find('.sspu-variant-price').focus();
                return;
            }

            $button.prop('disabled', true).text('Calculating...');

            $.post(sspu_ajax.ajaxurl, {
                action: 'sspu_calculate_volume_tiers',
                nonce: sspu_ajax.nonce,
                base_price: basePrice
            })
            .done(function(response) {
                debugLog('Calculate tiers response', response);
                if (response.success && response.data.tiers) {
                    const $tiersBody = $variantRow.find('.volume-tiers-body');

                    // Clear existing tiers
                    $tiersBody.empty();

                    // Add new tiers
                    response.data.tiers.forEach(function(tier) {
                        addVolumeTier($variantRow, tier.min_quantity, tier.price);
                    });

                    showNotification(`Added ${response.data.tiers.length} volume pricing tiers!`, 'success');
                } else {
                    showNotification('Error: ' + (response.data?.message || 'Failed to calculate tiers'), 'error');
                }
            })
            .fail(function(xhr) {
                debugLog('Calculate tiers error', xhr.responseText);
                showNotification('Failed to calculate tiers. Please try again.', 'error');
            })
            .always(function() {
                $button.prop('disabled', false).text('Auto-Calculate Tiers');
            });
        });

        // NEW: Global pricing tier manager
        $('#open-pricing-manager').on('click', function() {
            openPricingManager();
        });

        // Draft Management
        $('#sspu-save-draft').on('click', function() {
            debugLog('Save draft clicked');
            saveDraft(false);
        });

        $('#sspu-load-draft').on('click', function() {
            debugLog('Load draft clicked');
            loadDraft();
        });

        // Variant management
        $('#sspu-add-variant-btn').on('click', function() {
            debugLog('Add variant clicked');
            addVariant();
        });

        $(document).on('click', '.sspu-remove-variant-btn', function() {
            if (confirm('Are you sure you want to remove this variant?')) {
                const $variantRow = $(this).closest('.sspu-variant-row');
                $variantRow.fadeOut(300, function() {
                    $(this).remove();
                    updateVariantNumbers();
                    showNotification('Variant removed.', 'success');
                });
            }
        });

        $(document).on('click', '.sspu-duplicate-variant', function() {
            debugLog('Duplicate variant clicked');
            const $variantRow = $(this).closest('.sspu-variant-row');
            const variantData = getVariantData($variantRow);
            addVariant(variantData);
            showNotification('Variant duplicated.', 'success');
        });

        // Volume tier management - FIXED HANDLERS
        $(document).on('click', '.add-volume-tier', function() {
            debugLog('Add volume tier clicked');
            const $variantRow = $(this).closest('.sspu-variant-row');
            addVolumeTier($variantRow);
        });

        $(document).on('click', '.remove-volume-tier', function() {
            debugLog('Remove volume tier clicked');
            const $tierRow = $(this).closest('.volume-tier-row');
            const $variantRow = $(this).closest('.sspu-variant-row');

            $tierRow.fadeOut(200, function() {
                $(this).remove();
                // Update indices after removal
                updateTierIndices($variantRow);
            });
        });

        // Image upload handling with validation
        let mediaUploader;
        $(document).on('click', '.sspu-upload-image-btn', function(e) {
            e.preventDefault();
            debugLog('Upload image button clicked');

            const $button = $(this);
            const isMultiple = $button.data('multiple') === true;

            let $preview, $idField;
            if ($button.data('target-preview-class')) {
                 $preview = $button.closest('.sspu-variant-row').find('.' + $button.data('target-preview-class'));
                 $idField = $button.closest('.sspu-variant-row').find('.' + $button.data('target-id-class'));
            } else {
                $preview = $('#' + $button.data('target-preview'));
                $idField = $('#' + $button.data('target-id'));
            }

            mediaUploader = wp.media.frames.file_frame = wp.media({
                title: 'Select Image',
                button: { text: 'Use this image' },
                multiple: isMultiple,
                library: {
                    type: 'image'
                }
            });

            mediaUploader.on('select', function() {
                const attachments = mediaUploader.state().get('selection').toJSON();
                let ids = [];
                if (!isMultiple) {
                    $preview.html('');
                }

                attachments.forEach(function(attachment) {
                    // Validate image
                    validateImage(attachment.id, function(isValid, data) {
                        if (isValid) {
                            ids.push(attachment.id);
                            const $img = $(`<img src="${attachment.sizes.thumbnail.url}" alt="${attachment.alt}" data-id="${attachment.id}"/>`);
                            $img.hide().appendTo($preview).fadeIn(300);
                        } else {
                            showNotification('Image validation failed: ' + data.message, 'error');
                        }
                    });
                });

                setTimeout(function() {
                    $idField.val(ids.join(','));
                    updateImageOrder($preview, $idField);
                }, 500);
            });

            mediaUploader.open();
        });

        // AI Suggest All Pricing - FIXED VERSION
        $('#ai-suggest-all-pricing').on('click', function() {
            debugLog('AI Suggest All Pricing clicked');

            const productName = $('input[name="product_name"]').val();
            const mainImageId = $('input[name="main_image_id"]').val();
            const minQuantity = $('input[name="product_min"]').val() || 25;

            if (!productName) {
                showNotification('Please enter a product name first.', 'warning');
                $('input[name="product_name"]').focus();
                return;
            }

            if (!mainImageId) {
                showNotification('Please select a main product image first.', 'warning');
                return;
            }

            const $button = $(this);
            const $spinner = $button.siblings('.spinner');

            $button.prop('disabled', true);
            $spinner.addClass('is-active');

            // Show loading message
            showNotification('Analyzing product and generating pricing...', 'info');

            $.ajax({
                url: sspu_ajax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'sspu_ai_suggest_all_pricing',
                    nonce: sspu_ajax.nonce,
                    product_name: productName,
                    main_image_id: mainImageId,
                    min_quantity: minQuantity
                },
                dataType: 'json',
                success: function(response) {
                    debugLog('AI Pricing response', response);

                    if (response.success && response.data) {
                        // Apply base price to first variant
                        const $firstVariant = $('.sspu-variant-row:first');
                        if ($firstVariant.length && response.data.base_price) {
                            $firstVariant.find('.sspu-variant-price')
                                .val(response.data.base_price)
                                .addClass('updated-field')
                                .trigger('change');

                            debugLog('Base price set', response.data.base_price);
                        }

                        // Add volume tiers to first variant
                        if (response.data.tiers && response.data.tiers.length > 0 && $firstVariant.length) {
                            const $tiersBody = $firstVariant.find('.volume-tiers-body');

                            // Clear any existing tiers
                            $tiersBody.empty();
                            debugLog('Cleared existing tiers');

                            // Add each tier
                            response.data.tiers.forEach(function(tier, index) {
                                debugLog('Adding AI tier', {
                                    index: index,
                                    tier: tier,
                                    variantId: $firstVariant.attr('data-variant-id')
                                });
                                addVolumeTier($firstVariant, tier.min_quantity, tier.price);
                            });

                            showNotification(
                                `AI Pricing Complete! Base price: $${response.data.base_price} with ${response.data.tiers.length} volume tiers.`,
                                'success'
                            );

                            // Show rationale if provided
                            if (response.data.rationale) {
                                const rationaleHtml = `
                                    <div class="pricing-rationale notice notice-info is-dismissible" style="margin-top: 15px;">
                                        <p><strong>AI Pricing Rationale:</strong> ${response.data.rationale}</p>
                                    </div>
                                `;
                                $firstVariant.after(rationaleHtml);

                                // Auto-dismiss after 15 seconds
                                setTimeout(function() {
                                    $('.pricing-rationale').fadeOut(function() {
                                        $(this).remove();
                                    });
                                }, 15000);
                            }

                            // Highlight the updated fields
                            setTimeout(function() {
                                $('.updated-field').removeClass('updated-field');
                            }, 2000);

                            // Offer to apply to all variants if there are multiple
                            if ($('.sspu-variant-row').length > 1) {
                                setTimeout(function() {
                                    if (confirm('Would you like to apply this pricing to all variants?')) {
                                        $('#apply-price-to-all').trigger('click');
                                        setTimeout(function() {
                                            $('#apply-tiers-to-all').trigger('click');
                                        }, 500);
                                    }
                                }, 1000);
                            }
                        } else {
                            showNotification('AI generated base price but no volume tiers.', 'warning');
                        }
                    } else {
                        const errorMsg = response.data?.message || 'Failed to generate AI pricing';
                        showNotification('Error: ' + errorMsg, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    debugLog('AI Pricing error', {
                        status: xhr.status,
                        error: error,
                        responseText: xhr.responseText
                    });

                    let errorMsg = 'Failed to generate AI pricing.';
                    if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                        errorMsg = xhr.responseJSON.data.message;
                    }

                    showNotification(errorMsg, 'error');
                },
                complete: function() {
                    $button.prop('disabled', false);
                    $spinner.removeClass('is-active');
                }
            });
        });

        // Form submission with enhanced progress tracking
        $('#sspu-product-form').on('submit', function(e) {
            e.preventDefault();
            debugLog('Form submitted');

            if (isSubmitting) {
                debugLog('Already submitting, ignoring');
                return false;
            }

            const $form = $(this);
            const $submitButton = $('#sspu-submit-button');
            const $spinner = $form.find('.spinner');
            const $statusBox = $('#sspu-status-box');
            const $statusLog = $('#sspu-status-log');
            const $statusHeading = $('#sspu-status-heading');
            const $progressBar = $('#sspu-progress-bar');
            const $progressFill = $('#sspu-progress-fill');
            const $progressText = $('#sspu-progress-text');

            // Validate form
            if (!validateForm()) {
                return false;
            }

            // Clear any existing auto-save timer
            clearTimeout(autoSaveTimer);

            isSubmitting = true;
            $submitButton.prop('disabled', true);
            $spinner.addClass('is-active');
            $statusBox.removeClass('success error').addClass('processing').show();
            $progressBar.show();
            $statusHeading.text('Processing...');
            $statusLog.text('Initializing submission...');

            // Enhanced progress simulation
            let progress = 0;
            const progressSteps = [
                { progress: 10, message: 'Validating form data...' },
                { progress: 25, message: 'Preparing product data...' },
                { progress: 40, message: 'Creating product on Shopify...' },
                { progress: 60, message: 'Uploading images...' },
                { progress: 75, message: 'Setting up variants...' },
                { progress: 90, message: 'Finalizing...' }
            ];

            let stepIndex = 0;
            const progressInterval = setInterval(function() {
                if (stepIndex < progressSteps.length) {
                    const step = progressSteps[stepIndex];
                    progress = step.progress;
                    updateProgress(progress);
                    $statusLog.append('\n' + step.message);
                    $statusLog.scrollTop($statusLog[0].scrollHeight);
                    stepIndex++;
                } else {
                    progress += Math.random() * 5;
                    if (progress > 95) progress = 95;
                    updateProgress(progress);
                }
            }, 800);

            const formData = collectFormData();
            debugLog('Form data collected', formData);

            function updateProgress(percent) {
                $progressFill.css('width', percent + '%');
                $progressText.text(Math.round(percent) + '%');
            }

            $.ajax({
                url: sspu_ajax.ajaxurl,
                type: 'POST',
                data: formData,
                dataType: 'json'
            })
            .done(function(response) {
                clearInterval(progressInterval);
                updateProgress(100);
                debugLog('Form submission response', response);

                if (response.success) {
                    $statusBox.removeClass('processing').addClass('success');
                    $statusHeading.html('Success! <a href="' + response.data.product_url + '" target="_blank" class="button button-primary">View Product in Shopify</a>');
                    $statusLog.text(response.data.log.join('\n'));

                    // Clear the form after successful submission
                    setTimeout(function() {
                        if (confirm('Product uploaded successfully! Would you like to clear the form to create another product?')) {
                            clearForm();
                            $statusBox.fadeOut();
                        }
                    }, 2000);
                } else {
                    $statusBox.removeClass('processing').addClass('error');
                    $statusHeading.text('Error!');
                    const errorMessage = response.data.log ? response.data.log.join('\n') : 'An unknown error occurred.';
                    $statusLog.text(errorMessage);
                }
            })
            .fail(function(xhr) {
                clearInterval(progressInterval);
                $statusBox.removeClass('processing').addClass('error');
                $statusHeading.text('Fatal Error');
                $statusLog.text('A server error occurred. Check the browser console (F12) for more details.\n\n' + xhr.responseText);
                debugLog('Form submission error', xhr.responseText);
            })
            .always(function() {
                isSubmitting = false;
                $submitButton.prop('disabled', false);
                $spinner.removeClass('is-active');
                setTimeout(function() {
                    $progressBar.hide();
                }, 2000);
            });
        });

        // Helper Functions
        function initializeSortables() {
            debugLog('Initializing sortables');

            // Make images sortable
            $('.sortable-images').sortable({
                tolerance: 'pointer',
                cursor: 'move',
                placeholder: 'sortable-placeholder',
                update: function(event, ui) {
                    const $container = $(this);
                    const $idField = $container.siblings('input[type="hidden"]');
                    updateImageOrder($container, $idField);
                }
            });

            // Make variants sortable
            $('#sspu-variants-wrapper').sortable({
                handle: '.drag-handle',
                tolerance: 'pointer',
                cursor: 'move',
                placeholder: 'sortable-placeholder',
                update: function(event, ui) {
                    updateVariantNumbers();
                }
            });

            // Make volume tiers sortable
            $(document).on('sortstop', '.sortable-tiers', function(event, ui) {
                // Update tier order in form data
            });
        }

        function initializeDragAndDrop() {
            debugLog('Initializing drag and drop');

            const $dropZone = $('#sspu-image-drop-zone');

            $dropZone.on('dragover', function(e) {
                e.preventDefault();
                $(this).addClass('drag-over');
            });

            $dropZone.on('dragleave', function(e) {
                e.preventDefault();
                $(this).removeClass('drag-over');
            });

            $dropZone.on('drop', function(e) {
                e.preventDefault();
                $(this).removeClass('drag-over');

                const files = e.originalEvent.dataTransfer.files;
                handleFileUpload(files);
            });

            $dropZone.on('click', function() {
                $('#sspu-additional-images-preview').siblings('.sspu-upload-image-btn').click();
            });
        }

        function initializeSeoCounters() {
            debugLog('Initializing SEO counters');

            $('input[name="seo_title"]').on('input', function() {
                const length = $(this).val().length;
                const $counter = $(this).siblings('.seo-feedback').find('.char-count');
                $counter.text(length + '/60');
                $counter.toggleClass('over-limit', length > 60);
            });

            $('textarea[name="meta_description"]').on('input', function() {
                const length = $(this).val().length;
                const $counter = $(this).siblings('.seo-feedback').find('.char-count');
                $counter.text(length + '/160');
                $counter.toggleClass('over-limit', length > 160);
            });

            $('input[name="product_name"]').on('input', function() {
                const length = $(this).val().length;
                const $feedback = $(this).siblings('.seo-feedback').find('.title-length');
                $feedback.text(`Title length: ${length} characters`);

                if (length > 70) {
                    $feedback.addClass('warning').text(`Title length: ${length} characters (too long for SEO)`);
                } else {
                    $feedback.removeClass('warning');
                }
            });
        }

        function initializeAutoSave() {
            debugLog('Initializing auto-save');

            // Auto-save every 30 seconds
            setInterval(function() {
                if (!isSubmitting) {
                    saveDraft(true);
                }
            }, 30000);

            // Save on form changes
            $('#sspu-product-form').on('change input', function() {
                clearTimeout(autoSaveTimer);
                autoSaveTimer = setTimeout(function() {
                    if (!isSubmitting) {
                        saveDraft(true);
                    }
                }, 5000);
            });
        }

        function initializeKeyboardShortcuts() {
            debugLog('Initializing keyboard shortcuts');

            $(document).on('keydown', function(e) {
                // Ctrl/Cmd + S to save draft
                if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                    e.preventDefault();
                    saveDraft(false);
                }

                // Ctrl/Cmd + Enter to submit form
                if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                    e.preventDefault();
                    $('#sspu-product-form').submit();
                }
            });
        }

        // UPDATED validateForm function with enhanced debugging
        function validateForm() {
         debugLog('Validating form');
         let isValid = true;
         const errors = [];

        // Check product name
        const $productNameInput = $('input[name="product_name"]');
          if (!$productNameInput.val().trim()) {
        errors.push('Product name is required');
        isValid = false;
        
        // Make sure the Basic Info tab is visible
        if (!$('#tab-basic').is(':visible')) {
            $('#sspu-tabs').tabs('option', 'active', 0); // Switch to first tab
        }
        
        // Focus the field after a short delay to ensure tab is switched
        setTimeout(function() {
            $productNameInput.focus();
        }, 100);
    }

            // Check for at least one variant
            const $allVariants = $('.sspu-variant-row');
            const $visibleVariants = $('.sspu-variant-row:visible');

            debugLog('Total variant rows in DOM', $allVariants.length);
            debugLog('Visible variant rows', $visibleVariants.length);

            // Debug each variant
            $allVariants.each(function(index) {
                const $row = $(this);
                const isVisible = $row.is(':visible');
                const display = $row.css('display');
                const optionValue = $row.find('.sspu-variant-option-value').val();
                const price = $row.find('.sspu-variant-price').val();

                debugLog(`Variant ${index + 1}:`, {
                    isVisible: isVisible,
                    display: display,
                    hasOptionValue: !!optionValue,
                    optionValue: optionValue,
                    hasPrice: !!price,
                    price: price
                });
            });

            if ($visibleVariants.length === 0) {
                errors.push('At least one variant is required');
                isValid = false;
            }

            // Check variant data - only validate visible variants
            $visibleVariants.each(function(index) {
                const $row = $(this);
                const variantNum = index + 1; // Human-readable number

                if (!$row.find('.sspu-variant-option-value').val().trim()) {
                    errors.push(`Variant ${variantNum}: Option value is required`);
                    isValid = false;
                }

                const price = $row.find('.sspu-variant-price').val();
                if (!price || parseFloat(price) <= 0) {
                    errors.push(`Variant ${variantNum}: Price is required`);
                    isValid = false;
                }
            });

            if (!isValid) {
                showNotification('Please fix the following errors:\n' + errors.join('\n'), 'error');
                debugLog('Form validation failed', errors);
            }

            return isValid;
        }

        function validateImage(imageId, callback) {
            debugLog('Validating image', imageId);

            $.post(sspu_ajax.ajaxurl, {
                action: 'sspu_validate_image',
                nonce: sspu_ajax.nonce,
                image_id: imageId
            })
            .done(function(response) {
                callback(response.success, response.data);
            })
            .fail(function() {
                callback(false, { message: 'Failed to validate image' });
            });
        }

        // FIXED generateAIContent function with better error handling
        function generateAIContent(type) {
            debugLog('Generating AI content', type);

            if (!sspu_ajax.openai_configured) {
                showNotification(sspu_ajax.strings.no_openai_key, 'warning');
                return;
            }

            const inputText = $('#sspu-ai-input-text').val().trim();

            if (!inputText && aiImageIds.length === 0) {
                showNotification('Please provide some text or upload images for AI analysis.', 'warning');
                return;
            }

            const $spinner = $('#sspu-ai-spinner');
            $spinner.addClass('is-active');

            // Show which operation is running
            showNotification(`Generating ${type}...`, 'info');

            const requestData = {
                action: 'sspu_generate_description',
                nonce: sspu_ajax.nonce,
                input_text: inputText,
                image_ids: aiImageIds,
                type: type
            };

            debugLog('AI generation request', requestData);

            $.ajax({
                url: sspu_ajax.ajaxurl,
                type: 'POST',
                data: requestData,
                dataType: 'json',
                success: function(response) {
                    debugLog('AI generation response', response);
                    if (response.success) {
                        switch(type) {
                            case 'description':
                                if (response.data.description) {
                                    setEditorContent('product_description', response.data.description);
                                    showNotification('Description generated successfully!', 'success');
                                } else {
                                    showNotification('No description generated', 'error');
                                }
                                break;
                            case 'tags':
                                if (response.data.tags) {
                                    $('input[name="product_tags"]').val(response.data.tags).addClass('updated-field');
                                    showNotification('Tags generated successfully!', 'success');
                                } else {
                                    showNotification('No tags generated', 'error');
                                }
                                break;
                            case 'price':
                                if (response.data.price) {
                                    const $firstVariant = $('.sspu-variant-row:first');
                                    if ($firstVariant.length) {
                                        $firstVariant.find('.sspu-variant-price').val(response.data.price).addClass('updated-field');
                                        showNotification('Price suggested successfully!', 'success');
                                    } else {
                                        showNotification(`Suggested price: ${response.data.price}`, 'info');
                                    }
                                } else {
                                    showNotification('No price suggested', 'error');
                                }
                                break;
                        }

                        setTimeout(function() {
                            $('.updated-field').removeClass('updated-field');
                        }, 1000);
                    } else {
                        // Better error message handling
                        const errorMsg = response.data && response.data.message ? response.data.message : 'Unknown error occurred';
                        showNotification('Error: ' + errorMsg, 'error');
                        debugLog('AI Generation Error', response);
                    }
                },
                error: function(xhr, status, error) {
                    debugLog('AI generation AJAX error', {
                        status: xhr.status,
                        error: error,
                        responseText: xhr.responseText
                    });

                    let errorMsg = 'Failed to generate ' + type + '.';
                    if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                        errorMsg = xhr.responseJSON.data.message;
                    } else if (xhr.status === 0) {
                        errorMsg = 'Network error. Please check your connection.';
                    } else if (xhr.status === 500) {
                        errorMsg = 'Server error. Please try again later.';
                    }

                    showNotification(errorMsg, 'error');
                },
                complete: function() {
                    $spinner.removeClass('is-active');
                }
            });
        }

        function saveDraft(isAutoSave = false) {
            if (!isAutoSave) {
                debugLog('Manual save draft');
            }

            const formData = collectFormData();

            $.post(sspu_ajax.ajaxurl, {
                action: isAutoSave ? 'sspu_auto_save_draft' : 'sspu_save_draft',
                nonce: sspu_ajax.nonce,
                draft_data: formData
            })
            .done(function(response) {
                if (response.success) {
                    const status = isAutoSave ? 'Auto-saved' : 'Draft saved';
                    $('.sspu-auto-save-status').text(status + ' at ' + new Date().toLocaleTimeString()).show().fadeOut(3000);
                    if (!isAutoSave) {
                        showNotification('Draft saved successfully!', 'success');
                    }
                } else if (!isAutoSave) {
                    showNotification('Error: ' + (response.data?.message || 'Failed to save draft'), 'error');
                }
            })
            .fail(function(xhr) {
                if (!isAutoSave) {
                    debugLog('Save draft error', xhr.responseText);
                    showNotification('Failed to save draft.', 'error');
                }
            });
        }

        function loadDraft() {
            debugLog('Loading draft');

            $.post(sspu_ajax.ajaxurl, {
                action: 'sspu_load_draft',
                nonce: sspu_ajax.nonce
            })
            .done(function(response) {
                debugLog('Load draft response', response);
                if (response.success) {
                    populateFormFromDraft(response.data.draft_data);
                    showNotification('Draft loaded successfully!', 'success');
                } else {
                    showNotification('No draft found or error loading draft.', 'warning');
                }
            })
            .fail(function(xhr) {
                debugLog('Load draft error', xhr.responseText);
                showNotification('Failed to load draft.', 'error');
            });
        }

        function addVariant(variantData = {}) {
            variantCounter++;
            const uniqueId = 'variant_' + variantCounter + '_' + Date.now();
            debugLog('Adding variant', { id: uniqueId, data: variantData });

            let template = $('#sspu-variant-template').html();

            // Replace all instances of [0] with unique identifier
            template = template.replace(/\[0\]/g, '[' + uniqueId + ']');
            template = template.replace(/variant_options\[0\]/g, 'variant_options[' + uniqueId + ']');
            template = template.replace(/data-variant="0"/g, 'data-variant="' + uniqueId + '"');

            const $newVariant = $(template);
            $newVariant.attr('data-variant-id', uniqueId);

            // Populate with data if provided
            if (variantData.name) $newVariant.find('.sspu-variant-option-name').val(variantData.name);
            if (variantData.value) $newVariant.find('.sspu-variant-option-value').val(variantData.value);
            if (variantData.price) $newVariant.find('.sspu-variant-price').val(variantData.price);
            if (variantData.sku) $newVariant.find('.sspu-variant-sku').val(variantData.sku);

            $newVariant.hide();
            $('#sspu-variants-wrapper').append($newVariant);
            $newVariant.slideDown(300);

            // Make the new variant's tiers sortable
            $newVariant.find('.sortable-tiers').sortable({
                handle: '.drag-handle',
                tolerance: 'pointer',
                cursor: 'move'
            });

            // Initialize tier features for the new variant
            initializeVariantTierFeatures($newVariant);

            updateVariantNumbers();
        }

        // FIXED addVolumeTier function
        function addVolumeTier($variantRow, minQuantity = '', price = '') {
            // Get the variant's unique ID
            const variantId = $variantRow.attr('data-variant-id');
            const $tiersBody = $variantRow.find('.volume-tiers-body');
            const tierCount = $tiersBody.find('.volume-tier-row').length;

            debugLog('Adding volume tier', {
                variantId: variantId,
                tierCount: tierCount,
                minQuantity: minQuantity,
                price: price
            });

            // Create the tier row HTML directly with the unique variant ID
            const $tierRow = $(`
                <tr class="volume-tier-row">
                    <td>
                        <input type="number" 
                               name="variant_options[${variantId}][tiers][${tierCount}][min_quantity]" 
                               class="tier-min-quantity" 
                               min="1" 
                               value="${minQuantity}" 
                               placeholder="e.g., 50" />
                    </td>
                    <td>
                        <input type="number" 
                               name="variant_options[${variantId}][tiers][${tierCount}][price]" 
                               class="tier-price" 
                               step="0.01" 
                               min="0" 
                               value="${price}" 
                               placeholder="e.g., 18.99" />
                    </td>
                    <td>
                        <button type="button" class="button button-link-delete remove-volume-tier">Remove</button>
                        <span class="drag-handle">⋮⋮</span>
                    </td>
                </tr>
            `);

            // Add with animation
            $tierRow.hide();
            $tiersBody.append($tierRow);
            $tierRow.fadeIn(200);
        }

        // Helper function to update tier indices after changes
        function updateTierIndices($variantRow) {
            const variantId = $variantRow.attr('data-variant-id');

            $variantRow.find('.volume-tier-row').each(function(tierIndex) {
                $(this).find('.tier-min-quantity').attr('name',
                    `variant_options[${variantId}][tiers][${tierIndex}][min_quantity]`);
                $(this).find('.tier-price').attr('name',
                    `variant_options[${variantId}][tiers][${tierIndex}][price]`);
            });
        }

        function updateVariantNumbers() {
            $('.sspu-variant-row').each(function(index) {
                $(this).find('.variant-number').text(index + 1);
            });
        }

        function updateImageOrder($container, $idField) {
            const ids = [];
            $container.find('img').each(function() {
                const id = $(this).data('id');
                if (id) ids.push(id);
            });
            $idField.val(ids.join(','));
            debugLog('Updated image order', ids);
        }

        function getVariantData($variantRow) {
            return {
                name: $variantRow.find('.sspu-variant-option-name').val(),
                value: $variantRow.find('.sspu-variant-option-value').val(),
                price: $variantRow.find('.sspu-variant-price').val(),
                sku: $variantRow.find('.sspu-variant-sku').val(),
                weight: $variantRow.find('.sspu-variant-weight').val(),
                image_id: $variantRow.find('.sspu-variant-image-id').val()
            };
        }

        // AI Weight Estimation for all variants
$('#ai-estimate-weight').on('click', function() {
    debugLog('AI Estimate Weight clicked');

    const productName = $('input[name="product_name"]').val();
    const mainImageId = $('input[name="main_image_id"]').val();

    if (!productName) {
        showNotification('Please enter a product name first.', 'warning');
        $('input[name="product_name"]').focus();
        return;
    }

    if (!mainImageId) {
        showNotification('Please select a main product image first.', 'warning');
        return;
    }

    const $button = $(this);
    const $spinner = $button.siblings('.spinner');

    $button.prop('disabled', true);
    $spinner.addClass('is-active');

    showNotification('Analyzing product to estimate weight...', 'info');

    $.ajax({
        url: sspu_ajax.ajaxurl,
        type: 'POST',
        data: {
            action: 'sspu_estimate_weight',
            nonce: sspu_ajax.nonce,
            product_name: productName,
            main_image_id: mainImageId
        },
        dataType: 'json',
        success: function(response) {
            debugLog('AI Weight response', response);

            if (response.success && response.data.weight) {
                // Apply weight to first variant
                const $firstVariant = $('.sspu-variant-row:first');
                if ($firstVariant.length) {
                    $firstVariant.find('.sspu-variant-weight')
                        .val(response.data.weight)
                        .addClass('updated-field')
                        .trigger('change');

                    showNotification(
                        `Estimated weight: ${response.data.weight} lbs. Applied to first variant.`,
                        'success'
                    );

                    // Offer to apply to all variants if there are multiple
                    if ($('.sspu-variant-row').length > 1) {
                        setTimeout(function() {
                            if (confirm('Would you like to apply this weight to all variants?')) {
                                $('#apply-weight-to-all').trigger('click');
                            }
                        }, 1000);
                    }
                }

                setTimeout(function() {
                    $('.updated-field').removeClass('updated-field');
                }, 2000);
            } else {
                const errorMsg = response.data?.message || 'Failed to estimate weight';
                showNotification('Error: ' + errorMsg, 'error');
            }
        },
        error: function(xhr, status, error) {
            debugLog('AI Weight error', {
                status: xhr.status,
                error: error,
                responseText: xhr.responseText
            });

            let errorMsg = 'Failed to estimate weight.';
            if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                errorMsg = xhr.responseJSON.data.message;
            }

            showNotification(errorMsg, 'error');
        },
        complete: function() {
            $button.prop('disabled', false);
            $spinner.removeClass('is-active');
        }
    });
});

// Individual variant weight suggestion
$(document).on('click', '.suggest-weight', function(e) {
    e.preventDefault();
    debugLog('Variant weight suggestion clicked');

    if (!sspu_ajax.openai_configured) {
        showNotification(sspu_ajax.strings.no_openai_key, 'warning');
        return;
    }

    const $button = $(this);
    const $variantRow = $button.closest('.sspu-variant-row');
    const productName = $('input[name="product_name"]').val();
    const variantValue = $variantRow.find('.sspu-variant-option-value').val();
    const mainImageId = $('input[name="main_image_id"]').val();

    if (!productName) {
        showNotification('Please enter a product name first.', 'warning');
        return;
    }

    $button.prop('disabled', true).addClass('loading');

    const fullProductName = variantValue ? `${productName} - ${variantValue}` : productName;

    $.ajax({
        url: sspu_ajax.ajaxurl,
        type: 'POST',
        data: {
            action: 'sspu_estimate_weight',
            nonce: sspu_ajax.nonce,
            product_name: fullProductName,
            main_image_id: mainImageId || 0
        },
        dataType: 'json',
        success: function(response) {
            debugLog('Variant weight response', response);
            if (response.success && response.data.weight) {
                $variantRow.find('.sspu-variant-weight').val(response.data.weight).addClass('updated-field');
                showNotification('Weight estimated successfully!', 'success');
                setTimeout(function() {
                    $('.updated-field').removeClass('updated-field');
                }, 1000);
            } else {
                showNotification('Error: ' + (response.data?.message || 'Failed to estimate weight'), 'error');
            }
        },
        error: function(xhr) {
            debugLog('Variant weight error', xhr.responseText);
            showNotification('Failed to estimate weight.', 'error');
        },
        complete: function() {
            $button.prop('disabled', false).removeClass('loading');
        }
    });
});

// Apply weight to all variants
$('#apply-weight-to-all').on('click', function() {
    debugLog('Apply weight to all clicked');

    const $firstVariant = $('.sspu-variant-row:first');
    if ($firstVariant.length === 0) {
        showNotification('No variants found.', 'warning');
        return;
    }

    const firstWeight = $firstVariant.find('.sspu-variant-weight').val();
    if (!firstWeight) {
        showNotification('Please set a weight in the first variant.', 'warning');
        $firstVariant.find('.sspu-variant-weight').focus();
        return;
    }

    $('.sspu-variant-row:visible').each(function (index) {
        if (index > 0) { // Skip first variant
            $(this).find('.sspu-variant-weight').val(firstWeight).addClass('updated-field');
        }
    });

    showNotification('Weight applied to all variants.', 'success');
    setTimeout(function() {
        $('.updated-field').removeClass('updated-field');
    }, 1000);
});

        // Helper function to get tier data from a variant
        function getVariantTierData($variantRow) {
            const tiers = [];

            $variantRow.find('.volume-tier-row').each(function() {
                const minQty = $(this).find('.tier-min-quantity').val();
                const price = $(this).find('.tier-price').val();

                if (minQty && price) {
                    tiers.push({
                        min_quantity: parseInt(minQty),
                        price: parseFloat(price)
                    });
                }
            });

            return tiers;
        }

        // UPDATED collectFormData function to only collect visible variants
        function collectFormData() {
            const formData = {
                action: 'sspu_submit_product',
                sspu_nonce: $('#sspu_nonce').val(),
                product_name: $('input[name="product_name"]').val(),
                product_description: getEditorContent('product_description'),
                product_collections: $('#sspu-collection-select').val() || [], // Changed to handle array
                product_tags: $('input[name="product_tags"]').val(),
                seo_title: $('input[name="seo_title"]').val(),
                meta_description: $('textarea[name="meta_description"]').val(),
                url_handle: $('input[name="url_handle"]').val(),
                main_image_id: $('input[name="main_image_id"]').val(),
                additional_image_ids: $('input[name="additional_image_ids"]').val(),
                print_methods: [],
                product_min: $('input[name="product_min"]').val(),
                product_max: $('input[name="product_max"]').val(),
                variant_options: []
            };

            // Collect print methods
            $('input[name="print_methods[]"]:checked').each(function() {
                formData.print_methods.push($(this).val());
            });

            // Collect variant data including tiers - only collect VISIBLE variants
            $('.sspu-variant-row:visible').each(function(index) {
                const $row = $(this);
                const variantId = $row.attr('data-variant-id');

                debugLog(`Collecting data for variant ${index + 1} (ID: ${variantId})`);

                const variantData = {
                    name: $row.find('.sspu-variant-option-name').val(),
                    value: $row.find('.sspu-variant-option-value').val(),
                    price: $row.find('.sspu-variant-price').val(),
                    sku: $row.find('.sspu-variant-sku').val(),
                    image_id: $row.find('.sspu-variant-image-id').val(),
                    tiers: getVariantTierData($row)
                };

                formData.variant_options.push(variantData);
            });

            debugLog('Collected form data', formData);
            return formData;
        }

        function getEditorContent(editorId) {
            if (typeof tinymce !== 'undefined' && tinymce.get(editorId)) {
                return tinymce.get(editorId).getContent();
            }
            return $('textarea[name="' + editorId + '"]').val();
        }

        function setEditorContent(editorId, content) {
            if (typeof tinymce !== 'undefined' && tinymce.get(editorId)) {
                tinymce.get(editorId).setContent(content);
            } else {
                $('textarea[name="' + editorId + '"]').val(content);
            }
        }

        function populateFormFromDraft(draftData) {
            if (!draftData) return;

            debugLog('Populating form from draft', draftData);

            // Populate basic fields
            $('input[name="product_name"]').val(draftData.product_name || '').trigger('input');
            $('input[name="product_tags"]').val(draftData.product_tags || '');
            $('input[name="seo_title"]').val(draftData.seo_title || '').trigger('input');
            $('textarea[name="meta_description"]').val(draftData.meta_description || '').trigger('input');
            $('input[name="url_handle"]').val(draftData.url_handle || '');
            $('input[name="product_min"]').val(draftData.product_min || '');
            $('input[name="product_max"]').val(draftData.product_max || '');

            // Handle multiple collections
            if (draftData.product_collections) {
                if (Array.isArray(draftData.product_collections)) {
                    $('#sspu-collection-select').val(draftData.product_collections);
                } else {
                    // Handle legacy single collection
                    $('#sspu-collection-select').val([draftData.product_collections]);
                }
                updateSelectedCollectionsCount();
            }

            // Populate images
            if (draftData.main_image_id) {
                $('input[name="main_image_id"]').val(draftData.main_image_id);
                loadImagePreviews('main_image_id', draftData.main_image_id, $('#sspu-main-image-preview'));
            }

            if (draftData.additional_image_ids) {
                $('input[name="additional_image_ids"]').val(draftData.additional_image_ids);
                loadImagePreviews('additional_image_ids', draftData.additional_image_ids, $('#sspu-additional-images-preview'));
            }

            // Populate description
            if (draftData.product_description) {
                setEditorContent('product_description', draftData.product_description);
            }

            // Populate print methods
            $('input[name="print_methods[]"]').prop('checked', false);
            if (draftData.print_methods && draftData.print_methods.length) {
                draftData.print_methods.forEach(function(method) {
                    $('input[name="print_methods[]"][value="' + method + '"]').prop('checked', true);
                });
            }

            // Populate variants
            $('#sspu-variants-wrapper').empty();
            variantCounter = 0;
            if (draftData.variant_options && draftData.variant_options.length) {
                draftData.variant_options.forEach(function(variant) {
                    addVariant(variant);

                    // If the variant has tiers, populate them too
                    if (variant.tiers && variant.tiers.length) {
                        const $variantRow = $('.sspu-variant-row:last');
                        variant.tiers.forEach(function(tier) {
                            addVolumeTier($variantRow, tier.min_quantity, tier.price);
                        });
                    }

                    // If the variant has an image, load its preview
                    if (variant.image_id) {
                        const $variantRow = $('.sspu-variant-row:last');
                        loadImagePreviews('variant_image', variant.image_id, $variantRow.find('.sspu-variant-image-preview'));
                    }
                });

                // Initialize tier features for all loaded variants
                initializeTierCopyPaste();
            }
        }

        function loadImagePreviews(fieldType, imageIds, $container) {
            if (!imageIds || !$container) return;

            debugLog('Loading image previews', { fieldType, imageIds });

            const ids = Array.isArray(imageIds) ? imageIds : imageIds.split(',');
            $container.empty();

            ids.forEach(function(id) {
                if (!id) return;

                // Get image data using WordPress media API
                if (wp.media.attachment) {
                    wp.media.attachment(id).fetch().done(function() {
                        const attachment = wp.media.attachment(id);
                        if (attachment && attachment.attributes && attachment.attributes.sizes) {
                            const thumbUrl = attachment.attributes.sizes.thumbnail ?
                                            attachment.attributes.sizes.thumbnail.url :
                                            attachment.attributes.url;

                            const $img = $(`<img src="${thumbUrl}" alt="${attachment.attributes.alt || ''}" data-id="${id}"/>`);
                            $img.hide().appendTo($container).fadeIn(300);
                        }
                    });
                }
            });
        }

        function clearForm() {
            debugLog('Clearing form');

            $('#sspu-product-form')[0].reset();
            $('#sspu-variants-wrapper').empty();
            $('.sspu-image-preview').empty();
            $('input[type="hidden"]').val('');
            if (typeof tinymce !== 'undefined' && tinymce.get('product_description')) {
                tinymce.get('product_description').setContent('');
            }
            variantCounter = 0;
            aiImageIds = [];
            $('#sspu-ai-images-preview').empty();
            $('.seo-feedback .char-count').text('0/60').removeClass('over-limit');
        }

        function handleFileUpload(files) {
            if (!files || !files.length) return;

            debugLog('Handling file upload', { fileCount: files.length });

            // Validate file types
            const allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            const maxSize = 5 * 1024 * 1024; // 5MB
            let invalidFiles = [];

            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const extension = file.name.split('.').pop().toLowerCase();

                if (!allowedTypes.includes(extension)) {
                    invalidFiles.push(file.name + ' - Invalid file type');
                    continue;
                }

                if (file.size > maxSize) {
                    invalidFiles.push(file.name + ' - File too large (max 5MB)');
                    continue;
                }
            }

            if (invalidFiles.length > 0) {
                showNotification('Invalid files:\n' + invalidFiles.join('\n'), 'error');
                return;
            }

            // Create FormData for AJAX upload
            const formData = new FormData();
            for (let i = 0; i < files.length; i++) {
                formData.append('file_' + i, files[i]);
            }
            formData.append('action', 'sspu_upload_images');
            formData.append('nonce', sspu_ajax.nonce);

            // Show loading indicator
            $('#sspu-image-drop-zone').addClass('uploading');

            // Upload files
            $.ajax({
                url: sspu_ajax.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false
            })
            .done(function(response) {
                debugLog('File upload response', response);
                if (response.success && response.data.ids) {
                    // Add to additional images
                    const currentIds = $('#sspu-additional-image-ids').val() || '';
                    const newIds = currentIds ? currentIds + ',' + response.data.ids.join(',') : response.data.ids.join(',');
                    $('#sspu-additional-image-ids').val(newIds);

                    // Update previews
                    response.data.ids.forEach(function(id) {
                        if (response.data.urls[id]) {
                            const $img = $(`<img src="${response.data.urls[id]}" alt="" data-id="${id}"/>`);
                            $img.hide().appendTo('#sspu-additional-images-preview').fadeIn(300);
                        }
                    });

                    updateImageOrder($('#sspu-additional-images-preview'), $('#sspu-additional-image-ids'));
                    showNotification('Images uploaded successfully!', 'success');
                } else {
                    showNotification('Error: ' + (response.data?.message || 'Unknown error'), 'error');
                }
            })
            .fail(function(xhr) {
                debugLog('File upload error', xhr.responseText);
                showNotification('Failed to upload images. Please try again or use the select button.', 'error');
            })
            .always(function() {
                $('#sspu-image-drop-zone').removeClass('uploading');
            });
        }

        // Updated loadCollections function to handle ALL collections
        function loadCollections(callback) {
            debugLog('Loading collections');

            // Store current selections
            const currentSelections = $('#sspu-collection-select').val() || [];

            // Show loading indicator
            $('#sspu-collection-select').prop('disabled', true);
            $('#sspu-refresh-collections').prop('disabled', true);
            
            // Update the select to show loading
            const $select = $('#sspu-collection-select');
            $select.html('<option value="" disabled>Loading all collections...</option>');

            $.post(sspu_ajax.ajaxurl, {
                action: 'sspu_get_collections',
                nonce: sspu_ajax.nonce
            })
            .done(function(response) {
                debugLog('Collections loaded', response);
                if (response.success) {
                    $select.empty();
                    
                    // Add placeholder option
                    $select.append('<option value="" disabled>Select Collections...</option>');
                    
                    // Track collection count
                    let collectionCount = 0;
                    
                    // Add all collections
                    response.data.forEach(function(collection) {
                        const isSelected = currentSelections.includes(collection.id.toString());
                        const collectionType = collection.hasOwnProperty('rules') ? ' (Smart)' : '';
                        $select.append(`<option value="${collection.id}" ${isSelected ? 'selected' : ''}>${collection.title}${collectionType}</option>`);
                        collectionCount++;
                    });
                    
                    debugLog(`Loaded ${collectionCount} total collections`);
                    
                    // Update placeholder to show count
                    $select.find('option:first').text(`Select from ${collectionCount} collections...`);
                    
                    updateSelectedCollectionsCount();

                    if (callback) callback();
                } else {
                    showNotification('Error: ' + (response.data?.message || 'Unknown error'), 'error');
                    $select.html('<option value="" disabled>Failed to load collections</option>');
                }
            })
            .fail(function(xhr) {
                debugLog('Collections load error', xhr.responseText);
                showNotification('Failed to load collections. Please check your Shopify settings.', 'error');
                $select.html('<option value="" disabled>Failed to load collections</option>');
            })
            .always(function() {
                $('#sspu-collection-select').prop('disabled', false);
                $('#sspu-refresh-collections').prop('disabled', false);
            });
        }

        function formatBytes(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        // NEW: Global Pricing Manager
        function openPricingManager() {
            // Create modal HTML
            const modalHtml = `
                <div id="pricing-manager-modal" class="sspu-modal">
                    <div class="sspu-modal-content">
                        <div class="sspu-modal-header">
                            <h2>Global Pricing Manager</h2>
                            <button class="sspu-modal-close">&times;</button>
                        </div>
                        <div class="sspu-modal-body">
                            <p>Set pricing tiers that will be applied to all variants:</p>
                            <table class="pricing-manager-table">
                                <thead>
                                    <tr>
                                        <th>Quantity</th>
                                        <th>Discount %</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="global-tiers-body">
                                    <tr>
                                        <td><input type="number" class="global-tier-qty" value="25" min="1"></td>
                                        <td><input type="number" class="global-tier-discount" value="0" min="0" max="100" step="0.1">%</td>
                                        <td><button class="button button-link-delete remove-global-tier">Remove</button></td>
                                    </tr>
                                </tbody>
                            </table>
                            <div class="pricing-manager-controls">
                                <button id="add-global-tier" class="button">Add Tier</button>
                                <button id="apply-global-pricing" class="button button-primary">Apply to All Variants</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Add modal to page
            $('body').append(modalHtml);

            // Modal close handlers
            $('.sspu-modal-close, #pricing-manager-modal').on('click', function(e) {
                if (e.target === this) {
                    $('#pricing-manager-modal').remove();
                }
            });

            // Add tier handler
            $('#add-global-tier').on('click', function() {
                const newRow = `
                    <tr>
                        <td><input type="number" class="global-tier-qty" value="50" min="1"></td>
                        <td><input type="number" class="global-tier-discount" value="5" min="0" max="100" step="0.1">%</td>
                        <td><button class="button button-link-delete remove-global-tier">Remove</button></td>
                    </tr>
                `;
                $('#global-tiers-body').append(newRow);
            });

            // Remove tier handler
            $(document).on('click', '.remove-global-tier', function() {
                $(this).closest('tr').remove();
            });

            // Apply pricing handler
            $('#apply-global-pricing').on('click', function() {
                const tiers = [];
                $('#global-tiers-body tr').each(function() {
                    const qty = parseInt($(this).find('.global-tier-qty').val());
                    const discount = parseFloat($(this).find('.global-tier-discount').val());
                    if (qty && discount >= 0) {
                        tiers.push({ quantity: qty, discount: discount });
                    }
                });

                // Sort tiers by quantity
                tiers.sort((a, b) => a.quantity - b.quantity);

                // Apply to all variants
                $('.sspu-variant-row').each(function() {
                    const $variantRow = $(this);
                    const basePrice = parseFloat($variantRow.find('.sspu-variant-price').val());

                    if (basePrice > 0) {
                        const $tiersBody = $variantRow.find('.volume-tiers-body');
                        $tiersBody.empty();

                        tiers.forEach(function(tier) {
                            const tierPrice = (basePrice * (1 - tier.discount / 100)).toFixed(2);
                            addVolumeTier($variantRow, tier.quantity, tierPrice);
                        });
                    }
                });

                $('#pricing-manager-modal').remove();
                showNotification('Global pricing applied to all variants!', 'success');
            });
        }

        // Add button to open pricing manager
        const pricingManagerBtn = '<button type="button" id="open-pricing-manager" class="button">Global Pricing Manager</button>';
        $('.variant-pricing-controls .bulk-actions').append(pricingManagerBtn);

        // Debug helper to inspect tier structure
        window.debugInspectTiers = function() {
            console.log('=== Tier Structure Debug ===');
            $('.sspu-variant-row').each(function(variantIndex) {
                const $variant = $(this);
                const variantId = $variant.attr('data-variant-id');
                const isVisible = $variant.is(':visible');
                console.log(`Variant ${variantIndex + 1} (ID: ${variantId}) - Visible: ${isVisible}`);

                const $tiersBody = $variant.find('.volume-tiers-body');
                console.log('  Tiers body exists:', $tiersBody.length > 0);

                const $tierRows = $tiersBody.find('.volume-tier-row');
                console.log('  Number of tier rows:', $tierRows.length);

                $tierRows.each(function(tierIndex) {
                    const $row = $(this);
                    const minQty = $row.find('.tier-min-quantity').val();
                    const price = $row.find('.tier-price').val();
                    const minQtyName = $row.find('.tier-min-quantity').attr('name');
                    const priceName = $row.find('.tier-price').attr('name');
                    console.log(`    Tier ${tierIndex + 1}: ${minQty} units @ ${price}`);
                    console.log(`      Input names: qty="${minQtyName}", price="${priceName}"`);
                });
            });
        };

        // Debug helper to inspect form data
        window.debugFormData = function() {
            const formData = collectFormData();
            console.log('=== Form Data Debug ===');
            console.log('Variant count:', formData.variant_options.length);
            formData.variant_options.forEach(function(variant, index) {
                console.log(`Variant ${index + 1}:`, variant);
                if (variant.tiers && variant.tiers.length > 0) {
                    console.log(`  Has ${variant.tiers.length} tiers:`, variant.tiers);
                }
            });
        };

        // Debug helper to clean up hidden variants
        window.cleanupHiddenVariants = function() {
            const $allVariants = $('.sspu-variant-row');
            const $hiddenVariants = $allVariants.filter(':hidden');

            console.log('Total variants:', $allVariants.length);
            console.log('Hidden variants:', $hiddenVariants.length);

            if ($hiddenVariants.length > 0) {
                if (confirm(`Found ${$hiddenVariants.length} hidden variants. Remove them?`)) {
                    $hiddenVariants.remove();
                    updateVariantNumbers();
                    console.log('Hidden variants removed');
                }
            } else {
                console.log('No hidden variants found');
            }
        };

        // Debug helper to reset variant IDs
        window.resetVariantIds = function() {
            $('.sspu-variant-row').each(function(index) {
                const newId = 'variant_' + (index + 1) + '_' + Date.now();
                const $variant = $(this);

                // Update the variant's data-variant-id
                $variant.attr('data-variant-id', newId);

                // Update all input names within this variant
                $variant.find('input').each(function() {
                    const $input = $(this);
                    const name = $input.attr('name');
                    if (name) {
                        // Replace the variant ID portion in the name
                        const newName = name.replace(/variant_options\[[^\]]+\]/, `variant_options[${newId}]`);
                        $input.attr('name', newName);
                    }
                });

                console.log(`Updated variant ${index + 1} to ID: ${newId}`);
            });

            updateVariantNumbers();
            console.log('All variant IDs have been reset');
        };
    });

})(jQuery);