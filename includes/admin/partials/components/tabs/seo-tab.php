<?php
/**
 * SEO Tab Component
 */
if(!defined('WPINC'))die;
?>

<table class="form-table">
    <tr valign="top">
        <th scope="row"><?php esc_html_e('SEO Title', 'sspu'); ?></th>
        <td>
            <input type="text" name="seo_title" class="regular-text" maxlength="60" />
            <div class="seo-feedback">
                <span class="char-count">0/60</span>
                <button type="button" id="sspu-generate-seo-title" class="button button-small">
                    <?php esc_html_e('Auto-Generate', 'sspu'); ?>
                </button>
            </div>
        </td>
    </tr>
    <tr valign="top">
        <th scope="row"><?php esc_html_e('Meta Description', 'sspu'); ?></th>
        <td>
            <textarea name="meta_description" class="large-text" rows="3" maxlength="160"></textarea>
            <div class="seo-feedback">
                <span class="char-count">0/160</span>
                <button type="button" id="sspu-generate-meta-desc" class="button button-small">
                    <?php esc_html_e('Auto-Generate', 'sspu'); ?>
                </button>
            </div>
        </td>
    </tr>
    <tr valign="top">
        <th scope="row"><?php esc_html_e('URL Handle', 'sspu'); ?></th>
        <td>
            <input type="text" name="url_handle" class="regular-text" />
            <p class="description">
                <?php esc_html_e('Auto-generated from product name if left empty.', 'sspu'); ?>
            </p>
        </td>
    </tr>
</table>