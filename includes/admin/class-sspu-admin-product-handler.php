<?php
/**
 * SSPU Admin Product Handler - Complete Remake
 * 
 * Handles product submission to Shopify with comprehensive error handling,
 * detailed logging, and retry mechanisms.
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
     * Handle product submission with comprehensive error handling
     */
    public function handle_product_submission() {
        // Initialize submission
        $this->submission_log = [];
        $this->errors = [];
        $start_time = microtime(true);
        
        try {
            $this->log('=== PRODUCT SUBMISSION STARTED ===');
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
            
            // Step 6: Process additional data (images, collections, metafields)
            $this->process_product_extras($product, $_POST);
            
            // Step 7: Log success and send response
            $duration = round(microtime(true) - $start_time, 2);
            $this->log("Product creation completed in {$duration} seconds");
            
            $this->analytics->log_activity(
                get_current_user_id(),
                'product_created', // Action type for product creation
                [
                    'product_id' => $product['id'],
                    'product_title' => $product['title'],
                    'variant_count' => count($product['variants']),
                    'upload_duration' => $duration
                ]
            );
            
            // Clear any saved drafts
            delete_user_meta(get_current_user_id(), 'sspu_product_draft');
            
            $this->send_success_response($product);
            
        } catch (Exception $e) {
            $this->handle_exception($e);
        }
    }
    
    /**
     * Verify security (nonce)
     */
    private function verify_security() {
        $this->log('Verifying security token...');
        
        if (!isset($_POST['sspu_nonce'])) {
            $this->log('ERROR: Security token missing from request');
            $this->errors[] = 'Security token missing';
            return false;
        }
        
        if (!wp_verify_nonce($_POST['sspu_nonce'], 'sspu_submit_product')) {
            $this->log('ERROR: Invalid security token');
            $this->errors[] = 'Invalid security token';
            return false;
        }
        
        $this->log('Security token verified');
        return true;
    }
    
    /**
     * Verify user permissions
     */
    private function verify_permissions() {
        $this->log('Verifying user permissions...');
        
        if (!is_user_logged_in()) {
            $this->log('ERROR: User not logged in');
            $this->errors[] = 'User not logged in';
            return false;
        }
        
        $user = wp_get_current_user();
        $this->log('User: ' . $user->user_login . ' (ID: ' . $user->ID . ')');
        $this->log('User roles: ' . implode(', ', $user->roles));
        
        if (!current_user_can('upload_shopify_products')) {
            $this->log('ERROR: User lacks upload_shopify_products capability');
            $this->errors[] = 'Insufficient permissions';
            return false;
        }
        
        $this->log('User permissions verified');
        return true;
    }
    
    /**
     * Verify Shopify configuration
     */
    private function verify_shopify_config() {
        $this->log('Verifying Shopify configuration...');
        
        $store_name = get_option('sspu_shopify_store_name');
        $access_token = get_option('sspu_shopify_access_token');
        
        if (empty($store_name)) {
            $this->log('ERROR: Shopify store name not configured');
            $this->errors[] = 'Shopify store name not configured';
            return false;
        }
        
        if (empty($access_token)) {
            $this->log('ERROR: Shopify access token not configured');
            $this->errors[] = 'Shopify access token not configured';
            return false;
        }
        
        $this->log('Shopify configuration verified');
        $this->log('Store: ' . $store_name);
        
        return true;
    }
    
    /**
     * Prepare product data from form submission
     */
    private function prepare_product_data($post_data) {
        $this->log('Preparing product data...');
        
        // Validate required fields
        if (empty($post_data['product_name'])) {
            $this->log('ERROR: Product name is required');
            return new WP_Error('missing_name', 'Product name is required');
        }
        
        if (empty($post_data['variant_options']) || !is_array($post_data['variant_options'])) {
            $this->log('ERROR: At least one variant is required');
            return new WP_Error('missing_variants', 'At least one variant is required');
        }
        
        // Start building product data
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
            'metafields' => []
        ];
        
        $this->log('Basic product data prepared');
        
        // Process SEO metafields
        if (!empty($post_data['seo_title'])) {
            $product_data['metafields'][] = [
                'namespace' => 'global',
                'key' => 'title_tag',
                'value' => sanitize_text_field($post_data['seo_title']),
                'type' => 'single_line_text_field'
            ];
        }
        
        if (!empty($post_data['meta_description'])) {
            $product_data['metafields'][] = [
                'namespace' => 'global',
                'key' => 'description_tag',
                'value' => sanitize_textarea_field($post_data['meta_description']),
                'type' => 'multi_line_text_field'
            ];
        }
        
        // Handle URL handle
        if (!empty($post_data['url_handle'])) {
            $product_data['handle'] = sanitize_title($post_data['url_handle']);
        }
        
        // Process variants
        $option_names = [];
        $variant_count = 0;
        
        foreach ($post_data['variant_options'] as $index => $variant_data) {
            if (empty($variant_data['value'])) {
                $this->log("WARNING: Skipping variant {$index} - no value provided");
                continue;
            }
            
            $option_name = !empty($variant_data['name']) ? sanitize_text_field($variant_data['name']) : 'Option';
            
            if (!in_array($option_name, $option_names)) {
                $option_names[] = $option_name;
            }
            
            $variant = [
                'option1' => sanitize_text_field($variant_data['value']),
                'price' => $this->sanitize_price($variant_data['price'] ?? 0),
                'sku' => sanitize_text_field($variant_data['sku'] ?? ''),
                'weight' => floatval($variant_data['weight'] ?? 0),
                'weight_unit' => 'lb',
                'inventory_management' => 'shopify',
                'inventory_quantity' => 100,
                'inventory_policy' => 'deny',
                'fulfillment_service' => 'manual',
                'taxable' => true
            ];
            
            // Validate variant data
            if ($variant['price'] <= 0) {
                $this->log("WARNING: Variant {$index} has invalid price, setting to 0.01");
                $variant['price'] = '0.01';
            }
            
            $product_data['variants'][] = $variant;
            $variant_count++;
        }
        
        if ($variant_count === 0) {
            $this->log('ERROR: No valid variants found');
            return new WP_Error('no_valid_variants', 'No valid variants found');
        }
        
        $this->log("Processed {$variant_count} variants");
        
        // Set product options
        foreach ($option_names as $option_name) {
            $product_data['options'][] = ['name' => $option_name];
        }
        
        // Process images
        $this->process_product_images($product_data, $post_data);
        
        $this->log('Product data preparation complete');
        
        // Allow filtering
        $product_data = apply_filters('sspu_before_product_creation', $product_data, $post_data);
        
        return $product_data;
    }
    
    /**
     * Process product images
     */
    private function process_product_images(&$product_data, $post_data) {
        $position = 1;
        
        // Main image
        if (!empty($post_data['main_image_id'])) {
            $main_image_url = wp_get_attachment_url($post_data['main_image_id']);
            if ($main_image_url) {
                $product_data['images'][] = [
                    'src' => $main_image_url,
                    'position' => $position++
                ];
                $this->log('Main image added: ' . $main_image_url);
            } else {
                $this->log('WARNING: Could not get URL for main image ID: ' . $post_data['main_image_id']);
            }
        }
        
        // Additional images
        if (!empty($post_data['additional_image_ids'])) {
            $image_ids = explode(',', $post_data['additional_image_ids']);
            foreach ($image_ids as $image_id) {
                $image_id = trim($image_id);
                if (empty($image_id)) continue;
                
                $image_url = wp_get_attachment_url($image_id);
                if ($image_url) {
                    $product_data['images'][] = [
                        'src' => $image_url,
                        'position' => $position++
                    ];
                } else {
                    $this->log('WARNING: Could not get URL for image ID: ' . $image_id);
                }
            }
            $this->log('Added ' . count($image_ids) . ' additional images');
        }
    }
    
    /**
     * Create product with retry mechanism
     */
    private function create_product_with_retry($product_data) {
        $attempts = 0;
        $product = null;
        
        while ($attempts < $this->max_retries && !$product) {
            $attempts++;
            $this->log("Attempt {$attempts} of {$this->max_retries} to create product...");
            
            try {
                $response = $this->shopify_api->send_request('products.json', 'POST', ['product' => $product_data]);
                
                // Log the response
                $this->log_api_response($response);
                
                if (isset($response['errors'])) {
                    $this->handle_shopify_errors($response['errors']);
                    
                    // Check if error is retryable
                    if ($this->is_retryable_error($response['errors'])) {
                        $this->log("Retryable error detected, waiting {$this->retry_delay} seconds...");
                        sleep($this->retry_delay);
                        continue;
                    } else {
                        // Non-retryable error
                        break;
                    }
                }
                
                if (isset($response['product'])) {
                    $product = $response['product'];
                    $this->log('Product created successfully! ID: ' . $product['id']);
                    $this->log('Product handle: ' . $product['handle']);
                    break;
                }
                
            } catch (Exception $e) {
                $this->log('EXCEPTION during product creation: ' . $e->getMessage());
                if ($attempts < $this->max_retries) {
                    $this->log("Waiting {$this->retry_delay} seconds before retry...");
                    sleep($this->retry_delay);
                }
            }
        }
        
        return $product;
    }
    
    /**
     * Process additional product data after creation
     */
    private function process_product_extras($product, $post_data) {
        $this->log('Processing additional product data...');
        
        // Handle variant images
        if (!empty($post_data['variant_options']) && !empty($product['variants'])) {
            $this->process_variant_images($product, $post_data['variant_options']);
        }
        
        // Handle collections
        if (!empty($post_data['product_collections'])) {
            $this->add_to_collections($product['id'], $post_data['product_collections']);
        }
        
        // Handle print methods metafields
        if (!empty($post_data['print_methods'])) {
            $this->process_print_methods($product['id'], $post_data['print_methods']);
        }
        
        // Handle min/max metafields
        $this->process_min_max_metafields($product['id'], $post_data);
        
        // Handle variant metafields (including design files)
        if (!empty($product['variants']) && !empty($post_data['variant_options'])) {
            $this->process_variant_metafields($product['id'], $product['variants'], $post_data['variant_options']);
        }
        
        $this->log('Additional product data processing complete');
    }
    
    /**
     * Process variant images
     */
    private function process_variant_images($product, $variant_options) {
        $this->log('Processing variant images...');
        
        foreach ($variant_options as $index => $variant_data) {
            if (!empty($variant_data['image_id']) && isset($product['variants'][$index])) {
                $variant = $product['variants'][$index];
                $image_url = wp_get_attachment_url($variant_data['image_id']);
                
                if ($image_url) {
                    $this->log("Uploading image for variant: " . $variant_data['value']);
                    
                    $image_response = $this->shopify_api->send_request(
                        "products/{$product['id']}/images.json",
                        'POST',
                        [
                            'image' => [
                                'src' => $image_url,
                                'variant_ids' => [$variant['id']]
                            ]
                        ]
                    );
                    
                    if (isset($image_response['image'])) {
                        $this->log('Variant image uploaded successfully');
                    } else {
                        $this->log('WARNING: Failed to upload variant image');
                        if (isset($image_response['errors'])) {
                            $this->log('Error: ' . json_encode($image_response['errors']));
                        }
                    }
                } else {
                    $this->log('WARNING: Could not get URL for variant image ID: ' . $variant_data['image_id']);
                }
            }
        }
    }
    
    /**
     * Add product to collections
     */
    private function add_to_collections($product_id, $collection_ids) {
        $this->log('Adding product to collections...');
        
        if (!is_array($collection_ids)) {
            $collection_ids = [$collection_ids];
        }
        
        $success_count = 0;
        
        foreach ($collection_ids as $collection_id) {
            $collection_id = intval($collection_id);
            if ($collection_id <= 0) continue;
            
            $response = $this->shopify_api->send_request(
                'collects.json',
                'POST',
                [
                    'collect' => [
                        'product_id' => $product_id,
                        'collection_id' => $collection_id
                    ]
                ]
            );
            
            if (isset($response['collect'])) {
                $success_count++;
            } else {
                $this->log('WARNING: Failed to add to collection ID: ' . $collection_id);
            }
        }
        
        $this->log("Added to {$success_count} of " . count($collection_ids) . " collections");
    }
    
    /**
     * Process print methods metafields
     */
    private function process_print_methods($product_id, $print_methods) {
        $this->log('Processing print methods...');
        
        $available_methods = ['silkscreen', 'uvprint', 'embroidery', 'sublimation', 'emboss', 'laserengrave'];
        
        foreach ($available_methods as $method) {
            $value = in_array($method, $print_methods) ? 'true' : 'false';
            
            $response = $this->shopify_api->send_request(
                "products/{$product_id}/metafields.json",
                'POST',
                [
                    'metafield' => [
                        'namespace' => 'custom',
                        'key' => $method,
                        'value' => $value,
                        'type' => 'boolean'
                    ]
                ]
            );
            
            if (!isset($response['metafield'])) {
                $this->log('WARNING: Failed to set print method: ' . $method);
            }
        }
    }
    
    /**
     * Process min/max metafields
     */
    private function process_min_max_metafields($product_id, $post_data) {
        if (!empty($post_data['product_min'])) {
            $this->shopify_api->send_request(
                "products/{$product_id}/metafields.json",
                'POST',
                [
                    'metafield' => [
                        'namespace' => 'custom',
                        'key' => 'min_quantity',
                        'value' => intval($post_data['product_min']),
                        'type' => 'number_integer'
                    ]
                ]
            );
        }
        
        if (!empty($post_data['product_max'])) {
            $this->shopify_api->send_request(
                "products/{$product_id}/metafields.json",
                'POST',
                [
                    'metafield' => [
                        'namespace' => 'custom',
                        'key' => 'max_quantity',
                        'value' => intval($post_data['product_max']),
                        'type' => 'number_integer'
                    ]
                ]
            );
        }
    }
    
    /**
     * Process variant metafields
     */
    private function process_variant_metafields($product_id, $variants, $variant_options) {
    $this->log('Processing variant metafields...');

    // Keep track of uploaded mask images for cleanup
    $uploaded_mask_images = [];

    foreach ($variants as $index => $variant) {
        if (!isset($variant_options[$index])) continue;

        $variant_data = $variant_options[$index];

        // Designer data - Upload files to Shopify Files first
        if (!empty($variant_data['designer_background_url']) && !empty($variant_data['designer_mask_url'])) {
            $this->log("Processing design files for variant: " . $variant['id']);

            // Upload background file to Shopify Files
            $background_shopify_url = $this->upload_design_file_to_shopify(
                $product_id,
                $variant_data['designer_background_url'],
                "variant-{$variant['id']}-background"
            );

            // Upload mask file to Shopify Files
            $mask_shopify_url = $this->upload_design_file_to_shopify(
                $product_id,
                $variant_data['designer_mask_url'],
                "variant-{$variant['id']}-mask"
            );

            if ($background_shopify_url && $mask_shopify_url) {
                $designer_data = [
                    'background_image' => $background_shopify_url,
                    'mask_image' => $mask_shopify_url
                ];

                $response = $this->shopify_api->send_request(
                    "variants/{$variant['id']}/metafields.json",
                    'POST',
                    [
                        'metafield' => [
                            'namespace' => 'custom',
                            'key' => 'designer_data',
                            'value' => json_encode($designer_data),
                            'type' => 'json'
                        ]
                    ]
                );

                if (isset($response['metafield'])) {
                    $this->log("Successfully stored design URLs for variant {$variant['id']}");
                } else {
                    $this->log("ERROR: Failed to store design URLs in metafield");
                    if (isset($response['errors'])) {
                        $this->log("Metafield Error: " . json_encode($response['errors']));
                    }
                }
            } else {
                $this->log("ERROR: Failed to upload design files for variant {$variant['id']}");
            }
        }

        // Volume tiers
        if (!empty($variant_data['tiers']) && is_array($variant_data['tiers'])) {
            $this->shopify_api->send_request(
                "variants/{$variant['id']}/metafields.json",
                'POST',
                [
                    'metafield' => [
                        'namespace' => 'custom',
                        'key' => 'volume_tiers',
                        'value' => json_encode($variant_data['tiers']),
                        'type' => 'json'
                    ]
                ]
            );
        }
    }
}

    /**
     * Upload design file to Shopify
     */
    /**
 * Upload design file to Shopify using the correct GraphQL API flow.
 */
