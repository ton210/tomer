<?php
if(!defined('WPINC'))die;

// Include admin-specific classes and partials loader
require_once SSPU_PLUGIN_PATH . 'includes/admin/class-sspu-admin-menus.php';
require_once SSPU_PLUGIN_PATH . 'includes/admin/class-sspu-admin-scripts.php';
require_once SSPU_PLUGIN_PATH . 'includes/admin/class-sspu-admin-settings.php';
// require_once SSPU_PLUGIN_PATH . 'includes/admin/class-sspu-admin-ajax.php'; // REMOVE THIS LINE
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
     * @var SSPU_Admin_Product_Handler  // Changed from SSPU_Admin_Ajax
     */
    private $product_handler; // Changed from $ajax
    
    /**
     * @var SSPU_Admin_Partials
     */
    private $partials;
    
    public function __construct() {
        // Initialize sub-components
        $this->menus = new SSPU_Admin_Menus();
        $this->scripts = new SSPU_Admin_Scripts();
        $this->settings = new SSPU_Admin_Settings();
        // $this->ajax = new SSPU_Admin_Ajax(); // REMOVE THIS LINE
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
        
        // Initialize product submission and AJAX handlers from the single product handler class
        $this->product_handler->init_handlers();
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
}