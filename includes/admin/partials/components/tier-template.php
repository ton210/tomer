<?php
/**
 * Tier Template Component
 */
if(!defined('WPINC'))die;
?>

<script type="text/template" id="sspu-tier-template">
    <tr class="volume-tier-row">
        <td>
            <input type="number" name="variant_options[0][tiers][0][min_quantity]" 
                   class="tier-min-quantity" min="1" />
        </td>
        <td>
            <input type="number" name="variant_options[0][tiers][0][price]" 
                   class="tier-price" step="0.01" min="0" />
        </td>
        <td>
            <button type="button" class="button button-link-delete remove-volume-tier">
                <?php esc_html_e('Remove', 'sspu'); ?>
            </button>
            <span class="drag-handle">⋮⋮</span>
        </td>
    </tr>
</script>