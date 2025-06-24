<?php
if (!defined('WPINC')) {
    die;
}

class SSPU_Admin_Ajax
{
    /**
     * Initialize AJAX handlers
     */
    public function init_handlers()
    {
        // Product-related
        add_action('wp_ajax_sspu_submit_product', [$this, 'delegate_to_product_handler']);
        add_action('wp_ajax_sspu_generate_description', [$this, 'handle_description_generation']);
        add_action('wp_ajax_sspu_format_product_name', [$this, 'handle_format_product_name']);
        add_action('wp_ajax_sspu_generate_variants', [$this, 'handle_generate_variants']);
        add_action('wp_ajax_sspu_apply_pricing_to_all', [$this, 'handle_apply_pricing_to_all']);
        add_action('wp_ajax_sspu_generate_sku', [$this, 'handle_sku_generation']);
        add_action('wp_ajax_sspu_calculate_volume_tiers', [$this, 'handle_volume_tier_calculation']);
        add_action('wp_ajax_sspu_refresh_nonce', [$this, 'handle_refresh_nonce']);
        add_action('wp_ajax_sspu_suggest_price', [$this, 'handle_price_suggestion']);
        add_action('wp_ajax_sspu_generate_seo', [$this, 'handle_seo_generation']);
        add_action('wp_ajax_sspu_ai_suggest_all_pricing', [$this, 'handle_ai_suggest_all_pricing']);
        add_action('wp_ajax_sspu_estimate_weight', [$this, 'handle_estimate_weight']);
        add_action('wp_ajax_sspu_smart_rotate_image', [$this, 'handle_smart_rotate']);
        add_action('wp_ajax_sspu_mimic_all_variants', [$this, 'handle_mimic_all_variants']);

        // Collection-related
        add_action('wp_ajax_sspu_get_collections', [$this, 'handle_get_collections']);
        add_action('wp_ajax_sspu_create_collection', [$this, 'handle_create_collection']);

        // Draft-related
        add_action('wp_ajax_sspu_save_draft', [$this, 'handle_save_draft']);
        add_action('wp_ajax_sspu_load_draft', [$this, 'handle_load_draft']);
        add_action('wp_ajax_sspu_auto_save_draft', [$this, 'handle_auto_save_draft']);

        // API testing
        add_action('wp_ajax_sspu_test_openai_api', [$this, 'handle_test_openai_api']);
        add_action('wp_ajax_sspu_test_gemini_api', [$this, 'handle_test_gemini_api']);
        add_action('wp_ajax_sspu_test_shopify_connection', [$this, 'handle_test_shopify_connection']); // New in second script

        // Image-related
        add_action('wp_ajax_sspu_validate_image', [$this, 'handle_validate_image']);
        add_action('wp_ajax_sspu_upload_images', [$this, 'handle_upload_images']);
        add_action('wp_ajax_sspu_scrape_alibaba_variants', [$this, 'handle_scrape_alibaba_variants']);
        add_action('wp_ajax_sspu_create_masked_image', [$this, 'handle_create_masked_image']);

        // Alibaba-related
        add_action('wp_ajax_sspu_get_current_alibaba_url', [$this, 'handle_get_current_alibaba_url']);
        add_action('wp_ajax_sspu_fetch_alibaba_product_name', [$this, 'handle_fetch_alibaba_product_name']);
        add_action('wp_ajax_sspu_detect_color', [$this, 'handle_detect_color']);
        add_action('wp_ajax_sspu_fetch_alibaba_moq', [$this, 'handle_fetch_alibaba_moq']);
        add_action('wp_ajax_sspu_fetch_alibaba_description', [$this, 'handle_fetch_alibaba_description']);

        // Template-related
        add_action('wp_ajax_sspu_get_single_template_content', [$this, 'handle_get_single_template_content']);

        // Analytics proxy handlers
        add_action('wp_ajax_sspu_get_analytics', [$this, 'handle_get_analytics_proxy']);
        add_action('wp_ajax_sspu_export_analytics', [$this, 'handle_export_analytics']);
        add_action('wp_ajax_sspu_get_user_activity', [$this, 'handle_get_user_activity_proxy']);
        add_action('wp_ajax_sspu_global_search', [$this, 'handle_global_search_proxy']);
        add_action('wp_ajax_sspu_get_search_filters', [$this, 'handle_get_search_filters_proxy']);

        // Alibaba queue handlers
        add_action('wp_ajax_sspu_request_alibaba_url', [$this, 'handle_request_alibaba_url']);
        add_action('wp_ajax_sspu_complete_alibaba_url', [$this, 'handle_complete_alibaba_url']);
        add_action('wp_ajax_sspu_release_alibaba_url', [$this, 'handle_release_alibaba_url']);

        // Image retriever handlers
        add_action('wp_ajax_sspu_retrieve_alibaba_images', [$this, 'handle_retrieve_alibaba_images']);
        add_action('wp_ajax_sspu_download_external_image', [$this, 'handle_download_external_image']);

        // AI handlers
        add_action('wp_ajax_sspu_ai_edit_image', [$this, 'handle_ai_edit_image']);
        add_action('wp_ajax_sspu_get_chat_history', [$this, 'handle_get_chat_history']);
        add_action('wp_ajax_sspu_save_edited_image', [$this, 'handle_save_edited_image']);

        // Template handlers
        add_action('wp_ajax_sspu_get_image_templates', [$this, 'handle_get_image_templates']);
        add_action('wp_ajax_sspu_save_image_template', [$this, 'handle_save_image_template']);
        add_action('wp_ajax_sspu_delete_image_template', [$this, 'handle_delete_image_template']);

        // Mimic handlers
        add_action('wp_ajax_sspu_get_mimic_images', [$this, 'handle_get_mimic_images']);
        add_action('wp_ajax_sspu_mimic_image', [$this, 'handle_mimic_image']);

        // Live editor handlers
        add_action('wp_ajax_sspu_search_live_products', [$this, 'handle_search_live_products']);
        add_action('wp_ajax_sspu_get_live_product_data', [$this, 'handle_get_live_product_data']);
        add_action('wp_ajax_sspu_update_live_product', [$this, 'handle_update_live_product']);
        add_action('wp_ajax_sspu_update_product_images_order', [$this, 'handle_update_product_images_order']);
        add_action('wp_ajax_sspu_delete_product_image', [$this, 'handle_delete_product_image']);
        add_action('wp_ajax_sspu_update_variant_inventory', [$this, 'handle_update_variant_inventory']);
        add_action('wp_ajax_sspu_get_shopify_locations', [$this, 'handle_get_shopify_locations']);
        add_action('wp_ajax_sspu_update_product_metafield', [$this, 'handle_update_product_metafield']);
        add_action('wp_ajax_sspu_duplicate_product', [$this, 'handle_duplicate_product']);
        add_action('wp_ajax_sspu_get_vendors', [$this, 'handle_get_vendors']);
        add_action('wp_ajax_sspu_update_product_collections', [$this, 'handle_update_product_collections']);
        add_action('wp_ajax_sspu_live_editor_autosave', [$this, 'handle_live_editor_autosave']);
    }

    /**
     * Delegate to product handler
     */
    public function delegate_to_product_handler()
    {
        $product_handler = new SSPU_Admin_Product_Handler();
        $product_handler->handle_product_submission();
    }

    /**
     * Handle nonce refresh request
     */
/**
     * Handle nonce refresh request
     */
    public function handle_refresh_nonce()
    {
        // THE FIX: The check_ajax_referer() line was removed from here.
        // It was incorrectly preventing expired nonces from being refreshed.

        if (!current_user_can('upload_shopify_products')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }

        // Generate a fresh nonce for product submission
        wp_send_json_success([
            'nonce' => wp_create_nonce('sspu_submit_product')
        ]);
    }

