<?php
/**
 * SSPU Admin Product Handler - Complete Remake
 *
 * Handles product submission to Shopify with comprehensive error handling,
 * detailed logging, and a multi-step asset processing flow.
 */

if (!defined('WPINC')) {
    die;
}

class SSPU_Admin_Product_Handler {

    /**
     * @var SSPU_Shopify_API
     */
    private $shopify_api;

    /**
     * @var SSPU_Analytics
     */
    private $analytics;

    /**
     * @var SSPU_OpenAI
     */
    private $openai;

    /**
     * @var array
     */
    private $submission_log = [];

    /**
     * @var array
     */
    private $errors = [];

    /**
     * @var int
     */
    private $max_retries = 3;

    /**
     * @var int
     */
    private $retry_delay = 2; // seconds

    /**
     * Constructor
     */
    public function __construct() {
        $this->shopify_api = new SSPU_Shopify_API();
        $this->analytics = new SSPU_Analytics();
        $this->openai = new SSPU_OpenAI();
    }

    /**
     * Initialize handlers
     */
    public function init_handlers() {
        // Product submission
        add_action('wp_ajax_sspu_submit_product', [$this, 'handle_product_submission']);

        // Test endpoints
        add_action('wp_ajax_sspu_test_shopify_connection', [$this, 'handle_test_connection']);

        // Live editor endpoints
        add_action('wp_ajax_sspu_get_live_product_data', [$this, 'handle_get_product_data']);
        add_action('wp_ajax_sspu_update_live_product', [$this, 'handle_update_product']);
        add_action('wp_ajax_sspu_search_live_products', [$this, 'handle_search_products']);
        add_action('wp_ajax_sspu_update_variant_inventory', [$this, 'handle_update_inventory']);
        add_action('wp_ajax_sspu_delete_product_image', [$this, 'handle_delete_image']);
        add_action('wp_ajax_sspu_update_product_images_order', [$this, 'handle_update_images_order']);
        add_action('wp_ajax_sspu_duplicate_product', [$this, 'handle_duplicate_product']);
        add_action('wp_ajax_sspu_get_shopify_locations', [$this, 'handle_get_locations']);
        add_action('wp_ajax_sspu_update_product_metafield', [$this, 'handle_update_metafield']);
        add_action('wp_ajax_sspu_update_variant_metafield', [$this, 'handle_update_variant_metafield']);
        add_action('wp_ajax_sspu_get_vendors', [$this, 'handle_get_vendors']);
        add_action('wp_ajax_sspu_update_product_collections', [$this, 'handle_update_collections']);
    }

    /**
     * Main handler for product submission with the new multi-step flow.
     */
    public function handle_product_submission() {
        $this->submission_log = [];
        $this->errors = [];
        $start_time = microtime(true);

        try {
            $this->log('=== NEW PRODUCT SUBMISSION FLOW STARTED ===');
            $this->log('Timestamp: ' . current_time('mysql'));
            $this->log('User ID: ' . get_current_user_id());

            // Step 1: Verify security
            if (!$this->verify_security()) {
                $this->send_error_response('Security verification failed');
                return;
            }

            // Step 2: Verify permissions
            if (!$this->verify_permissions()) {
                $this->send_error_response('Permission denied');
                return;
            }

            // Step 3: Verify Shopify configuration
            if (!$this->verify_shopify_config()) {
                $this->send_error_response('Shopify configuration missing');
                return;
            }

            // Step 4: Validate and prepare product data
            $product_data = $this->prepare_product_data($_POST);
            if (is_wp_error($product_data)) {
                $this->send_error_response($product_data->get_error_message());
                return;
            }

            // Step 5: Create product in Shopify
            $product = $this->create_product_with_retry($product_data);
            if (!$product) {
                $this->send_error_response('Failed to create product after ' . $this->max_retries . ' attempts');
                return;
            }
            
            // Step 6: Process additional data (collections, static metafields)
            $this->log('Starting Step 6: Processing product extras...');
            $this->process_product_extras($product, $_POST);
            
            // Step 7: Process variant assets (upload masks, set metafields, cleanup)
            $this->log('Starting Step 7: Processing variant assets...');
            $this->process_variant_assets($product, $_POST);

            // Step 8: Final description reformatting
            $this->log('Starting Step 8: Reformatting product description...');
            $this->reformat_final_description($product, $product_data['body_html']);

            // Step 9: Final success logging and response
            $duration = round(microtime(true) - $start_time, 2);
            $this->log("✅ SUCCESS: Product upload process completed in {$duration} seconds.");
            
            $this->analytics->log_activity(
                get_current_user_id(),
                'product_created_v2', // New action type for the enhanced flow
                [
                    'product_id' => $product['id'],
                    'product_title' => $product['title'],
                    'upload_duration' => $duration
                ]
            );

            delete_user_meta(get_current_user_id(), 'sspu_product_draft');
            $this->send_success_response($product);

        } catch (Exception $e) {
            $this->handle_exception($e);
        }
    }

