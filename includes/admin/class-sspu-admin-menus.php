<?php
if(!defined('WPINC'))die;

class SSPU_Admin_Menus {
    
    /**
     * Add admin menu items
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Shopify Uploader', 'sspu'),
            __('Shopify', 'sspu'),
            'upload_shopify_products',
            'sspu-uploader',
            [$this, 'render_uploader_page'],
            'dashicons-cart',
            25
        );
        
        add_submenu_page(
            'sspu-uploader',
            __('Dashboard & Stats', 'sspu'),
            __('Dashboard & Stats', 'sspu'),
            'upload_shopify_products',
            'sspu-leaderboard',
            [$this, 'render_leaderboard_page']
        );
        
        add_submenu_page(
            'sspu-uploader',
            __('Analytics', 'sspu'),
            __('Analytics', 'sspu'),
            'upload_shopify_products',
            'sspu-analytics',
            [$this, 'render_analytics_page']
        );
        
        add_submenu_page(
            'sspu-uploader',
            __('Product Search & Listing', 'sspu'),
            __('Product Search & Listing', 'sspu'),
            'upload_shopify_products',
            'sspu-search',
            [$this, 'render_search_page']
        );
        
        add_submenu_page(
            'sspu-uploader',
            __('Live Product Editor', 'sspu'),
            __('Live Product Editor', 'sspu'),
            'upload_shopify_products',
            'sspu-live-editor',
            [$this, 'render_live_editor_page']
        );
        
        add_submenu_page(
            'sspu-uploader',
            __('Alibaba Queue', 'sspu'),
            __('Alibaba Queue', 'sspu'),
            'manage_options',
            'sspu-alibaba-queue',
            [$this, 'render_alibaba_queue_page']
        );
        
        add_submenu_page(
            'sspu-uploader',
            __('Image Templates', 'sspu'),
            __('Image Templates', 'sspu'),
            'upload_shopify_products',
            'sspu-image-templates',
            [$this, 'render_image_templates_page']
        );
        
        add_submenu_page(
            'sspu-uploader',
            __('Settings', 'sspu'),
            __('Settings', 'sspu'),
            'manage_options',
            'sspu-settings',
            [$this, 'render_settings_page']
        );
    }
    
    /**
     * Render methods that load partials
     */
    public function render_uploader_page() {
        $partials = new SSPU_Admin_Partials();
        $partials->render('uploader-page');
    }
    
    public function render_leaderboard_page() {
        $partials = new SSPU_Admin_Partials();
        $partials->render('leaderboard-page');
    }
    
    public function render_analytics_page() {
        $partials = new SSPU_Admin_Partials();
        $partials->render('analytics-page');
    }
    
    public function render_search_page() {
        $partials = new SSPU_Admin_Partials();
        $partials->render('search-page');
    }
    
    public function render_live_editor_page() {
        $partials = new SSPU_Admin_Partials();
        $partials->render('live-editor-page');
    }
    
    public function render_alibaba_queue_page() {
        if(!current_user_can('manage_options'))return;
        $partials = new SSPU_Admin_Partials();
        $partials->render('alibaba-queue-page');
    }
    
    public function render_image_templates_page() {
        $partials = new SSPU_Admin_Partials();
        $partials->render('image-templates-page');
    }
    
    public function render_settings_page() {
        if(!current_user_can('manage_options'))return;
        $partials = new SSPU_Admin_Partials();
        $partials->render('settings-page');
    }
}