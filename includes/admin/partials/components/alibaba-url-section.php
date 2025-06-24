<?php
/**
 * Alibaba URL Section Component
 */
if(!defined('WPINC'))die;
?>

<div class="sspu-alibaba-url-section">
    <h3><?php esc_html_e('Alibaba Product URL', 'sspu'); ?></h3>
    
    <div id="alibaba-url-container">
        <!-- No URL Assigned State -->
        <div id="no-url-assigned" style="display: none;">
            <p><?php esc_html_e('No Alibaba URL currently assigned.', 'sspu'); ?></p>
            <button type="button" id="request-alibaba-url" class="button button-primary">
                <?php esc_html_e('Request New URL', 'sspu'); ?>
            </button>
        </div>
        
        <!-- URL Assigned State -->
        <div id="url-assigned" style="display: none;">
            <p><?php esc_html_e('Current Alibaba URL:', 'sspu'); ?></p>
            <div class="alibaba-url-display">
                <input type="text" id="current-alibaba-url" readonly class="regular-text" style="width: 70%;" />
                <a href="#" id="open-alibaba-url" target="_blank" class="button">
                    <?php esc_html_e('Open', 'sspu'); ?>
                </a>
                <button type="button" id="fetch-alibaba-product-name" class="button">
                    <?php esc_html_e('Fetch Product Name', 'sspu'); ?>
                </button>
            </div>
            <p class="description">
                <?php esc_html_e('Assigned at:', 'sspu'); ?> 
                <span id="url-assigned-time"></span>
            </p>
            <div class="url-actions">
                <button type="button" id="complete-alibaba-url" class="button button-primary">
                    <?php esc_html_e('Mark as Complete', 'sspu'); ?>
                </button>
                <button type="button" id="release-alibaba-url" class="button">
                    <?php esc_html_e('Release Back to Queue', 'sspu'); ?>
                </button>
            </div>
        </div>
        
        <div class="spinner" id="alibaba-url-spinner"></div>
    </div>
</div>