    /**
     * Processes variant assets.
     * This is now ONLY for setting metafields, as uploads are handled via AJAX.
     */
    private function process_variant_assets($product, $post_data) {
        $this->log('Processing variant metafields.');

        foreach ($product['variants'] as $index => $variant) {
            if (!isset($post_data['variant_options'][$index])) continue;

            $variant_data = $post_data['variant_options'][$index];

            // --- Designer Data Handling ---
            $background_url = $variant_data['designer_background_url'] ?? '';
            $mask_url = $variant_data['designer_mask_url'] ?? '';
            
            // Only proceed if we have BOTH Cloudinary URLs from the form
            if (!empty($background_url) && !empty($mask_url) && strpos($background_url, 'cloudinary') !== false) {
                $this->log("Found Cloudinary URLs for variant ID: {$variant['id']}");

                $designer_data = [
                    'background_image' => esc_url_raw($background_url),
                    'mask_image'       => esc_url_raw($mask_url)
                ];
                
                $this->shopify_api->update_variant_metafield($variant['id'], [
                    'namespace' => 'custom',
                    'key' => 'designer_data',
                    'value' => json_encode($designer_data),
                    'type' => 'json'
                ]);
                $this->log("Saved designer_data metafield for variant ID: {$variant['id']}.");
            }

            // --- Volume Tiers Handling ---
            if (!empty($variant_data['tiers']) && is_array($variant_data['tiers'])) {
                $this->shopify_api->update_variant_metafield($variant['id'], [
                    'namespace' => 'custom',
                    'key' => 'volume_tiers',
                    'value' => json_encode($variant_data['tiers']),
                    'type' => 'json'
                ]);
                $this->log("Saved volume tiers for variant ID: {$variant['id']}.");
            }
        }
        
        $this->log("Variant metafield processing complete.");
    }
    
    /**
     * Reformats the description using the styled prompt and updates the product.
     *
     * @param array $product The created Shopify product object.
     * @param string $simple_html The initial simple HTML description.
     */
    private function reformat_final_description($product, $simple_html) {
        $this->log('Reformatting description with styled template...');
        
        // Gather attributes for the prompt
        $attributes = [
            'product_name' => $product['title'],
            'moq' => $_POST['product_min'] ?? 'N/A', // Get from original POST data
            'print_methods' => $_POST['print_methods'] ?? [],
            'variants' => $product['variants']
        ];
        
        $styled_html = $this->openai->reformat_description_with_style($simple_html, $attributes);
        
        if ($styled_html) {
            $this->log('Successfully generated styled HTML. Updating product...');
            $update_data = ['id' => $product['id'], 'body_html' => $styled_html];
            $this->shopify_api->update_product($product['id'], $update_data);
        } else {
            $this->log('WARNING: Failed to generate styled HTML. The simple description will be used.');
            $this->errors[] = 'Styled description generation failed. Using basic version.';
        }
    }