private function upload_design_file_to_shopify($product_id, $local_url, $alt_text = '') {
    $this->log("Uploading design file to Shopify Files CDN: {$local_url}");

    if (empty($local_url)) {
        $this->log("ERROR: Local URL for design file is empty.");
        return false;
    }

    // The shopify_api property should already be initialized in the constructor.
    // If not, you might need to add `$this->shopify_api = new SSPU_Shopify_API();`
    // in the constructor of SSPU_Admin_Product_Handler.

    // Get the filename from the URL to pass to the API
    $filename = basename(parse_url($local_url, PHP_URL_PATH));

    // Use the correct API method that uploads to Shopify Files
    $shopify_cdn_url = $this->shopify_api->upload_file_from_url($local_url, $filename);

    if ($shopify_cdn_url) {
        $this->log("Successfully uploaded design file. Shopify URL: " . $shopify_cdn_url);
        return $shopify_cdn_url;
    } else {
        $this->log("ERROR: Failed to upload design file using upload_file_from_url(). URL: " . $local_url);
        // Add specific error from the API call if available
        if (property_exists($this->shopify_api, 'last_error') && !empty($this->shopify_api->last_error)) {
             $this->log("Shopify API Error: " . $this->shopify_api->last_error);
        }
        return false;
    }
}
    
    /**
     * Debug Shopify API response
     */
    private function debug_shopify_response($response, $context) {
        $this->log("=== SHOPIFY API DEBUG: $context ===");
        
        if (isset($response['errors'])) {
            $this->log("ERRORS: " . json_encode($response['errors']));
        }
        
        if (isset($response['error'])) {
            $this->log("ERROR: " . $response['error']);
        }
        
        if (isset($response['message'])) {
            $this->log("MESSAGE: " . $response['message']);
        }
        
        // Log HTTP response code if available
        if (isset($response['response']['code'])) {
            $this->log("HTTP CODE: " . $response['response']['code']);
        }
        
        $this->log("FULL RESPONSE: " . substr(json_encode($response), 0, 1000));
        $this->log("=== END DEBUG ===");
    }
    
    /**
     * Send success response
     */
    private function send_success_response($product) {
        $response_data = [
            'product_id' => $product['id'],
            'product_url' => 'https://' . $this->shopify_api->get_store_name() . '.myshopify.com/products/' . $product['handle'],
            'log' => $this->submission_log
        ];
        
        wp_send_json_success($response_data);
    }
    
    /**
     * Send error response
     */
    private function send_error_response($message) {
        $response_data = [
            'message' => $message,
            'log' => $this->submission_log,
            'errors' => $this->errors
        ];
        
        // Log to error log
        error_log('SSPU Product Submission Failed: ' . $message);
        error_log('Errors: ' . print_r($this->errors, true));
        
        wp_send_json_error($response_data);
    }
    
    /**
     * Handle exception
     */
    private function handle_exception($exception) {
        $this->log('EXCEPTION: ' . $exception->getMessage());
        $this->log('Stack trace: ' . $exception->getTraceAsString());
        
        error_log('SSPU Exception: ' . $exception->getMessage());
        error_log('Stack trace: ' . $exception->getTraceAsString());
        
        $this->send_error_response('An unexpected error occurred: ' . $exception->getMessage());
    }
    
    /**
     * Handle Shopify API errors
     */
    private function handle_shopify_errors($errors) {
        $this->log('Shopify API returned errors:');
        
        if (is_array($errors)) {
            foreach ($errors as $field => $messages) {
                if (is_array($messages)) {
                    foreach ($messages as $message) {
                        $error_text = ucfirst($field) . ': ' . $message;
                        $this->log('- ' . $error_text);
                        $this->errors[] = $error_text;
                    }
                } else {
                    $error_text = ucfirst($field) . ': ' . $messages;
                    $this->log('- ' . $error_text);
                    $this->errors[] = $error_text;
                }
            }
        } else {
            $this->log('- ' . strval($errors));
            $this->errors[] = strval($errors);
        }
    }
    
    /**
     * Check if error is retryable
     */
    private function is_retryable_error($errors) {
        $retryable_patterns = [
            'rate limit',
            'throttled',
            'too many requests',
            'timeout',
            'temporarily unavailable',
            '503',
            '504'
        ];
        
        $error_text = json_encode($errors);
        
        foreach ($retryable_patterns as $pattern) {
            if (stripos($error_text, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Log API response
     */
    private function log_api_response($response) {
        if (isset($response['errors'])) {
            $this->log('API Response: ERROR');
            $this->log('Errors: ' . json_encode($response['errors']));
        } elseif (isset($response['product'])) {
            $this->log('API Response: SUCCESS');
            $this->log('Product ID: ' . $response['product']['id']);
        } else {
            $this->log('API Response: UNKNOWN');
            $this->log('Response: ' . substr(json_encode($response), 0, 500));
        }
    }
    
    /**
     * Sanitize price value
     */
    private function sanitize_price($price) {
        $price = str_replace([',', ', '], '', $price);
        $price = floatval($price);
        return number_format($price, 2, '.', '');
    }
    
    /**
     * Add log entry
     */
    private function log($message) {
        $this->submission_log[] = '[' . date('H:i:s') . '] ' . $message;
        
        // Also log to WordPress debug log if enabled
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('SSPU: ' . $message);
        }
    }
    
    /**
     * Test Shopify connection
     */
    public function handle_test_connection() {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');
        
        if (!current_user_can('upload_shopify_products')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }
        
        $response = $this->shopify_api->send_request('shop.json', 'GET');
        
        if (isset($response['shop'])) {
            wp_send_json_success([
                'message' => 'Connection successful',
                'shop' => [
                    'name' => $response['shop']['name'],
                    'email' => $response['shop']['email'],
                    'domain' => $response['shop']['domain'],
                    'currency' => $response['shop']['currency'],
                    'plan_name' => $response['shop']['plan_name']
                ]
            ]);
        } else {
            wp_send_json_error([
                'message' => 'Connection failed',
                'response' => $response
            ]);
        }
    }
    
    /**
     * Get product data for live editor
     */
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
            // Get collections for this product
            $response['product']['collection_ids'] = $this->shopify_api->get_product_collections($product_id);
            
            // Cache for comparison
            set_transient('sspu_editing_product_' . get_current_user_id(), $response['product'], HOUR_IN_SECONDS);
            
            wp_send_json_success(['product' => $response['product']]);
        } else {
            wp_send_json_error(['message' => 'Product not found']);
        }
    }
    
    /**
     * Update product
     */
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
        
        // Get original product for comparison
        $original = get_transient('sspu_editing_product_' . get_current_user_id());
        
        // Prepare update data
        $update_data = [
            'title' => sanitize_text_field($product_data['title']),
            'body_html' => wp_kses_post($product_data['body_html']),
            'vendor' => sanitize_text_field($product_data['vendor'] ?? ''),
            'product_type' => sanitize_text_field($product_data['product_type'] ?? ''),
            'tags' => sanitize_text_field($product_data['tags']),
            'published' => ($product_data['published'] === 'true' || $product_data['published'] === true),
        ];
        
        // Handle URL handle
        if (isset($product_data['handle'])) {
            $update_data['handle'] = sanitize_title($product_data['handle']);
        }
        
        // Update variants
        if (!empty($product_data['variants'])) {
            $update_data['variants'] = [];
            foreach ($product_data['variants'] as $variant) {
                $update_variant = [
                    'id' => intval($variant['id']),
                    'price' => $this->sanitize_price($variant['price']),
                    'sku' => sanitize_text_field($variant['sku']),
                    'weight' => floatval($variant['weight']),
                    'weight_unit' => sanitize_text_field($variant['weight_unit'] ?? 'lb'),
                    'taxable' => isset($variant['taxable']) && ($variant['taxable'] === 'true' || $variant['taxable'] === true),
                    'inventory_management' => sanitize_text_field($variant['inventory_management'] ?? 'shopify'),
                    'inventory_policy' => sanitize_text_field($variant['inventory_policy'] ?? 'deny'),
                ];
                
                if (!empty($variant['compare_at_price'])) {
                    $update_variant['compare_at_price'] = $this->sanitize_price($variant['compare_at_price']);
                }
                
                if (!empty($variant['barcode'])) {
                    $update_variant['barcode'] = sanitize_text_field($variant['barcode']);
                }
                
                $update_data['variants'][] = $update_variant;
            }
        }
        
        // Make the update
        $response = $this->shopify_api->update_product($product_id, $update_data);
        
        if (isset($response['product'])) {
            // Update metafields
            $this->update_product_metafields($product_id, $product_data);
            
            // Update collections
            if (isset($product_data['collection_ids'])) {
                $this->update_product_collections($product_id, $product_data['collection_ids']);
            }
            
            // Track changes
            $changes = $this->calculate_changes($original, $product_data);
            
            // Log activity
            $this->analytics->log_activity(get_current_user_id(), 'product_updated', [
                'product_id' => $product_id,
                'product_title' => $response['product']['title'],
                'changes' => $changes
            ]);
            
            wp_send_json_success([
                'message' => 'Product updated successfully',
                'product' => $response['product'],
                'changes' => $changes
            ]);
        } else {
            $error = isset($response['errors']) ? json_encode($response['errors']) : 'Update failed';
            wp_send_json_error(['message' => $error]);
        }
    }
    
    /**
     * Update product metafields
     */
    private function update_product_metafields($product_id, $product_data) {
        // SEO metafields
        if (isset($product_data['seo_title'])) {
            $this->shopify_api->update_product_metafield($product_id, [
                'namespace' => 'global',
                'key' => 'title_tag',
                'value' => sanitize_text_field($product_data['seo_title']),
                'type' => 'single_line_text_field'
            ]);
        }
        
        if (isset($product_data['seo_description'])) {
            $this->shopify_api->update_product_metafield($product_id, [
                'namespace' => 'global',
                'key' => 'description_tag',
                'value' => sanitize_textarea_field($product_data['seo_description']),
                'type' => 'multi_line_text_field'
            ]);
        }
        
        // Print methods
        if (isset($product_data['print_methods']) && is_array($product_data['print_methods'])) {
            $print_methods = ['silkscreen', 'uvprint', 'embroidery', 'sublimation', 'emboss', 'laserengrave'];
            foreach ($print_methods as $method) {
                $value = in_array('custom.' . $method, $product_data['print_methods']) ? 'true' : 'false';
                
                $this->shopify_api->update_product_metafield($product_id, [
                    'namespace' => 'custom',
                    'key' => $method,
                    'value' => $value,
                    'type' => 'boolean'
                ]);
            }
        }
        
        // Custom metafields
        if (!empty($product_data['metafields']) && is_array($product_data['metafields'])) {
            foreach ($product_data['metafields'] as $metafield) {
                if (!empty($metafield['key']) && !empty($metafield['value'])) {
                    $this->shopify_api->update_product_metafield($product_id, $metafield);
                }
            }
        }
        
        // Variant metafields
        if (!empty($product_data['variants'])) {
            foreach ($product_data['variants'] as $variant_data) {
                if (!empty($variant_data['id'])) {
                    // Volume tiers
                    if (!empty($variant_data['volume_tiers'])) {
                        $this->shopify_api->update_variant_metafield($variant_data['id'], [
                            'namespace' => 'custom',
                            'key' => 'volume_tiers',
                            'value' => json_encode($variant_data['volume_tiers']),
                            'type' => 'json'
                        ]);
                    }
                    
                    // Designer data
                    if (!empty($variant_data['designer_background_url']) && !empty($variant_data['designer_mask_url'])) {
                        $designer_data = [
                            'background_image' => $variant_data['designer_background_url'],
                            'mask_image' => $variant_data['designer_mask_url']
                        ];
                        
                        $this->shopify_api->update_variant_metafield($variant_data['id'], [
                            'namespace' => 'custom',
                            'key' => 'designer_data',
                            'value' => json_encode($designer_data),
                            'type' => 'json'
                        ]);
                    }
                }
            }
        }
    }
    
    /**
     * Update product collections
     */
    private function update_product_collections($product_id, $new_collection_ids) {
        $current_collections = $this->shopify_api->get_product_collections($product_id);
        $new_collections = array_map('intval', $new_collection_ids);
        
        // Remove from collections
        $to_remove = array_diff($current_collections, $new_collections);
        foreach ($to_remove as $collection_id) {
            $this->shopify_api->remove_from_collection($product_id, $collection_id);
        }
        
        // Add to collections
        $to_add = array_diff($new_collections, $current_collections);
        if (!empty($to_add)) {
            $this->shopify_api->add_to_collections($product_id, array_values($to_add));
        }
    }
    
    /**
     * Calculate changes between original and new data
     */
    private function calculate_changes($original, $new_data) {
        $changes = [];
        
        if (!$original || !is_array($original)) {
            return ['all_fields'];
        }
        
        // Check basic fields
        $fields_to_check = ['title', 'body_html', 'vendor', 'product_type', 'tags'];
        foreach ($fields_to_check as $field) {
            if (isset($new_data[$field]) && 
                (!isset($original[$field]) || $original[$field] != $new_data[$field])) {
                $changes[] = $field;
            }
        }
        
        // Check variants
        if (isset($new_data['variants']) && isset($original['variants'])) {
            // Create a map of original variants by ID
            $original_variants = [];
            foreach ($original['variants'] as $variant) {
                if (isset($variant['id'])) {
                    $original_variants[$variant['id']] = $variant;
                }
            }
            
            // Compare with new variants
            foreach ($new_data['variants'] as $new_variant) {
                $variant_id = $new_variant['id'];
                if (isset($variant_id) && isset($original_variants[$variant_id])) {
                    $original_variant = $original_variants[$variant_id];
                    
                    if (isset($original_variant['price']) && isset($new_variant['price']) &&
                        $original_variant['price'] != $new_variant['price']) {
                        $changes[] = 'variant_price_' . $variant_id;
                    }
                    
                    if (isset($original_variant['sku']) && isset($new_variant['sku']) &&
                        $original_variant['sku'] != $new_variant['sku']) {
                        $changes[] = 'variant_sku_' . $variant_id;
                    }
                }
            }
        }
        
        return array_unique($changes);
    }
    
    /**
     * Handle product search
     */
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
            $data = [
                'products' => $response['products']
            ];
            
            // Include pagination info
            if (isset($response['next_page_info'])) {
                $data['next_page_info'] = $response['next_page_info'];
            }
            if (isset($response['prev_page_info'])) {
                $data['prev_page_info'] = $response['prev_page_info'];
            }
            
            // Log search
            $this->analytics->log_activity(get_current_user_id(), 'product_search', [
                'query' => $params['query'],
                'results_count' => count($response['products'])
            ]);
            
            wp_send_json_success($data);
        } else {
            wp_send_json_error(['message' => 'Search failed']);
        }
    }
    
    /**
     * Additional handler methods...
     */
    
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
            $this->analytics->log_activity(get_current_user_id(), 'product_duplicated', [
                'original_id' => $product_id,
                'new_id' => $response['product']['id'],
                'new_title' => $new_title
            ]);
            
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
        
        $clean_metafield = [
            'namespace' => sanitize_text_field($metafield['namespace']),
            'key' => sanitize_text_field($metafield['key']),
            'value' => sanitize_textarea_field($metafield['value']),
            'type' => sanitize_text_field($metafield['type'])
        ];
        
        if (!empty($metafield['id'])) {
            $clean_metafield['id'] = intval($metafield['id']);
        }
        
        $response = $this->shopify_api->update_product_metafield($product_id, $clean_metafield);
        
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
        
        $clean_metafield = [
            'namespace' => sanitize_text_field($metafield['namespace']),
            'key' => sanitize_text_field($metafield['key']),
            'value' => sanitize_textarea_field($metafield['value']),
            'type' => sanitize_text_field($metafield['type'])
        ];
        
        if (!empty($metafield['id'])) {
            $clean_metafield['id'] = intval($metafield['id']);
        }
        
        $response = $this->shopify_api->update_variant_metafield($variant_id, $clean_metafield);
        
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
        $collection_ids = array_map('intval', $_POST['collection_ids']);
        
        $this->update_product_collections($product_id, $collection_ids);
        
        wp_send_json_success(['message' => 'Collections updated']);
    }
}