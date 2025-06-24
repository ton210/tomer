<?php
/**
 * Alibaba Queue Page Template
 */
if(!defined('WPINC'))die;
?>

<div class="wrap">
    <h1><?php echo esc_html__('Alibaba URL Queue Management', 'sspu'); ?></h1>
    
    <div class="alibaba-queue-container">
        <!-- Queue Statistics -->
        <div class="queue-stats">
            <h2><?php esc_html_e('Queue Statistics', 'sspu'); ?></h2>
            <div class="stat-boxes">
                <div class="stat-box">
                    <h4><?php esc_html_e('Total URLs', 'sspu'); ?></h4>
                    <p id="stat-total">0</p>
                </div>
                <div class="stat-box">
                    <h4><?php esc_html_e('Available', 'sspu'); ?></h4>
                    <p id="stat-available">0</p>
                </div>
                <div class="stat-box">
                    <h4><?php esc_html_e('Assigned', 'sspu'); ?></h4>
                    <p id="stat-assigned">0</p>
                </div>
                <div class="stat-box">
                    <h4><?php esc_html_e('Completed', 'sspu'); ?></h4>
                    <p id="stat-completed">0</p>
                </div>
            </div>
        </div>
        
        <!-- URL Input Section -->
        <div class="url-input-section">
            <h2><?php esc_html_e('Add URLs to Queue', 'sspu'); ?></h2>
            <p class="description">
                <?php esc_html_e('Paste URLs below, one per line.', 'sspu'); ?>
            </p>
            <textarea id="alibaba-urls-input" rows="10" style="width: 100%; font-family: monospace;" 
                      placeholder="https://www.example.com/product-1
https://www.example.com/product-2"></textarea>
            <div class="url-actions">
                <button type="button" id="add-urls-btn" class="button button-primary">
                    <?php esc_html_e('Add URLs to Queue', 'sspu'); ?>
                </button>
                <span class="spinner"></span>
            </div>
        </div>
        
        <!-- Queue Management -->
        <div class="queue-management">
            <h2><?php esc_html_e('Queue Management', 'sspu'); ?></h2>
            <div class="management-actions">
                <button type="button" id="refresh-queue-btn" class="button">
                    <?php esc_html_e('Refresh', 'sspu'); ?>
                </button>
                <button type="button" id="clear-completed-btn" class="button">
                    <?php esc_html_e('Clear Completed', 'sspu'); ?>
                </button>
                <button type="button" id="clear-unassigned-btn" class="button">
                    <?php esc_html_e('Clear Unassigned', 'sspu'); ?>
                </button>
                <button type="button" id="clear-all-btn" class="button button-link-delete">
                    <?php esc_html_e('Clear All', 'sspu'); ?>
                </button>
            </div>
        </div>
        
        <!-- Current Queue -->
        <div class="current-queue">
            <h2><?php esc_html_e('Current Queue', 'sspu'); ?></h2>
            <div id="queue-list-container">
                <p><?php esc_html_e('Loading...', 'sspu'); ?></p>
            </div>
        </div>
    </div>
</div>