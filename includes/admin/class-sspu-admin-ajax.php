<?php
if (!defined('WPINC')) {
    die;
}

class SSPU_Admin_Ajax
{
    /**
     * Initialize AJAX handlers (complete version with all functions)
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
        add_action('wp_ajax_sspu_suggest_price', [$this, 'handle_price_suggestion']);
        add_action('wp_ajax_sspu_generate_seo', [$this, 'handle_seo_generation']);
        add_action('wp_ajax_sspu_ai_suggest_all_pricing', [$this, 'handle_ai_suggest_all_pricing']);
        add_action('wp_ajax_sspu_estimate_weight', [$this, 'handle_estimate_weight']);
        add_action('wp_ajax_sspu_smart_rotate_image', [$this, 'handle_smart_rotate']);
        add_action('wp_ajax_sspu_mimic_all_variants', [$this, 'handle_mimic_all_variants']);

        // Image-related
        add_action('wp_ajax_sspu_validate_image', [$this, 'handle_validate_image']);
        add_action('wp_ajax_sspu_upload_images', [$this, 'handle_upload_images']);
        add_action('wp_ajax_sspu_scrape_alibaba_variants', [$this, 'handle_scrape_alibaba_variants']);
        add_action('wp_ajax_sspu_create_masked_image', [$this, 'handle_create_masked_image']);
        add_action('wp_ajax_sspu_create_masked_image_with_custom_mask', [$this, 'handle_create_masked_image_with_custom_mask']);
        add_action('wp_ajax_sspu_download_external_image', [$this, 'handle_download_external_image']);

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
        add_action('wp_ajax_sspu_test_shopify_connection', [$this, 'handle_test_shopify_connection']);

        // Alibaba-related
        add_action('wp_ajax_sspu_get_current_alibaba_url', [$this, 'handle_get_current_alibaba_url']);
        add_action('wp_ajax_sspu_fetch_alibaba_product_name', [$this, 'handle_fetch_alibaba_product_name']);
        add_action('wp_ajax_sspu_detect_color', [$this, 'handle_detect_color']);
        add_action('wp_ajax_sspu_fetch_alibaba_moq', [$this, 'handle_fetch_alibaba_moq']);
        add_action('wp_ajax_sspu_fetch_alibaba_description', [$this, 'handle_fetch_alibaba_description']);

        // Template-related
        add_action('wp_ajax_sspu_get_single_template_content', [$this, 'handle_get_single_template_content']);
        add_action('wp_ajax_sspu_get_image_templates', [$this, 'handle_get_image_templates']);
        add_action('wp_ajax_sspu_save_image_template', [$this, 'handle_save_image_template']);
        add_action('wp_ajax_sspu_delete_image_template', [$this, 'handle_delete_image_template']);

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

        // AI handlers
        add_action('wp_ajax_sspu_ai_edit_image', [$this, 'handle_ai_edit_image']);
        add_action('wp_ajax_sspu_get_chat_history', [$this, 'handle_get_chat_history']);
        add_action('wp_ajax_sspu_save_edited_image', [$this, 'handle_save_edited_image']);

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

        // Utility handlers
        add_action('wp_ajax_sspu_refresh_nonce', [$this, 'handle_refresh_nonce']);
    }

    /**
     * Delegate to product handler
     */
    public function delegate_to_product_handler()
{
    error_log('SSPU: delegate_to_product_handler called');
    error_log('SSPU: POST data keys: ' . implode(', ', array_keys($_POST)));

    try {
        if (!class_exists('SSPU_Admin_Product_Handler')) {
            error_log('SSPU: SSPU_Admin_Product_Handler class not found');
            wp_send_json_error(['message' => 'Product handler class not found']);
            return;
        }

        error_log('SSPU: Creating product handler instance...');
        $product_handler = new SSPU_Admin_Product_Handler();

        error_log('SSPU: Calling handle_product_submission...');
        $product_handler->handle_product_submission();

    } catch (Exception $e) {
        error_log('SSPU: Exception in delegate_to_product_handler: ' . $e->getMessage());
        error_log('SSPU: Stack trace: ' . $e->getTraceAsString());
        wp_send_json_error(['message' => 'Handler error: ' . $e->getMessage()]);
    }
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
            $image_ids = array_map('absint', $image_ids);

            $image_urls = isset($_POST['image_urls']) ? (array) $_POST['image_urls'] : [];
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
        $source_data = json_decode(stripslashes($_POST['source_data']), true);

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
            $variant_info = sanitize_text_field($_POST['variant_info']);

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

    public function handle_smart_rotate()
    {
        $ai_editor = SSPU_AI_Image_Editor::get_instance();
        $ai_editor->handle_smart_rotate();
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
     * Handle get collections
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

    /**
     * Handle create collection
     */
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
     * Handle refresh nonce
     */
/**
 * Handle refresh nonce - FIXED VERSION
 */
public function handle_refresh_nonce()
{
    // FIXED: Use sspu_ajax_nonce for verification
    check_ajax_referer('sspu_ajax_nonce', 'sspu_nonce');

    if (!current_user_can('upload_shopify_products')) {
        wp_send_json_error(['message' => 'Permission denied']);
        return;
    }

    wp_send_json_success([
        'nonce' => wp_create_nonce('sspu_ajax_nonce')  // FIXED: Create with correct action
    ]);
}

    /**
     * Handle scrape alibaba variants
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
     * Handle test shopify connection
     */
    public function handle_test_shopify_connection()
    {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');

        if (!current_user_can('upload_shopify_products')) {
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

    /**
     * Handle save draft
     */
    public function handle_save_draft() {
        try {
            check_ajax_referer('sspu_ajax_nonce', 'nonce');
            if (!current_user_can('upload_shopify_products')) {
                wp_send_json_error(['message' => 'Permission denied']);
                return;
            }

            $draft_data_raw = $_POST['draft_data'] ?? '';
            if (is_array($draft_data_raw)) {
                $draft_data = $draft_data_raw;
            } else {
                $draft_data = json_decode(stripslashes($draft_data_raw), true);
            }

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

    /**
     * Handle load draft
     */
    public function handle_load_draft() {
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

    /**
     * Handle auto save draft
     */
    public function handle_auto_save_draft() {
        try {
            check_ajax_referer('sspu_ajax_nonce', 'nonce');
            if (!current_user_can('upload_shopify_products')) {
                wp_send_json_error(['message' => 'Permission denied']);
                return;
            }

            $draft_data_raw = $_POST['draft_data'] ?? '';
            if (is_array($draft_data_raw)) {
                $draft_data = $draft_data_raw;
            } else {
                $draft_data = json_decode(stripslashes($draft_data_raw), true);
            }

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
     * Handle test OpenAI API
     */
    public function handle_test_openai_api() {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
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
    }

    /**
     * Handle test Gemini API
     */
    public function handle_test_gemini_api() {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
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
    }

    /**
     * Handle validate image
     */
    public function handle_validate_image() {
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
    }

    /**
     * Handle upload images
     */
    public function handle_upload_images() {
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
    }

    /**
     * Handle create masked image
     */
    public function handle_create_masked_image() {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');

        if (!current_user_can('upload_shopify_products')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }

        $image_id = absint($_POST['image_id']);
        $mask_coordinates = $_POST['mask_coordinates'];

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
        imagealphablending($mask_image, false);
        imagesavealpha($mask_image, true);

        imagecopy($mask_image, $source_image, 0, 0, 0, 0, $width, $height);

        // Make the cropped area transparent in the mask image
        $transparent = imagecolorallocatealpha($mask_image, 0, 0, 0, 127);
        imagefilledrectangle($mask_image, $crop_x, $crop_y,
            $crop_x + $crop_width,
            $crop_y + $crop_height,
            $transparent);

        $upload_dir = wp_upload_dir();
        $base_name = pathinfo($image_path, PATHINFO_FILENAME);
        $timestamp = time();

        $background_filename = $base_name . '_background_' . $timestamp . '.png';
        $background_path = $upload_dir['path'] . '/' . $background_filename;

        if (!wp_mkdir_p($upload_dir['path'])) {
            imagedestroy($source_image);
            imagedestroy($background_image);
            imagedestroy($mask_image);
            wp_send_json_error(['message' => 'Unable to create upload directory.']);
            return;
        }

        imagepng($background_image, $background_path, 9);

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
     * Handle create masked image with custom mask
     */
    /**
 * Handle create masked image with custom mask
 */
/**
 * Handle create masked image with custom mask
 */
public function handle_create_masked_image_with_custom_mask() {
    check_ajax_referer('sspu_ajax_nonce', 'nonce');

    if (!current_user_can('upload_shopify_products')) {
        wp_send_json_error(['message' => 'Permission denied']);
        return;
    }

    $image_id = absint($_POST['image_id']);
    $custom_mask_url = esc_url_raw($_POST['custom_mask_url']);
    $mask_adjustments = isset($_POST['mask_adjustments']) ? $_POST['mask_adjustments'] : [];

    if (!$image_id || !$custom_mask_url) {
        wp_send_json_error(['message' => 'Missing required parameters.']);
        return;
    }

    // Get adjustment values
    $mask_size = isset($mask_adjustments['size']) ? floatval($mask_adjustments['size']) / 100 : 1.0;
    $mask_x = isset($mask_adjustments['x']) ? floatval($mask_adjustments['x']) / 100 : 0.5;
    $mask_y = isset($mask_adjustments['y']) ? floatval($mask_adjustments['y']) / 100 : 0.5;

    // Get the original image path
    $image_path = get_attached_file($image_id);
    if (!$image_path || !file_exists($image_path)) {
        wp_send_json_error(['message' => 'Original image file not found.']);
        return;
    }

    // Download the custom mask to a temporary file
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    $mask_tmp = download_url($custom_mask_url, 300);

    if (is_wp_error($mask_tmp)) {
        wp_send_json_error(['message' => 'Failed to download custom mask: ' . $mask_tmp->get_error_message()]);
        return;
    }

    $image_info = getimagesize($image_path);
    if (!$image_info) {
        @unlink($mask_tmp);
        wp_send_json_error(['message' => 'Invalid original image file.']);
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
            if (function_exists('imagecreatefromwebp')) {
                $source_image = imagecreatefromwebp($image_path);
            }
            break;
    }

    if (!$source_image) {
        @unlink($mask_tmp);
        wp_send_json_error(['message' => 'Failed to create image resource from original file.']);
        return;
    }

    // Load the custom mask
    $mask_info = getimagesize($mask_tmp);
    if (!$mask_info) {
        imagedestroy($source_image);
        @unlink($mask_tmp);
        wp_send_json_error(['message' => 'Invalid mask image file.']);
        return;
    }

    $mask_original = null;
    switch ($mask_info['mime']) {
        case 'image/jpeg':
            $mask_original = imagecreatefromjpeg($mask_tmp);
            break;
        case 'image/png':
            $mask_original = imagecreatefrompng($mask_tmp);
            break;
        case 'image/gif':
            $mask_original = imagecreatefromgif($mask_tmp);
            break;
        case 'image/webp':
            if (function_exists('imagecreatefromwebp')) {
                $mask_original = imagecreatefromwebp($mask_tmp);
            }
            break;
    }

    @unlink($mask_tmp); // Clean up temp file

    if (!$mask_original) {
        imagedestroy($source_image);
        wp_send_json_error(['message' => 'Failed to load custom mask image.']);
        return;
    }

    // Create adjusted mask
    $mask_orig_width = imagesx($mask_original);
    $mask_orig_height = imagesy($mask_original);

    // Calculate scaled dimensions
    $scaled_width = $mask_orig_width * $mask_size;
    $scaled_height = $mask_orig_height * $mask_size;

    // Calculate position
    $pos_x = ($width * $mask_x) - ($scaled_width / 2);
    $pos_y = ($height * $mask_y) - ($scaled_height / 2);

    // Create a new mask image with adjustments applied
    $mask_image = imagecreatetruecolor($width, $height);
    imagealphablending($mask_image, false);
    imagesavealpha($mask_image, true);

    // Fill with black (areas to be made transparent)
    $black = imagecolorallocate($mask_image, 0, 0, 0);
    imagefill($mask_image, 0, 0, $black);

    // Copy and resize the mask to the specified position
    imagecopyresampled(
        $mask_image,
        $mask_original,
        $pos_x, $pos_y,  // Destination position
        0, 0,            // Source position
        $scaled_width, $scaled_height,   // Destination size
        $mask_orig_width, $mask_orig_height  // Source size
    );

    imagedestroy($mask_original);

    // Create background image (full original image)
    $background_image = imagecreatetruecolor($width, $height);
    imagecopy($background_image, $source_image, 0, 0, 0, 0, $width, $height);

    // Create final mask image by applying the custom mask
    $final_mask = imagecreatetruecolor($width, $height);
    imagealphablending($final_mask, false);
    imagesavealpha($final_mask, true);

    // Copy original image
    imagecopy($final_mask, $source_image, 0, 0, 0, 0, $width, $height);

    // Apply mask - white/light areas in mask = design area (kept), black = transparent
    for ($x = 0; $x < $width; $x++) {
        for ($y = 0; $y < $height; $y++) {
            $mask_color = imagecolorat($mask_image, $x, $y);
            $mask_rgba = imagecolorsforindex($mask_image, $mask_color);

            // Calculate brightness (0-255)
            $brightness = ($mask_rgba['red'] + $mask_rgba['green'] + $mask_rgba['blue']) / 3;

            // If dark (brightness < 128), make transparent
            if ($brightness < 128) {
                $transparent = imagecolorallocatealpha($final_mask, 0, 0, 0, 127);
                imagesetpixel($final_mask, $x, $y, $transparent);
            }
        }
    }

    $upload_dir = wp_upload_dir();
    $base_name = pathinfo($image_path, PATHINFO_FILENAME);
    $timestamp = time();

    // Save background image
    $background_filename = $base_name . '_background_custom_' . $timestamp . '.png';
    $background_path = $upload_dir['path'] . '/' . $background_filename;
    imagepng($background_image, $background_path, 9);

    // Save mask image
    $mask_filename = $base_name . '_mask_custom_' . $timestamp . '.png';
    $mask_path = $upload_dir['path'] . '/' . $mask_filename;
    imagepng($final_mask, $mask_path, 9);

    // Clean up
    imagedestroy($source_image);
    imagedestroy($background_image);
    imagedestroy($mask_image);
    imagedestroy($final_mask);

    // Create WordPress attachments
    $background_attachment_id = $this->create_attachment($background_path, $background_filename, 'Custom Mask Background - ' . $base_name);
    $mask_attachment_id = $this->create_attachment($mask_path, $mask_filename, 'Custom Mask - ' . $base_name);

    if (!$background_attachment_id || !$mask_attachment_id) {
        @unlink($background_path);
        @unlink($mask_path);
        wp_send_json_error(['message' => 'Failed to create WordPress attachments.']);
        return;
    }

    $background_url = wp_get_attachment_url($background_attachment_id);
    $mask_url = wp_get_attachment_url($mask_attachment_id);

    if (class_exists('SSPU_Analytics')) {
        $analytics = new SSPU_Analytics();
        $analytics->log_activity(get_current_user_id(), 'custom_mask_applied', [
            'original_image_id' => $image_id,
            'background_id' => $background_attachment_id,
            'mask_id' => $mask_attachment_id,
            'custom_mask_url' => $custom_mask_url,
            'adjustments' => $mask_adjustments
        ]);
    }

    wp_send_json_success([
        'background_url' => $background_url,
        'mask_url' => $mask_url,
        'background_id' => $background_attachment_id,
        'mask_id' => $mask_attachment_id,
        'message' => 'Design files created with custom mask successfully'
    ]);
}


    /**
     * Handle download external image - IMPLEMENTED DIRECTLY
     */
    public function handle_download_external_image() {
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
            'post_content' => 'Downloaded from external source: ' . $image_url,
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

    // Delegate remaining handlers to their respective classes
    public function handle_get_current_alibaba_url() {
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

    public function handle_fetch_alibaba_product_name() {
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

        $response = wp_remote_get($alibaba_url, ['timeout' => 30]);

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => 'Failed to fetch page: ' . $response->get_error_message()]);
            return;
        }

        $body = wp_remote_retrieve_body($response);

        preg_match('/<h1[^>]*class="title"[^>]*>(.*?)<\/h1>/is', $body, $matches_class);
        preg_match('/<h1[^>]*itemprop="name"[^>]*>(.*?)<\/h1>/is', $body, $matches_itemprop);
        preg_match('/<h1[^>]*>(.*?)<\/h1>/is', $body, $matches_generic);

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
            $product_name = preg_replace('/\s+/', ' ', $product_name);

            wp_send_json_success(['product_name' => $product_name]);
        } else {
            wp_send_json_error(['message' => 'Could not find product title on the Alibaba page. The page structure might have changed.']);
        }
    }

    public function handle_detect_color() {
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
                'model' => 'gpt-4o',
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

    public function handle_fetch_alibaba_moq() {
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

        // Check if the method exists before calling it
        if (!method_exists($retriever, 'fetch_alibaba_moq')) {
            wp_send_json_error(['message' => 'MOQ fetching method not available.']);
            return;
        }

        $moq_data = $retriever->fetch_alibaba_moq($alibaba_url);

        if ($moq_data && isset($moq_data['moq'])) {
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

    public function handle_fetch_alibaba_description() {
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

        // Check if the method exists before calling it
        if (!method_exists($retriever, 'fetch_alibaba_description')) {
            wp_send_json_error(['message' => 'Description fetching method not available.']);
            return;
        }

        $description_data = $retriever->fetch_alibaba_description($alibaba_url);

        if ($description_data && (!empty($description_data['attributes']) || !empty($description_data['description']) || !empty($description_data['features']))) {
            $html_content = '';

            if (!empty($description_data['attributes'])) {
                // Check if format method exists
                if (method_exists($retriever, 'format_attributes_as_table')) {
                    $html_content .= $retriever->format_attributes_as_table($description_data['attributes']);
                } else {
                    // Fallback formatting
                    $html_content .= '<table class="attributes-table">';
                    foreach ($description_data['attributes'] as $key => $value) {
                        $html_content .= '<tr><td><strong>' . esc_html($key) . '</strong></td><td>' . esc_html($value) . '</td></tr>';
                    }
                    $html_content .= '</table>';
                }
            }

            if (!empty($description_data['description'])) {
                $html_content .= '<p>' . wp_kses_post($description_data['description']) . '</p>';
            }

            if (!empty($description_data['features'])) {
                $html_content .= '<h4>Product Features:</h4><ul>';
                foreach ($description_data['features'] as $feature) {
                    $html_content .= '<li>' . wp_kses_post($feature) . '</li>';
                }
                $html_content .= '</ul>';
            }

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

    // Template handlers
    public function handle_get_single_template_content() {
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
            'content' => sanitize_textarea_field($template_data->prompt)
        ]);
    }

    // Analytics proxy handlers
    public function handle_get_analytics_proxy() {
        if (!class_exists('SSPU_Analytics')) {
            wp_send_json_error(['message' => 'Analytics class not found.']);
            return;
        }
        $analytics = new SSPU_Analytics();
        $analytics->handle_get_analytics();
    }

    public function handle_export_analytics() {
        wp_send_json_error(['message' => 'Export functionality not yet implemented']);
    }

    public function handle_get_user_activity_proxy() {
        if (!class_exists('SSPU_Analytics')) {
            wp_send_json_error(['message' => 'Analytics class not found.']);
            return;
        }
        $analytics = new SSPU_Analytics();
        $analytics->handle_get_user_activity();
    }

    public function handle_global_search_proxy() {
        if (!class_exists('SSPU_Search')) {
            wp_send_json_error(['message' => 'Search class not found.']);
            return;
        }
        $search = new SSPU_Search();
        $search->handle_global_search();
    }

    public function handle_get_search_filters_proxy() {
        if (!class_exists('SSPU_Search')) {
            wp_send_json_error(['message' => 'Search class not found.']);
            return;
        }
        $search = new SSPU_Search();
        $search->handle_get_search_filters();
    }

    // Alibaba queue handlers
    public function handle_request_alibaba_url() {
        if (!class_exists('SSPU_Alibaba_Queue')) {
            wp_send_json_error(['message' => 'Alibaba Queue class not found.']);
            return;
        }
        $queue = new SSPU_Alibaba_Queue();
        $queue->handle_request_url();
    }

    public function handle_complete_alibaba_url() {
        if (!class_exists('SSPU_Alibaba_Queue')) {
            wp_send_json_error(['message' => 'Alibaba Queue class not found.']);
            return;
        }
        $queue = new SSPU_Alibaba_Queue();
        $queue->handle_complete_url();
    }

    public function handle_release_alibaba_url() {
        if (!class_exists('SSPU_Alibaba_Queue')) {
            wp_send_json_error(['message' => 'Alibaba Queue class not found.']);
            return;
        }
        $queue = new SSPU_Alibaba_Queue();
        $queue->handle_release_url();
    }

    // Image retriever handlers
    public function handle_retrieve_alibaba_images() {
        if (!class_exists('SSPU_Image_Retriever')) {
            wp_send_json_error(['message' => 'Image Retriever class not found.']);
            return;
        }
        $retriever = new SSPU_Image_Retriever();
        $retriever->handle_retrieve_images();
    }

    // AI handlers
    public function handle_ai_edit_image() {
        if (!class_exists('SSPU_AI_Image_Editor')) {
            wp_send_json_error(['message' => 'AI Image Editor class not found.']);
            return;
        }
        $ai_editor = SSPU_AI_Image_Editor::get_instance();
        $ai_editor->handle_ai_edit();
    }

    public function handle_get_chat_history() {
        if (!class_exists('SSPU_AI_Image_Editor')) {
            wp_send_json_error(['message' => 'AI Image Editor class not found.']);
            return;
        }
        $ai_editor = SSPU_AI_Image_Editor::get_instance();
        $ai_editor->handle_get_chat_history();
    }

    public function handle_save_edited_image() {
        if (!class_exists('SSPU_AI_Image_Editor')) {
            wp_send_json_error(['message' => 'AI Image Editor class not found.']);
            return;
        }
        $ai_editor = SSPU_AI_Image_Editor::get_instance();
        $ai_editor->handle_save_edited_image();
    }

    // Template handlers
    public function handle_get_image_templates() {
        if (!class_exists('SSPU_Image_Templates')) {
            wp_send_json_error(['message' => 'Image Templates class not found.']);
            return;
        }
        $templates = new SSPU_Image_Templates();
        $templates->handle_get_templates();
    }

    public function handle_save_image_template() {
        if (!class_exists('SSPU_Image_Templates')) {
            wp_send_json_error(['message' => 'Image Templates class not found.']);
            return;
        }
        $templates = new SSPU_Image_Templates();
        $templates->handle_save_template();
    }

    public function handle_delete_image_template() {
        if (!class_exists('SSPU_Image_Templates')) {
            wp_send_json_error(['message' => 'Image Templates class not found.']);
            return;
        }
        $templates = new SSPU_Image_Templates();
        $templates->handle_delete_template();
    }

    // Mimic handlers
    public function handle_get_mimic_images() {
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
                'image_id' => $attachment->ID,
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

    public function handle_mimic_image() {
        if (!class_exists('SSPU_AI_Image_Editor')) {
            wp_send_json_error(['message' => 'AI Image Editor class not found.']);
            return;
        }
        $ai_editor = SSPU_AI_Image_Editor::get_instance();
        $ai_editor->handle_mimic_style();
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
        $product_data = $_POST['product_data'];

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
                            'namespace' => 'custom',
                            'key' => 'volume_tiers',
                            'value' => json_encode($sanitized_tiers),
                            'type' => 'json_string'
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
        $image_ids = array_map('absint', $_POST['image_ids']);

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

        if (!isset($response['errors'])) {
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
        $metafield = $_POST['metafield'];

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

        if (isset($metafield['id'])) {
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
        $draft_data = json_decode(stripslashes($_POST['draft_data']), true);

        if (!$product_id || !is_array($draft_data)) {
            wp_send_json_error(['message' => 'Missing product ID or invalid draft data.']);
            return;
        }

        $draft_key = 'sspu_live_edit_draft_' . get_current_user_id() . '_' . $product_id;
        set_transient($draft_key, $draft_data, DAY_IN_SECONDS);

        wp_send_json_success(['message' => 'Draft saved for live editor']);
    }

    /**
     * Helper method to create WordPress attachment
     */
    private function create_attachment($file_path, $filename, $title = '') {
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
            'post_title' => $title ?: sanitize_file_name(pathinfo($filename, PATHINFO_FILENAME)),
            'post_content' => '',
            'post_status' => 'inherit'
        ];

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
     * Download image from URL helper method
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

        $tmp = download_url($image_url, 60);

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
                $file_info['ext'] = 'jpg';
                $file_info['type'] = 'image/jpeg';
            }
        }

        $file_array = [
            'name' => sanitize_file_name($filename) . '.' . $file_info['ext'],
            'tmp_name' => $tmp,
            'type' => $file_info['type'],
            'error' => 0,
            'size' => filesize($tmp),
        ];

        $attachment_id = media_handle_sideload($file_array, 0, null, [
            'post_title' => ucwords(str_replace('-', ' ', $filename)),
            'post_content' => 'Downloaded via SSPU from external source: ' . $image_url,
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

    /**
     * Calculate changes between original and new data
     */
    private function calculate_changes($original, $new_data)
    {
        $changes = [];

        if (empty($original) || !is_array($original)) {
            return array_keys($new_data);
        }

        $fields_to_check = ['title', 'body_html', 'vendor', 'product_type', 'tags', 'published', 'handle'];

        foreach ($fields_to_check as $field) {
            $original_value = $original[$field] ?? null;
            $new_value = $new_data[$field] ?? null;

            if (is_bool($original_value) && is_bool($new_value)) {
                if ($original_value !== $new_value) {
                    $changes[] = $field;
                }
            } else if (is_string($original_value) && is_string($new_value)) {
                if (trim($original_value) !== trim($new_value)) {
                    $changes[] = $field;
                }
            } else {
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

                        if (($orig_v_val === null && $new_v_val === '') || ($orig_v_val === '' && $new_v_val === null)) {
                            // Consider them equal
                        } else if ($orig_v_val != $new_v_val) {
                            $changes[] = "variant_{$field}_" . $variant_id;
                        }
                    }

                    $original_volume_tiers = json_decode($original_variant['metafields']['volume_tiers']['value'] ?? '[]', true);
                    $new_volume_tiers = $new_variant['volume_tiers'] ?? [];
                    if (json_encode($original_volume_tiers) !== json_encode($new_volume_tiers)) {
                        $changes[] = "variant_volume_tiers_" . $variant_id;
                    }

                } else if ($variant_id) {
                    $changes[] = 'new_variant_' . $variant_id;
                }
            }

            if (count($original['variants']) > count($new_data['variants'])) {
                 $new_variant_ids = array_column($new_data['variants'], 'id');
                 foreach($original['variants'] as $orig_variant){
                     if(!in_array($orig_variant['id'], $new_variant_ids)){
                         $changes[] = 'deleted_variant_' . $orig_variant['id'];
                     }
                 }
            }
        }

        // Compare collections
        if (isset($new_data['collection_ids']) && is_array($new_data['collection_ids']) && isset($original['collection_ids']) && is_array($original['collection_ids'])) {
            $original_collections = array_map('absint', $original['collection_ids']);
            $new_collections = array_map('absint', $new_data['collection_ids']);

            sort($original_collections);
            sort($new_collections);

            if ($original_collections !== $new_collections) {
                $changes[] = 'collections';
            }
        }

        return array_values(array_unique($changes));
    }
}