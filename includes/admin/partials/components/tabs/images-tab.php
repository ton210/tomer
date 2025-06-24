<?php
/**
 * Images Tab Component
 */
if(!defined('WPINC'))die;
?>

<div class="image-drop-zone" id="sspu-image-drop-zone">
    <p><?php esc_html_e('Drag and drop images here or click to select', 'sspu'); ?></p>
</div>

<table class="form-table">
    <tr valign="top">
        <th scope="row"><?php esc_html_e('Main Image', 'sspu'); ?></th>
        <td>
            <div class="sspu-image-preview sortable-images" id="sspu-main-image-preview"></div>
            <input type="hidden" name="main_image_id" id="sspu-main-image-id" />
            <button type="button" class="button sspu-upload-image-btn" 
                    data-target-id="sspu-main-image-id" 
                    data-target-preview="sspu-main-image-preview">
                <?php esc_html_e('Select Main Image', 'sspu'); ?>
            </button>
        </td>
    </tr>
    <tr valign="top">
        <th scope="row"><?php esc_html_e('Additional Images', 'sspu'); ?></th>
        <td>
            <div class="sspu-image-preview sortable-images" id="sspu-additional-images-preview"></div>
            <input type="hidden" name="additional_image_ids" id="sspu-additional-image-ids" />
            <button type="button" class="button sspu-upload-image-btn" 
                    data-target-id="sspu-additional-image-ids" 
                    data-target-preview="sspu-additional-images-preview" 
                    data-multiple="true">
                <?php esc_html_e('Select Additional Images', 'sspu'); ?>
            </button>
        </td>
    </tr>
</table>