    /**
     * Add log entry
     * @param string $message The message to log.
     */
    private function log($message) {
        $timestamp = date('H:i:s');
        $this->submission_log[] = "[{$timestamp}] " . $message;
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('SSPU Handler: ' . $message);
        }
    }
    
    /**
     * Helper to upload a single image to a specific product's gallery.
     *
     * @param int $product_id The Shopify Product ID.
     * @param string $image_url The URL of the image to upload.
     * @return array|false The Shopify image object on success, false on failure.
     */
    private function upload_image_to_product($product_id, $image_url) {
        if (empty($image_url)) return false;

        $this->log("Attempting to upload image to product {$product_id} from URL: {$image_url}");
        $response = $this->shopify_api->send_request(
            "products/{$product_id}/images.json",
            'POST',
            ['image' => ['src' => $image_url]]
        );

        if (isset($response['image'])) {
            $this->log("Image uploaded to product, new image ID: {$response['image']['id']}");
            return $response['image'];
        }
        
        $this->log("ERROR: Failed to upload image from URL: {$image_url}. Response: " . json_encode($response));
        $this->errors[] = "Image upload failed for URL: " . basename($image_url);
        return false;
    }
    
    private function verify_security() {
        $this->log('Verifying security token...');
        if (!isset($_POST['sspu_nonce']) || !wp_verify_nonce($_POST['sspu_nonce'], 'sspu_submit_product')) {
            $this->log('ERROR: Security check failed.');
            $this->errors[] = 'Invalid security token.';
            return false;
        }
        $this->log('Security token verified.');
        return true;
    }
    
    private function verify_permissions() {
        $this->log('Verifying user permissions...');
        if (!current_user_can('upload_shopify_products')) {
            $this->log('ERROR: User does not have "upload_shopify_products" capability.');
            $this->errors[] = 'Insufficient permissions.';
            return false;
        }
        $this->log('User permissions verified.');
        return true;
    }
    
    private function verify_shopify_config() {
        $this->log('Verifying Shopify configuration...');
        if (empty($this->shopify_api->get_store_name()) || empty(get_option('sspu_shopify_access_token'))) {
            $this->log('ERROR: Shopify API credentials are not set.');
            $this->errors[] = 'Shopify API credentials are not set.';
            return false;
        }
        $this->log('Shopify configuration verified.');
        return true;
    }
    
    private function prepare_product_data($post_data) {
        $this->log('Preparing initial product data...');
        
        if (empty($post_data['product_name'])) {
            $this->log('ERROR: Product name is required.');
            return new WP_Error('missing_name', 'Product name is required');
        }
        
        $product_data = [
            'title' => sanitize_text_field($post_data['product_name']),
            'body_html' => wp_kses_post($post_data['product_description'] ?? ''),
            'vendor' => sanitize_text_field($post_data['product_vendor'] ?? ''),
            'product_type' => sanitize_text_field($post_data['product_type'] ?? ''),
            'tags' => sanitize_text_field($post_data['product_tags'] ?? ''),
            'published' => true,
            'options' => [],
            'variants' => [],
            'images' => [],
        ];
        
        if (!empty($post_data['url_handle'])) {
            $product_data['handle'] = sanitize_title($post_data['url_handle']);
        }
        
        // Process Main and Additional Images
        $position = 1;
        if (!empty($post_data['main_image_id'])) {
            $product_data['images'][] = ['src' => wp_get_attachment_url($post_data['main_image_id']), 'position' => $position++];
        }
        if (!empty($post_data['additional_image_ids'])) {
            foreach (explode(',', $post_data['additional_image_ids']) as $img_id) {
                $product_data['images'][] = ['src' => wp_get_attachment_url(trim($img_id)), 'position' => $position++];
            }
        }
        
        // Process Variants
        if (empty($post_data['variant_options']) || !is_array($post_data['variant_options'])) {
            return new WP_Error('missing_variants', 'At least one variant is required.');
        }
        
        $option_names = [];
        foreach ($post_data['variant_options'] as $variant_data) {
            $option_name = !empty($variant_data['name']) ? sanitize_text_field($variant_data['name']) : 'Default';
            if (!in_array($option_name, $option_names)) {
                $option_names[] = $option_name;
            }
            $product_data['variants'][] = [
                'option1' => sanitize_text_field($variant_data['value']),
                'price' => number_format(floatval($variant_data['price']), 2, '.', ''),
                'sku' => sanitize_text_field($variant_data['sku'] ?? ''),
            ];
        }
        foreach ($option_names as $name) {
            $product_data['options'][] = ['name' => $name];
        }
        
        $this->log('Product data preparation complete.');
        return $product_data;
    }
    
    private function create_product_with_retry($product_data) {
        $this->log('Attempting to create product in Shopify...');
        $attempts = 0;
        while ($attempts < $this->max_retries) {
            $attempts++;
            $this->log("Creation attempt #{$attempts}...");
            $response = $this->shopify_api->send_request('products.json', 'POST', ['product' => $product_data]);
            if (isset($response['product'])) {
                $this->log("Product created successfully! Shopify ID: {$response['product']['id']}");
                return $response['product'];
            }
            $this->log("Attempt #{$attempts} failed. Retrying in {$this->retry_delay} seconds...");
            sleep($this->retry_delay);
        }
        $this->log('ERROR: Failed to create product after all retries.');
        $this->errors[] = 'Failed to create product in Shopify after multiple attempts.';
        return false;
    }
    
    private function process_product_extras($product, $post_data) {
        $this->log('Processing collections and static metafields...');
        
        // Add to collections
        if (!empty($post_data['product_collections'])) {
            $this->add_to_collections($product['id'], (array)$post_data['product_collections']);
        }
        
        // Process print methods
        if (!empty($post_data['print_methods'])) {
            $this->process_print_methods($product['id'], $post_data['print_methods']);
        }

        // Process min/max metafields
        $this->process_min_max_metafields($product['id'], $post_data);
    }
    
    private function add_to_collections($product_id, $collection_ids) {
        $this->log('Adding product to ' . count($collection_ids) . ' collections...');
        foreach ($collection_ids as $collection_id) {
            $this->shopify_api->send_request('collects.json', 'POST', [
                'collect' => [
                    'product_id' => $product_id,
                    'collection_id' => intval($collection_id)
                ]
            ]);
        }
    }

    private function process_print_methods($product_id, $print_methods) {
        $this->log('Setting print method metafields...');
        $available_methods = ['silkscreen', 'uvprint', 'embroidery', 'sublimation', 'emboss', 'laserengrave'];
        foreach ($available_methods as $method) {
            $this->shopify_api->update_product_metafield($product_id, [
                'namespace' => 'custom',
                'key' => $method,
                'value' => in_array($method, $print_methods) ? 'true' : 'false',
                'type' => 'boolean'
            ]);
        }
    }

    private function process_min_max_metafields($product_id, $post_data) {
        if (!empty($post_data['product_min'])) {
            $this->log('Setting min quantity metafield...');
            $this->shopify_api->update_product_metafield($product_id, [
                'namespace' => 'custom', 'key' => 'min_quantity', 
                'value' => intval($post_data['product_min']), 'type' => 'number_integer'
            ]);
        }
        if (!empty($post_data['product_max'])) {
             $this->log('Setting max quantity metafield...');
            $this->shopify_api->update_product_metafield($product_id, [
                'namespace' => 'custom', 'key' => 'max_quantity', 
                'value' => intval($post_data['product_max']), 'type' => 'number_integer'
            ]);
        }
    }

    private function send_success_response($product) {
        wp_send_json_success([
            'product_id' => $product['id'],
            'product_url' => 'https://' . $this->shopify_api->get_store_name() . '.myshopify.com/admin/products/' . $product['id'],
            'log' => $this->submission_log
        ]);
    }
    
    private function send_error_response($message) {
        $this->log("ERROR: {$message}");
        wp_send_json_error([
            'message' => $message,
            'log' => $this->submission_log,
            'errors' => $this->errors
        ]);
    }

    private function handle_exception($e) {
        $this->log('FATAL EXCEPTION: ' . $e->getMessage());
        $this->log('Stack Trace: ' . $e->getTraceAsString());
        $this->send_error_response('An unexpected server error occurred.');
    }

    // --- All other AJAX handlers for live editor, etc. ---
    
    public function handle_test_connection() {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');
        $response = $this->shopify_api->send_request('shop.json');
        if (isset($response['shop'])) {
            wp_send_json_success(['message' => 'Connection successful!', 'data' => $response['shop']]);
        } else {
            wp_send_json_error(['message' => 'Connection failed.', 'response' => $response]);
        }
    }

    public function handle_get_product_data() {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');
        if (!current_user_can('upload_shopify_products')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }
        $product_id = intval($_POST['product_id']);
        if (!$product_id) {
            wp_send_json_error(['message' => 'Invalid product ID']);
            return;
        }
        $response = $this->shopify_api->get_product($product_id);
        if (isset($response['product'])) {
            $response['product']['collection_ids'] = $this->shopify_api->get_product_collections($product_id);
            set_transient('sspu_editing_product_' . get_current_user_id(), $response['product'], HOUR_IN_SECONDS);
            wp_send_json_success(['product' => $response['product']]);
        } else {
            wp_send_json_error(['message' => 'Product not found']);
        }
    }

    public function handle_update_product() {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');
        if (!current_user_can('upload_shopify_products')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }
        $product_id = intval($_POST['product_id']);
        $product_data = $_POST['product_data'];
        if (!$product_id || !$product_data) {
            wp_send_json_error(['message' => 'Missing required data']);
            return;
        }
        
        $original = get_transient('sspu_editing_product_' . get_current_user_id());
        $response = $this->shopify_api->update_product($product_id, $product_data);
        if (isset($response['product'])) {
            $this->update_product_metafields($product_id, $product_data);
            if (isset($product_data['collection_ids'])) {
                $this->update_product_collections($product_id, $product_data['collection_ids']);
            }
            $changes = $this->calculate_changes($original, $product_data);
            $this->analytics->log_activity(get_current_user_id(), 'product_updated', [
                'product_id' => $product_id,
                'changes' => $changes
            ]);
            wp_send_json_success(['product' => $response['product'], 'changes' => $changes]);
        } else {
            wp_send_json_error(['message' => 'Update failed.']);
        }
    }

    public function handle_search_products() {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');
        if (!current_user_can('upload_shopify_products')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }
        $params = [
            'query' => sanitize_text_field($_POST['query'] ?? ''),
            'status' => sanitize_text_field($_POST['status'] ?? ''),
            'vendor' => sanitize_text_field($_POST['vendor'] ?? ''),
            'collection_id' => intval($_POST['collection_id'] ?? 0),
            'limit' => intval($_POST['limit'] ?? 50),
            'page_info' => sanitize_text_field($_POST['page_info'] ?? '')
        ];
        $response = $this->shopify_api->search_products($params);
        if (isset($response['products'])) {
            wp_send_json_success($response);
        } else {
            wp_send_json_error(['message' => 'Search failed.']);
        }
    }

    public function handle_update_inventory() {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');
        if (!current_user_can('upload_shopify_products')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }
        $inventory_item_id = intval($_POST['inventory_item_id']);
        $available = intval($_POST['available']);
        $location_id = intval($_POST['location_id']);
        $response = $this->shopify_api->update_inventory_level($inventory_item_id, $available, $location_id);
        if (isset($response['inventory_level'])) {
            wp_send_json_success(['inventory_level' => $response['inventory_level']]);
        } else {
            wp_send_json_error(['message' => 'Failed to update inventory']);
        }
    }

    public function handle_delete_image() {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');
        if (!current_user_can('upload_shopify_products')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }
        $product_id = intval($_POST['product_id']);
        $image_id = intval($_POST['image_id']);
        $response = $this->shopify_api->delete_product_image($product_id, $image_id);
        if (!isset($response['errors'])) {
            wp_send_json_success(['message' => 'Image deleted']);
        } else {
            wp_send_json_error(['message' => 'Failed to delete image']);
        }
    }

    public function handle_update_images_order() {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');
        if (!current_user_can('upload_shopify_products')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }
        $product_id = intval($_POST['product_id']);
        $image_ids = array_map('intval', $_POST['image_ids']);
        $response = $this->shopify_api->update_images_order($product_id, $image_ids);
        if (isset($response['product'])) {
            wp_send_json_success(['message' => 'Image order updated']);
        } else {
            wp_send_json_error(['message' => 'Failed to update image order']);
        }
    }

    public function handle_duplicate_product() {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');
        if (!current_user_can('upload_shopify_products')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }
        $product_id = intval($_POST['product_id']);
        $new_title = sanitize_text_field($_POST['new_title']);
        $response = $this->shopify_api->duplicate_product($product_id, $new_title);
        if (isset($response['product'])) {
            $this->analytics->log_activity(get_current_user_id(), 'product_duplicated', ['original_id' => $product_id, 'new_id' => $response['product']['id']]);
            wp_send_json_success(['product' => $response['product']]);
        } else {
            wp_send_json_error(['message' => 'Failed to duplicate product']);
        }
    }

    public function handle_get_locations() {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');
        if (!current_user_can('upload_shopify_products')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }
        $response = $this->shopify_api->get_locations();
        if (isset($response['locations'])) {
            wp_send_json_success(['locations' => $response['locations']]);
        } else {
            wp_send_json_error(['message' => 'Failed to get locations']);
        }
    }

    public function handle_update_metafield() {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');
        if (!current_user_can('upload_shopify_products')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }
        $product_id = intval($_POST['product_id']);
        $metafield = $_POST['metafield'];
        $response = $this->shopify_api->update_product_metafield($product_id, $metafield);
        if (isset($response['metafield'])) {
            wp_send_json_success(['metafield' => $response['metafield']]);
        } else {
            wp_send_json_error(['message' => 'Failed to update metafield']);
        }
    }

    public function handle_update_variant_metafield() {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');
        if (!current_user_can('upload_shopify_products')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }
        $variant_id = intval($_POST['variant_id']);
        $metafield = $_POST['metafield'];
        $response = $this->shopify_api->update_variant_metafield($variant_id, $metafield);
        if (isset($response['metafield'])) {
            wp_send_json_success(['metafield' => $response['metafield']]);
        } else {
            wp_send_json_error(['message' => 'Failed to update variant metafield']);
        }
    }

    public function handle_get_vendors() {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');
        if (!current_user_can('upload_shopify_products')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }
        $vendors = $this->shopify_api->get_vendors();
        wp_send_json_success(['vendors' => $vendors]);
    }
    
    public function handle_update_collections() {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');
        if (!current_user_can('upload_shopify_products')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }
        $product_id = intval($_POST['product_id']);
        $collection_ids = array_map('intval', $_POST['collection_ids'] ?? []);
        $original_ids = array_map('intval', $_POST['original_ids'] ?? []);

        $to_add = array_diff($collection_ids, $original_ids);
        $to_remove = array_diff($original_ids, $collection_ids);

        foreach ($to_add as $id) {
            $this->shopify_api->add_product_to_collection($product_id, $id);
        }

        foreach ($to_remove as $id) {
            $this->shopify_api->remove_product_from_collection($product_id, $id);
        }

        wp_send_json_success(['message' => 'Collections updated.']);
    }

    private function update_product_collections($product_id, $new_ids) {
        $this->log("Updating collections for product {$product_id}");
        $original_ids = $this->shopify_api->get_product_collections($product_id);

        $to_add = array_diff($new_ids, $original_ids);
        $to_remove = array_diff($original_ids, $new_ids);
        
        foreach ($to_add as $id) {
            $this->shopify_api->add_product_to_collection($product_id, $id);
        }
        foreach ($to_remove as $id) {
            $this->shopify_api->remove_product_from_collection($product_id, $id);
        }
        $this->log("Collections updated.");
    }

    private function calculate_changes($original, $new_data) {
        if (empty($original)) return ['summary' => 'No original data to compare.'];
        
        $changes = [];
        $simple_fields = ['title', 'body_html', 'vendor', 'product_type', 'status', 'tags'];
        foreach ($simple_fields as $field) {
            if ($original[$field] != $new_data[$field]) {
                $changes[$field] = [
                    'from' => $original[$field],
                    'to' => $new_data[$field]
                ];
            }
        }
        // Add more complex change detection if needed (variants, images, etc.)
        return $changes;
    }
}