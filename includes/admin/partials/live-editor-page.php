<?php
/**
 * Enhanced Live Editor Page Template
 */

if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap sspu-live-editor">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-edit-large"></span> Live Product Editor
    </h1>
    <a href="<?php echo admin_url('admin.php?page=sspu-uploader'); ?>" class="page-title-action">
        <span class="dashicons dashicons-plus-alt"></span> Create New Product
    </a>
    <a href="#" id="refresh-all-products" class="page-title-action">
        <span class="dashicons dashicons-update"></span> Refresh All
    </a>

    <div class="sspu-editor-container">
        <!-- Search Section -->
        <div class="sspu-search-section">
            <div class="search-filters">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="sspu-product-search">Search Products</label>
                        <input type="text" 
                               id="sspu-product-search" 
                               class="regular-text" 
                               placeholder="Search by title, SKU, or vendor..." 
                               autocomplete="off" />
                    </div>

                    <div class="filter-group">
                        <label for="filter-status">Status</label>
                        <select id="filter-status">
                            <option value="">All Status</option>
                            <option value="active">Published</option>
                            <option value="draft">Draft</option>
                            <option value="archived">Archived</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="filter-vendor">Vendor</label>
                        <select id="filter-vendor">
                            <option value="">All Vendors</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="filter-collection">Collection</label>
                        <select id="filter-collection">
                            <option value="">All Collections</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="filter-product-type">Product Type</label>
                        <input type="text" 
                               id="filter-product-type" 
                               class="regular-text" 
                               placeholder="e.g., T-Shirt" />
                    </div>

                    <div class="filter-group">
                        <button type="button" id="sspu-search-btn" class="button button-primary">
                            <span class="dashicons dashicons-search"></span> Search
                        </button>
                        <button type="button" id="sspu-clear-filters" class="button">
                            Clear
                        </button>
                    </div>
                </div>

                <!-- Additional Filters -->
                <div class="advanced-filters" style="display: none;">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="filter-tags">Tags</label>
                            <input type="text" 
                                   id="filter-tags" 
                                   class="regular-text" 
                                   placeholder="Comma-separated tags" />
                        </div>
                        
                        <div class="filter-group">
                            <label for="filter-price-min">Min Price</label>
                            <input type="number" 
                                   id="filter-price-min" 
                                   class="regular-text" 
                                   step="0.01" 
                                   min="0" />
                        </div>
                        
                        <div class="filter-group">
                            <label for="filter-price-max">Max Price</label>
                            <input type="number" 
                                   id="filter-price-max" 
                                   class="regular-text" 
                                   step="0.01" 
                                   min="0" />
                        </div>
                    </div>
                </div>
                
                <div style="text-align: right; margin-top: 10px;">
                    <a href="#" id="toggle-advanced-filters">
                        <span class="dashicons dashicons-admin-settings"></span> Advanced Filters
                    </a>
                </div>
            </div>

            <div id="sspu-search-results"></div>
        </div>

        <!-- Hidden Editor Section (Moved to Modal) -->
        <div id="sspu-editor-section" style="display: none;">
            <form id="sspu-live-editor-form">
                <input type="hidden" id="sspu-product-id" value="" />

                <div class="editor-tabs">
                    <ul class="tab-nav">
                        <li><a href="#tab-general">General</a></li>
                        <li><a href="#tab-images">Images</a></li>
                        <li><a href="#tab-variants">Variants</a></li>
                        <li><a href="#tab-seo">SEO</a></li>
                        <li><a href="#tab-organization">Organization</a></li>
                        <li><a href="#tab-metafields">Metafields</a></li>
                    </ul>

                    <!-- General Tab -->
                    <div id="tab-general" class="tab-content">
                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="product-title">Product Title *</label></th>
                                <td>
                                    <input type="text" id="product-title" name="title" class="large-text" required />
                                    <p class="description">The name of the product as it will appear to customers.</p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row"><label for="product-description">Description</label></th>
                                <td>
                                    <div id="product-description-editor"></div>
                                    <textarea id="product-description" name="body_html" style="display: none;"></textarea>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row"><label for="product-vendor">Vendor</label></th>
                                <td>
                                    <input type="text" id="product-vendor" name="vendor" class="regular-text" list="vendor-list" />
                                    <datalist id="vendor-list"></datalist>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row"><label for="product-type">Product Type</label></th>
                                <td>
                                    <input type="text" id="product-type" name="product_type" class="regular-text" />
                                    <p class="description">Used for filtering and organizing products (e.g., T-Shirt, Mug, etc.)</p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row"><label for="product-status">Status</label></th>
                                <td>
                                    <select id="product-status" name="published">
                                        <option value="true">Published</option>
                                        <option value="false">Draft</option>
                                    </select>
                                </td>
                            </tr>
                        </table>

                        <!-- Print Methods Section -->
                        <div id="print-methods-container"></div>
                    </div>

                    <!-- Images Tab -->
                    <div id="tab-images" class="tab-content">
                        <div class="images-section">
                            <h3>Product Images</h3>
                            <p class="description">
                                Drag to reorder. First image is the main product image. 
                                You can add images from your WordPress Media Library.
                            </p>

                            <div id="sspu-images-grid" class="images-grid sortable"></div>

                            <div class="image-actions">
                                <button type="button" id="sspu-add-images" class="button button-primary">
                                    <span class="dashicons dashicons-plus-alt"></span> Add Images from Media
                                </button>
                                <button type="button" id="sspu-ai-edit-images" class="button">
                                    <span class="dashicons dashicons-art"></span> Edit with AI
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Variants Tab -->
                    <div id="tab-variants" class="tab-content">
                        <div class="variants-section">
                            <h3>Product Variants</h3>
                            <div id="sspu-variants-table"></div>
                        </div>
                    </div>

                    <!-- SEO Tab -->
                    <div id="tab-seo" class="tab-content">
                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="seo-title">SEO Title</label></th>
                                <td>
                                    <input type="text" id="seo-title" name="seo_title" class="large-text" maxlength="70" />
                                    <p class="description">
                                        <span class="char-count">0</span>/70 characters.
                                        This will appear in search engine results.
                                    </p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row"><label for="seo-description">Meta Description</label></th>
                                <td>
                                    <textarea id="seo-description" name="seo_description" rows="3" class="large-text" maxlength="160"></textarea>
                                    <p class="description">
                                        <span class="char-count">0</span>/160 characters.
                                        Brief description for search engines.
                                    </p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row"><label for="url-handle">URL Handle</label></th>
                                <td>
                                    <div class="url-preview">
                                        <?php echo esc_html('https://' . get_option('sspu_shopify_store_name') . '.myshopify.com/products/'); ?><span id="url-handle-preview"></span>
                                    </div>
                                    <input type="text" id="url-handle" name="url_handle" class="regular-text" />
                                    <p class="description">The URL-friendly version of the product title.</p>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- Organization Tab -->
                    <div id="tab-organization" class="tab-content">
                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="product-tags">Tags</label></th>
                                <td>
                                    <input type="text" id="product-tags" name="tags" class="large-text" />
                                    <p class="description">Comma-separated tags for categorization and search.</p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row"><label>Collections</label></th>
                                <td>
                                    <div id="product-collections" class="collections-checklist"></div>
                                    <p class="description">Select collections this product belongs to.</p>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- Metafields Tab -->
                    <div id="tab-metafields" class="tab-content">
                        <div class="metafields-section">
                            <h3>Custom Metafields</h3>
                            <p class="description">
                                Add custom data fields to extend product information. 
                                These can be used by your theme or apps.
                            </p>
                            <div id="metafields-list"></div>
                        </div>
                    </div>
                </div>

                <div class="submit-section">
                    <button type="submit" class="button button-primary button-large">Save Changes</button>
                    <button type="button" id="sspu-cancel-edit" class="button button-large">Cancel</button>
                    <span class="spinner"></span>
                    <div class="save-status"></div>
                </div>
            </form>
        </div>

        <!-- Bulk Price Edit Modal Template -->
        <div id="bulk-price-modal" style="display: none;">
            <h3>Bulk Edit Variant Prices</h3>
            <div class="bulk-edit-options">
                <label>
                    <input type="radio" name="price-action" value="set" checked />
                    Set all prices to: <input type="number" id="bulk-price-value" step="0.01" />
                </label>
                <label>
                    <input type="radio" name="price-action" value="increase" />
                    Increase all prices by: <input type="number" id="bulk-price-percent" step="1" />%
                </label>
                <label>
                    <input type="radio" name="price-action" value="decrease" />
                    Decrease all prices by: <input type="number" id="bulk-price-percent-dec" step="1" />%
                </label>
            </div>
        </div>
    </div>
</div>

<script>
// Advanced filters toggle
jQuery('#toggle-advanced-filters').on('click', function(e) {
    e.preventDefault();
    jQuery('.advanced-filters').slideToggle();
    const $icon = jQuery(this).find('.dashicons');
    $icon.toggleClass('dashicons-admin-settings dashicons-no-alt');
});

// Refresh all products
jQuery('#refresh-all-products').on('click', function(e) {
    e.preventDefault();
    if (window.SSPULiveEditor) {
        window.SSPULiveEditor.clearCache();
        window.SSPULiveEditor.searchProducts();
    }
});
</script>