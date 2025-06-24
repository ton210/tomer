<?php
if(!defined('WPINC'))die;

// Include admin-specific classes and partials loader
require_once SSPU_PLUGIN_PATH . 'includes/admin/class-sspu-admin-menus.php';
require_once SSPU_PLUGIN_PATH . 'includes/admin/class-sspu-admin-scripts.php';
require_once SSPU_PLUGIN_PATH . 'includes/admin/class-sspu-admin-settings.php';
require_once SSPU_PLUGIN_PATH . 'includes/admin/class-sspu-admin-ajax.php';
require_once SSPU_PLUGIN_PATH . 'includes/admin/class-sspu-admin-product-handler.php';
require_once SSPU_PLUGIN_PATH . 'includes/admin/class-sspu-admin-partials.php';

class SSPU_Admin {
    
    /**
     * @var SSPU_Admin_Menus
     */
    private $menus;
    
    /**
     * @var SSPU_Admin_Scripts
     */
    private $scripts;
    
    /**
     * @var SSPU_Admin_Settings
     */
    private $settings;
    
    /**
     * @var SSPU_Admin_Ajax
     */
    private $ajax_handler;
    
    /**
     * @var SSPU_Admin_Product_Handler
     */
    private $product_handler;
    
    /**
     * @var SSPU_Admin_Partials
     */
    private $partials;
    
    public function __construct() {
        // Initialize sub-components
        $this->menus = new SSPU_Admin_Menus();
        $this->scripts = new SSPU_Admin_Scripts();
        $this->settings = new SSPU_Admin_Settings();
        $this->ajax_handler = new SSPU_Admin_Ajax();
        $this->product_handler = new SSPU_Admin_Product_Handler();
        $this->partials = new SSPU_Admin_Partials();
        
        // Initialize hooks
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Menu hooks
        add_action('admin_menu', [$this->menus, 'add_admin_menu']);
        
        // Script hooks
        add_action('admin_enqueue_scripts', [$this->scripts, 'enqueue_scripts']);
        
        // Settings hooks
        add_action('admin_init', [$this->settings, 'register_settings']);
        
        // Initialize AJAX handlers from the main Ajax class only
        $this->ajax_handler->init_handlers();
        
        // Note: Product handler is available via $this->product_handler
        // but we do NOT call init_handlers() on it since the main Ajax class
        // handles all AJAX routing and delegates to the product handler as needed
    }
    
    /**
     * Get the partials loader
     */
    public function get_partials() {
        return $this->partials;
    }
    
    /**
     * Get the product handler
     */
    public function get_product_handler() {
        return $this->product_handler;
    }
    
    /**
     * Get the ajax handler
     */
    public function get_ajax_handler() {
        return $this->ajax_handler;
    }
}