    /**
     * Handle test Shopify connection (NEW function from second script)
     */
    public function handle_test_shopify_connection()
    {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');

        if (!current_user_can('upload_shopify_products')) { // Changed permission to match general usage
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }

        $shopify_api = new SSPU_Shopify_API();
        $response = $shopify_api->send_request('shop.json', 'GET');

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

    public function handle_smart_rotate()
    {
        // Correct way to get the instance
        $ai_editor = SSPU_AI_Image_Editor::get_instance();
        $ai_editor->handle_smart_rotate();
    }

    /**
     * Product generation handlers
     */
    public function handle_description_generation()
    {
        try {
            check_ajax_referer('sspu_ajax_nonce', 'nonce');
            if (!current_user_can('upload_shopify_products')) {
                wp_send_json_error(['message' => 'Permission denied']);
                return;
            }

            error_log('SSPU: handle_description_generation called');

            $input_text = sanitize_textarea_field($_POST['input_text']);
            $image_ids = isset($_POST['image_ids']) ? (array) $_POST['image_ids'] : [];
            // Ensure array_map is used for sanitization of image_ids if they are expected to be numeric
            $image_ids = array_map('absint', $image_ids);

            $image_urls = isset($_POST['image_urls']) ? (array) $_POST['image_urls'] : [];
            // Ensure array_map is used for sanitization of image_urls
            $image_urls = array_map('esc_url_raw', $image_urls);

            $type = sanitize_text_field($_POST['type']);

            if (empty($input_text) && empty($image_ids) && empty($image_urls)) {
                wp_send_json_error(['message' => 'Please provide text or images for AI analysis']);
                return;
            }

            $openai = new SSPU_OpenAI();
            $result = false;

            switch ($type) {
                case 'description':
                    $result = $openai->generate_product_description($input_text, $image_ids, $image_urls);
                    if ($result) {
                        wp_send_json_success(['description' => $result]);
                    }
                    break;
                case 'tags':
                    $result = $openai->generate_tags($input_text);
                    if ($result) {
                        wp_send_json_success(['tags' => $result]);
                    }
                    break;
                case 'price':
                    // This case in original script calls suggest_price with empty variant_info
                    // The handle_price_suggestion below handles it more comprehensively
                    // This might be a legacy call or simple fallback. Keeping as is.
                    $result = $openai->suggest_price($input_text, '', '');
                    if ($result) {
                        wp_send_json_success(['price' => $result]);
                    }
                    break;
                default:
                    wp_send_json_error(['message' => 'Invalid generation type']);
                    return;
            }

            if ($result === false) {
                $error = $openai->get_last_error() ?: 'Failed to generate content';
                error_log('SSPU: AI generation error - ' . $error);
                wp_send_json_error(['message' => $error]);
            }
        } catch (Exception $e) {
            error_log('SSPU: Exception in handle_description_generation - ' . $e->getMessage());
            wp_send_json_error(['message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    public function handle_format_product_name()
    {
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

            $openai = new SSPU_OpenAI();
            $formatted = $openai->format_product_name($name);

            if ($formatted) {
                wp_send_json_success(['formatted_name' => $formatted]);
            } else {
                $error = $openai->get_last_error() ?: 'Failed to format product name';
                wp_send_json_error(['message' => $error]);
            }
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    public function handle_generate_variants()
    {
        try {
            check_ajax_referer('sspu_ajax_nonce', 'nonce');
            if (!current_user_can('upload_shopify_products')) {
                wp_send_json_error(['message' => 'Permission denied']);
                return;
            }

            $option_name = sanitize_text_field($_POST['option_name']);
            $option_values = sanitize_textarea_field($_POST['option_values']);
            $base_price = floatval($_POST['base_price']);

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
                    'name' => $option_name,
                    'value' => $value,
                    'price' => $base_price,
                    'sku' => ''
                ];
            }

            wp_send_json_success(['variants' => $variants]);
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    public function handle_apply_pricing_to_all()
    {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');
        if (!current_user_can('upload_shopify_products')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }

        $action_type = sanitize_text_field($_POST['action_type']);
        // For complex arrays like 'source_data', deeper recursive sanitization is recommended
        // For this example, we'll cast it to array and assume further handling on client side or in specific processing methods.
        $source_data = json_decode(stripslashes($_POST['source_data']), true); // Assuming it comes as JSON string

        wp_send_json_success(['action_type' => $action_type, 'data' => $source_data]);
    }

    /**
     * SKU generation handler
     */
    public function handle_sku_generation()
    {
        try {
            check_ajax_referer('sspu_ajax_nonce', 'nonce');
            if (!current_user_can('upload_shopify_products')) {
                wp_send_json_error(['message' => 'Permission denied']);
                return;
            }

            $product_name = sanitize_text_field($_POST['product_name']);
            $variant_name = sanitize_text_field($_POST['variant_name']);
            $variant_value = sanitize_text_field($_POST['variant_value']);

            // Ensure SSPU_SKU_Generator class exists and is instantiated correctly
            if (!class_exists('SSPU_SKU_Generator')) {
                wp_send_json_error(['message' => 'SKU Generator class not found.']);
                return;
            }
            $sku_generator = SSPU_SKU_Generator::getInstance();
            $sku = $sku_generator->generate_sku($product_name, $variant_name, $variant_value);

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
     * Volume tier calculation handler
     */
    public function handle_volume_tier_calculation()
    {
        try {
            check_ajax_referer('sspu_ajax_nonce', 'nonce');
            if (!current_user_can('upload_shopify_products')) {
                wp_send_json_error(['message' => 'Permission denied']);
                return;
            }

            $base_price = floatval($_POST['base_price']);
            $multipliers_string = get_option('sspu_volume_tier_multipliers', '0.95,0.90,0.85,0.80,0.75');
            $multipliers = array_map('floatval', explode(',', $multipliers_string));
            $default_quantities = [25, 50, 100, 200, 500];

            $tiers = [];
            foreach ($multipliers as $index => $multiplier) {
                if (isset($default_quantities[$index])) {
                    $tiers[] = [
                        'min_quantity' => $default_quantities[$index],
                        'price' => round($base_price * $multiplier, 2)
                    ];
                }
            }

            wp_send_json_success(['tiers' => $tiers]);
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    /**
     * SEO generation handler
     */
    public function handle_seo_generation()
    {
        try {
            check_ajax_referer('sspu_ajax_nonce', 'nonce');
            if (!current_user_can('upload_shopify_products')) {
                wp_send_json_error(['message' => 'Permission denied']);
                return;
            }

            $product_name = sanitize_text_field($_POST['product_name']);
            $description = sanitize_textarea_field($_POST['description']);
            $type = sanitize_text_field($_POST['type']);

            if (!class_exists('SSPU_OpenAI')) {
                wp_send_json_error(['message' => 'OpenAI class not found.']);
                return;
            }
            $openai = new SSPU_OpenAI();

            if ($type === 'title') {
                $result = $openai->generate_seo_title($product_name, $description);
            } else {
                $result = $openai->generate_meta_description($product_name, $description);
            }

            if ($result) {
                wp_send_json_success(['content' => $result]);
            } else {
                $error = $openai->get_last_error() ?: 'Failed to generate SEO content';
                wp_send_json_error(['message' => $error]);
            }
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    /**
     * Price suggestion handler
     */
    public function handle_price_suggestion()
    {
        try {
            check_ajax_referer('sspu_ajax_nonce', 'nonce');
            if (!current_user_can('upload_shopify_products')) {
                wp_send_json_error(['message' => 'Permission denied']);
                return;
            }

            $product_name = sanitize_text_field($_POST['product_name']);
            $description = sanitize_textarea_field($_POST['description']);
            $variant_info = sanitize_text_field($_POST['variant_info']); // This might need more specific sanitization depending on format

            if (!class_exists('SSPU_OpenAI')) {
                wp_send_json_error(['message' => 'OpenAI class not found.']);
                return;
            }
            $openai = new SSPU_OpenAI();
            $suggested_price = $openai->suggest_price($product_name, $description, $variant_info);

            if ($suggested_price) {
                wp_send_json_success(['price' => $suggested_price]);
            } else {
                $error = $openai->get_last_error() ?: 'Failed to suggest price';
                wp_send_json_error(['message' => $error]);
            }
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    /**
     * AI pricing suggestion handler
     */
    public function handle_ai_suggest_all_pricing()
    {
        try {
            check_ajax_referer('sspu_ajax_nonce', 'nonce');
            if (!current_user_can('upload_shopify_products')) {
                wp_send_json_error(['message' => 'Permission denied']);
                return;
            }

            $product_name = sanitize_text_field($_POST['product_name']);
            $main_image_id = absint($_POST['main_image_id']);
            $min_quantity = absint($_POST['min_quantity']);

            if (empty($min_quantity) || $min_quantity < 1) {
                $min_quantity = 25;
            }

            if (empty($product_name) || empty($main_image_id)) {
                wp_send_json_error(['message' => 'Product name and main image are required']);
                return;
            }

            if (!class_exists('SSPU_OpenAI')) {
                wp_send_json_error(['message' => 'OpenAI class not found.']);
                return;
            }
            $openai = new SSPU_OpenAI();
            $base_price = $openai->suggest_base_price_with_image($product_name, $main_image_id, $min_quantity);

            if ($base_price === false) {
                $error = $openai->get_last_error() ?: 'Failed to generate base price';
                wp_send_json_error(['message' => $error]);
                return;
            }

            $tiers = $openai->suggest_volume_tiers($product_name, $base_price, $min_quantity);

            if ($tiers === false) {
                // Even if tiers fail, still return the base price as it's valid
                wp_send_json_success([
                    'base_price' => $base_price,
                    'tiers' => [],
                    'rationale' => 'Base price generated successfully, but volume tiers generation failed.'
                ]);
                return;
            }

            wp_send_json_success([
                'base_price' => $base_price,
                'tiers' => $tiers,
                'rationale' => "AI-generated pricing based on product analysis. Base price for {$min_quantity} units, with volume discounts for larger orders."
            ]);
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    /**
     * Weight estimation handler
     */
    public function handle_estimate_weight()
    {
        try {
            check_ajax_referer('sspu_ajax_nonce', 'nonce');
            if (!current_user_can('upload_shopify_products')) {
                wp_send_json_error(['message' => 'Permission denied']);
                return;
            }

            $product_name = sanitize_text_field($_POST['product_name']);
            $main_image_id = absint($_POST['main_image_id']);

            if (empty($product_name)) {
                wp_send_json_error(['message' => 'Product name is required']);
                return;
            }

            if (empty($main_image_id)) {
                wp_send_json_error(['message' => 'Please select a main product image first']);
                return;
            }

            if (!class_exists('SSPU_OpenAI')) {
                wp_send_json_error(['message' => 'OpenAI class not found.']);
                return;
            }
            $openai = new SSPU_OpenAI();
            $estimated_weight = $openai->estimate_product_weight($product_name, $main_image_id);

            if ($estimated_weight !== false) {
                wp_send_json_success(['weight' => $estimated_weight]);
            } else {
                $error = $openai->get_last_error() ?: 'Failed to estimate weight';
                wp_send_json_error(['message' => $error]);
            }
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    /**
     * Collection handlers
     */
    public function handle_get_collections()
    {
        try {
            check_ajax_referer('sspu_ajax_nonce', 'nonce');
            if (!current_user_can('upload_shopify_products')) {
                wp_send_json_error(['message' => 'Permission denied.']);
                return;
            }

            if (!class_exists('SSPU_Shopify_API')) {
                wp_send_json_error(['message' => 'Shopify API class not found.']);
                return;
            }
            $shopify_api = new SSPU_Shopify_API();
            $collections = $shopify_api->get_all_collections();

            if (isset($collections['errors'])) {
                wp_send_json_error(['message' => 'Failed to fetch collections from Shopify: ' . $collections['errors']]);
            } else {
                if (class_exists('SSPU_Analytics')) {
                    $analytics = new SSPU_Analytics();
                    $analytics->log_activity(get_current_user_id(), 'get_collections', [
                        'status' => 'success',
                        'count' => count($collections)
                    ]);
                }
                wp_send_json_success($collections);
            }
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'An error occurred while fetching collections: ' . $e->getMessage()]);
        }
    }

    public function handle_create_collection()
    {
        try {
            check_ajax_referer('sspu_ajax_nonce', 'nonce');
            if (!current_user_can('upload_shopify_products')) {
                wp_send_json_error(['message' => 'Permission denied']);
                return;
            }

            $collection_name = sanitize_text_field($_POST['collection_name']);
            if (empty($collection_name)) {
                wp_send_json_error(['message' => 'Collection name is required']);
                return;
            }

            if (!class_exists('SSPU_Shopify_API')) {
                wp_send_json_error(['message' => 'Shopify API class not found.']);
                return;
            }
            $shopify_api = new SSPU_Shopify_API();
            $response = $shopify_api->send_request('custom_collections.json', 'POST', [
                'custom_collection' => ['title' => $collection_name]
            ]);

            if ($response && isset($response['custom_collection'])) {
                wp_send_json_success($response['custom_collection']);
            } else {
                $error = isset($response['errors']) ? json_encode($response['errors']) : 'Failed to create collection';
                wp_send_json_error(['message' => $error]);
            }
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    /**
     * Draft handlers
     */
    public function handle_save_draft()
    {
        try {
            check_ajax_referer('sspu_ajax_nonce', 'nonce');
            if (!current_user_can('upload_shopify_products')) {
                wp_send_json_error(['message' => 'Permission denied']);
                return;
            }

            // Sanitization for draft_data needs to be deep and context-aware.
            // For now, it's passed as is, assuming SSPU_Drafts handles it.
            $draft_data = json_decode(stripslashes($_POST['draft_data']), true); // Assuming JSON string from JS
            $user_id = get_current_user_id();

            if (!class_exists('SSPU_Drafts')) {
                wp_send_json_error(['message' => 'Drafts class not found.']);
                return;
            }
            $drafts = new SSPU_Drafts();
            $draft_id = $drafts->save_draft($user_id, $draft_data);

            if ($draft_id) {
                wp_send_json_success(['draft_id' => $draft_id]);
            } else {
                wp_send_json_error(['message' => 'Failed to save draft']);
            }
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    public function handle_load_draft()
    {
        try {
            check_ajax_referer('sspu_ajax_nonce', 'nonce');
            if (!current_user_can('upload_shopify_products')) {
                wp_send_json_error(['message' => 'Permission denied']);
                return;
            }

            $user_id = get_current_user_id();
            if (!class_exists('SSPU_Drafts')) {
                wp_send_json_error(['message' => 'Drafts class not found.']);
                return;
            }
            $drafts = new SSPU_Drafts();
            $draft = $drafts->get_latest_draft($user_id);

            if ($draft) {
                wp_send_json_success(['draft_data' => $draft]);
            } else {
                wp_send_json_error(['message' => 'No draft found']);
            }
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    public function handle_auto_save_draft()
    {
        try {
            check_ajax_referer('sspu_ajax_nonce', 'nonce');
            if (!current_user_can('upload_shopify_products')) {
                wp_send_json_error(['message' => 'Permission denied']);
                return;
            }

            // Sanitization for draft_data needs to be deep and context-aware.
            // For now, it's passed as is, assuming SSPU_Drafts handles it.
            $draft_data = json_decode(stripslashes($_POST['draft_data']), true); // Assuming JSON string from JS
            $user_id = get_current_user_id();

            if (!class_exists('SSPU_Drafts')) {
                wp_send_json_error(['message' => 'Drafts class not found.']);
                return;
            }
            $drafts = new SSPU_Drafts();
            $draft_id = $drafts->save_draft($user_id, $draft_data, true);

            if ($draft_id) {
                wp_send_json_success([
                    'draft_id' => $draft_id,
                    'timestamp' => current_time('mysql')
                ]);
            } else {
                wp_send_json_error(['message' => 'Failed to auto-save draft']);
            }
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    /**
     * API test handlers
     */
    public function handle_test_openai_api()
    {
        try {
            check_ajax_referer('sspu_ajax_nonce', 'nonce');
            if (!current_user_can('manage_options')) { // 'manage_options' is typically for admins
                wp_send_json_error(['message' => 'Permission denied']);
                return;
            }

            if (!class_exists('SSPU_OpenAI')) {
                wp_send_json_error(['message' => 'OpenAI class not found.']);
                return;
            }
            $openai = new SSPU_OpenAI();
            if ($openai->test_api_connection()) {
                wp_send_json_success(['message' => 'API connection successful']);
            } else {
                $error = $openai->get_last_error() ?: 'Connection test failed';
                wp_send_json_error(['message' => $error]);
            }
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    public function handle_test_gemini_api()
    {
        try {
            check_ajax_referer('sspu_ajax_nonce', 'nonce');
            if (!current_user_can('manage_options')) { // 'manage_options' is typically for admins
                wp_send_json_error(['message' => 'Permission denied']);
                return;
            }

            if (!class_exists('SSPU_AI_Image_Editor')) {
                wp_send_json_error(['message' => 'AI Image Editor class not found.']);
                return;
            }
            $ai_editor = SSPU_AI_Image_Editor::get_instance();
            if ($ai_editor->test_gemini_connection()) {
                wp_send_json_success(['message' => 'API connection successful']);
            } else {
                wp_send_json_error(['message' => 'Connection test failed']);
            }
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    /**
     * Image handlers
     */
    public function handle_validate_image()
    {
        try {
            check_ajax_referer('sspu_ajax_nonce', 'nonce');
            if (!current_user_can('upload_shopify_products')) {
                wp_send_json_error(['message' => 'Permission denied']);
                return;
            }

            $image_id = absint($_POST['image_id']);
            $attachment = get_post($image_id);

            if (!$attachment || $attachment->post_type !== 'attachment') {
                wp_send_json_error(['message' => 'Invalid image']);
                return;
            }

            $mime_type = get_post_mime_type($image_id);
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

            if (!in_array($mime_type, $allowed_types)) {
                wp_send_json_error(['message' => 'Invalid image type. Allowed: JPEG, PNG, GIF, WebP']);
                return;
            }

            wp_send_json_success(['message' => 'Image is valid']);
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    public function handle_upload_images()
    {
        try {
            check_ajax_referer('sspu_ajax_nonce', 'nonce');
            if (!current_user_can('upload_shopify_products')) {
                wp_send_json_error(['message' => 'Permission denied']);
                return;
            }

            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');

            $uploaded_ids = [];
            $uploaded_urls = [];

            foreach ($_FILES as $key => $file) {
                if (strpos($key, 'file_') !== 0) continue;

                // Basic validation for uploaded files
                if ($file['error'] !== UPLOAD_ERR_OK) {
                    wp_send_json_error(['message' => 'File upload error: ' . $file['error']]);
                    return;
                }

                $upload = wp_handle_upload($file, ['test_form' => false]);

                if (isset($upload['error'])) {
                    wp_send_json_error(['message' => $upload['error']]);
                    return;
                }

                $attachment_id = wp_insert_attachment([
                    'post_mime_type' => $upload['type'],
                    'post_title' => preg_replace('/\.[^.]+$/', '', basename($upload['file'])),
                    'post_content' => '',
                    'post_status' => 'inherit'
                ], $upload['file']);

                if (!is_wp_error($attachment_id)) {
                    wp_update_attachment_metadata($attachment_id, wp_generate_attachment_metadata($attachment_id, $upload['file']));
                    $uploaded_ids[] = $attachment_id;
                    $uploaded_urls[$attachment_id] = wp_get_attachment_thumb_url($attachment_id);
                } else {
                     error_log('SSPU Upload Image Error: ' . $attachment_id->get_error_message());
                }
            }

            if (empty($uploaded_ids)) {
                wp_send_json_error(['message' => 'No files were uploaded successfully']);
                return;
            }

            wp_send_json_success([
                'ids' => $uploaded_ids,
                'urls' => $uploaded_urls
            ]);
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    /**
     * Alibaba-related handlers
     */
    public function handle_get_current_alibaba_url()
    {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');
        if (!current_user_can('upload_shopify_products')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }

        $user_id = get_current_user_id();
        if (class_exists('SSPU_Alibaba_Queue')) {
            $assignment = SSPU_Alibaba_Queue::get_user_assignment($user_id);
            if ($assignment) {
                wp_send_json_success([
                    'url' => $assignment->url,
                    'queue_id' => $assignment->queue_id,
                    'assigned_at' => $assignment->assigned_at
                ]);
                return;
            }
        }

        wp_send_json_error(['message' => 'No URL assigned']);
    }

    public function handle_fetch_alibaba_product_name()
    {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');
        if (!current_user_can('upload_shopify_products')) { // Added permission check
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }

        $alibaba_url = isset($_POST['alibaba_url']) ? esc_url_raw($_POST['alibaba_url']) : '';

        if (empty($alibaba_url)) {
            wp_send_json_error(['message' => 'No URL provided']);
            return;
        }

        $response = wp_remote_get($alibaba_url, ['timeout' => 30]); // Added timeout

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => 'Failed to fetch page: ' . $response->get_error_message()]);
            return;
        }

        $body = wp_remote_retrieve_body($response);

        // More robust parsing for h1 tag, considering multiple possible structures
        preg_match('/<h1[^>]*class="title"[^>]*>(.*?)<\/h1>/is', $body, $matches_class); // Prefer class="title"
        preg_match('/<h1[^>]*itemprop="name"[^>]*>(.*?)<\/h1>/is', $body, $matches_itemprop); // Or itemprop="name"
        preg_match('/<h1[^>]*>(.*?)<\/h1>/is', $body, $matches_generic); // Fallback generic h1

        $product_name = '';
        if (!empty($matches_class[1])) {
            $product_name = $matches_class[1];
        } elseif (!empty($matches_itemprop[1])) {
            $product_name = $matches_itemprop[1];
        } elseif (!empty($matches_generic[1])) {
            $product_name = $matches_generic[1];
        }

        if (!empty($product_name)) {
            $product_name = strip_tags($product_name);
            $product_name = html_entity_decode($product_name, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $product_name = trim($product_name);
            $product_name = preg_replace('/\s+/', ' ', $product_name); // Normalize whitespace

            wp_send_json_success(['product_name' => $product_name]);
        } else {
            wp_send_json_error(['message' => 'Could not find product title on the Alibaba page. The page structure might have changed.']);
        }
    }

    /**
     * Detect color using AI (IMPROVED in second script)
     */
    public function handle_detect_color()
    {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');

        if (!current_user_can('upload_shopify_products')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }

        $openai_api_key = get_option('sspu_openai_api_key');
        if (empty($openai_api_key)) {
            wp_send_json_error(['message' => 'OpenAI API key not configured']);
            return;
        }

        $image_data = isset($_POST['image_data']) ? $_POST['image_data'] : '';

        if (empty($image_data)) {
            wp_send_json_error(['message' => 'No image data provided']);
            return;
        }

        // Validate base64 image data
        if (strpos($image_data, 'data:image/') !== 0) {
            wp_send_json_error(['message' => 'Invalid image data format. Expected base64 image URL.']);
            return;
        }

        $messages = [
            [
                'role' => 'user',
                'content' => [
                    [
                        'type' => 'text',
                        'text' => 'Look at this product image and identify the primary color. Return ONLY the color name in 1-2 words. Examples: Red, Blue, Light Green, Dark Brown, White, Black. Do not include any other text or explanation.'
                    ],
                    [
                        'type' => 'image_url',
                        'image_url' => ['url' => $image_data]
                    ]
                ]
            ]
        ];

        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $openai_api_key,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode([
                'model' => 'gpt-4o', // Updated model for better vision capabilities
                'messages' => $messages,
                'max_tokens' => 50
            ]),
            'timeout' => 30
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => 'API request failed: ' . $response->get_error_message()]);
            return;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['choices'][0]['message']['content'])) {
            $color = trim($body['choices'][0]['message']['content']);

            // Clean up the response - remove any extra text or punctuation
            $color = preg_replace('/[^a-zA-Z\s]/', '', $color);
            $color = trim($color);

            if (!empty($color)) {
                wp_send_json_success(['color' => $color]);
            } else {
                wp_send_json_error(['message' => 'Could not determine color from image. AI response was empty or unparseable.']);
            }
        } else {
            $error_message = 'Failed to detect color. AI response malformed.';
            if (isset($body['error']['message'])) {
                $error_message .= ': ' . $body['error']['message'];
            }
            wp_send_json_error(['message' => $error_message]);
        }
    }

    /**
     * Handle fetching MOQ from Alibaba
     */
    public function handle_fetch_alibaba_moq()
    {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');

        if (!current_user_can('upload_shopify_products')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }

        $alibaba_url = isset($_POST['alibaba_url']) ? esc_url_raw($_POST['alibaba_url']) : '';

        if (empty($alibaba_url)) {
            wp_send_json_error(['message' => 'No URL provided']);
            return;
        }

        if (!class_exists('SSPU_Image_Retriever')) {
            wp_send_json_error(['message' => 'Image Retriever class not found.']);
            return;
        }
        $retriever = new SSPU_Image_Retriever();
        $moq_data = $retriever->fetch_alibaba_moq($alibaba_url);

        if ($moq_data && isset($moq_data['moq'])) {
            // Log the activity
            if (class_exists('SSPU_Analytics')) {
                $analytics = new SSPU_Analytics();
                $analytics->log_activity(get_current_user_id(), 'alibaba_moq_fetched', [
                    'url' => $alibaba_url,
                    'moq' => $moq_data['moq'],
                    'unit' => $moq_data['unit']
                ]);
            }

            wp_send_json_success([
                'moq' => $moq_data['moq'],
                'unit' => $moq_data['unit'],
                'message' => sprintf('MOQ found: %d %s', $moq_data['moq'], $moq_data['unit'])
            ]);
        } else {
            wp_send_json_error(['message' => 'Could not find MOQ on the page. The page structure might have changed.']);
        }
    }

    /**
     * Handle fetching product description from Alibaba
     */
    public function handle_fetch_alibaba_description()
    {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');

        if (!current_user_can('upload_shopify_products')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }

        $alibaba_url = isset($_POST['alibaba_url']) ? esc_url_raw($_POST['alibaba_url']) : '';

        if (empty($alibaba_url)) {
            wp_send_json_error(['message' => 'No URL provided']);
            return;
        }

        if (!class_exists('SSPU_Image_Retriever')) {
            wp_send_json_error(['message' => 'Image Retriever class not found.']);
            return;
        }
        $retriever = new SSPU_Image_Retriever();
        $description_data = $retriever->fetch_alibaba_description($alibaba_url);

        if ($description_data && (!empty($description_data['attributes']) || !empty($description_data['description']) || !empty($description_data['features']))) {
            $html_content = '';

            // Format attributes as HTML table
            if (!empty($description_data['attributes'])) {
                $html_content .= $retriever->format_attributes_as_table($description_data['attributes']);
            }

            // Add any additional description
            if (!empty($description_data['description'])) {
                $html_content .= '<p>' . wp_kses_post($description_data['description']) . '</p>'; // Use wp_kses_post for description
            }

            // Add features if any
            if (!empty($description_data['features'])) {
                $html_content .= '<h4>Product Features:</h4><ul>';
                foreach ($description_data['features'] as $feature) {
                    $html_content .= '<li>' . wp_kses_post($feature) . '</li>'; // Use wp_kses_post for features
                }
                $html_content .= '</ul>';
            }

            // Log the activity
            if (class_exists('SSPU_Analytics')) {
                $analytics = new SSPU_Analytics();
                $analytics->log_activity(get_current_user_id(), 'alibaba_description_fetched', [
                    'url' => $alibaba_url,
                    'attributes_count' => count($description_data['attributes']),
                    'features_count' => count($description_data['features'])
                ]);
            }

            wp_send_json_success([
                'html' => $html_content,
                'attributes' => $description_data['attributes'],
                'features' => $description_data['features'],
                'description' => $description_data['description'],
                'message' => sprintf('Found %d product attributes', count($description_data['attributes']))
            ]);
        } else {
            wp_send_json_error(['message' => 'Could not find product details on the page. The page structure might have changed or content is missing.']);
        }
    }

    /**
     * Template handlers
     */
    public function handle_get_single_template_content()
    {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');
        if (!current_user_can('upload_shopify_products')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }

        $template_id = absint($_POST['template_id']);

        global $wpdb;
        $table_name = $wpdb->prefix . 'sspu_image_templates';
        $template_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE template_id = %d",
            $template_id
        ));

        if (!$template_data) {
            wp_send_json_error(['message' => 'Template not found']);
            return;
        }

        wp_send_json_success([
            'name' => sanitize_text_field($template_data->name),
            'content' => sanitize_textarea_field($template_data->prompt) // Assuming 'prompt' is the content field
        ]);
    }

    /**
     * Proxy handlers for other classes
     */
    public function handle_get_analytics_proxy()
    {
        if (!class_exists('SSPU_Analytics')) {
            wp_send_json_error(['message' => 'Analytics class not found.']);
            return;
        }
        $analytics = new SSPU_Analytics();
        $analytics->handle_get_analytics();
    }

    public function handle_export_analytics()
    {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');
        if (!current_user_can('upload_shopify_products')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }

        wp_send_json_error(['message' => 'Export functionality not yet implemented']);
    }

    public function handle_get_user_activity_proxy()
    {
        if (!class_exists('SSPU_Analytics')) {
            wp_send_json_error(['message' => 'Analytics class not found.']);
            return;
        }
        $analytics = new SSPU_Analytics();
        $analytics->handle_get_user_activity();
    }

    public function handle_global_search_proxy()
    {
        if (!class_exists('SSPU_Search')) {
            wp_send_json_error(['message' => 'Search class not found.']);
            return;
        }
        $search = new SSPU_Search();
        $search->handle_global_search();
    }

    public function handle_get_search_filters_proxy()
    {
        if (!class_exists('SSPU_Search')) {
            wp_send_json_error(['message' => 'Search class not found.']);
            return;
        }
        $search = new SSPU_Search();
        $search->handle_get_search_filters();
    }

    public function handle_request_alibaba_url()
    {
        if (!class_exists('SSPU_Alibaba_Queue')) {
            wp_send_json_error(['message' => 'Alibaba Queue class not found.']);
            return;
        }
        $queue = new SSPU_Alibaba_Queue();
        $queue->handle_request_url();
    }

    public function handle_complete_alibaba_url()
    {
        if (!class_exists('SSPU_Alibaba_Queue')) {
            wp_send_json_error(['message' => 'Alibaba Queue class not found.']);
            return;
        }
        $queue = new SSPU_Alibaba_Queue();
        $queue->handle_complete_url();
    }

    public function handle_release_alibaba_url()
    {
        if (!class_exists('SSPU_Alibaba_Queue')) {
            wp_send_json_error(['message' => 'Alibaba Queue class not found.']);
            return;
        }
        $queue = new SSPU_Alibaba_Queue();
        $queue->handle_release_url();
    }

    public function handle_retrieve_alibaba_images()
    {
        if (!class_exists('SSPU_Image_Retriever')) {
            wp_send_json_error(['message' => 'Image Retriever class not found.']);
            return;
        }
        $retriever = new SSPU_Image_Retriever();
        $retriever->handle_retrieve_images();
    }

    public function handle_download_external_image()
    {
        error_log('[SSPU Download] ===== DOWNLOAD REQUEST START =====');

        check_ajax_referer('sspu_ajax_nonce', 'nonce');
        if (!current_user_can('upload_shopify_products')) {
            error_log('[SSPU Download] Permission denied for user: ' . get_current_user_id());
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }

        $image_url = esc_url_raw($_POST['image_url']);
        $filename = sanitize_file_name($_POST['filename']);

        error_log('[SSPU Download] Raw image URL from POST: ' . $_POST['image_url']);
        error_log('[SSPU Download] Escaped image URL: ' . $image_url);
        error_log('[SSPU Download] Filename: ' . $filename);

        if (empty($image_url)) {
            error_log('[SSPU Download] Empty image URL after escaping');
            wp_send_json_error(['message' => 'Invalid image URL']);
            return;
        }

        // Additional URL validation
        if (!filter_var($image_url, FILTER_VALIDATE_URL)) {
            error_log('[SSPU Download] URL failed FILTER_VALIDATE_URL: ' . $image_url);
            wp_send_json_error(['message' => 'Invalid URL format: ' . $image_url]);
            return;
        }

        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        error_log('[SSPU Download] Starting download_url() for: ' . $image_url);
        $tmp = download_url($image_url, 300); // Increased timeout for download

        if (is_wp_error($tmp)) {
            error_log('[SSPU Download] download_url() failed: ' . $tmp->get_error_message());
            error_log('[SSPU Download] Error code: ' . $tmp->get_error_code());
            error_log('[SSPU Download] Error data: ' . print_r($tmp->get_error_data(), true));
            wp_send_json_error(['message' => 'Failed to download image: ' . $tmp->get_error_message()]);
            return;
        }

        error_log('[SSPU Download] File downloaded to: ' . $tmp);
        error_log('[SSPU Download] File size: ' . filesize($tmp) . ' bytes');

        if (filesize($tmp) > wp_max_upload_size()) {
            error_log('[SSPU Download] File too large: ' . filesize($tmp) . ' > ' . wp_max_upload_size());
            @unlink($tmp);
            wp_send_json_error(['message' => 'File too large (max ' . size_format(wp_max_upload_size()) . ')']);
            return;
        }

        $file_info = wp_check_filetype($tmp);
        // Robust file extension detection for cases where wp_check_filetype fails
        if (!$file_info['ext']) {
            $url_ext = pathinfo(parse_url($image_url, PHP_URL_PATH), PATHINFO_EXTENSION);
            error_log('[SSPU Download] No extension detected by wp_check_filetype. Trying URL extension: ' . $url_ext);
            if (in_array(strtolower($url_ext), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $file_info['ext'] = strtolower($url_ext);
                $file_info['type'] = 'image/' . ($file_info['ext'] === 'jpg' ? 'jpeg' : $file_info['ext']);
            } else {
                // Fallback to a default if still no valid extension
                $file_info['ext'] = 'jpg';
                $file_info['type'] = 'image/jpeg';
                error_log('[SSPU Download] Falling back to default jpg/jpeg for extension.');
            }
        }

        $file_array = [
            'name' => $filename . '.' . $file_info['ext'],
            'type' => $file_info['type'],
            'tmp_name' => $tmp,
            'error' => 0,
            'size' => filesize($tmp),
        ];

        error_log('[SSPU Download] Proceeding with media_handle_sideload...');
        error_log('[SSPU Download] File array: ' . print_r($file_array, true));

        // Use current post ID as parent if available in context, otherwise 0
        $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;

        $attachment_id = media_handle_sideload($file_array, $post_id, null, [
            'post_title' => $filename,
            'post_content' => 'Downloaded from external source: ' . $image_url, // More informative content
            'post_status' => 'inherit'
        ]);

        @unlink($tmp); // Clean up the temporary file

        if (is_wp_error($attachment_id)) {
            error_log('[SSPU Download] media_handle_sideload failed: ' . $attachment_id->get_error_message());
            wp_send_json_error(['message' => 'Failed to create attachment: ' . $attachment_id->get_error_message()]);
            return;
        }

        $attachment_url = wp_get_attachment_url($attachment_id);
        $thumb_url = wp_get_attachment_thumb_url($attachment_id);

        error_log('[SSPU Download] SUCCESS! Attachment ID: ' . $attachment_id);
        error_log('[SSPU Download] Attachment URL: ' . $attachment_url);
        error_log('[SSPU Download] Thumb URL: ' . $thumb_url);

        if (class_exists('SSPU_Analytics')) {
            $analytics = new SSPU_Analytics();
            $analytics->log_activity(get_current_user_id(), 'external_image_downloaded', [
                'attachment_id' => $attachment_id,
                'source_url' => $image_url,
                'filename' => $filename
            ]);
        }

        wp_send_json_success([
            'attachment_id' => $attachment_id,
            'url' => $attachment_url,
            'thumb_url' => $thumb_url,
            'filename' => $filename
        ]);
    }

    public function handle_ai_edit_image()
    {
        if (!class_exists('SSPU_AI_Image_Editor')) {
            wp_send_json_error(['message' => 'AI Image Editor class not found.']);
            return;
        }
        $ai_editor = SSPU_AI_Image_Editor::get_instance();
        $ai_editor->handle_ai_edit();
    }

    public function handle_get_chat_history()
    {
        if (!class_exists('SSPU_AI_Image_Editor')) {
            wp_send_json_error(['message' => 'AI Image Editor class not found.']);
            return;
        }
        $ai_editor = SSPU_AI_Image_Editor::get_instance();
        $ai_editor->handle_get_chat_history();
    }

    public function handle_save_edited_image()
    {
        if (!class_exists('SSPU_AI_Image_Editor')) {
            wp_send_json_error(['message' => 'AI Image Editor class not found.']);
            return;
        }
        $ai_editor = SSPU_AI_Image_Editor::get_instance();
        $ai_editor->handle_save_edited_image();
    }

    public function handle_get_image_templates()
    {
        if (!class_exists('SSPU_Image_Templates')) {
            wp_send_json_error(['message' => 'Image Templates class not found.']);
            return;
        }
        $templates = new SSPU_Image_Templates();
        $templates->handle_get_templates();
    }

    public function handle_save_image_template()
    {
        if (!class_exists('SSPU_Image_Templates')) {
            wp_send_json_error(['message' => 'Image Templates class not found.']);
            return;
        }
        $templates = new SSPU_Image_Templates();
        $templates->handle_save_template();
    }

    public function handle_delete_image_template()
    {
        if (!class_exists('SSPU_Image_Templates')) {
            wp_send_json_error(['message' => 'Image Templates class not found.']);
            return;
        }
        $templates = new SSPU_Image_Templates();
        $templates->handle_delete_template();
    }

    /**
     * Mimic handlers
     */
    public function handle_get_mimic_images()
    {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');
        if (!current_user_can('upload_shopify_products')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }

        $category = sanitize_text_field($_POST['category'] ?? 'all');

        $args = [
            'post_type' => 'attachment',
            'post_mime_type' => 'image',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => '_sspu_mimic_reference',
                    'value' => 'yes',
                    'compare' => '='
                ]
            ]
        ];

        if ($category !== 'all') {
            $args['meta_query'][] = [
                'key' => '_sspu_mimic_category',
                'value' => $category,
                'compare' => '='
            ];
        }

        $attachments = get_posts($args);
        $mimic_images = [];

        foreach ($attachments as $attachment) {
            $mimic_images[] = [
                'mimic_id' => $attachment->ID,
                'image_id' => $attachment->ID, // Keeping both for clarity/flexibility
                'name' => $attachment->post_title,
                'description' => $attachment->post_content,
                'image_url' => wp_get_attachment_url($attachment->ID),
                'thumbnail_url' => wp_get_attachment_thumb_url($attachment->ID),
                'style_keywords' => get_post_meta($attachment->ID, '_sspu_style_keywords', true),
                'usage_count' => intval(get_post_meta($attachment->ID, '_sspu_mimic_usage_count', true))
            ];
        }

        wp_send_json_success(['mimic_images' => $mimic_images]);
    }

    public function handle_mimic_image()
    {
        if (!class_exists('SSPU_AI_Image_Editor')) {
            wp_send_json_error(['message' => 'AI Image Editor class not found.']);
            return;
        }
        $ai_editor = SSPU_AI_Image_Editor::get_instance();
        $ai_editor->handle_mimic_style();
    }

    /**
     * Scrape Alibaba variants handler
     */
    public function handle_scrape_alibaba_variants()
    {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');
        if (!current_user_can('upload_shopify_products')) {
            wp_send_json_error(['message' => 'Permission denied.']);
            return;
        }

        $url = isset($_POST['url']) ? sanitize_url($_POST['url']) : '';
        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            wp_send_json_error(['message' => 'A valid URL is required.']);
            return;
        }

        if (!class_exists('SSPU_Image_Retriever')) {
            wp_send_json_error(['message' => 'Image Retriever class not found.']);
            return;
        }
        $image_retriever = new SSPU_Image_Retriever();
        $variants_data = $image_retriever->scrape_variants_from_url($url);

        if ($variants_data === false) {
            wp_send_json_error(['message' => 'Failed to retrieve or parse variant data from the URL. The page structure might have changed.']);
            return;
        }

        if (empty($variants_data)) {
            wp_send_json_error(['message' => 'No variant data with images found on the page.']);
            return;
        }

        wp_send_json_success(['variants' => $variants_data]);
    }

    /**
     * Create masked image handler
     */
    public function handle_create_masked_image()
    {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');

        if (!current_user_can('upload_shopify_products')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }

        $image_id = absint($_POST['image_id']);
        $mask_coordinates = $_POST['mask_coordinates']; // Needs sanitization if values are not numeric

        if (!$image_id || !is_array($mask_coordinates) || empty($mask_coordinates)) {
            wp_send_json_error(['message' => 'Missing required parameters or invalid mask coordinates.']);
            return;
        }

        // Sanitize coordinates explicitly
        $crop_x = intval($mask_coordinates['x'] ?? 0);
        $crop_y = intval($mask_coordinates['y'] ?? 0);
        $crop_width = intval($mask_coordinates['width'] ?? 0);
        $crop_height = intval($mask_coordinates['height'] ?? 0);

        $image_path = get_attached_file($image_id);
        if (!$image_path || !file_exists($image_path)) {
            wp_send_json_error(['message' => 'Image file not found on server.']);
            return;
        }

        $image_info = getimagesize($image_path);
        if (!$image_info) {
            wp_send_json_error(['message' => 'Invalid image file or unable to get image dimensions.']);
            return;
        }

        $mime_type = $image_info['mime'];
        $width = $image_info[0];
        $height = $image_info[1];

        // Create image resource from source image
        $source_image = null;
        switch ($mime_type) {
            case 'image/jpeg':
                $source_image = imagecreatefromjpeg($image_path);
                break;
            case 'image/png':
                $source_image = imagecreatefrompng($image_path);
                break;
            case 'image/gif':
                $source_image = imagecreatefromgif($image_path);
                break;
            case 'image/webp':
                // Check if webp functions are available
                if (function_exists('imagecreatefromwebp')) {
                    $source_image = imagecreatefromwebp($image_path);
                }
                break;
            default:
                wp_send_json_error(['message' => 'Unsupported image type: ' . $mime_type]);
                return;
        }

        if (!$source_image) {
            wp_send_json_error(['message' => 'Failed to create image resource from original file.']);
            return;
        }

        // Adjust crop coordinates to stay within image bounds
        $crop_x = max(0, $crop_x);
        $crop_y = max(0, $crop_y);
        $crop_width = min($crop_width, $width - $crop_x);
        $crop_height = min($crop_height, $height - $crop_y);

        if ($crop_width <= 0 || $crop_height <= 0) {
            imagedestroy($source_image);
            wp_send_json_error(['message' => 'Invalid crop area dimensions.']);
            return;
        }

        // Create background image (full image)
        $background_image = imagecreatetruecolor($width, $height);
        imagecopy($background_image, $source_image, 0, 0, 0, 0, $width, $height);

        // Create mask image (original image with cropped area transparent)
        $mask_image = imagecreatetruecolor($width, $height);
        imagealphablending($mask_image, false); // Disable blending for proper alpha handling
        imagesavealpha($mask_image, true); // Save full alpha channel

        imagecopy($mask_image, $source_image, 0, 0, 0, 0, $width, $height);

        // Make the cropped area transparent in the mask image
        $transparent = imagecolorallocatealpha($mask_image, 0, 0, 0, 127); // 127 is full transparency
        imagefilledrectangle($mask_image, $crop_x, $crop_y,
            $crop_x + $crop_width, // Corrected to use crop_width/height directly
            $crop_y + $crop_height,
            $transparent);

        $upload_dir = wp_upload_dir();
        $base_name = pathinfo($image_path, PATHINFO_FILENAME);
        $timestamp = time(); // Use current timestamp for unique filenames

        $background_filename = $base_name . '_background_' . $timestamp . '.png';
        $background_path = $upload_dir['path'] . '/' . $background_filename;
        // Check if directory exists and is writable
        if (!wp_mkdir_p($upload_dir['path'])) {
            imagedestroy($source_image);
            imagedestroy($background_image);
            imagedestroy($mask_image);
            wp_send_json_error(['message' => 'Unable to create upload directory.']);
            return;
        }
        imagepng($background_image, $background_path, 9); // Quality 9 for PNG

        $mask_filename = $base_name . '_mask_' . $timestamp . '.png';
        $mask_path = $upload_dir['path'] . '/' . $mask_filename;
        imagepng($mask_image, $mask_path, 9);

        // Destroy image resources to free up memory
        imagedestroy($source_image);
        imagedestroy($background_image);
        imagedestroy($mask_image);

        // Create WordPress attachments for the new images
        $background_attachment_id = $this->create_attachment($background_path, $background_filename, 'Design Tool Background - ' . $base_name);
        $mask_attachment_id = $this->create_attachment($mask_path, $mask_filename, 'Design Tool Mask - ' . $base_name);

        if (!$background_attachment_id || !$mask_attachment_id) {
            // Clean up files if attachment creation fails
            @unlink($background_path);
            @unlink($mask_path);
            wp_send_json_error(['message' => 'Failed to create WordPress attachments for generated images.']);
            return;
        }

        $background_url = wp_get_attachment_url($background_attachment_id);
        $mask_url = wp_get_attachment_url($mask_attachment_id);

        if (class_exists('SSPU_Analytics')) {
            $analytics = new SSPU_Analytics();
            $analytics->log_activity(get_current_user_id(), 'design_files_created', [
                'original_image_id' => $image_id,
                'background_id' => $background_attachment_id,
                'mask_id' => $mask_attachment_id,
                'crop_area' => $mask_coordinates
            ]);
        }

        wp_send_json_success([
            'background_url' => $background_url,
            'mask_url' => $mask_url,
            'background_id' => $background_attachment_id,
            'mask_id' => $mask_attachment_id,
            'message' => 'Design files created successfully'
        ]);
    }

    /**
     * Live Product Editor Handlers
     */

    /**
     * Handle product search for live editor with advanced filters
     */
    public function handle_search_live_products()
    {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');
        if (!current_user_can('upload_shopify_products')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }

        $params = [
            'query' => sanitize_text_field($_POST['query'] ?? ''),
            'status' => sanitize_text_field($_POST['status'] ?? ''),
            'vendor' => sanitize_text_field($_POST['vendor'] ?? ''),
            'collection_id' => absint($_POST['collection_id'] ?? 0),
            'limit' => absint($_POST['limit'] ?? 50)
        ];

        if (!empty($_POST['page_info'])) {
            $params['page_info'] = sanitize_text_field($_POST['page_info']);
        }

        if (!class_exists('SSPU_Shopify_API')) {
            wp_send_json_error(['message' => 'Shopify API class not found.']);
            return;
        }
        $shopify_api = new SSPU_Shopify_API();
        $response = $shopify_api->search_products($params);

        if (isset($response['products'])) {
            if (class_exists('SSPU_Analytics')) {
                $analytics = new SSPU_Analytics();
                $analytics->log_activity(get_current_user_id(), 'live_editor_search', [
                    'query' => $params['query'],
                    'results_count' => count($response['products'])
                ]);
            }

            $result = ['products' => $response['products']];
            if (isset($response['next_page_info'])) {
                $result['next_page_info'] = $response['next_page_info'];
            }
            if (isset($response['prev_page_info'])) {
                $result['prev_page_info'] = $response['prev_page_info'];
            }

            wp_send_json_success($result);
        } else {
            // Provide more specific error if available from Shopify API
            $error_message = 'Failed to search products.';
            if (isset($response['errors'])) {
                $error_message .= ' Shopify Error: ' . (is_array($response['errors']) ? json_encode($response['errors']) : $response['errors']);
            }
            wp_send_json_error(['message' => $error_message]);
        }
    }

    /**
     * Handle getting full product data with all details
     */
    public function handle_get_live_product_data()
    {
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

        if (!class_exists('SSPU_Shopify_API')) {
            wp_send_json_error(['message' => 'Shopify API class not found.']);
            return;
        }
        $shopify_api = new SSPU_Shopify_API();
        $response = $shopify_api->get_product($product_id);

        if (isset($response['product'])) {
            $collections = $shopify_api->get_product_collections($product_id);
            $response['product']['collection_ids'] = $collections;

            // Store product data in a transient for later comparison during update
            set_transient('sspu_editing_product_' . get_current_user_id(), $response['product'], HOUR_IN_SECONDS);

            wp_send_json_success(['product' => $response['product']]);
        } else {
            $error_message = 'Product not found.';
            if (isset($response['errors'])) {
                $error_message .= ' Shopify Error: ' . (is_array($response['errors']) ? json_encode($response['errors']) : $response['errors']);
            }
            wp_send_json_error(['message' => $error_message]);
        }
    }

    /**
     * Handle updating live product with comprehensive data
     */
    public function handle_update_live_product()
    {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');
        if (!current_user_can('upload_shopify_products')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }

        $product_id = absint($_POST['product_id']);
        $product_data = $_POST['product_data']; // This needs deep sanitization

        if (!$product_id || !is_array($product_data) || empty($product_data)) {
            wp_send_json_error(['message' => 'Missing or invalid required data for product update.']);
            return;
        }

        // Get original product for comparison from transient
        $original = get_transient('sspu_editing_product_' . get_current_user_id());

        // Sanitize product data
        $clean_data = [
            'title' => sanitize_text_field($product_data['title'] ?? ''),
            'body_html' => wp_kses_post($product_data['body_html'] ?? ''),
            'vendor' => sanitize_text_field($product_data['vendor'] ?? ''),
            'product_type' => sanitize_text_field($product_data['product_type'] ?? ''),
            'tags' => sanitize_text_field($product_data['tags'] ?? ''),
            'published' => (isset($product_data['published']) && ($product_data['published'] === 'true' || $product_data['published'] === true)),
        ];

        // Handle SEO fields as metafields
        $metafields_to_update = [];
        if (isset($product_data['seo_title'])) {
            $metafields_to_update[] = [
                'namespace' => 'global',
                'key' => 'title_tag',
                'value' => sanitize_text_field($product_data['seo_title']),
                'type' => 'single_line_text_field'
            ];
        }
        if (isset($product_data['seo_description'])) {
            $metafields_to_update[] = [
                'namespace' => 'global',
                'key' => 'description_tag',
                'value' => sanitize_textarea_field($product_data['seo_description']),
                'type' => 'multi_line_text_field'
            ];
        }
        if (isset($product_data['url_handle'])) {
            $clean_data['handle'] = sanitize_title($product_data['url_handle']);
        }

        // Handle variants
        if (isset($product_data['variants']) && is_array($product_data['variants'])) {
            $clean_data['variants'] = [];
            foreach ($product_data['variants'] as $variant) {
                $clean_variant = [
                    'id' => absint($variant['id'] ?? 0),
                    'price' => sanitize_text_field($variant['price'] ?? '0.00'),
                    'compare_at_price' => !empty($variant['compare_at_price']) ? sanitize_text_field($variant['compare_at_price']) : null,
                    'sku' => sanitize_text_field($variant['sku'] ?? ''),
                    'barcode' => sanitize_text_field($variant['barcode'] ?? ''),
                    'weight' => floatval($variant['weight'] ?? 0),
                    'weight_unit' => sanitize_text_field($variant['weight_unit'] ?? 'lb'),
                    'taxable' => (isset($variant['taxable']) && ($variant['taxable'] === 'true' || $variant['taxable'] === true)),
                    'inventory_management' => sanitize_text_field($variant['inventory_management'] ?? 'shopify'),
                    'inventory_policy' => sanitize_text_field($variant['inventory_policy'] ?? 'deny'),
                    'fulfillment_service' => sanitize_text_field($variant['fulfillment_service'] ?? 'manual'),
                ];
                $clean_data['variants'][] = $clean_variant;
            }
        }

        if (!class_exists('SSPU_Shopify_API')) {
            wp_send_json_error(['message' => 'Shopify API class not found.']);
            return;
        }
        $shopify_api = new SSPU_Shopify_API();
        $response = $shopify_api->update_product($product_id, $clean_data);

        if (isset($response['product'])) {
            // Update metafields
            foreach ($metafields_to_update as $metafield) {
                $shopify_api->update_product_metafield($product_id, $metafield);
            }

            // Handle print methods metafields
            if (isset($product_data['print_methods']) && is_array($product_data['print_methods'])) {
                $print_methods_keys = ['silkscreen', 'uvprint', 'embroidery', 'sublimation', 'emboss', 'laserengrave'];
                foreach ($print_methods_keys as $method) {
                    $key_with_namespace = 'custom.' . $method;
                    // Check if the current method's full key is in the received print_methods array
                    $value = in_array($key_with_namespace, $product_data['print_methods']) ? 'true' : 'false';

                    $metafield_data = [
                        'namespace' => 'custom',
                        'key' => $method,
                        'value' => $value,
                        'type' => 'boolean'
                    ];

                    $shopify_api->update_product_metafield($product_id, $metafield_data);
                }
            }

            // Handle variant metafields (e.g., Volume Tiers)
            if (isset($product_data['variants']) && is_array($product_data['variants'])) {
                foreach ($product_data['variants'] as $variant_data) {
                    $variant_id = absint($variant_data['id'] ?? 0);

                    // Volume tiers
                    if ($variant_id && isset($variant_data['volume_tiers']) && is_array($variant_data['volume_tiers'])) {
                        // Ensure volume tiers are properly sanitized. Each tier should have min_quantity and price.
                        $sanitized_tiers = [];
                        foreach ($variant_data['volume_tiers'] as $tier) {
                            if (isset($tier['min_quantity']) && isset($tier['price'])) {
                                $sanitized_tiers[] = [
                                    'min_quantity' => absint($tier['min_quantity']),
                                    'price' => floatval($tier['price'])
                                ];
                            }
                        }

                        $metafield = [
                            'namespace' => 'custom', // Or a more specific namespace if needed
                            'key' => 'volume_tiers',
                            'value' => json_encode($sanitized_tiers),
                            'type' => 'json_string' // Use 'json_string' if Shopify expects a string, 'json' if it handles native JSON
                        ];
                        $shopify_api->update_variant_metafield($variant_id, $metafield);
                    }
                }
            }

            // Handle collections update
            if (isset($product_data['collection_ids']) && is_array($product_data['collection_ids'])) {
                $new_collections = array_map('absint', $product_data['collection_ids']);
                $current_collections = $shopify_api->get_product_collections($product_id);

                $to_add = array_diff($new_collections, $current_collections);
                if (!empty($to_add)) {
                    $shopify_api->add_to_collections($product_id, array_values($to_add));
                }

                $to_remove = array_diff($current_collections, $new_collections);
                foreach ($to_remove as $collection_id) {
                    $shopify_api->remove_from_collection($product_id, $collection_id);
                }
            }

            // Track changes and log activity
            $changes = $this->calculate_changes($original, $clean_data);
            if (class_exists('SSPU_Analytics')) {
                $analytics = new SSPU_Analytics();
                $analytics->log_activity(get_current_user_id(), 'live_product_updated', [
                    'product_id' => $product_id,
                    'product_title' => $clean_data['title'],
                    'changes' => $changes
                ]);
            }

            // Update the transient with the newly saved data
            set_transient('sspu_editing_product_' . get_current_user_id(), $response['product'], HOUR_IN_SECONDS);


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
     * Handle updating product images order
     */
    public function handle_update_product_images_order()
    {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');
        if (!current_user_can('upload_shopify_products')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }

        $product_id = absint($_POST['product_id']);
        $image_ids = array_map('absint', $_POST['image_ids']); // Ensure IDs are integers

        if (!$product_id || empty($image_ids)) {
            wp_send_json_error(['message' => 'Missing product ID or image IDs for reordering.']);
            return;
        }

        if (!class_exists('SSPU_Shopify_API')) {
            wp_send_json_error(['message' => 'Shopify API class not found.']);
            return;
        }
        $shopify_api = new SSPU_Shopify_API();
        $response = $shopify_api->update_images_order($product_id, $image_ids);

        if (isset($response['product'])) {
            wp_send_json_success(['message' => 'Image order updated successfully']);
        } else {
            $error_message = 'Failed to update image order.';
            if (isset($response['errors'])) {
                $error_message .= ' Shopify Error: ' . (is_array($response['errors']) ? json_encode($response['errors']) : $response['errors']);
            }
            wp_send_json_error(['message' => $error_message]);
        }
    }

    /**
     * Handle deleting product image
     */
    public function handle_delete_product_image()
    {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');
        if (!current_user_can('upload_shopify_products')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }

        $product_id = absint($_POST['product_id']);
        $image_id = absint($_POST['image_id']);

        if (!$product_id || !$image_id) {
            wp_send_json_error(['message' => 'Missing product ID or image ID for deletion.']);
            return;
        }

        if (!class_exists('SSPU_Shopify_API')) {
            wp_send_json_error(['message' => 'Shopify API class not found.']);
            return;
        }
        $shopify_api = new SSPU_Shopify_API();
        $response = $shopify_api->delete_product_image($product_id, $image_id);

        if (!isset($response['errors'])) { // Shopify API usually returns an empty array or 200 status for success
            wp_send_json_success(['message' => 'Image deleted successfully']);
        } else {
            $error_message = 'Failed to delete image.';
            if (isset($response['errors'])) {
                $error_message .= ' Shopify Error: ' . (is_array($response['errors']) ? json_encode($response['errors']) : $response['errors']);
            }
            wp_send_json_error(['message' => $error_message]);
        }
    }

    /**
     * Handle updating variant inventory
     */
    public function handle_update_variant_inventory()
    {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');
        if (!current_user_can('upload_shopify_products')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }

        $inventory_item_id = absint($_POST['inventory_item_id']);
        $available = intval($_POST['available']);
        $location_id = absint($_POST['location_id']);

        if (!$inventory_item_id || !$location_id) {
            wp_send_json_error(['message' => 'Missing inventory item ID or location ID.']);
            return;
        }

        if (!class_exists('SSPU_Shopify_API')) {
            wp_send_json_error(['message' => 'Shopify API class not found.']);
            return;
        }
        $shopify_api = new SSPU_Shopify_API();
        $response = $shopify_api->update_inventory_level($inventory_item_id, $available, $location_id);

        if (isset($response['inventory_level'])) {
            wp_send_json_success(['inventory_level' => $response['inventory_level'], 'message' => 'Inventory updated successfully']);
        } else {
            $error_message = 'Failed to update inventory.';
            if (isset($response['errors'])) {
                $error_message .= ' Shopify Error: ' . (is_array($response['errors']) ? json_encode($response['errors']) : $response['errors']);
            }
            wp_send_json_error(['message' => $error_message]);
        }
    }

    /**
     * Handle getting Shopify locations
     */
    public function handle_get_shopify_locations()
    {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');
        if (!current_user_can('upload_shopify_products')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }

        if (!class_exists('SSPU_Shopify_API')) {
            wp_send_json_error(['message' => 'Shopify API class not found.']);
            return;
        }
        $shopify_api = new SSPU_Shopify_API();
        $response = $shopify_api->get_locations();

        if (isset($response['locations'])) {
            wp_send_json_success(['locations' => $response['locations']]);
        } else {
            $error_message = 'Failed to get locations.';
            if (isset($response['errors'])) {
                $error_message .= ' Shopify Error: ' . (is_array($response['errors']) ? json_encode($response['errors']) : $response['errors']);
            }
            wp_send_json_error(['message' => $error_message]);
        }
    }

    /**
     * Handle updating product metafield
     */
    public function handle_update_product_metafield()
    {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');
        if (!current_user_can('upload_shopify_products')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }

        $product_id = absint($_POST['product_id']);
        $metafield = $_POST['metafield']; // This needs deep sanitization

        if (!$product_id || !is_array($metafield) || empty($metafield)) {
            wp_send_json_error(['message' => 'Missing product ID or metafield data.']);
            return;
        }

        $clean_metafield = [
            'namespace' => sanitize_text_field($metafield['namespace'] ?? ''),
            'key' => sanitize_text_field($metafield['key'] ?? ''),
            'value' => sanitize_textarea_field($metafield['value'] ?? ''),
            'type' => sanitize_text_field($metafield['type'] ?? '')
        ];

        if (isset($metafield['id'])) { // Metafield ID is present for existing metafields
            $clean_metafield['id'] = absint($metafield['id']);
        }

        if (!class_exists('SSPU_Shopify_API')) {
            wp_send_json_error(['message' => 'Shopify API class not found.']);
            return;
        }
        $shopify_api = new SSPU_Shopify_API();
        $response = $shopify_api->update_product_metafield($product_id, $clean_metafield);

        if (isset($response['metafield'])) {
            wp_send_json_success(['metafield' => $response['metafield'], 'message' => 'Metafield updated successfully']);
        } else {
            $error_message = 'Failed to update metafield.';
            if (isset($response['errors'])) {
                $error_message .= ' Shopify Error: ' . (is_array($response['errors']) ? json_encode($response['errors']) : $response['errors']);
            }
            wp_send_json_error(['message' => $error_message]);
        }
    }

    /**
     * Handle duplicating product
     */
    public function handle_duplicate_product()
    {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');
        if (!current_user_can('upload_shopify_products')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }

        $product_id = absint($_POST['product_id']);
        $new_title = sanitize_text_field($_POST['new_title']);

        if (!$product_id || empty($new_title)) {
            wp_send_json_error(['message' => 'Missing product ID or new title for duplication.']);
            return;
        }

        if (!class_exists('SSPU_Shopify_API')) {
            wp_send_json_error(['message' => 'Shopify API class not found.']);
            return;
        }
        $shopify_api = new SSPU_Shopify_API();
        $response = $shopify_api->duplicate_product($product_id, $new_title);

        if (isset($response['product'])) {
            if (class_exists('SSPU_Analytics')) {
                $analytics = new SSPU_Analytics();
                $analytics->log_activity(get_current_user_id(), 'product_duplicated', [
                    'original_id' => $product_id,
                    'new_id' => $response['product']['id'],
                    'new_title' => $new_title
                ]);
            }

            wp_send_json_success([
                'message' => 'Product duplicated successfully',
                'product' => $response['product']
            ]);
        } else {
            $error_message = 'Failed to duplicate product.';
            if (isset($response['errors'])) {
                $error_message .= ' Shopify Error: ' . (is_array($response['errors']) ? json_encode($response['errors']) : $response['errors']);
            }
            wp_send_json_error(['message' => $error_message]);
        }
    }

    /**
     * Handle getting vendors
     */
    public function handle_get_vendors()
    {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');
        if (!current_user_can('upload_shopify_products')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }

        if (!class_exists('SSPU_Shopify_API')) {
            wp_send_json_error(['message' => 'Shopify API class not found.']);
            return;
        }
        $shopify_api = new SSPU_Shopify_API();
        $vendors = $shopify_api->get_vendors();

        wp_send_json_success(['vendors' => $vendors]);
    }

    /**
     * Handle updating product collections
     */
    public function handle_update_product_collections()
    {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');
        if (!current_user_can('upload_shopify_products')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }

        $product_id = absint($_POST['product_id']);
        $collection_ids = array_map('absint', $_POST['collection_ids']);

        if (!$product_id || !is_array($collection_ids)) {
            wp_send_json_error(['message' => 'Missing product ID or invalid collection IDs.']);
            return;
        }

        if (!class_exists('SSPU_Shopify_API')) {
            wp_send_json_error(['message' => 'Shopify API class not found.']);
            return;
        }
        $shopify_api = new SSPU_Shopify_API();

        $current = $shopify_api->get_product_collections($product_id);

        $to_add = array_diff($collection_ids, $current);
        if (!empty($to_add)) {
            $response_add = $shopify_api->add_to_collections($product_id, array_values($to_add));
            if (isset($response_add['errors'])) {
                error_log('SSPU: Failed to add product to collections: ' . json_encode($response_add['errors']));
            }
        }

        $to_remove = array_diff($current, $collection_ids);
        foreach ($to_remove as $collection_id) {
            $response_remove = $shopify_api->remove_from_collection($product_id, $collection_id);
            if (isset($response_remove['errors'])) {
                error_log('SSPU: Failed to remove product from collection ' . $collection_id . ': ' . json_encode($response_remove['errors']));
            }
        }

        // Re-fetch current collections to confirm state
        $final_collections = $shopify_api->get_product_collections($product_id);
        wp_send_json_success(['message' => 'Collections updated', 'current_collections' => $final_collections]);
    }

    /**
     * Handle live editor autosave
     */
    public function handle_live_editor_autosave()
    {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');
        if (!current_user_can('upload_shopify_products')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }

        $product_id = absint($_POST['product_id']);
        // Assuming draft_data is a JSON string, decode it.
        $draft_data = json_decode(stripslashes($_POST['draft_data']), true);

        if (!$product_id || !is_array($draft_data)) {
            wp_send_json_error(['message' => 'Missing product ID or invalid draft data.']);
            return;
        }

        $draft_key = 'sspu_live_edit_draft_' . get_current_user_id() . '_' . $product_id;
        set_transient($draft_key, $draft_data, DAY_IN_SECONDS); // Store for 1 day

        wp_send_json_success(['message' => 'Draft saved for live editor']);
    }

    public function handle_mimic_all_variants()
    {
        if (!class_exists('SSPU_AI_Image_Editor')) {
            wp_send_json_error(['message' => 'AI Image Editor class not found.']);
            return;
        }
        $ai_editor = SSPU_AI_Image_Editor::get_instance();
        $ai_editor->handle_mimic_all_variants();
    }

    /**
     * Helper methods
     */
    private function download_image_from_url($image_url, $filename)
    {
        if (!current_user_can('upload_files')) {
            error_log('SSPU Helper: Permission denied for download_image_from_url for user ' . get_current_user_id());
            return false;
        }

        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $tmp = download_url($image_url, 60); // Increased timeout to 60 seconds

        if (is_wp_error($tmp)) {
            error_log('SSPU Helper Download Error: ' . $tmp->get_error_message() . ' for URL: ' . $image_url);
            return false;
        }

        if (!file_exists($tmp) || filesize($tmp) === 0) {
            @unlink($tmp);
            error_log('SSPU Helper Download Error: Downloaded file is empty or invalid for URL: ' . $image_url);
            return false;
        }

        if (filesize($tmp) > wp_max_upload_size()) {
            @unlink($tmp);
            error_log('SSPU Helper Download Error: File too large (max ' . size_format(wp_max_upload_size()) . ') for URL: ' . $image_url);
            return false;
        }


        $file_info = wp_check_filetype($tmp);
        if (!$file_info['ext']) {
            $url_ext = pathinfo(parse_url($image_url, PHP_URL_PATH), PATHINFO_EXTENSION);
            if (in_array(strtolower($url_ext), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $file_info['ext'] = strtolower($url_ext);
                $file_info['type'] = 'image/' . ($file_info['ext'] === 'jpg' ? 'jpeg' : $file_info['ext']);
            } else {
                // Fallback if no extension can be determined
                $file_info['ext'] = 'jpg';
                $file_info['type'] = 'image/jpeg';
            }
        }

        $file_array = [
            'name' => sanitize_file_name($filename) . '.' . $file_info['ext'],
            'tmp_name' => $tmp,
            'type' => $file_info['type'], // Added type for media_handle_sideload
            'error' => 0, // Assume no error as download_url already checked
            'size' => filesize($tmp), // Added size
        ];

        $attachment_id = media_handle_sideload($file_array, 0, null, [
            'post_title' => ucwords(str_replace('-', ' ', $filename)),
            'post_content' => 'Downloaded via SSPU from external source: ' . $image_url, // More detailed content
            'post_status' => 'inherit',
        ]);

        @unlink($tmp);

        if (is_wp_error($attachment_id)) {
            error_log('SSPU Helper Sideload Error: ' . $attachment_id->get_error_message() . ' for URL: ' . $image_url);
            return false;
        }

        return [
            'attachment_id' => $attachment_id,
            'url' => wp_get_attachment_url($attachment_id),
            'thumb_url' => wp_get_attachment_thumb_url($attachment_id) ?: wp_get_attachment_url($attachment_id),
        ];
    }

    private function create_attachment($file_path, $filename, $title = '')
    {
        // Check if file exists before trying to create attachment
        if (!file_exists($file_path)) {
            error_log('SSPU Helper: File not found for attachment creation: ' . $file_path);
            return false;
        }

        $wp_filetype = wp_check_filetype($filename, null);
        if (empty($wp_filetype['type'])) {
            error_log('SSPU Helper: Could not determine file type for attachment: ' . $filename);
            return false;
        }

        $attachment = [
            'post_mime_type' => $wp_filetype['type'],
            'post_title' => $title ?: sanitize_file_name(pathinfo($filename, PATHINFO_FILENAME)), // Use filename without extension
            'post_content' => '',
            'post_status' => 'inherit'
        ];

        // Insert the attachment post type
        $attach_id = wp_insert_attachment($attachment, $file_path);

        if (is_wp_error($attach_id)) {
            error_log('SSPU Helper: wp_insert_attachment failed: ' . $attach_id->get_error_message());
            return false;
        }

        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $file_path);
        wp_update_attachment_metadata($attach_id, $attach_data);

        return $attach_id;
    }

    /**
     * Calculate changes between original and new data
     */
    private function calculate_changes($original, $new_data)
    {
        $changes = [];

        // Return all new data keys if no original data to compare
        if (empty($original) || !is_array($original)) {
            return array_keys($new_data);
        }

        $fields_to_check = ['title', 'body_html', 'vendor', 'product_type', 'tags', 'published', 'handle'];

        foreach ($fields_to_check as $field) {
            $original_value = $original[$field] ?? null;
            $new_value = $new_data[$field] ?? null;

            // Handle boolean types correctly
            if (is_bool($original_value) && is_bool($new_value)) {
                if ($original_value !== $new_value) {
                    $changes[] = $field;
                }
            } else if (is_string($original_value) && is_string($new_value)) {
                // Trim and compare strings
                if (trim($original_value) !== trim($new_value)) {
                    $changes[] = $field;
                }
            } else {
                // General comparison for other types
                if ($original_value !== $new_value) {
                    $changes[] = $field;
                }
            }
        }

        // Compare variants
        if (isset($new_data['variants']) && is_array($new_data['variants']) && isset($original['variants']) && is_array($original['variants'])) {
            $original_variants_map = [];
            foreach ($original['variants'] as $variant) {
                $original_variants_map[$variant['id']] = $variant;
            }

            foreach ($new_data['variants'] as $new_variant) {
                $variant_id = $new_variant['id'] ?? null;
                if ($variant_id && isset($original_variants_map[$variant_id])) {
                    $original_variant = $original_variants_map[$variant_id];
                    $variant_fields_to_check = ['price', 'compare_at_price', 'sku', 'barcode', 'weight', 'weight_unit', 'taxable', 'inventory_management', 'inventory_policy', 'fulfillment_service'];
                    foreach ($variant_fields_to_check as $field) {
                        $orig_v_val = $original_variant[$field] ?? null;
                        $new_v_val = $new_variant[$field] ?? null;
                        
                        // Special handling for null vs empty string for non-required fields
                        if (($orig_v_val === null && $new_v_val === '') || ($orig_v_val === '' && $new_v_val === null)) {
                            // Consider them equal
                        } else if ($orig_v_val != $new_v_val) {
                            $changes[] = "variant_{$field}_" . $variant_id;
                        }
                    }
                    // Check volume_tiers separately if they are stored as JSON strings
                    $original_volume_tiers = json_decode($original_variant['metafields']['volume_tiers']['value'] ?? '[]', true); // Assuming metafields structure
                    $new_volume_tiers = $new_variant['volume_tiers'] ?? [];
                    if (json_encode($original_volume_tiers) !== json_encode($new_volume_tiers)) {
                        $changes[] = "variant_volume_tiers_" . $variant_id;
                    }

                } else if ($variant_id) {
                    // New variant added
                    $changes[] = 'new_variant_' . $variant_id;
                }
            }
            // Check for deleted variants (if original has more variants than new_data with matching IDs)
            if (count($original['variants']) > count($new_data['variants'])) {
                 $new_variant_ids = array_column($new_data['variants'], 'id');
                 foreach($original['variants'] as $orig_variant){
                     if(!in_array($orig_variant['id'], $new_variant_ids)){
                         $changes[] = 'deleted_variant_' . $orig_variant['id'];
                     }
                 }
            }
        }

        // Compare collections (simple array comparison)
        if (isset($new_data['collection_ids']) && is_array($new_data['collection_ids']) && isset($original['collection_ids']) && is_array($original['collection_ids'])) {
            $original_collections = array_map('absint', $original['collection_ids']);
            $new_collections = array_map('absint', $new_data['collection_ids']);

            sort($original_collections);
            sort($new_collections);

            if ($original_collections !== $new_collections) {
                $changes[] = 'collections';
            }
        }
        
        // Compare SEO metafields (global title_tag, description_tag)
        // This requires fetching actual metafields or storing them in the transient initially
        // Assuming original product data includes 'metafields' array for comparison
        $original_metafields = [];
        if (isset($original['metafields']) && is_array($original['metafields'])) {
            foreach ($original['metafields'] as $mf) {
                $original_metafields[$mf['namespace'] . '.' . $mf['key']] = $mf['value'];
            }
        }
        
        // For seo_title / title_tag
        $original_seo_title = $original_metafields['global.title_tag'] ?? '';
        $new_seo_title = sanitize_text_field($new_data['seo_title'] ?? '');
        if (trim($original_seo_title) !== trim($new_seo_title)) {
            $changes[] = 'seo_title';
        }

        // For seo_description / description_tag
        $original_seo_description = $original_metafields['global.description_tag'] ?? '';
        $new_seo_description = sanitize_textarea_field($new_data['seo_description'] ?? '');
        if (trim($original_seo_description) !== trim($new_seo_description)) {
            $changes[] = 'seo_description';
        }

        // Print methods metafields
        $original_print_methods = [];
        $print_method_keys = ['silkscreen', 'uvprint', 'embroidery', 'sublimation', 'emboss', 'laserengrave'];
        foreach ($print_method_keys as $method) {
            $key_lookup = 'custom.' . $method;
            $original_print_methods[$key_lookup] = ($original_metafields[$key_lookup] ?? 'false') === 'true';
        }

        $new_print_methods_array = $new_data['print_methods'] ?? []; // These come as 'custom.method'
        $new_print_methods = [];
        foreach ($print_method_keys as $method) {
            $key_lookup = 'custom.' . $method;
            $new_print_methods[$key_lookup] = in_array($key_lookup, $new_print_methods_array);
        }

        foreach ($print_method_keys as $method) {
            $key_lookup = 'custom.' . $method;
            if (($original_print_methods[$key_lookup] ?? false) !== ($new_print_methods[$key_lookup] ?? false)) {
                $changes[] = "print_method_{$method}";
            }
        }


        return array_values(array_unique($changes));
    }
}