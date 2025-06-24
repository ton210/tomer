(function($) {
    'use strict';

    const LiveEditor = {
        currentProduct: null,
        editor: null,
        hasChanges: false,
        autoSaveTimer: null,
        locations: [],
        selectedLocation: null,

        init() {
            this.bindEvents();
            this.initSearch();
            this.initTabs();
            this.loadLocations();
            this.setupAutoSave();
        },

        bindEvents() {
            // Search events
            $('#sspu-search-btn').on('click', () => this.searchProducts());
            $('#sspu-clear-filters').on('click', () => this.clearFilters());
            $('#sspu-product-search').on('keypress', (e) => {
                if (e.which === 13) this.searchProducts();
            });

            // Pagination events
            $(document).on('click', '.pagination-btn', (e) => {
                const pageInfo = $(e.target).data('page-info');
                this.searchProducts(pageInfo);
            });

            // Form events
            $('#sspu-live-editor-form').on('submit', (e) => {
                e.preventDefault();
                this.saveProduct();
            });
            $('#sspu-cancel-edit').on('click', () => this.cancelEdit());

            // Track changes
            $('#sspu-live-editor-form').on('input change', 'input, select, textarea', () => {
                this.hasChanges = true;
                this.updateAutoSaveStatus('unsaved');
            });

            // Editor actions
            $('#sspu-preview-btn').on('click', () => this.previewProduct());
            $('#sspu-duplicate-btn').on('click', () => this.duplicateProduct());
            $('#sspu-view-in-shopify').on('click', () => this.viewInShopify());

            // Image management
            $('#sspu-add-images').on('click', () => this.addImages());
            $('#sspu-ai-edit-images').on('click', () => this.openAIEditor());

            // Bulk actions
            $('#bulk-edit-prices').on('click', () => this.bulkEditPrices());
            $('#bulk-edit-inventory').on('click', () => this.bulkEditInventory());

            // Metafields
            $('#add-metafield').on('click', () => this.addMetafield());

            // SEO character counters
            $('#seo-title').on('input', function() {
                const length = $(this).val().length;
                $(this).siblings('.description').find('.char-count').text(length);
            });
            $('#seo-description').on('input', function() {
                const length = $(this).val().length;
                $(this).siblings('.description').find('.char-count').text(length);
            });

            // URL handle preview
            $('#url-handle').on('input', function() {
                $('#url-handle-preview').text($(this).val());
            });

            // Delegate events
            $(document).on('click', '.edit-product-btn', (e) => {
                const productId = $(e.target).data('product-id');
                this.loadProduct(productId);
            });

            $(document).on('click', '.remove-image-btn', (e) => {
                if (confirm('Remove this image?')) {
                    const imageId = $(e.target).data('image-id');
                    this.removeImage(imageId);
                }
            });

            $(document).on('change', '.variant-inventory-qty', (e) => {
                const $input = $(e.target);
                const variantId = $input.data('variant-id');
                const inventoryItemId = $input.data('inventory-item-id');
                const value = $input.val();
                this.updateInventory(inventoryItemId, value);
            });

            $(document).on('click', '.delete-metafield', (e) => {
                const $row = $(e.target).closest('.metafield-row');
                if (confirm('Delete this metafield?')) {
                    $row.remove();
                    this.hasChanges = true;
                }
            });

            // Designer data events
            $(document).on('click', '.upload-designer-bg', (e) => {
                this.uploadDesignerImage($(e.target), 'background');
            });

            $(document).on('click', '.upload-designer-mask', (e) => {
                this.uploadDesignerImage($(e.target), 'mask');
            });

            $(document).on('input', '.designer-background-url, .designer-mask-url', (e) => {
                this.updateDesignerPreview($(e.target));
            });

            // Volume tier events
            $(document).on('click', '.add-volume-tier', (e) => {
                const $variant = $(e.target).closest('.variant-row');
                this.addVolumeTier($variant);
            });

            $(document).on('click', '.remove-tier', (e) => {
                $(e.target).closest('.tier-row').remove();
                this.hasChanges = true;
            });

            // Prevent accidental navigation
            $(window).on('beforeunload', (e) => {
                if (this.hasChanges) {
                    e.preventDefault();
                    e.returnValue = '';
                    return '';
                }
            });
        },

        initSearch() {
            $('#sspu-product-search').autocomplete({
                source: (request, response) => {
                    if (request.term.length < 2) {
                        response([]);
                        return;
                    }

                    $.ajax({
                        url: sspu_ajax.ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'sspu_search_live_products',
                            nonce: sspu_ajax.nonce,
                            query: request.term
                        },
                        success: (data) => {
                            if (data.success) {
                                response(data.data.products.map(product => ({
                                    label: product.title,
                                    value: product.title,
                                    id: product.id
                                })));
                            }
                        }
                    });
                },
                select: (event, ui) => {
                    this.loadProduct(ui.item.id);
                },
                minLength: 2
            });
        },

        initTabs() {
            $('.editor-tabs').tabs({
                activate: (event, ui) => {
                    // Refresh TinyMCE editor if switching to general tab
                    if (ui.newPanel.attr('id') === 'tab-general' && typeof tinymce !== 'undefined') {
                        const editor = tinymce.get('product-description');
                        if (editor && !editor.isHidden()) {
                            editor.execCommand('mceRepaint');
                        }
                    }
                }
            });
        },

        async loadLocations() {
            try {
                const response = await $.ajax({
                    url: sspu_ajax.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'sspu_get_shopify_locations',
                        nonce: sspu_ajax.nonce
                    }
                });

                if (response.success) {
                    this.locations = response.data.locations;
                    this.selectedLocation = this.locations[0]?.id;
                }
            } catch (error) {
                console.error('Failed to load locations:', error);
            }
        },

        setupAutoSave() {
            // Auto-save every 30 seconds if there are changes
            setInterval(() => {
                if (this.hasChanges && this.currentProduct) {
                    this.autoSave();
                }
            }, 30000);
        },

        clearFilters() {
            $('#sspu-product-search').val('');
            $('#filter-status').val('');
            $('#filter-vendor').val('');
            $('#filter-collection').val('');
            $('#sspu-search-results').empty();
        },

        searchProducts(pageInfo = null) {
            const query = $('#sspu-product-search').val().trim();
            const status = $('#filter-status').val();
            const vendor = $('#filter-vendor').val();
            const collection_id = $('#filter-collection').val();

            $('#sspu-search-btn').prop('disabled', true).text('Searching...');

            $.ajax({
                url: sspu_ajax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'sspu_search_live_products',
                    nonce: sspu_ajax.nonce,
                    query: query,
                    status: status,
                    vendor: vendor,
                    collection_id: collection_id,
                    limit: 50,
                    page_info: pageInfo
                },
                success: (response) => {
                    if (response.success) {
                        this.displaySearchResults(response.data.products, response.data);
                    } else {
                        alert('Search failed: ' + response.data.message);
                    }
                },
                complete: () => {
                    $('#sspu-search-btn').prop('disabled', false).text('Search');
                }
            });
        },

        displaySearchResults(products, responseData) {
            const $results = $('#sspu-search-results');

            if (products.length === 0) {
                $results.html('<p class="no-results">No products found matching your criteria.</p>');
                return;
            }

            let html = `
                <div class="results-header">
                    <h3>Found ${products.length} products</h3>
                    <div class="pagination-controls">`;
            
            if (responseData.prev_page_info) {
                html += `<button class="button pagination-btn" data-page-info="${responseData.prev_page_info}">← Previous</button>`;
            }
            if (responseData.next_page_info) {
                html += `<button class="button pagination-btn" data-page-info="${responseData.next_page_info}">Next →</button>`;
            }
            
            html += `</div></div>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 60px;">Image</th>
                            <th>Title</th>
                            <th style="width: 100px;">Price</th>
                            <th style="width: 80px;">Variants</th>
                            <th style="width: 100px;">Status</th>
                            <th style="width: 150px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>`;

            products.forEach(product => {
                const imageUrl = product.image ? product.image.src : sspu_ajax.plugin_url + 'assets/images/placeholder.png';
                const price = product.variants[0]?.price || '0.00';
                const status = product.published_at ? 'Published' : 'Draft';
                const statusClass = product.published_at ? 'published' : 'draft';

                html += `
                    <tr>
                        <td><img src="${imageUrl}" alt="" style="width: 50px; height: 50px; object-fit: cover;"></td>
                        <td>
                            <strong>${this.escapeHtml(product.title)}</strong>
                            ${product.vendor ? `<br><small>by ${this.escapeHtml(product.vendor)}</small>` : ''}
                        </td>
                        <td>${sspu_ajax.currency} ${price}</td>
                        <td>${product.variants.length}</td>
                        <td><span class="status-badge ${statusClass}">${status}</span></td>
                        <td>
                            <button class="button button-primary edit-product-btn" data-product-id="${product.id}">Edit</button>
                            <a href="${sspu_ajax.store_url}/products/${product.handle}" target="_blank" class="button">View</a>
                        </td>
                    </tr>`;
            });

            html += '</tbody></table>';
            $results.html(html);
        },

        async loadProduct(productId) {
            $('.spinner').addClass('is-active');
            $('#sspu-editor-section').slideUp();

            try {
                const response = await $.ajax({
                    url: sspu_ajax.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'sspu_get_live_product_data',
                        nonce: sspu_ajax.nonce,
                        product_id: productId
                    }
                });

                if (response.success) {
                    this.currentProduct = response.data.product;
                    this.populateEditor();
                    $('#sspu-editor-section').slideDown();
                    
                    // Scroll to editor
                    $('html, body').animate({
                        scrollTop: $('#sspu-editor-section').offset().top - 50
                    }, 500);
                    
                    // Reset change tracking
                    this.hasChanges = false;
                    this.updateAutoSaveStatus('saved');
                } else {
                    alert('Failed to load product: ' + response.data.message);
                }
            } catch (error) {
                alert('Error loading product. Please try again.');
                console.error(error);
            } finally {
                $('.spinner').removeClass('is-active');
            }
        },

        populateEditor() {
            if (!this.currentProduct) return;

            // Basic fields
            $('#sspu-product-id').val(this.currentProduct.id);
            $('#sspu-editing-title').text(this.currentProduct.title);
            $('#product-title').val(this.currentProduct.title);
            $('#product-vendor').val(this.currentProduct.vendor || '');
            $('#product-type').val(this.currentProduct.product_type || '');
            $('#product-tags').val(this.currentProduct.tags || '');
            $('#product-status').val(this.currentProduct.published_at ? 'true' : 'false');

            // URL handle
            $('#url-handle').val(this.currentProduct.handle);
            $('#url-handle-preview').text(this.currentProduct.handle);

            // SEO fields from metafields
            const seoTitle = this.findMetafield('global', 'title_tag');
            const seoDescription = this.findMetafield('global', 'description_tag');
            $('#seo-title').val(seoTitle || '').trigger('input');
            $('#seo-description').val(seoDescription || '').trigger('input');

            // Collections
            $('input[name="collection_ids[]"]').prop('checked', false);
            if (this.currentProduct.collection_ids) {
                this.currentProduct.collection_ids.forEach(id => {
                    $(`input[name="collection_ids[]"][value="${id}"]`).prop('checked', true);
                });
            }

            // Load print methods
            if (this.currentProduct.metafields) {
                const printMethods = [
                    'silkscreen', 'uvprint', 'embroidery', 
                    'sublimation', 'emboss', 'laserengrave'
                ];
                
                printMethods.forEach(method => {
                    const metafield = this.currentProduct.metafields.find(m => 
                        m.namespace === 'custom' && m.key === method
                    );
                    if (metafield && metafield.value === 'true') {
                        $(`input[name="print_methods[]"][value="custom.${method}"]`).prop('checked', true);
                    }
                });
            }

            // Initialize TinyMCE for description
            if (typeof tinymce !== 'undefined') {
                // Remove existing editor if any
                if (tinymce.get('product-description')) {
                    tinymce.get('product-description').remove();
                }
                
                // Set the content first
                $('#product-description').val(this.currentProduct.body_html || '');
                
                // Initialize editor
                wp.editor.initialize('product-description', {
                    tinymce: {
                        wpautop: true,
                        plugins: 'lists link textcolor wordpress wplink',
                        toolbar1: 'formatselect bold italic | bullist numlist | link unlink | forecolor backcolor | code',
                        toolbar2: 'alignleft aligncenter alignright | outdent indent | undo redo',
                        height: 400,
                        init_instance_callback: (editor) => {
                            this.editor = editor;
                            editor.setContent(this.currentProduct.body_html || '');
                        }
                    },
                    quicktags: true,
                    mediaButtons: true
                });
            }

            // Populate images
            this.displayImages();

            // Populate variants with metafields
            this.displayVariants();

            // Populate metafields
            this.displayMetafields();

            // Add print methods section
            this.addPrintMethodsSection();
        },

        addPrintMethodsSection() {
            // Add print methods checkboxes if not already in the template
            if ($('#print-methods-section').length === 0) {
                const printMethodsHtml = `
                    <div id="print-methods-section" style="margin-top: 20px;">
                        <h3>Print Methods</h3>
                        <div class="print-methods-grid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px;">
                            <label><input type="checkbox" name="print_methods[]" value="custom.silkscreen"> Silkscreen</label>
                            <label><input type="checkbox" name="print_methods[]" value="custom.uvprint"> UV Print</label>
                            <label><input type="checkbox" name="print_methods[]" value="custom.embroidery"> Embroidery</label>
                            <label><input type="checkbox" name="print_methods[]" value="custom.sublimation"> Sublimation</label>
                            <label><input type="checkbox" name="print_methods[]" value="custom.emboss"> Emboss</label>
                            <label><input type="checkbox" name="print_methods[]" value="custom.laserengrave"> Laser Engrave</label>
                        </div>
                    </div>
                `;
                $('#tab-general .form-table').after(printMethodsHtml);
            }
        },

        findMetafield(namespace, key) {
            if (!this.currentProduct.metafields) return null;
            const metafield = this.currentProduct.metafields.find(m => 
                m.namespace === namespace && m.key === key
            );
            return metafield ? metafield.value : null;
        },

        displayImages() {
            const $grid = $('#sspu-images-grid');
            $grid.empty();

            if (!this.currentProduct.images || this.currentProduct.images.length === 0) {
                $grid.html('<p class="no-images">No images uploaded yet.</p>');
                return;
            }

            this.currentProduct.images.forEach((image, index) => {
                const isMain = index === 0;
                $grid.append(`
                    <div class="image-item" data-image-id="${image.id}">
                        <img src="${image.src}" alt="${image.alt || ''}" />
                        <div class="image-overlay">
                            ${isMain ? '<span class="main-badge">Main</span>' : ''}
                            <button type="button" class="remove-image-btn" data-image-id="${image.id}" title="Remove">×</button>
                        </div>
                    </div>
                `);
            });

            // Initialize sortable
            $grid.sortable({
                items: '.image-item',
                placeholder: 'image-placeholder',
                tolerance: 'pointer',
                update: () => {
                    this.updateImageOrder();
                }
            });
        },

        displayVariants() {
            const $container = $('#sspu-variants-table');
            
            if (!this.currentProduct.variants || this.currentProduct.variants.length === 0) {
                $container.html('<p>No variants found.</p>');
                return;
            }

            let html = `
                <table class="wp-list-table widefat">
                    <thead>
                        <tr>
                            <th>Variant</th>
                            <th>SKU</th>
                            <th>Price</th>
                            <th>Compare Price</th>
                            <th>Inventory</th>
                            <th>Weight</th>
                            <th>Status</th>
                            <th>Designer Data</th>
                            <th>Volume Tiers</th>
                        </tr>
                    </thead>
                    <tbody>`;

            this.currentProduct.variants.forEach(variant => {
                const inventory = variant.inventory_level?.available || variant.inventory_quantity || 0;
                const inventoryStatus = variant.inventory_management === 'shopify' ? 
                    (inventory > 0 ? 'in-stock' : 'out-of-stock') : 'not-tracked';

                // Parse designer data and volume tiers from metafields
                let designerData = { background_image: '', mask_image: '' };
                let volumeTiers = [];
                
                if (variant.metafields) {
                    const designerMeta = variant.metafields.find(m => 
                        m.namespace === 'custom' && m.key === 'designer_data'
                    );
                    if (designerMeta) {
                        try {
                            designerData = JSON.parse(designerMeta.value);
                        } catch (e) {}
                    }
                    
                    const tiersMeta = variant.metafields.find(m => 
                        m.namespace === 'custom' && m.key === 'volume_tiers'
                    );
                    if (tiersMeta) {
                        try {
                            volumeTiers = JSON.parse(tiersMeta.value);
                        } catch (e) {}
                    }
                }

                html += `
                    <tr data-variant-id="${variant.id}" class="variant-row">
                        <td>
                            <strong>${this.escapeHtml(variant.title)}</strong>
                            ${variant.barcode ? `<br><small>Barcode: ${variant.barcode}</small>` : ''}
                        </td>
                        <td>
                            <input type="text" name="variants[${variant.id}][sku]" 
                                   value="${variant.sku || ''}" class="regular-text variant-sku" />
                        </td>
                        <td>
                            <input type="number" name="variants[${variant.id}][price]" 
                                   value="${variant.price}" step="0.01" class="small-text variant-price" />
                        </td>
                        <td>
                            <input type="number" name="variants[${variant.id}][compare_at_price]" 
                                   value="${variant.compare_at_price || ''}" step="0.01" class="small-text" />
                        </td>
                        <td>
                            <input type="number" class="small-text variant-inventory-qty" 
                                   value="${inventory}" 
                                   data-variant-id="${variant.id}"
                                   data-inventory-item-id="${variant.inventory_item_id}"
                                   ${variant.inventory_management !== 'shopify' ? 'disabled' : ''} />
                            <span class="inventory-status ${inventoryStatus}"></span>
                        </td>
                        <td>
                            <input type="number" name="variants[${variant.id}][weight]" 
                                   value="${variant.weight || ''}" step="0.01" class="small-text" />
                            <select name="variants[${variant.id}][weight_unit]" class="small-text">
                                <option value="lb" ${variant.weight_unit === 'lb' ? 'selected' : ''}>lb</option>
                                <option value="kg" ${variant.weight_unit === 'kg' ? 'selected' : ''}>kg</option>
                                <option value="g" ${variant.weight_unit === 'g' ? 'selected' : ''}>g</option>
                                <option value="oz" ${variant.weight_unit === 'oz' ? 'selected' : ''}>oz</option>
                            </select>
                        </td>
                        <td>
                            <select name="variants[${variant.id}][taxable]" class="small-text">
                                <option value="true" ${variant.taxable ? 'selected' : ''}>Taxable</option>
                                <option value="false" ${!variant.taxable ? 'selected' : ''}>Not Taxable</option>
                            </select>
                        </td>
                        <td>
                            <div class="designer-data-cell">
                                <input type="text" name="variants[${variant.id}][designer_background_url]" 
                                       class="designer-background-url" value="${designerData.background_image || ''}" 
                                       placeholder="Background URL" style="width: 100%; margin-bottom: 5px;" />
                                <input type="text" name="variants[${variant.id}][designer_mask_url]" 
                                       class="designer-mask-url" value="${designerData.mask_image || ''}" 
                                       placeholder="Mask URL" style="width: 100%;" />
                                <button type="button" class="button button-small upload-designer-bg" style="margin-top: 5px;">
                                    Upload BG
                                </button>
                                <button type="button" class="button button-small upload-designer-mask">
                                    Upload Mask
                                </button>
                            </div>
                        </td>
                        <td>
                            <div class="volume-tiers-container">
                                <div class="volume-tiers-list">`;
                
                // Display existing volume tiers
                if (volumeTiers.length > 0) {
                    volumeTiers.forEach((tier, index) => {
                        html += `
                            <div class="tier-row" style="margin-bottom: 5px;">
                                <input type="number" name="variants[${variant.id}][tiers][${index}][min_quantity]" 
                                       value="${tier.min_quantity}" class="small-text" placeholder="Min Qty" 
                                       style="width: 60px;" />
                                <input type="number" name="variants[${variant.id}][tiers][${index}][price]" 
                                       value="${tier.price}" step="0.01" class="small-text" placeholder="Price" 
                                       style="width: 80px;" />
                                <button type="button" class="button button-small remove-tier">×</button>
                            </div>
                        `;
                    });
                }
                
                html += `
                                </div>
                                <button type="button" class="button button-small add-volume-tier">Add Tier</button>
                            </div>
                        </td>
                    </tr>`;
            });

            html += '</tbody></table>';
            $container.html(html);
        },

        addVolumeTier($variant) {
            const variantId = $variant.data('variant-id');
            const tierCount = $variant.find('.tier-row').length;
            
            const tierHtml = `
                <div class="tier-row" style="margin-bottom: 5px;">
                    <input type="number" name="variants[${variantId}][tiers][${tierCount}][min_quantity]" 
                           class="small-text" placeholder="Min Qty" style="width: 60px;" />
                    <input type="number" name="variants[${variantId}][tiers][${tierCount}][price]" 
                           step="0.01" class="small-text" placeholder="Price" style="width: 80px;" />
                    <button type="button" class="button button-small remove-tier">×</button>
                </div>
            `;
            
            $variant.find('.volume-tiers-list').append(tierHtml);
            this.hasChanges = true;
        },

        displayMetafields() {
            const $container = $('#metafields-list');
            $container.empty();

            if (!this.currentProduct.metafields || this.currentProduct.metafields.length === 0) {
                $container.html('<p>No custom metafields defined.</p>');
                return;
            }

            // Filter out system metafields we handle elsewhere
            const customMetafields = this.currentProduct.metafields.filter(m => 
                !(m.namespace === 'global' && (m.key === 'title_tag' || m.key === 'description_tag')) &&
                !(m.namespace === 'custom' && ['silkscreen', 'uvprint', 'embroidery', 'sublimation', 'emboss', 'laserengrave'].includes(m.key))
            );

            customMetafields.forEach(metafield => {
                this.addMetafieldRow(metafield);
            });
        },

        addMetafieldRow(metafield = {}) {
            const $container = $('#metafields-list');
            const index = $('.metafield-row').length;
            
            const html = `
                <div class="metafield-row" data-metafield-id="${metafield.id || ''}">
                    <input type="hidden" name="metafields[${index}][id]" value="${metafield.id || ''}" />
                    <div class="metafield-fields">
                        <input type="text" name="metafields[${index}][namespace]" 
                               value="${metafield.namespace || 'custom'}" 
                               placeholder="Namespace" class="regular-text" />
                        <input type="text" name="metafields[${index}][key]" 
                               value="${metafield.key || ''}" 
                               placeholder="Key" class="regular-text" />
                        <input type="text" name="metafields[${index}][value]" 
                               value="${metafield.value || ''}" 
                               placeholder="Value" class="regular-text" />
                        <select name="metafields[${index}][type]" class="regular-text">
                            <option value="single_line_text_field" ${metafield.type === 'single_line_text_field' ? 'selected' : ''}>Text</option>
                            <option value="multi_line_text_field" ${metafield.type === 'multi_line_text_field' ? 'selected' : ''}>Multi-line Text</option>
                            <option value="number_integer" ${metafield.type === 'number_integer' ? 'selected' : ''}>Integer</option>
                            <option value="number_decimal" ${metafield.type === 'number_decimal' ? 'selected' : ''}>Decimal</option>
                            <option value="json" ${metafield.type === 'json' ? 'selected' : ''}>JSON</option>
                            <option value="boolean" ${metafield.type === 'boolean' ? 'selected' : ''}>Boolean</option>
                        </select>
                        <button type="button" class="button delete-metafield">Delete</button>
                    </div>
                </div>`;
            
            $container.append(html);
        },

        uploadDesignerImage($button, type) {
            const $row = $button.closest('tr');
            const frame = wp.media({
                title: `Select ${type === 'background' ? 'Background' : 'Mask'} Image`,
                button: { text: 'Use this image' },
                multiple: false
            });

            frame.on('select', () => {
                const attachment = frame.state().get('selection').first().toJSON();
                const $input = $row.find(`.designer-${type === 'background' ? 'background' : 'mask'}-url`);
                $input.val(attachment.url).trigger('input');
            });

            frame.open();
        },

        updateDesignerPreview($input) {
            // Visual feedback that designer data was updated
            $input.css('background-color', '#f0f8ff');
            setTimeout(() => {
                $input.css('background-color', '');
            }, 1000);
        },

        addMetafield() {
            this.addMetafieldRow();
            this.hasChanges = true;
        },

        async updateImageOrder() {
            const imageIds = [];
            $('#sspu-images-grid .image-item').each(function() {
                imageIds.push($(this).data('image-id'));
            });

            try {
                await $.ajax({
                    url: sspu_ajax.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'sspu_update_product_images_order',
                        nonce: sspu_ajax.nonce,
                        product_id: this.currentProduct.id,
                        image_ids: imageIds
                    }
                });
                
                this.showNotification('Image order updated', 'success');
            } catch (error) {
                this.showNotification('Failed to update image order', 'error');
            }
        },

        async removeImage(imageId) {
            try {
                const response = await $.ajax({
                    url: sspu_ajax.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'sspu_delete_product_image',
                        nonce: sspu_ajax.nonce,
                        product_id: this.currentProduct.id,
                        image_id: imageId
                    }
                });

                if (response.success) {
                    $(`.image-item[data-image-id="${imageId}"]`).fadeOut(() => {
                        $(this).remove();
                    });
                    
                    // Update current product data
                    this.currentProduct.images = this.currentProduct.images.filter(img => img.id !== imageId);
                    this.displayImages();
                    
                    this.showNotification('Image removed', 'success');
                }
            } catch (error) {
                this.showNotification('Failed to remove image', 'error');
            }
        },

        async updateInventory(inventoryItemId, quantity) {
            if (!this.selectedLocation) {
                alert('No location selected');
                return;
            }

            try {
                await $.ajax({
                    url: sspu_ajax.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'sspu_update_variant_inventory',
                        nonce: sspu_ajax.nonce,
                        inventory_item_id: inventoryItemId,
                        available: quantity,
                        location_id: this.selectedLocation
                    }
                });
                
                this.showNotification('Inventory updated', 'success');
            } catch (error) {
                this.showNotification('Failed to update inventory', 'error');
            }
        },

        addImages() {
            const frame = wp.media({
                title: 'Select Product Images',
                button: { text: 'Add to Product' },
                multiple: true,
                library: { type: 'image' }
            });

            frame.on('select', () => {
                const attachments = frame.state().get('selection').toJSON();
                // Implementation would upload these to Shopify
                console.log('Selected images:', attachments);
                alert('Image upload to Shopify not implemented in this demo');
            });

            frame.open();
        },

        openAIEditor() {
            if (window.AIImageEditor && this.currentProduct.images.length > 0) {
                const firstImage = this.currentProduct.images[0];
                // This would need to map Shopify image to WP attachment
                alert('AI Editor integration would open here for image editing');
            } else {
                alert('Please add images first');
            }
        },

        bulkEditPrices() {
            $('#bulk-price-modal').dialog({
                title: 'Bulk Edit Prices',
                modal: true,
                width: 400,
                buttons: {
                    'Apply': () => {
                        this.applyBulkPrices();
                        $('#bulk-price-modal').dialog('close');
                    },
                    'Cancel': function() {
                        $(this).dialog('close');
                    }
                }
            });
        },

        applyBulkPrices() {
            const action = $('input[name="price-action"]:checked').val();
            let multiplier = 1;

            switch (action) {
                case 'set':
                    const newPrice = $('#bulk-price-value').val();
                    $('.variant-price').val(newPrice);
                    break;
                case 'increase':
                    multiplier = 1 + ($('#bulk-price-percent').val() / 100);
                    break;
                case 'decrease':
                    multiplier = 1 - ($('#bulk-price-percent-dec').val() / 100);
                    break;
            }

            if (action !== 'set') {
                $('.variant-price').each(function() {
                    const currentPrice = parseFloat($(this).val()) || 0;
                    $(this).val((currentPrice * multiplier).toFixed(2));
                });
            }

            this.hasChanges = true;
            this.showNotification('Prices updated', 'success');
        },

        bulkEditInventory() {
            const newQuantity = prompt('Set all inventory quantities to:');
            if (newQuantity !== null && !isNaN(newQuantity)) {
                $('.variant-inventory-qty:not(:disabled)').val(newQuantity).trigger('change');
            }
        },

        previewProduct() {
            const url = `${sspu_ajax.store_url}/products/${this.currentProduct.handle}`;
            window.open(url, '_blank');
        },

        viewInShopify() {
            const url = `${sspu_ajax.store_url}/admin/products/${this.currentProduct.id}`;
            window.open(url, '_blank');
        },

        async duplicateProduct() {
            const newTitle = prompt('Enter title for the duplicated product:', this.currentProduct.title + ' (Copy)');
            if (!newTitle) return;

            $('.spinner').addClass('is-active');

            try {
                const response = await $.ajax({
                    url: sspu_ajax.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'sspu_duplicate_product',
                        nonce: sspu_ajax.nonce,
                        product_id: this.currentProduct.id,
                        new_title: newTitle
                    }
                });

                if (response.success) {
                    this.showNotification('Product duplicated successfully!', 'success');
                    
                    // Load the new product
                    setTimeout(() => {
                        this.loadProduct(response.data.product.id);
                    }, 1000);
                }
            } catch (error) {
                alert('Failed to duplicate product');
            } finally {
                $('.spinner').removeClass('is-active');
            }
        },

        async saveProduct() {
            if (!this.currentProduct) return;

            $('.spinner').addClass('is-active');
            $('button[type="submit"]').prop('disabled', true);

            // Sync editor content
            if (typeof tinymce !== 'undefined') {
                const editor = tinymce.get('product-description');
                if (editor) {
                    editor.save();
                }
            }

            // Gather form data
            const formData = {
                title: $('#product-title').val(),
                body_html: $('#product-description').val(),
                vendor: $('#product-vendor').val(),
                product_type: $('#product-type').val(),
                tags: $('#product-tags').val(),
                published: $('#product-status').val(),
                handle: $('#url-handle').val(),
                seo_title: $('#seo-title').val(),
                seo_description: $('#seo-description').val(),
                variants: [],
                collection_ids: [],
                print_methods: [],
                metafields: []
            };

            // Collect print methods
            $('input[name="print_methods[]"]:checked').each(function() {
                formData.print_methods.push($(this).val());
            });

            // Collect variant data with designer data and volume tiers
            $('#sspu-variants-table tbody tr').each(function() {
                const $row = $(this);
                const variantId = $row.data('variant-id');
                
                const variantData = {
                    id: variantId,
                    sku: $row.find(`input[name="variants[${variantId}][sku]"]`).val(),
                    price: $row.find(`input[name="variants[${variantId}][price]"]`).val(),
                    compare_at_price: $row.find(`input[name="variants[${variantId}][compare_at_price]"]`).val(),
                    weight: $row.find(`input[name="variants[${variantId}][weight]"]`).val(),
                    weight_unit: $row.find(`select[name="variants[${variantId}][weight_unit]"]`).val(),
                    taxable: $row.find(`select[name="variants[${variantId}][taxable]"]`).val(),
                    designer_background_url: $row.find('.designer-background-url').val(),
                    designer_mask_url: $row.find('.designer-mask-url').val()
                };
                
                // Get volume tiers
                const volumeTiers = [];
                $row.find('.tier-row').each(function() {
                    const minQty = $(this).find('input[name*="min_quantity"]').val();
                    const price = $(this).find('input[name*="price"]').val();
                    if (minQty && price) {
                        volumeTiers.push({
                            min_quantity: parseInt(minQty),
                            price: parseFloat(price)
                        });
                    }
                });
                
                if (volumeTiers.length > 0) {
                    variantData.volume_tiers = volumeTiers;
                }
                
                formData.variants.push(variantData);
            });

            // Collect collection IDs
            $('input[name="collection_ids[]"]:checked').each(function() {
                formData.collection_ids.push($(this).val());
            });

            // Collect custom metafields
            $('.metafield-row').each(function() {
                const $row = $(this);
                const index = $row.index();
                
                const metafield = {
                    id: $row.find(`input[name="metafields[${index}][id]"]`).val(),
                    namespace: $row.find(`input[name="metafields[${index}][namespace]"]`).val(),
                    key: $row.find(`input[name="metafields[${index}][key]"]`).val(),
                    value: $row.find(`input[name="metafields[${index}][value]"]`).val(),
                    type: $row.find(`select[name="metafields[${index}][type]"]`).val()
                };
                
                if (metafield.key && metafield.value) {
                    formData.metafields.push(metafield);
                }
            });

            try {
                const response = await $.ajax({
                    url: sspu_ajax.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'sspu_update_live_product',
                        nonce: sspu_ajax.nonce,
                        product_id: this.currentProduct.id,
                        product_data: formData
                    }
                });

                if (response.success) {
                    this.showNotification('Product updated successfully!', 'success');
                    this.currentProduct = response.data.product;
                    $('#sspu-editing-title').text(this.currentProduct.title);
                    
                    // Reset change tracking
                    this.hasChanges = false;
                    this.updateAutoSaveStatus('saved');
                    
                    // Show what changed
                    if (response.data.changes && response.data.changes.length > 0) {
                        console.log('Changes made:', response.data.changes);
                    }
                } else {
                    alert('Update failed: ' + response.data.message);
                }
            } catch (error) {
                alert('Network error. Please try again.');
                console.error(error);
            } finally {
                $('.spinner').removeClass('is-active');
                $('button[type="submit"]').prop('disabled', false);
            }
        },

        async autoSave() {
            if (!this.hasChanges || !this.currentProduct) return;

            const formData = $('#sspu-live-editor-form').serialize();
            
            try {
                await $.ajax({
                    url: sspu_ajax.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'sspu_live_editor_autosave',
                        nonce: sspu_ajax.nonce,
                        product_id: this.currentProduct.id,
                        draft_data: formData
                    }
                });
                
                this.updateAutoSaveStatus('autosaved');
            } catch (error) {
                console.error('Autosave failed:', error);
            }
        },

        updateAutoSaveStatus(status) {
            const $status = $('.autosave-status');
            const messages = {
                'saved': '<span class="saved">All changes saved</span>',
                'unsaved': '<span class="unsaved">Unsaved changes</span>',
                'autosaved': '<span class="autosaved">Autosaved at ' + new Date().toLocaleTimeString() + '</span>'
            };
            
            $status.html(messages[status] || '');
        },

        showNotification(message, type = 'info') {
            const $notification = $(`<div class="notice notice-${type} is-dismissible"><p>${message}</p></div>`);
            $('.wrap').prepend($notification);
            
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                $notification.fadeOut(() => $notification.remove());
            }, 5000);
        },

        cancelEdit() {
            if (this.hasChanges) {
                if (!confirm('You have unsaved changes. Are you sure you want to cancel?')) {
                    return;
                }
            }

            $('#sspu-editor-section').slideUp();
            this.currentProduct = null;
            this.hasChanges = false;
            
            if (typeof tinymce !== 'undefined' && tinymce.get('product-description')) {
                tinymce.get('product-description').remove();
            }
        },

        escapeHtml(str) {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }
    };

    // Initialize when document is ready
    $(document).ready(() => {
        if ($('.sspu-live-editor').length) {
            LiveEditor.init();
        }
    });

})(jQuery);