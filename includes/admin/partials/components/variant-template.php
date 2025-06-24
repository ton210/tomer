<?php
/**
 * Variant Template Component
 */
if(!defined('WPINC'))die;
?>

<div id="sspu-variant-template" style="display: none;">
    <div class="sspu-variant-row">
        <div class="variant-header">
            <h4><?php esc_html_e('Variant', 'sspu'); ?> <span class="variant-number">1</span></h4>
            <div class="variant-controls">
                <button type="button" class="button button-small sspu-duplicate-variant">
                    <?php esc_html_e('Duplicate', 'sspu'); ?>
                </button>
                <button type="button" class="button button-link-delete sspu-remove-variant-btn">
                    <?php esc_html_e('Remove', 'sspu'); ?>
                </button>
                <span class="drag-handle">⋮⋮</span>
            </div>
        </div>
        
        <table class="form-table">
            <tr>
                <th><?php esc_html_e('Option Name', 'sspu'); ?></th>
                <td>
                    <input type="text" name="variant_options[0][name]" 
                           class="sspu-variant-option-name" 
                           placeholder="<?php esc_attr_e('e.g., Size', 'sspu'); ?>"/>
                </td>
                <th><?php esc_html_e('Option Value', 'sspu'); ?></th>
                <td>
                    <input type="text" name="variant_options[0][value]" 
                           class="sspu-variant-option-value" 
                           placeholder="<?php esc_attr_e('e.g., Large', 'sspu'); ?>"/>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Price', 'sspu'); ?></th>
                <td>
                    <input type="number" step="0.01" name="variant_options[0][price]" 
                           class="sspu-variant-price" 
                           placeholder="<?php esc_attr_e('e.g., 19.99', 'sspu'); ?>"/>
                    <button type="button" class="button button-small suggest-price" data-variant="0">
                        <?php esc_html_e('Suggest', 'sspu'); ?>
                    </button>
                </td>
                <th><?php esc_html_e('SKU', 'sspu'); ?></th>
                <td>
                    <input type="text" name="variant_options[0][sku]" 
                           class="sspu-variant-sku" 
                           placeholder="<?php esc_attr_e('e.g., TSHIRT-LG-RED', 'sspu'); ?>"/>
                    <button type="button" class="button button-small generate-sku" data-variant="0">
                        <?php esc_html_e('Generate', 'sspu'); ?>
                    </button>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Weight (lbs)', 'sspu'); ?></th>
                <td>
                    <input type="number" step="0.01" name="variant_options[0][weight]" 
                           class="sspu-variant-weight" 
                           placeholder="<?php esc_attr_e('e.g., 1.5', 'sspu'); ?>"/>
                    <button type="button" class="button button-small suggest-weight" data-variant="0">
                        <?php esc_html_e('AI Estimate', 'sspu'); ?>
                    </button>
                </td>
                <th><?php esc_html_e('Variant Image', 'sspu'); ?></th>
                <td>
                    <div class="sspu-image-preview sspu-variant-image-preview sortable-images"></div>
                    <input type="hidden" name="variant_options[0][image_id]" class="sspu-variant-image-id" />
                    <input type="hidden" name="variant_options[0][designer_background_url]" class="sspu-designer-background-url" />
                    <input type="hidden" name="variant_options[0][designer_mask_url]" class="sspu-designer-mask-url" />
                    <button type="button" class="button sspu-upload-image-btn" 
                            data-target-preview-class="sspu-variant-image-preview" 
                            data-target-id-class="sspu-variant-image-id">
                        <?php esc_html_e('Select Variant Image', 'sspu'); ?>
                    </button>
                    <button type="button" class="button sspu-ai-edit-variant-image" 
                            style="margin-top: 5px; display: none;">
                        🎨 <?php esc_html_e('AI Edit Image', 'sspu'); ?>
                    </button>
                    <button type="button" class="button button-small detect-color">
                        🎨 <?php esc_html_e('Detect Color', 'sspu'); ?>
                    </button>
                    <button type="button" class="button sspu-create-design-tool-files" style="margin-top: 5px;">
                        <?php esc_html_e('Create Design Files', 'sspu'); ?>
                    </button>
                    <div class="sspu-design-files-status" style="font-size: 12px; color: #46b450; margin-top: 5px;"></div>
                    <button type="button" class="button button-small copy-design-mask">
                        <?php esc_html_e('Copy Design', 'sspu'); ?>
                    </button>
                    <button type="button" class="button button-small paste-design-mask" disabled>
                        <?php esc_html_e('Paste Design', 'sspu'); ?>
                    </button>
                </td>
            </tr>
        </table>
        
        <h4><?php esc_html_e('Volume Pricing Tiers', 'sspu'); ?></h4>
        <div class="volume-pricing-controls">
            <button type="button" class="button auto-calculate-tiers">
                <?php esc_html_e('Auto-Calculate Tiers', 'sspu'); ?>
            </button>
            <button type="button" class="button add-volume-tier">
                <?php esc_html_e('Add Tier', 'sspu'); ?>
            </button>
        </div>
        <div class="volume-pricing-tiers">
            <table class="volume-tier-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Min Quantity', 'sspu'); ?></th>
                        <th><?php esc_html_e('Price', 'sspu'); ?></th>
                        <th><?php esc_html_e('Action', 'sspu'); ?></th>
                    </tr>
                </thead>
                <tbody class="volume-tiers-body sortable-tiers"></tbody>
            </table>
        </div>
    </div>
</div>