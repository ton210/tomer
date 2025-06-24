<?php
if(!defined('WPINC'))die;

class SSPU_Admin_Partials {
    
    /**
     * Path to partials directory
     */
    private $partials_path;
    
    public function __construct() {
        $this->partials_path = SSPU_PLUGIN_PATH . 'includes/admin/partials/';
    }
    
    /**
     * Render a partial template
     */
    public function render($partial_name, $data = []) {
        // Extract data for use in partial
        extract($data);
        
        $file_path = $this->partials_path . $partial_name . '.php';
        
        if(file_exists($file_path)) {
            include $file_path;
        } else {
            error_log('SSPU: Partial not found - ' . $file_path);
            echo '<div class="error"><p>Template file not found: ' . esc_html($partial_name) . '</p></div>';
        }
    }
    
    /**
     * Get partial content as string
     */
    public function get($partial_name, $data = []) {
        ob_start();
        $this->render($partial_name, $data);
        return ob_get_clean();
    }
    
    /**
     * Render a component partial (smaller reusable pieces)
     */
    public function component($component_name, $data = []) {
        $this->render('components/' . $component_name, $data);
    }
    
    /**
     * Check if partial exists
     */
    public function exists($partial_name) {
        $file_path = $this->partials_path . $partial_name . '.php';
        return file_exists($file_path);
    }
}