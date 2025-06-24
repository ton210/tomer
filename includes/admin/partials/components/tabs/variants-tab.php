<?php
/**
 * Variants Tab Component
 */
if(!defined('WPINC'))die;
?>

<!-- Variant Generator Section -->
<div class="variant-generator-section">
    <h3><?php esc_html_e('Generate Variants', 'sspu'); ?></h3>
    <table class="form-table">
        <tr>
            <th scope="row"><?php esc_html_e('Option Name', 'sspu'); ?></th>
            <td>
                <input type="text" id="variant-option-name" 
                       placeholder="<?php esc_attr_e('e.g., Color, Size, Material', 'sspu'); ?>" 
                       class="regular-text" />
            </td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e('Option Values', 'sspu'); ?></th>
            <td>
                <textarea id="variant-option-values" rows="3" class="large-text" 
                          placeholder="<?php esc_attr_e("Enter values separated by commas or new lines, e.g.:\nRed, Blue, Green, Black\nOr:\nRed\nBlue\nGreen\nBlack", 'sspu'); ?>"></textarea>
                <p class="description">
                    <?php esc_html_e('Enter each variant value separated by commas or on separate lines.', 'sspu'); ?>
                </p>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e('Base Price', 'sspu'); ?></th>
            <td>
                <input type="number" id="variant-base-price" step="0.01" min="0" 
                       class="regular-text" placeholder="<?php esc_attr_e('19.99', 'sspu'); ?>" />
                <p class="description">
                    <?php esc_html_e('This price will be applied to all generated variants.', 'sspu'); ?>
                </p>
            </td>
        </tr>
    </table>
    <div class="variant-generator-controls">
        <button type="button" id="generate-variants-btn" class="button button-primary">
            <?php esc_html_e('Generate Variants', 'sspu'); ?>
        </button>
        <button type="button" id="sspu-scrape-variants-btn" class="button button-secondary">
            <?php esc_html_e('Scrape Variants from URL', 'sspu'); ?>
        </button>
        <button type="button" id="detect-all-colors-btn" class="button">
            🎨 <?php esc_html_e('Detect All Colors', 'sspu'); ?>
        </button>
        <button type="button" id="clear-variants-btn" class="button">
            <?php esc_html_e('Clear All Variants', 'sspu'); ?>
        </button>
    </div>
</div>

<!-- Bulk Pricing Controls -->
<div class="variant-pricing-controls">
    <h3><?php esc_html_e('Bulk Pricing Actions', 'sspu'); ?></h3>
    <div class="bulk-actions">
        <button type="button" id="apply-price-to-all" class="button">
            <?php esc_html_e('Apply First Variant Price to All', 'sspu'); ?>
        </button>
        <button type="button" id="apply-tiers-to-all" class="button">
            <?php esc_html_e('Apply First Variant Tiers to All', 'sspu'); ?>
        </button>
        <button type="button" id="apply-weight-to-all" class="button">
            <?php esc_html_e('Apply First Variant Weight to All', 'sspu'); ?>
        </button>
        <button type="button" id="auto-generate-all-skus" class="button">
            <?php esc_html_e('Auto-Generate All SKUs', 'sspu'); ?>
        </button>
        <button type="button" id="ai-suggest-all-pricing" class="button button-primary">
            <?php esc_html_e('AI Suggest All Pricing', 'sspu'); ?> <span class="spinner"></span>
        </button>
        <button type="button" id="ai-estimate-weight" class="button button-primary">
            <?php esc_html_e('AI Estimate Weight', 'sspu'); ?> <span class="spinner"></span>
        </button>
        <button type="button" id="apply-design-mask-to-all" class="button">
            <?php esc_html_e('Apply Design Mask to All', 'sspu'); ?>
        </button>
        <button type="button" id="mimic-all-variants" class="button button-primary">
            🎯 <?php esc_html_e('Mimic All Variants', 'sspu'); ?>
        </button>
        <button type="button" id="smart-rotate-all-variants" class="button button-primary">
    🔄 <?php esc_html_e('Smart Rotate All Variants', 'sspu'); ?>
</button>
    </div>
</div>

<!-- Variants Container -->
<div id="sspu-variants-wrapper" class="sortable-variants"></div>

<!-- Add Individual Variant Button -->
<button type="button" id="sspu-add-variant-btn" class="button">
    <?php esc_html_e('Add Individual Variant', 'sspu'); ?>
</button>