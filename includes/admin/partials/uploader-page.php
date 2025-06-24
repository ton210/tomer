<?php
/**
 * Uploader Page Template
 * 
 * @package SSPU
 */

if(!defined('WPINC'))die;
?>

<div class="wrap" id="sspu-uploader-wrapper">
    <h1><?php echo esc_html__('Upload New Product to Shopify', 'sspu'); ?></h1>
    
    <!-- Draft Controls -->
    <?php $this->component('draft-controls'); ?>
    
    <!-- Alibaba URL Section -->
    <?php $this->component('alibaba-url-section'); ?>
    
    <!-- Status Box -->
    <?php $this->component('status-box'); ?>
    
    <!-- Product Form Tabs -->
    <div id="sspu-tabs">
        <ul>
            <li><a href="#tab-basic"><?php esc_html_e('Basic Info', 'sspu'); ?></a></li>
            <li><a href="#tab-seo"><?php esc_html_e('SEO', 'sspu'); ?></a></li>
            <li><a href="#tab-images"><?php esc_html_e('Images', 'sspu'); ?></a></li>
            <li><a href="#tab-metafields"><?php esc_html_e('Metafields', 'sspu'); ?></a></li>
            <li><a href="#tab-variants"><?php esc_html_e('Variants', 'sspu'); ?></a></li>
        </ul>
        
        <form id="sspu-product-form" method="post">
<?php wp_nonce_field('sspu_submit_product', 'sspu_nonce'); ?>            
            <!-- Basic Info Tab -->
            <div id="tab-basic">
                <?php $this->component('tabs/basic-info-tab'); ?>
            </div>
            
            <!-- SEO Tab -->
            <div id="tab-seo">
                <?php $this->component('tabs/seo-tab'); ?>
            </div>
            
            <!-- Images Tab -->
            <div id="tab-images">
                <?php $this->component('tabs/images-tab'); ?>
            </div>
            
            <!-- Metafields Tab -->
            <div id="tab-metafields">
                <?php $this->component('tabs/metafields-tab'); ?>
            </div>
            
            <!-- Variants Tab -->
            <div id="tab-variants">
                <?php $this->component('tabs/variants-tab'); ?>
            </div>
            
            <?php submit_button(__('Upload Product to Shopify', 'sspu'), 'primary', 'sspu-submit-button'); ?>
            <span class="spinner"></span>
        </form>
    </div>
    
    <!-- Hidden Templates -->
    <?php $this->component('variant-template'); ?>
    <?php $this->component('tier-template'); ?>
</div>