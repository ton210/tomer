<?php
/**
 * Search Page Template
 */
if(!defined('WPINC'))die;
?>

<div class="wrap">
    <h1><?php esc_html_e('Product Search & Listing', 'sspu'); ?></h1>
    
    <div class="search-interface">
        <div class="search-form">
            <input type="text" id="global-search-input" 
                   placeholder="<?php esc_attr_e('Search products, variants, collections...', 'sspu'); ?>" />
            <button id="global-search-btn" class="button button-primary">
                <?php esc_html_e('Search', 'sspu'); ?>
            </button>
            <button id="clear-search-btn" class="button">
                <?php esc_html_e('Clear', 'sspu'); ?>
            </button>
        </div>
        
        <div class="search-filters">
            <select id="search-type">
                <option value="all"><?php esc_html_e('All', 'sspu'); ?></option>
                <option value="products" selected><?php esc_html_e('Products', 'sspu'); ?></option>
                <option value="variants"><?php esc_html_e('Variants', 'sspu'); ?></option>
                <option value="collections"><?php esc_html_e('Collections', 'sspu'); ?></option>
            </select>
            
            <select id="search-date-range">
                <option value="all"><?php esc_html_e('All Time', 'sspu'); ?></option>
                <option value="7"><?php esc_html_e('Last 7 Days', 'sspu'); ?></option>
                <option value="30"><?php esc_html_e('Last 30 Days', 'sspu'); ?></option>
                <option value="90"><?php esc_html_e('Last 90 Days', 'sspu'); ?></option>
            </select>
            
            <select id="search-user">
                <option value="all"><?php esc_html_e('All Users', 'sspu'); ?></option>
            </select>
            
            <select id="search-status">
                <option value="all"><?php esc_html_e('All Status', 'sspu'); ?></option>
                <option value="success"><?php esc_html_e('Published', 'sspu'); ?></option>
                <option value="error"><?php esc_html_e('Failed', 'sspu'); ?></option>
            </select>
            
            <select id="results-per-page">
                <option value="20">20 <?php esc_html_e('per page', 'sspu'); ?></option>
                <option value="50" selected>50 <?php esc_html_e('per page', 'sspu'); ?></option>
                <option value="100">100 <?php esc_html_e('per page', 'sspu'); ?></option>
                <option value="200">200 <?php esc_html_e('per page', 'sspu'); ?></option>
            </select>
        </div>
        
        <div class="pagination-controls" id="pagination-top" style="margin: 20px 0; text-align: center;"></div>
    </div>
    
    <div id="search-results">
        <div class="loading-indicator" style="text-align: center; padding: 20px;">
            <span class="spinner is-active" style="float: none;"></span>
            <p><?php esc_html_e('Loading products...', 'sspu'); ?></p>
        </div>
    </div>
    
    <div class="pagination-controls" id="pagination-bottom" style="margin: 20px 0; text-align: center;"></div>
</div>