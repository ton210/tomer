<?php
/**
 * Live Editor Page Template
 */

if (!defined('WPINC')) {
    die;
}

// Get collections and vendors for filters
$shopify_api = new SSPU_Shopify_API();
$collections = $shopify_api->get_all_collections();
$vendors = $shopify_api->get_vendors();
?>

<div class="wrap sspu-live-editor">
    <h1 class="wp-heading-inline">Live Product Editor</h1>
    <a href="<?php echo admin_url('admin.php?page=sspu-uploader'); ?>" class="page-title-action">Create New Product</a>

    <div class="sspu-editor-container">
        <div class="sspu-search-section">
            <div class="search-filters">
                <div class="filter-row">
                    <div class="filter-group">
                        <label>Search Products</label>
                        <input type="text" id="sspu-product-search" class="regular-text" placeholder="Search by title..." />
                    </div>

                    <div class="filter-group">
                        <label>Status</label>
                        <select id="filter-status">
                            <option value="">All Status</option>
                            <option value="active">Published</option>
                            <option value="draft">Draft</option>
                            <option value="archived">Archived</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Vendor</label>
                        <select id="filter-vendor">
                            <option value="">All Vendors</option>
                            <?php foreach ($vendors as $vendor) : ?>
                                <option value="<?php echo esc_attr($vendor); ?>"><?php echo esc_html($vendor); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Collection</label>
                        <select id="filter-collection">
                            <option value="">All Collections</option>
                            <?php foreach ($collections as $collection) : ?>
                                <option value="<?php echo esc_attr($collection['id']); ?>"><?php echo esc_html($collection['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <button type="button" id="sspu-search-btn" class="button button-primary">Search</button>
                        <button type="button" id="sspu-clear-filters" class="button">Clear</button>
                    </div>
                </div>
            </div>

            <div id="sspu-search-results"></div>
        </div>

        <div id="sspu-editor-section" style="display: none;">
            <div class="editor-header">
                <h2>Editing: <span id="sspu-editing-title"></span></h2>
                <div class="editor-actions">
                    <button type="button" id="sspu-preview-btn" class="button">Preview</button>
                    <button type="button" id="sspu-duplicate-btn" class="button">Duplicate</button>
                    <button type="button" id="sspu-view-in-shopify" class="button">View in Shopify</button>
                    <span class="autosave-status"></span>
                </div>
            </div>

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
                                <th scope="row"><label for="product-status">Status</label></th>
                                <td>
                                    <select id="product-status" name="published">
                                        <option value="true">Published</option>
                                        <option value="false">Draft</option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div id="tab-images" class="tab-content">
                        <div class="images-section">
                            <h3>Product Images</h3>
                            <p class="description">Drag to reorder. First image is the main product image.</p>

                            <div id="sspu-images-grid" class="images-grid sortable"></div>

                            <div class="image-actions">
                                <button type="button" id="sspu-add-images" class="button">Add Images</button>
                                <button type="button" id="sspu-ai-edit-images" class="button">Edit with AI</button>
                            </div>
                        </div>
                    </div>

                    <div id="tab-variants" class="tab-content">
                        <div class="variants-section">
                            <div class="variants-header">
                                <h3>Product Variants</h3>
                                <div class="bulk-actions">
                                    <button type="button" id="bulk-edit-prices" class="button">Bulk Edit Prices</button>
                                    <button type="button" id="bulk-edit-inventory" class="button">Bulk Edit Inventory</button>
                                </div>
                            </div>

                            <div id="sspu-variants-table"></div>
                        </div>
                    </div>

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
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div id="tab-organization" class="tab-content">
                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="product-vendor">Vendor</label></th>
                                <td>
                                    <input type="text" id="product-vendor" name="vendor" class="regular-text" list="vendor-list" />
                                    <datalist id="vendor-list">
                                        <?php foreach ($vendors as $vendor) : ?>
                                            <option value="<?php echo esc_attr($vendor); ?>">
                                        <?php endforeach; ?>
                                    </datalist>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row"><label for="product-type">Product Type</label></th>
                                <td>
                                    <input type="text" id="product-type" name="product_type" class="regular-text" />
                                    <p class="description">Used for filtering and organizing products.</p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row"><label for="product-tags">Tags</label></th>
                                <td>
                                    <input type="text" id="product-tags" name="tags" class="large-text" />
                                    <p class="description">Comma-separated tags for categorization.</p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row"><label>Collections</label></th>
                                <td>
                                    <div id="product-collections" class="collections-checklist">
                                        <?php foreach ($collections as $collection) : ?>
                                            <label>
                                                <input type="checkbox" name="collection_ids[]" value="<?php echo esc_attr($collection['id']); ?>" />
                                                <?php echo esc_html($collection['title']); ?>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div id="tab-metafields" class="tab-content">
                        <div class="metafields-section">
                            <h3>Custom Metafields</h3>
                            <div id="metafields-list"></div>
                            <button type="button" id="add-metafield" class="button">Add Metafield</button>
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

        <div id="preview-modal" style="display: none;">
            <div class="preview-container">
                <iframe id="preview-frame" width="100%" height="600"></iframe>
            </div>
        </div>

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