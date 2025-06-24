<?php
/**
 * Draft Controls Component
 */
if(!defined('WPINC'))die;
?>

<div class="sspu-draft-controls">
    <button type="button" id="sspu-save-draft" class="button">
        <?php esc_html_e('Save Draft', 'sspu'); ?>
    </button>
    <button type="button" id="sspu-load-draft" class="button">
        <?php esc_html_e('Load Draft', 'sspu'); ?>
    </button>
    <span class="sspu-auto-save-status"></span>
</div>