<?php
/**
 * Basic Info Tab Component
 */
if(!defined('WPINC'))die;
?>

<table class="form-table">
    <tr valign="top">
        <th scope="row"><?php esc_html_e('Product Name', 'sspu'); ?></th>
        <td>
            <div class="product-name-section">
                <input type="text" name="product_name" id="product-name-input" class="regular-text" required />
                <button type="button" id="format-product-name" class="button button-secondary">
                    <?php esc_html_e('Format with AI', 'sspu'); ?>
                </button>
                <div class="spinner" id="format-name-spinner"></div>
            </div>
            <div class="seo-feedback">
                <span class="title-length"></span>
                <span class="seo-suggestions"></span>
            </div>
            <p class="description">
                <?php esc_html_e('AI can reformat long product names into 4-7 words suitable for Shopify.', 'sspu'); ?>
            </p>
        </td>
    </tr>
    
    <tr valign="top">
        <th scope="row"><?php esc_html_e('Collections', 'sspu'); ?></th>
        <td>
            <div class="collections-search-container" style="margin-bottom: 10px;">
                <input type="text" id="sspu-collection-search" 
                       placeholder="<?php esc_attr_e('Search collections...', 'sspu'); ?>" 
                       class="regular-text" />
            </div>
            <select name="product_collections[]" id="sspu-collection-select" multiple 
                    style="width: 100%; min-height: 150px;">
                <option value="" disabled><?php esc_html_e('Loading collections...', 'sspu'); ?></option>
            </select>
            <div class="collection-controls" style="margin-top: 10px;">
                <button type="button" id="sspu-refresh-collections" class="button">
                    <?php esc_html_e('Refresh', 'sspu'); ?>
                </button>
                <button type="button" id="sspu-select-all-collections" class="button">
                    <?php esc_html_e('Select All', 'sspu'); ?>
                </button>
                <button type="button" id="sspu-clear-collections" class="button">
                    <?php esc_html_e('Clear Selection', 'sspu'); ?>
                </button>
                <button type="button" id="sspu-create-collection" class="button">
                    <?php esc_html_e('Create New', 'sspu'); ?>
                </button>
                <span style="margin-left: 10px;">
                    <?php esc_html_e('Selected:', 'sspu'); ?> 
                    <strong id="selected-collections-count">0</strong>
                </span>
            </div>
            <div id="sspu-new-collection" style="display:none; margin-top:10px;">
                <input type="text" id="sspu-new-collection-name" 
                       placeholder="<?php esc_attr_e('Collection Name', 'sspu'); ?>" />
                <button type="button" id="sspu-save-collection" class="button button-primary">
                    <?php esc_html_e('Save', 'sspu'); ?>
                </button>
                <button type="button" id="sspu-cancel-collection" class="button">
                    <?php esc_html_e('Cancel', 'sspu'); ?>
                </button>
            </div>
        </td>
    </tr>
    
    <tr valign="top">
        <th scope="row"><?php esc_html_e('Description', 'sspu'); ?></th>
        <td>
            <div class="ai-description-section">
                <textarea id="sspu-ai-input-text" 
                          placeholder="<?php esc_attr_e('Enter product details, features, specifications...', 'sspu'); ?>" 
                          rows="4" style="width: 100%;"></textarea>
                <div class="ai-image-upload">
                    <button type="button" id="sspu-upload-ai-images" class="button">
                        <?php esc_html_e('Upload Product Images for AI Analysis', 'sspu'); ?>
                    </button>
                    <div id="sspu-ai-images-preview"></div>
                    <div class="sspu-ai-image-urls" style="margin-top: 15px;">
                        <p class="description"><?php esc_html_e('Or, enter up to 5 image URLs (including .avif files):', 'sspu'); ?></p>
                        <input type="url" name="ai_image_urls[]" class="regular-text" placeholder="https://example.com/image1.avif" style="margin-bottom: 5px;">
                        <input type="url" name="ai_image_urls[]" class="regular-text" placeholder="https://example.com/image2.avif" style="margin-bottom: 5px;">
                        <input type="url" name="ai_image_urls[]" class="regular-text" placeholder="https://example.com/image3.avif" style="margin-bottom: 5px;">
                        <input type="url" name="ai_image_urls[]" class="regular-text" placeholder="https://example.com/image4.avif" style="margin-bottom: 5px;">
                        <input type="url" name="ai_image_urls[]" class="regular-text" placeholder="https://example.com/image5.avif" style="margin-bottom: 5px;">
                    </div>
                </div>
                <div class="ai-buttons">
                    <button type="button" id="sspu-generate-description" class="button button-primary">
                        <?php esc_html_e('Generate Description', 'sspu'); ?>
                    </button>
                    <button type="button" id="sspu-generate-tags" class="button">
                        <?php esc_html_e('Generate Tags', 'sspu'); ?>
                    </button>
                    <button type="button" id="sspu-suggest-price" class="button">
                        <?php esc_html_e('Suggest Price', 'sspu'); ?>
                    </button>
                    <button type="button" id="fetch-alibaba-description" class="button button-secondary">
                        <?php esc_html_e('Fetch Alibaba Details', 'sspu'); ?>
                    </button>
                </div>
                <div class="spinner" id="sspu-ai-spinner"></div>
            </div>
            <?php 
            wp_editor('', 'product_description', [
                'textarea_name' => 'product_description',
                'media_buttons' => true
            ]); 
            ?>
        </td>
    </tr>
    
    <tr valign="top">
        <th scope="row"><?php esc_html_e('Tags', 'sspu'); ?></th>
        <td>
            <input type="text" name="product_tags" class="regular-text" 
                   placeholder="<?php esc_attr_e('Comma-separated tags', 'sspu'); ?>" />
            <p class="description">
                <?php esc_html_e('AI can auto-generate tags based on your description.', 'sspu'); ?>
            </p>
        </td>
    </tr>
</table>