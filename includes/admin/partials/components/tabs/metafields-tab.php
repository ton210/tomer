<?php
/**
 * Metafields Tab Component
 */
if(!defined('WPINC'))die;
?>

<table class="form-table">
    <tr valign="top">
        <th scope="row"><?php esc_html_e('Print Methods', 'sspu'); ?></th>
        <td>
            <div class="print-methods-grid">
                <label>
                    <input type="checkbox" name="print_methods[]" value="silkscreen" /> 
                    <?php esc_html_e('Silkscreen', 'sspu'); ?>
                </label>
                <label>
                    <input type="checkbox" name="print_methods[]" value="uvprint" /> 
                    <?php esc_html_e('UV Print', 'sspu'); ?>
                </label>
                <label>
                    <input type="checkbox" name="print_methods[]" value="embroidery" /> 
                    <?php esc_html_e('Embroidery', 'sspu'); ?>
                </label>
                <label>
                    <input type="checkbox" name="print_methods[]" value="emboss" /> 
                    <?php esc_html_e('Emboss', 'sspu'); ?>
                </label>
                <label>
                    <input type="checkbox" name="print_methods[]" value="sublimation" /> 
                    <?php esc_html_e('Sublimation', 'sspu'); ?>
                </label>
                <label>
                    <input type="checkbox" name="print_methods[]" value="laserengrave" /> 
                    <?php esc_html_e('Laser Engrave', 'sspu'); ?>
                </label>
            </div>
        </td>
    </tr>
    <tr valign="top">
        <th scope="row"><?php esc_html_e('Minimum Quantity', 'sspu'); ?></th>
        <td>
            <div class="moq-input-group" style="display: flex; align-items: center; gap: 10px;">
                <input type="number" name="product_min" class="small-text" min="1" />
                <button type="button" id="fetch-alibaba-moq" class="button button-secondary">
                    <?php esc_html_e('Fetch MOQ from Alibaba', 'sspu'); ?>
                </button>
            </div>
            <p class="description">
                <?php esc_html_e('Click to auto-fill the MOQ from the current Alibaba URL', 'sspu'); ?>
            </p>
        </td>
    </tr>
    <tr valign="top">
        <th scope="row"><?php esc_html_e('Maximum Quantity', 'sspu'); ?></th>
        <td>
            <input type="number" name="product_max" class="small-text" min="1" />
        </td>
    </tr>
</table>