<?php
/**
 * SSPU Admin Product Handler v2 - Fixed Class Declaration and Enhanced with Cloudinary
 */

if (!defined('WPINC')) {
    die;
}

// Only declare the class if it doesn't already exist
if (!class_exists('SSPU_Admin_Product_Handler')) {

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
         * @var SSPU_Cloudinary_API
         */
        private $cloudinary_api;

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
            if (class_exists('SSPU_Analytics')) {
                $this->analytics = new SSPU_Analytics();
            }
            if (class_exists('SSPU_OpenAI')) {
                $this->openai = new SSPU_OpenAI();
            }
            if (class_exists('SSPU_Cloudinary_API')) {
                $this->cloudinary_api = new SSPU_Cloudinary_API();
            }
        }

        /**
         * Initialize handlers - Only product-related ones
         */
        // In SSPU_Admin_Product_Handler, remove or comment out this entire method:
        /*
        public function init_handlers() {
            // Remove all these add_action calls - they're handled by SSPU_Admin_Ajax
        }
        */

        /**
         * Handle product submission with comprehensive error handling and Cloudinary integration
         */
        public function handle_product_submission() {
            // Initialize submission
            $this->submission_log = [];
            $this->errors         = [];
            $start_time           = microtime(true);

            try {
                $this->log('=== PRODUCT SUBMISSION STARTED ===');
                $this->log('Timestamp: ' . current_time('mysql'));
                $user_id = get_current_user_id();
                $this->log('User ID: ' . $user_id);

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

                // Step 7: Log success and send notifications
                $duration = round(microtime(true) - $start_time, 2);
                $this->log("Product creation completed in {$duration} seconds");

                // **FIX**: Log to the correct table for reporting
                $this->log_successful_upload($user_id, $product, $duration);

                // **FIX**: Send Slack notification
                $this->send_slack_notification($user_id, $product);

                // Clear any saved drafts
                delete_user_meta($user_id, 'sspu_product_draft');

                $this->send_success_response($product);

            } catch (Exception $e) {
                $this->handle_exception($e);
            }
        }

        /**
         * **FIX ADDED**: Logs a successful upload to the dedicated product log table.
         */
        private function log_successful_upload($user_id, $product, $duration) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'sspu_product_log';

            $wpdb->insert($table_name, [
                'upload_timestamp'   => current_time('mysql'),
                'wp_user_id'         => $user_id,
                'shopify_product_id' => $product['id'],
                'product_title'      => $product['title'],
                'status'             => 'success',
                'upload_duration'    => $duration,
                'error_data'         => null,
                // Storing handle and URL for easier access in reporting
                'shopify_data'       => json_encode([
                    'handle' => $product['handle'],
                    'url'    => 'https://' . $this->shopify_api->get_store_name() . '.myshopify.com/products/' . $product['handle']
                ])
            ]);
            $this->log('Successfully logged product creation to sspu_product_log table.');
        }

        /**
         * **FIX ADDED**: Sends a notification to Slack when a product is uploaded.
         */
        private function send_slack_notification($user_id, $product) {
            $webhook_url = get_option('sspu_slack_webhook_url');

            if (empty($webhook_url)) {
                $this->log('Slack webhook URL is not configured. Skipping notification.');
                return;
            }

            $user        = get_user_by('id', $user_id);
            $product_url = 'https://' . $this->shopify_api->get_store_name() . '.myshopify.com/products/' . $product['handle'];
            $admin_url   = get_admin_url() . 'admin.php?page=sspu-live-editor'; // Link back to editor

            $message = sprintf(
                "%s just uploaded a new product: *<%s|%s>*",
                $user->display_name,
                $product_url,
                $product['title']
            );

            $payload = [
                'text'   => $message,
                'blocks' => [
                    [
                        'type' => 'section',
                        'text' => [
                            'type' => 'mrkdwn',
                            'text' => $message
                        ]
                    ],
                    [
                        'type'     => 'context',
                        'elements' => [ // <-- FIX: Was '_elements'
                            [
                                'type' => 'mrkdwn',
                                'text' => "Product Type: *{$product['product_type']}* | Vendor: *{$product['vendor']}*"
                            ],
                            [
                                'type' => 'mrkdwn',
                                'text' => "Variants: *" . count($product['variants']) . "*"
                            ]
                        ]
                    ],
                    [
                        'type'     => 'actions',
                        'elements' => [
                            [
                                'type'  => 'button',
                                'text'  => [
                                    'type'  => 'plain_text',
                                    'text'  => 'View on Shopify',
                                    'emoji' => true
                                ],
                                'url'   => $product_url,
                                'style' => 'primary'
                            ],
                            [
                                'type' => 'button',
                                'text' => [
                                    'type'  => 'plain_text',
                                    'text'  => 'Edit in Plugin',
                                    'emoji' => true
                                ],
                                'url'  => $admin_url
                            ]
                        ]
                    ]
                ]
            ];

            wp_remote_post($webhook_url, [
                'body'    => json_encode($payload),
                'headers' => ['Content-Type' => 'application/json'],
                'timeout' => 15,
            ]);

            $this->log('Slack notification sent.');
        }


        /**
         * Process variant metafields with Cloudinary integration for design files
         */
        private function process_variant_metafields($variants, $variant_options) {
            $this->log('Processing variant metafields with Cloudinary integration...');

            foreach ($variants as $index => $variant) {
                if (!isset($variant_options[$index])) continue;

                $variant_data = $variant_options[$index];

                // Handle designer data with Cloudinary upload
                if (!empty($variant_data['designer_background_url']) && !empty($variant_data['designer_mask_url'])) {
                    $designer_data = $this->process_design_files_to_cloudinary(
                        $variant_data['designer_background_url'],
                        $variant_data['designer_mask_url'],
                        $variant['id']
                    );

                    if ($designer_data) {
                        $this->shopify_api->send_request(
                            "variants/{$variant['id']}/metafields.json",
                            'POST',
                            [
                                'metafield' => [
                                    'namespace' => 'custom',
                                    'key'       => 'designer_data',
                                    'value'     => json_encode($designer_data),
                                    'type'      => 'json'
                                ]
                            ]
                        );
                        $this->log("Designer data with Cloudinary URLs saved for variant {$variant['id']}");
                    } else {
                        $this->log("WARNING: Failed to upload design files to Cloudinary for variant {$variant['id']}");
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
                                'key'       => 'volume_tiers',
                                'value'     => json_encode($variant_data['tiers']),
                                'type'      => 'json'
                            ]
                        ]
                    );
                }
            }
        }

        /**
         * Process design files to Cloudinary and return updated URLs
         */
        private function process_design_files_to_cloudinary($background_url, $mask_url, $variant_id) {
            $this->log("Processing design files to Cloudinary for variant {$variant_id}");

            if (!$this->cloudinary_api || !$this->cloudinary_api->is_configured()) {
                $this->log("WARNING: Cloudinary is not configured, storing original URLs");
                return [
                    'background_image' => $background_url,
                    'mask_image'       => $mask_url
                ];
            }

            $cloudinary_urls = [
                'background_image' => $background_url,
                'mask_image'       => $mask_url
            ];

            // Process background image
            if (strpos($background_url, 'http') === 0) {
                // It's a URL, download and upload to Cloudinary
                $background_path = $this->download_temp_file($background_url);
                if ($background_path) {
                    $background_result = $this->cloudinary_api->upload_image(
                        $background_path,
                        "variant_{$variant_id}_background_" . time(),
                        'sspu_design_files'
                    );

                    if (!is_wp_error($background_result) && isset($background_result['secure_url'])) {
                        $cloudinary_urls['background_image'] = $background_result['secure_url'];
                        $this->log("Background image uploaded to Cloudinary: " . $background_result['secure_url']);
                    }

                    @unlink($background_path);
                }
            } elseif (is_numeric($background_url)) {
                // It's a WordPress attachment ID
                $background_path = get_attached_file($background_url);
                if ($background_path && file_exists($background_path)) {
                    $background_result = $this->cloudinary_api->upload_image(
                        $background_path,
                        "variant_{$variant_id}_background_" . time(),
                        'sspu_design_files'
                    );

                    if (!is_wp_error($background_result) && isset($background_result['secure_url'])) {
                        $cloudinary_urls['background_image'] = $background_result['secure_url'];
                        $this->log("Background image uploaded to Cloudinary: " . $background_result['secure_url']);
                    }
                }
            }

            // Process mask image
            if (strpos($mask_url, 'http') === 0) {
                // It's a URL, download and upload to Cloudinary
                $mask_path = $this->download_temp_file($mask_url);
                if ($mask_path) {
                    $mask_result = $this->cloudinary_api->upload_image(
                        $mask_path,
                        "variant_{$variant_id}_mask_" . time(),
                        'sspu_design_files'
                    );

                    if (!is_wp_error($mask_result) && isset($mask_result['secure_url'])) {
                        $cloudinary_urls['mask_image'] = $mask_result['secure_url'];
                        $this->log("Mask image uploaded to Cloudinary: " . $mask_result['secure_url']);
                    }

                    @unlink($mask_path);
                }
            } elseif (is_numeric($mask_url)) {
                // It's a WordPress attachment ID
                $mask_path = get_attached_file($mask_url);
                if ($mask_path && file_exists($mask_path)) {
                    $mask_result = $this->cloudinary_api->upload_image(
                        $mask_path,
                        "variant_{$variant_id}_mask_" . time(),
                        'sspu_design_files'
                    );

                    if (!is_wp_error($mask_result) && isset($mask_result['secure_url'])) {
                        $cloudinary_urls['mask_image'] = $mask_result['secure_url'];
                        $this->log("Mask image uploaded to Cloudinary: " . $mask_result['secure_url']);
                    }
                }
            }

            return $cloudinary_urls;
        }

        /**
         * Download a file from URL to temporary location
         */
        private function download_temp_file($url) {
            $temp_file = download_url($url, 300);

            if (is_wp_error($temp_file)) {
                $this->log("ERROR: Failed to download file from URL: " . $url . " - " . $temp_file->get_error_message());
                return false;
            }

            return $temp_file;
        }

        /**
         * Verify security (nonce)
         */
        private function verify_security() {
            $this->log('Verifying security token...');
            $this->log('POST nonce value: ' . ($_POST['sspu_nonce'] ?? 'NOT SET'));

            // Test both possible nonce actions
            $ajax_nonce_valid   = wp_verify_nonce($_POST['sspu_nonce'] ?? '', 'sspu_ajax_nonce');
            $submit_nonce_valid = wp_verify_nonce($_POST['sspu_nonce'] ?? '', 'sspu_submit_product');

            $this->log('Ajax nonce valid: ' . ($ajax_nonce_valid ? 'YES' : 'NO'));
            $this->log('Submit nonce valid: ' . ($submit_nonce_valid ? 'YES' : 'NO'));

            if ($ajax_nonce_valid) {
                $this->log('Security token verified with sspu_ajax_nonce');
                return true;
            } elseif ($submit_nonce_valid) {
                $this->log('Security token verified with sspu_submit_product');
                return true;
            } else {
                $this->log('ERROR: Invalid security token for both actions');
                $this->errors[] = 'Invalid security token';
                return false;
            }
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

            $store_name   = get_option('sspu_shopify_store_name');
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
                'title'        => sanitize_text_field($post_data['product_name']),
                'body_html'    => wp_kses_post($post_data['product_description'] ?? ''),
                'vendor'       => sanitize_text_field($post_data['product_vendor'] ?? ''),
                'product_type' => sanitize_text_field($post_data['product_type'] ?? ''),
                'tags'         => sanitize_text_field($post_data['product_tags'] ?? ''),
                'published'    => true,
                'options'      => [],
                'variants'     => [],
                'images'       => [],
                'metafields'   => []
            ];

            $this->log('Basic product data prepared');

            // Process SEO metafields
            if (!empty($post_data['seo_title'])) {
                $product_data['metafields'][] = [
                    'namespace' => 'global',
                    'key'       => 'title_tag',
                    'value'     => sanitize_text_field($post_data['seo_title']),
                    'type'      => 'single_line_text_field'
                ];
            }

            if (!empty($post_data['meta_description'])) {
                $product_data['metafields'][] = [
                    'namespace' => 'global',
                    'key'       => 'description_tag',
                    'value'     => sanitize_textarea_field($post_data['meta_description']),
                    'type'      => 'multi_line_text_field'
                ];
            }

            // Handle URL handle
            if (!empty($post_data['url_handle'])) {
                $product_data['handle'] = sanitize_title($post_data['url_handle']);
            }

            // Process variants
            $option_names  = [];
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
                    'option1'              => sanitize_text_field($variant_data['value']),
                    'price'                => $this->sanitize_price($variant_data['price'] ?? 0),
                    'sku'                  => sanitize_text_field($variant_data['sku'] ?? ''),
                    'weight'               => floatval($variant_data['weight'] ?? 0),
                    'weight_unit'          => 'lb',
                    'inventory_management' => 'shopify',
                    'inventory_quantity'   => 100,
                    'inventory_policy'     => 'deny',
                    'fulfillment_service'  => 'manual',
                    'taxable'              => true
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
                        'src'      => $main_image_url,
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
                            'src'      => $image_url,
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
            $product  = null;

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

            // Handle variant metafields with Cloudinary integration
            if (!empty($product['variants']) && !empty($post_data['variant_options'])) {
                $this->process_variant_metafields($product['variants'], $post_data['variant_options']);
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
                    $variant   = $product['variants'][$index];
                    $image_url = wp_get_attachment_url($variant_data['image_id']);

                    if ($image_url) {
                        $this->log("Uploading image for variant: " . $variant_data['value']);

                        $image_response = $this->shopify_api->send_request(
                            "products/{$product['id']}/images.json",
                            'POST',
                            [
                                'image' => [
                                    'src'         => $image_url,
                                    'variant_ids' => [$variant['id']] // <-- FIX: Was '_ '
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
                            'product_id'    => $product_id,
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
                            'key'       => $method,
                            'value'     => $value,
                            'type'      => 'boolean'
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
                            'key'       => 'min_quantity',
                            'value'     => intval($post_data['product_min']),
                            'type'      => 'number_integer'
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
                            'key'       => 'max_quantity',
                            'value'     => intval($post_data['product_max']),
                            'type'      => 'number_integer'
                        ]
                    ]
                );
            }
        }

        /**
         * Send success response
         */
        private function send_success_response($product) {
            $response_data = [
                'product_id'  => $product['id'],
                'product_url' => 'https://' . $this->shopify_api->get_store_name() . '.myshopify.com/products/' . $product['handle'],
                'log'         => $this->submission_log
            ];

            wp_send_json_success($response_data);
        }

        /**
         * Send error response
         */
        private function send_error_response($message) {
            $response_data = [
                'message' => $message,
                'log'     => $this->submission_log,
                'errors'  => $this->errors
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
                            $error_text     = ucfirst($field) . ': ' . $message;
                            $this->log('- ' . $error_text);
                            $this->errors[] = $error_text;
                        }
                    } else {
                        $error_text     = ucfirst($field) . ': ' . $messages;
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

        // === PRODUCT GENERATION HANDLERS ===

        /**
         * Handle description generation
         */
        public function handle_description_generation() {
            try {
                check_ajax_referer('sspu_ajax_nonce', 'nonce');
                if (!current_user_can('upload_shopify_products')) {
                    wp_send_json_error(['message' => 'Permission denied']);
                    return;
                }

                $input_text = sanitize_textarea_field($_POST['input_text']);
                $image_ids  = isset($_POST['image_ids']) ? array_map('absint', (array) $_POST['image_ids']) : [];
                $image_urls = isset($_POST['image_urls']) ? array_map('esc_url_raw', (array) $_POST['image_urls']) : [];
                $type       = sanitize_text_field($_POST['type']);

                if (empty($input_text) && empty($image_ids) && empty($image_urls)) {
                    wp_send_json_error(['message' => 'Please provide text or images for AI analysis']);
                    return;
                }

                if (!$this->openai) {
                    wp_send_json_error(['message' => 'OpenAI not initialized']);
                    return;
                }

                $result = false;

                switch ($type) {
                    case 'description':
                        $result = $this->openai->generate_product_description($input_text, $image_ids, $image_urls);
                        if ($result) {
                            wp_send_json_success(['description' => $result]);
                        }
                        break;
                    case 'tags':
                        $result = $this->openai->generate_tags($input_text);
                        if ($result) {
                            wp_send_json_success(['tags' => $result]);
                        }
                        break;
                    case 'price':
                        $result = $this->openai->suggest_price($input_text, '', '');
                        if ($result) {
                            wp_send_json_success(['price' => $result]);
                        }
                        break;
                    default:
                        wp_send_json_error(['message' => 'Invalid generation type']);
                        return;
                }

                if ($result === false) {
                    $error = $this->openai->get_last_error() ?: 'Failed to generate content';
                    wp_send_json_error(['message' => $error]);
                }
            } catch (Exception $e) {
                wp_send_json_error(['message' => 'An error occurred: ' . $e->getMessage()]);
            }
        }

        /**
         * Handle format product name
         */
        public function handle_format_product_name() {
            try {
                check_ajax_referer('sspu_ajax_nonce', 'nonce');
                if (!current_user_can('upload_shopify_products')) {
                    wp_send_json_error(['message' => 'Permission denied']);
                    return;
                }

                $name = sanitize_text_field($_POST['product_name']);
                if (empty($name)) {
                    wp_send_json_error(['message' => 'Product name is required']);
                    return;
                }

                if (!$this->openai) {
                    wp_send_json_error(['message' => 'OpenAI not initialized']);
                    return;
                }

                $formatted = $this->openai->format_product_name($name);

                if ($formatted) {
                    wp_send_json_success(['formatted_name' => $formatted]);
                } else {
                    $error = $this->openai->get_last_error() ?: 'Failed to format product name';
                    wp_send_json_error(['message' => $error]);
                }
            } catch (Exception $e) {
                wp_send_json_error(['message' => 'An error occurred: ' . $e->getMessage()]);
            }
        }

        /**
         * Handle generate variants
         */
        public function handle_generate_variants() {
            try {
                check_ajax_referer('sspu_ajax_nonce', 'nonce');
                if (!current_user_can('upload_shopify_products')) {
                    wp_send_json_error(['message' => 'Permission denied']);
                    return;
                }

                $option_name   = sanitize_text_field($_POST['option_name']);
                $option_values = sanitize_textarea_field($_POST['option_values']);
                $base_price    = floatval($_POST['base_price']);

                if (empty($option_name) || empty($option_values)) {
                    wp_send_json_error(['message' => 'Option name and values are required']);
                    return;
                }

                $values = preg_split('/[,\n\r]+/', $option_values);
                $values = array_map('trim', $values);
                $values = array_filter($values);

                if (empty($values)) {
                    wp_send_json_error(['message' => 'No valid option values provided']);
                    return;
                }

                $variants = [];
                foreach ($values as $value) {
                    $variants[] = [
                        'name'  => $option_name,
                        'value' => $value,
                        'price' => $base_price,
                        'sku'   => ''
                    ];
                }

                wp_send_json_success(['variants' => $variants]);
            } catch (Exception $e) {
                wp_send_json_error(['message' => 'An error occurred: ' . $e->getMessage()]);
            }
        }

        /**
         * Handle apply pricing to all
         */
        public function handle_apply_pricing_to_all() {
            check_ajax_referer('sspu_ajax_nonce', 'nonce');
            if (!current_user_can('upload_shopify_products')) {
                wp_send_json_error(['message' => 'Permission denied']);
                return;
            }

            $action_type     = sanitize_text_field($_POST['action_type']);
            $source_data_raw = $_POST['source_data'] ?? '';

            if (is_array($source_data_raw)) {
                $source_data = $source_data_raw;
            } else {
                $source_data = json_decode(stripslashes($source_data_raw), true);
            }

            wp_send_json_success(['action_type' => $action_type, 'data' => $source_data]);
        }

        /**
         * Handle SKU generation
         */
        public function handle_sku_generation() {
            try {
                check_ajax_referer('sspu_ajax_nonce', 'nonce');
                if (!current_user_can('upload_shopify_products')) {
                    wp_send_json_error(['message' => 'Permission denied']);
                    return;
                }

                $product_name  = sanitize_text_field($_POST['product_name']);
                $variant_name  = sanitize_text_field($_POST['variant_name']);
                $variant_value = sanitize_text_field($_POST['variant_value']);

                if (!class_exists('SSPU_SKU_Generator')) {
                    wp_send_json_error(['message' => 'SKU Generator class not found.']);
                    return;
                }

                $sku_generator = SSPU_SKU_Generator::getInstance();
                $sku           = $sku_generator->generate_sku($product_name, $variant_name, $variant_value);

                if (!$sku) {
                    $error = $sku_generator->get_last_error() ?: 'Failed to generate SKU';
                    wp_send_json_error(['message' => $error]);
                    return;
                }

                wp_send_json_success(['sku' => $sku]);
            } catch (Exception $e) {
                wp_send_json_error(['message' => 'An error occurred: ' . $e->getMessage()]);
            }
        }

        /**
         * Handle volume tier calculation
         */
        public function handle_volume_tier_calculation() {
            try {
                check_ajax_referer('sspu_ajax_nonce', 'nonce');
                if (!current_user_can('upload_shopify_products')) {
                    wp_send_json_error(['message' => 'Permission denied']);
                    return;
                }

                $base_price         = floatval($_POST['base_price']);
                $multipliers_string = get_option('sspu_volume_tier_multipliers', '0.95,0.90,0.85,0.80,0.75');
                $multipliers        = array_map('floatval', explode(',', $multipliers_string));
                $default_quantities = [25, 50, 100, 200, 500];

                $tiers = [];
                foreach ($multipliers as $index => $multiplier) {
                    if (isset($default_quantities[$index])) {
                        $tiers[] = [
                            'min_quantity' => $default_quantities[$index],
                            'price'        => round($base_price * $multiplier, 2)
                        ];
                    }
                }

                wp_send_json_success(['tiers' => $tiers]);
            } catch (Exception $e) {
                wp_send_json_error(['message' => 'An error occurred: ' . $e->getMessage()]);
            }
        }

        /**
         * Handle price suggestion
         */
        public function handle_price_suggestion() {
            try {
                check_ajax_referer('sspu_ajax_nonce', 'nonce');
                if (!current_user_can('upload_shopify_products')) {
                    wp_send_json_error(['message' => 'Permission denied']);
                    return;
                }

                $product_name = sanitize_text_field($_POST['product_name']);
                $description  = sanitize_textarea_field($_POST['description']);
                $variant_info = sanitize_text_field($_POST['variant_info']);

                if (!$this->openai) {
                    wp_send_json_error(['message' => 'OpenAI not initialized']);
                    return;
                }

                $suggested_price = $this->openai->suggest_price($product_name, $description, $variant_info);

                if ($suggested_price) {
                    wp_send_json_success(['price' => $suggested_price]);
                } else {
                    $error = $this->openai->get_last_error() ?: 'Failed to suggest price';
                    wp_send_json_error(['message' => $error]);
                }
            } catch (Exception $e) {
                wp_send_json_error(['message' => 'An error occurred: ' . $e->getMessage()]);
            }
        }

        /**
         * Handle SEO generation
         */
        public function handle_seo_generation() {
            try {
                check_ajax_referer('sspu_ajax_nonce', 'nonce');
                if (!current_user_can('upload_shopify_products')) {
                    wp_send_json_error(['message' => 'Permission denied']);
                    return;
                }

                $product_name = sanitize_text_field($_POST['product_name']);
                $description  = sanitize_textarea_field($_POST['description']);
                $type         = sanitize_text_field($_POST['type']);

                if (!$this->openai) {
                    wp_send_json_error(['message' => 'OpenAI not initialized']);
                    return;
                }

                if ($type === 'title') {
                    $result = $this->openai->generate_seo_title($product_name, $description);
                } else {
                    $result = $this->openai->generate_meta_description($product_name, $description);
                }

                if ($result) {
                    wp_send_json_success(['content' => $result]);
                } else {
                    $error = $this->openai->get_last_error() ?: 'Failed to generate SEO content';
                    wp_send_json_error(['message' => $error]);
                }
            } catch (Exception $e) {
                wp_send_json_error(['message' => 'An error occurred: ' . $e->getMessage()]);
            }
        }

        /**
         * Handle AI pricing suggestion for all variants
         */
        public function handle_ai_suggest_all_pricing() {
            try {
                check_ajax_referer('sspu_ajax_nonce', 'nonce');
                if (!current_user_can('upload_shopify_products')) {
                    wp_send_json_error(['message' => 'Permission denied']);
                    return;
                }

                $product_name = sanitize_text_field($_POST['product_name']);
                $main_image_id = absint($_POST['main_image_id']);
                $min_quantity  = absint($_POST['min_quantity']);

                if (empty($min_quantity) || $min_quantity < 1) {
                    $min_quantity = 25;
                }

                if (empty($product_name) || empty($main_image_id)) {
                    wp_send_json_error(['message' => 'Product name and main image are required']);
                    return;
                }

                if (!$this->openai) {
                    wp_send_json_error(['message' => 'OpenAI not initialized']);
                    return;
                }

                $base_price = $this->openai->suggest_base_price_with_image($product_name, $main_image_id, $min_quantity);

                if ($base_price === false) {
                    $error = $this->openai->get_last_error() ?: 'Failed to generate base price';
                    wp_send_json_error(['message' => $error]);
                    return;
                }

                $tiers = $this->openai->suggest_volume_tiers($product_name, $base_price, $min_quantity);

                if ($tiers === false) {
                    wp_send_json_success([
                        'base_price' => $base_price,
                        'tiers'      => [],
                        'rationale'  => 'Base price generated successfully, but volume tiers generation failed.'
                    ]);
                    return;
                }

                wp_send_json_success([
                    'base_price' => $base_price,
                    'tiers'      => $tiers,
                    'rationale'  => "AI-generated pricing based on product analysis. Base price for {$min_quantity} units, with volume discounts for larger orders."
                ]);
            } catch (Exception $e) {
                wp_send_json_error(['message' => 'An error occurred: ' . $e->getMessage()]);
            }
        }

        /**
         * Handle weight estimation
         */
        public function handle_estimate_weight() {
            try {
                check_ajax_referer('sspu_ajax_nonce', 'nonce');
                if (!current_user_can('upload_shopify_products')) {
                    wp_send_json_error(['message' => 'Permission denied']);
                    return;
                }

                $product_name  = sanitize_text_field($_POST['product_name']);
                $main_image_id = absint($_POST['main_image_id']);

                if (empty($product_name)) {
                    wp_send_json_error(['message' => 'Product name is required']);
                    return;
                }

                if (empty($main_image_id)) {
                    wp_send_json_error(['message' => 'Please select a main product image first']);
                    return;
                }

                if (!$this->openai) {
                    wp_send_json_error(['message' => 'OpenAI not initialized']);
                    return;
                }

                $estimated_weight = $this->openai->estimate_product_weight($product_name, $main_image_id);

                if ($estimated_weight !== false) {
                    wp_send_json_success(['weight' => $estimated_weight]);
                } else {
                    $error = $this->openai->get_last_error() ?: 'Failed to estimate weight';
                    wp_send_json_error(['message' => $error]);
                }
            } catch (Exception $e) {
                wp_send_json_error(['message' => 'An error occurred: ' . $e->getMessage()]);
            }
        }

        /**
         * Handle smart rotate image
         */
        public function handle_smart_rotate() {
            if (!class_exists('SSPU_AI_Image_Editor')) {
                wp_send_json_error(['message' => 'AI Image Editor class not found.']);
                return;
            }

            $ai_editor = SSPU_AI_Image_Editor::get_instance();
            $ai_editor->handle_smart_rotate();
        }

        /**
         * Handle mimic all variants
         */
        public function handle_mimic_all_variants() {
            if (!class_exists('SSPU_AI_Image_Editor')) {
                wp_send_json_error(['message' => 'AI Image Editor class not found.']);
                return;
            }

            $ai_editor = SSPU_AI_Image_Editor::get_instance();
            $ai_editor->handle_mimic_all_variants();
        }

        // === LIVE EDITOR HANDLERS ===

        /**
         * Handle product search for live editor
         */
        public function handle_search_products() {
            check_ajax_referer('sspu_ajax_nonce', 'nonce');
            if (!current_user_can('upload_shopify_products')) {
                wp_send_json_error(['message' => 'Permission denied']);
                return;
            }

            $params = [
                'query'         => sanitize_text_field($_POST['query'] ?? ''),
                'status'        => sanitize_text_field($_POST['status'] ?? ''),
                'vendor'        => sanitize_text_field($_POST['vendor'] ?? ''),
                'collection_id' => absint($_POST['collection_id'] ?? 0),
                'limit'         => absint($_POST['limit'] ?? 50),
                'page_info'     => sanitize_text_field($_POST['page_info'] ?? '')
            ];

            $response = $this->shopify_api->search_products($params);

            if (isset($response['products'])) {
                $data = [
                    'products' => $response['products']
                ];

                if (isset($response['next_page_info'])) {
                    $data['next_page_info'] = $response['next_page_info'];
                }
                if (isset($response['prev_page_info'])) {
                    $data['prev_page_info'] = $response['prev_page_info'];
                }

                if ($this->analytics) {
                    $this->analytics->log_activity(get_current_user_id(), 'product_search', [
                        'query'         => $params['query'],
                        'results_count' => count($response['products'])
                    ]);
                }

                wp_send_json_success($data);
            } else {
                wp_send_json_error(['message' => 'Search failed']);
            }
        }

        /**
         * Handle get product data for live editor
         */
        public function handle_get_product_data() {
            check_ajax_referer('sspu_ajax_nonce', 'nonce');
            if (!current_user_can('upload_shopify_products')) {
                wp_send_json_error(['message' => 'Permission denied']);
                return;
            }

            $product_id = absint($_POST['product_id']);
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

        /**
         * Handle update product
         */
        public function handle_update_product() {
            check_ajax_referer('sspu_ajax_nonce', 'nonce');
            if (!current_user_can('upload_shopify_products')) {
                wp_send_json_error(['message' => 'Permission denied']);
                return;
            }

            $product_id   = absint($_POST['product_id']);
            $product_data = $_POST['product_data'];

            if (!$product_id || !$product_data) {
                wp_send_json_error(['message' => 'Missing required data']);
                return;
            }

            $original = get_transient('sspu_editing_product_' . get_current_user_id());

            $update_data = [
                'title'        => sanitize_text_field($product_data['title']),
                'body_html'    => wp_kses_post($product_data['body_html']),
                'vendor'       => sanitize_text_field($product_data['vendor'] ?? ''),
                'product_type' => sanitize_text_field($product_data['product_type'] ?? ''),
                'tags'         => sanitize_text_field($product_data['tags']),
                'published'    => ($product_data['published'] === 'true' || $product_data['published'] === true),
            ];

            if (isset($product_data['handle'])) {
                $update_data['handle'] = sanitize_title($product_data['handle']);
            }

            if (!empty($product_data['variants'])) {
                $update_data['variants'] = [];
                foreach ($product_data['variants'] as $variant) {
                    $update_variant = [
                        'id'                   => absint($variant['id']),
                        'price'                => $this->sanitize_price($variant['price']),
                        'sku'                  => sanitize_text_field($variant['sku']),
                        'weight'               => floatval($variant['weight']),
                        'weight_unit'          => sanitize_text_field($variant['weight_unit'] ?? 'lb'),
                        'taxable'              => isset($variant['taxable']) && ($variant['taxable'] === 'true' || $variant['taxable'] === true),
                        'inventory_management' => sanitize_text_field($variant['inventory_management'] ?? 'shopify'),
                        'inventory_policy'     => sanitize_text_field($variant['inventory_policy'] ?? 'deny'),
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

            $response = $this->shopify_api->update_product($product_id, $update_data);

            if (isset($response['product'])) {
                $this->update_product_metafields($product_id, $product_data);

                if (isset($product_data['collection_ids'])) {
                    $this->update_product_collections($product_id, $product_data['collection_ids']);
                }

                $changes = $this->calculate_changes($original, $product_data);

                if ($this->analytics) {
                    $this->analytics->log_activity(get_current_user_id(), 'product_updated', [
                        'product_id'    => $product_id,
                        'product_title' => $response['product']['title'],
                        'changes'       => $changes
                    ]);
                }

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
         * Update product metafields with Cloudinary integration
         */
        private function update_product_metafields($product_id, $product_data) {
            // SEO metafields
            if (isset($product_data['seo_title'])) {
                $this->shopify_api->update_product_metafield($product_id, [
                    'namespace' => 'global',
                    'key'       => 'title_tag',
                    'value'     => sanitize_text_field($product_data['seo_title']),
                    'type'      => 'single_line_text_field'
                ]);
            }

            if (isset($product_data['seo_description'])) {
                $this->shopify_api->update_product_metafield($product_id, [
                    'namespace' => 'global',
                    'key'       => 'description_tag',
                    'value'     => sanitize_textarea_field($product_data['seo_description']),
                    'type'      => 'multi_line_text_field'
                ]);
            }

            // Print methods
            if (isset($product_data['print_methods']) && is_array($product_data['print_methods'])) {
                $print_methods = ['silkscreen', 'uvprint', 'embroidery', 'sublimation', 'emboss', 'laserengrave'];
                foreach ($print_methods as $method) {
                    $value = in_array('custom.' . $method, $product_data['print_methods']) ? 'true' : 'false';

                    $this->shopify_api->update_product_metafield($product_id, [
                        'namespace' => 'custom',
                        'key'       => $method,
                        'value'     => $value,
                        'type'      => 'boolean'
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

            // Variant metafields with Cloudinary integration
            if (!empty($product_data['variants'])) {
                foreach ($product_data['variants'] as $variant_data) {
                    if (!empty($variant_data['id'])) {
                        // Volume tiers
                        if (!empty($variant_data['volume_tiers'])) {
                            $this->shopify_api->update_variant_metafield($variant_data['id'], [
                                'namespace' => 'custom',
                                'key'       => 'volume_tiers',
                                'value'     => json_encode($variant_data['volume_tiers']),
                                'type'      => 'json'
                            ]);
                        }

                        // Designer data with Cloudinary integration
                        if (!empty($variant_data['designer_background_url']) && !empty($variant_data['designer_mask_url'])) {
                            $designer_data = $this->process_design_files_to_cloudinary(
                                $variant_data['designer_background_url'],
                                $variant_data['designer_mask_url'],
                                $variant_data['id']
                            );

                            if ($designer_data) {
                                $this->shopify_api->update_variant_metafield($variant_data['id'], [
                                    'namespace' => 'custom',
                                    'key'       => 'designer_data',
                                    'value'     => json_encode($designer_data),
                                    'type'      => 'json'
                                ]);
                            }
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
            $new_collections     = array_map('intval', $new_collection_ids);

            $to_remove = array_diff($current_collections, $new_collections);
            foreach ($to_remove as $collection_id) {
                $this->shopify_api->remove_from_collection($product_id, $collection_id);
            }

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

            $fields_to_check = ['title', 'body_html', 'vendor', 'product_type', 'tags'];
            foreach ($fields_to_check as $field) {
                if (isset($new_data[$field]) &&
                    (!isset($original[$field]) || $original[$field] != $new_data[$field])) {
                    $changes[] = $field;
                }
            }

            if (isset($new_data['variants']) && isset($original['variants'])) {
                $original_variants = [];
                foreach ($original['variants'] as $variant) {
                    if (isset($variant['id'])) {
                        $original_variants[$variant['id']] = $variant;
                    }
                }

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

        // Stub implementations for remaining live editor handlers
        public function handle_update_inventory() {
            check_ajax_referer('sspu_ajax_nonce', 'nonce');
            if (!current_user_can('upload_shopify_products')) {
                wp_send_json_error(['message' => 'Permission denied']);
                return;
            }

            $inventory_item_id = absint($_POST['inventory_item_id']);
            $available         = intval($_POST['available']);
            $location_id       = absint($_POST['location_id']);

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

            $product_id = absint($_POST['product_id']);
            $image_id   = absint($_POST['image_id']);

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

            $product_id = absint($_POST['product_id']);
            $image_ids  = array_map('intval', $_POST['image_ids']);

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

            $product_id = absint($_POST['product_id']);
            $new_title  = sanitize_text_field($_POST['new_title']);

            $response = $this->shopify_api->duplicate_product($product_id, $new_title);

            if (isset($response['product'])) {
                if ($this->analytics) {
                    $this->analytics->log_activity(get_current_user_id(), 'product_duplicated', [
                        'original_id' => $product_id,
                        'new_id'      => $response['product']['id'],
                        'new_title'   => $new_title
                    ]);
                }

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

            $product_id = absint($_POST['product_id']);
            $metafield  = $_POST['metafield'];

            $clean_metafield = [
                'namespace' => sanitize_text_field($metafield['namespace']),
                'key'       => sanitize_text_field($metafield['key']),
                'value'     => sanitize_textarea_field($metafield['value']),
                'type'      => sanitize_text_field($metafield['type'])
            ];

            if (!empty($metafield['id'])) {
                $clean_metafield['id'] = absint($metafield['id']);
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

            $variant_id = absint($_POST['variant_id']);
            $metafield  = $_POST['metafield'];

            $clean_metafield = [
                'namespace' => sanitize_text_field($metafield['namespace']),
                'key'       => sanitize_text_field($metafield['key']),
                'value'     => sanitize_textarea_field($metafield['value']),
                'type'      => sanitize_text_field($metafield['type'])
            ];

            if (!empty($metafield['id'])) {
                $clean_metafield['id'] = absint($metafield['id']);
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

            $product_id     = absint($_POST['product_id']);
            $collection_ids = array_map('intval', $_POST['collection_ids']);

            $this->update_product_collections($product_id, $collection_ids);

            wp_send_json_success(['message' => 'Collections updated']);
        }

        public function handle_live_editor_autosave() {
            check_ajax_referer('sspu_ajax_nonce', 'nonce');
            if (!current_user_can('upload_shopify_products')) {
                wp_send_json_error(['message' => 'Permission denied']);
                return;
            }

            $product_id     = absint($_POST['product_id']);
            $draft_data_raw = $_POST['draft_data'] ?? '';

            if (is_array($draft_data_raw)) {
                $draft_data = $draft_data_raw;
            } else {
                $draft_data = json_decode(stripslashes($draft_data_raw), true);
            }

            if (!$product_id || !is_array($draft_data)) {
                wp_send_json_error(['message' => 'Missing product ID or invalid draft data.']);
                return;
            }

            $draft_key = 'sspu_live_edit_draft_' . get_current_user_id() . '_' . $product_id;
            set_transient($draft_key, $draft_data, DAY_IN_SECONDS);

            wp_send_json_success(['message' => 'Draft saved for live editor']);
        }
    }

} // End class_exists check