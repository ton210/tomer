<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * SSPU_SKU_Generator
 * 
 * Intelligent SKU generation system for Shopify Product Uploader
 */
class SSPU_SKU_Generator {
    
    private static $instance = null;
    private $category_codes = [];
    private $last_error = '';
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Initialize common product category codes
        $this->category_codes = [
            // Drinkware
            'tumbler' => 'TUM',
            'mug' => 'MUG',
            'bottle' => 'BTL',
            'cup' => 'CUP',
            'glass' => 'GLS',
            'flask' => 'FLK',
            
            // Apparel
            'shirt' => 'SHT',
            't-shirt' => 'TSH',
            'tshirt' => 'TSH',
            'polo' => 'PLO',
            'hoodie' => 'HDY',
            'jacket' => 'JKT',
            'cap' => 'CAP',
            'hat' => 'HAT',
            
            // Bags
            'bag' => 'BAG',
            'backpack' => 'BPK',
            'tote' => 'TOT',
            'duffel' => 'DFL',
            'briefcase' => 'BRF',
            
            // Office
            'pen' => 'PEN',
            'notebook' => 'NTB',
            'journal' => 'JRN',
            'planner' => 'PLN',
            'folder' => 'FLD',
            'binder' => 'BND',
            
            // Tech
            'usb' => 'USB',
            'charger' => 'CHG',
            'powerbank' => 'PWB',
            'speaker' => 'SPK',
            'headphone' => 'HDP',
            'mouse' => 'MOU',
            'keyboard' => 'KBD',
            
            // Other
            'keychain' => 'KEY',
            'lanyard' => 'LYD',
            'umbrella' => 'UMB',
            'towel' => 'TWL',
            'blanket' => 'BLK',
            'mat' => 'MAT',
        ];
    }
    
    /**
     * Generate an intelligent SKU based on product attributes
     */
    public function generate_sku($product_name, $variant_name = '', $variant_value = '', $options = []) {
        // Clean and prepare inputs
        $product_name = strtolower(trim($product_name));
        $variant_value = strtolower(trim($variant_value));
        
        // Extract components
        $category_code = $this->extract_category_code($product_name);
        $material_code = $this->extract_material_code($product_name . ' ' . $variant_value);
        $size_code = $this->extract_size_code($product_name . ' ' . $variant_value);
        $color_code = $this->extract_color_code($variant_value);
        
        // Build SKU components
        $sku_parts = [];
        
        // 1. Category code (required)
        $sku_parts[] = $category_code;
        
        // 2. Material code (if found)
        if ($material_code) {
            $sku_parts[] = $material_code;
        }
        
        // 3. Size code (if found)
        if ($size_code) {
            $sku_parts[] = $size_code;
        }
        
        // 4. Color code (if found)
        if ($color_code) {
            $sku_parts[] = $color_code;
        }
        
        // 5. Unique identifier
        $unique_id = $this->generate_unique_id($sku_parts);
        $sku_parts[] = $unique_id;
        
        // Join with hyphens
        $sku = implode('-', $sku_parts);
        
        // Ensure uniqueness by checking database
        $sku = $this->ensure_uniqueness($sku);
        
        return strtoupper($sku);
    }
    
    /**
     * Extract category code from product name
     */
    private function extract_category_code($product_name) {
        // Check for known categories
        foreach ($this->category_codes as $keyword => $code) {
            if (strpos($product_name, $keyword) !== false) {
                return $code;
            }
        }
        
        // If no match, create code from first significant word
        $words = preg_split('/\s+/', $product_name);
        foreach ($words as $word) {
            // Skip common words
            if (in_array($word, ['custom', 'branded', 'logo', 'promotional', 'bulk', 'wholesale'])) {
                continue;
            }
            // Use first 3 letters of first significant word
            if (strlen($word) >= 3) {
                return strtoupper(substr($word, 0, 3));
            }
        }
        
        // Fallback
        return 'PRD';
    }
    
    /**
     * Extract material code
     */
    private function extract_material_code($text) {
        $materials = [
            'stainless steel' => 'SS',
            'stainless' => 'SS',
            'steel' => 'STL',
            'plastic' => 'PLS',
            'glass' => 'GLS',
            'ceramic' => 'CRM',
            'wood' => 'WOD',
            'bamboo' => 'BMB',
            'cotton' => 'CTN',
            'polyester' => 'PES',
            'leather' => 'LTH',
            'nylon' => 'NYL',
            'silicone' => 'SIL',
            'rubber' => 'RBR',
            'aluminum' => 'ALU',
            'metal' => 'MTL',
            'paper' => 'PPR',
            'canvas' => 'CVS',
        ];
        
        foreach ($materials as $keyword => $code) {
            if (strpos($text, $keyword) !== false) {
                return $code;
            }
        }
        
        return null;
    }
    
    /**
     * Extract size code
     */
    private function extract_size_code($text) {
        // Clothing sizes
        if (preg_match('/\b(small|sm|s)\b/i', $text)) return 'S';
        if (preg_match('/\b(medium|med|m)\b/i', $text)) return 'M';
        if (preg_match('/\b(large|lg|l)\b/i', $text)) return 'L';
        if (preg_match('/\b(x-large|xlarge|xl)\b/i', $text)) return 'XL';
        if (preg_match('/\b(xx-large|xxlarge|xxl|2xl)\b/i', $text)) return '2XL';
        if (preg_match('/\b(xxx-large|xxxlarge|xxxl|3xl)\b/i', $text)) return '3XL';
        
        // Numeric sizes (oz, ml, etc)
        if (preg_match('/(\d+)\s*oz/i', $text, $matches)) {
            return $matches[1] . 'OZ';
        }
        if (preg_match('/(\d+)\s*ml/i', $text, $matches)) {
            return $matches[1] . 'ML';
        }
        if (preg_match('/(\d+)\s*l\b/i', $text, $matches)) {
            return $matches[1] . 'L';
        }
        if (preg_match('/(\d+)\s*inch/i', $text, $matches)) {
            return $matches[1] . 'IN';
        }
        if (preg_match('/(\d+)\s*cm/i', $text, $matches)) {
            return $matches[1] . 'CM';
        }
        
        return null;
    }
    
    /**
     * Extract color code
     */
    private function extract_color_code($text) {
        $colors = [
            // Primary colors
            'red' => 'RD',
            'blue' => 'BL',
            'green' => 'GR',
            'yellow' => 'YL',
            'black' => 'BK',
            'white' => 'WH',
            'gray' => 'GY',
            'grey' => 'GY',
            
            // Secondary colors
            'orange' => 'OR',
            'purple' => 'PP',
            'pink' => 'PK',
            'brown' => 'BR',
            'navy' => 'NV',
            'teal' => 'TL',
            'turquoise' => 'TQ',
            
            // Metallic
            'gold' => 'GD',
            'silver' => 'SV',
            'bronze' => 'BZ',
            'copper' => 'CP',
            
            // Descriptive
            'clear' => 'CLR',
            'transparent' => 'CLR',
            'multi' => 'MLT',
            'rainbow' => 'RBW',
        ];
        
        foreach ($colors as $keyword => $code) {
            if (strpos($text, $keyword) !== false) {
                return $code;
            }
        }
        
        return null;
    }
    
    /**
     * Generate unique identifier
     */
    private function generate_unique_id($existing_parts) {
        // Use a combination of:
        // 1. Current year (last 2 digits)
        // 2. Month (numeric)
        // 3. Random alphanumeric (3 chars)
        
        $year = date('y'); // e.g., 24 for 2024
        $month = date('n'); // 1-12 without leading zeros
        $random = $this->generate_random_string(3);
        
        return $year . $month . $random;
    }
    
    /**
     * Generate random alphanumeric string
     */
    private function generate_random_string($length) {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $string = '';
        
        for ($i = 0; $i < $length; $i++) {
            $string .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        return $string;
    }
    
    /**
     * Ensure SKU uniqueness by checking against existing SKUs
     */
    private function ensure_uniqueness($sku) {
        global $wpdb;
        
        // Check in the activity log for existing SKUs
        $table_name = $wpdb->prefix . 'sspu_activity_log';
        
        $exists = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM {$table_name} 
            WHERE action = 'sku_generated' 
            AND JSON_EXTRACT(metadata, '$.sku') = %s
        ", $sku));
        
        if ($exists) {
            // Add a suffix to make it unique
            $suffix = 1;
            $original_sku = $sku;
            
            while ($exists) {
                $sku = $original_sku . '-' . $suffix;
                $exists = $wpdb->get_var($wpdb->prepare("
                    SELECT COUNT(*) 
                    FROM {$table_name} 
                    WHERE action = 'sku_generated' 
                    AND JSON_EXTRACT(metadata, '$.sku') = %s
                ", $sku));
                $suffix++;
            }
        }
        
        // Log the generated SKU
        $analytics = new SSPU_Analytics();
        $analytics->log_activity(get_current_user_id(), 'sku_generated', [
            'sku' => $sku,
            'timestamp' => current_time('mysql')
        ]);
        
        return $sku;
    }
    
    /**
     * Validate SKU format
     */
    public function validate_sku($sku) {
        // SKU rules:
        // - 3-30 characters
        // - Alphanumeric and hyphens only
        // - No spaces
        // - Uppercase
        
        if (strlen($sku) < 3 || strlen($sku) > 30) {
            $this->last_error = 'SKU must be between 3 and 30 characters';
            return false;
        }
        
        if (!preg_match('/^[A-Z0-9\-]+$/', $sku)) {
            $this->last_error = 'SKU can only contain uppercase letters, numbers, and hyphens';
            return false;
        }
        
        return true;
    }
    
    /**
     * Get the last error message
     */
    public function get_last_error() {
        return $this->last_error;
    }
}