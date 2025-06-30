<?php
if (!defined('WPINC')) {
    die;
}

/**
 * SSPU AJAX Handler Registration
 */
add_action('wp_ajax_sspu_check_server_capabilities', 'sspu_check_server_capabilities');
// Note: Other AJAX actions are registered within the SSPU_Admin_Ajax class init method.


/**
 * Enqueue additional scripts needed for mask functionality
 */
add_action('admin_enqueue_scripts', 'sspu_enqueue_mask_scripts');

function sspu_enqueue_mask_scripts($hook) {
    // Only load on your plugin pages
    if (strpos($hook, 'sspu') === false) {
        return;
    }

    // Enqueue Cropper.js for mask selection
    wp_enqueue_script(
        'cropperjs',
        'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js',
        array('jquery'), // Depends on jQuery
        '1.5.13',
        true
    );

    wp_enqueue_style(
        'cropperjs',
        'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css',
        array(),
        '1.5.13'
    );

    // Add inline styles for proper mask display
    wp_add_inline_style('sspu-admin-style', '
        /* Mask Preview Modal Styles */
        .sspu-lightbox-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            z-index: 100000;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .sspu-lightbox-content {
            background: #fff;
            padding: 30px;
            max-width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        .sspu-lightbox-close {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 30px;
            font-weight: bold;
            color: #999;
            cursor: pointer;
            transition: color 0.3s;
            line-height: 1;
            padding: 5px;
        }

        .sspu-lightbox-close:hover,
        .sspu-lightbox-close:focus {
            color: #333;
            text-decoration: none;
        }

        #mask-preview-container {
            display: inline-block;
            position: relative;
            line-height: 0;
            background: #f0f0f0;
            min-width: 400px;
            min-height: 400px;
        }

        #mask-overlay {
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            height: 100% !important;
            pointer-events: none;
        }

        #preview-mask-image {
            pointer-events: auto !important;
            cursor: move;
            position: absolute !important;
            display: block !important;
        }

        #preview-base-image {
            display: block;
            position: relative;
            z-index: 1;
        }

        /* Cropper.js customization */
        .cropper-view-box {
            outline: 1px solid #39f;
        }

        .cropper-line {
            background-color: #39f;
        }

        .cropper-point {
            background-color: #39f;
        }

        /* Design files status */
        .sspu-design-files-status {
            font-size: 12px;
            color: #46b450;
            margin-top: 5px;
            font-weight: 500;
        }

        /* Mask adjustment controls */
        #mask-preview-modal input[type="range"] {
            width: 100%;
            margin: 5px 0;
        }

        #mask-preview-modal button {
            margin: 5px;
        }
    ');

    // Add JavaScript to check for required libraries
    wp_add_inline_script('sspu-variants', '
        jQuery(document).ready(function($) {
            // Check if Cropper.js is loaded
            if (typeof Cropper === "undefined") {
                console.error("SSPU: Cropper.js library not loaded!");
            }

            // Check if wp.media is available
            if (typeof wp === "undefined" || typeof wp.media === "undefined") {
                console.error("SSPU: WordPress media library not available!");
            }
        });
    ');
}

/**
 * Additional helper function to test if image libraries are available
 */
function sspu_check_image_libraries() {
    $libraries = array();

    // Check for Imagick
    if (class_exists('Imagick')) {
        $libraries['imagick'] = true;
        $imagick = new Imagick();
        $libraries['imagick_version'] = $imagick->getVersion();
    } else {
        $libraries['imagick'] = false;
    }

    // Check for GD
    if (extension_loaded('gd') && function_exists('gd_info')) {
        $libraries['gd'] = true;
        $libraries['gd_info'] = gd_info();
    } else {
        $libraries['gd'] = false;
    }

    return $libraries;
}

/**
 * AJAX endpoint to check server capabilities (Global Scope)
 */
function sspu_check_server_capabilities() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'sspu_ajax_nonce')) {
        wp_send_json_error(['message' => 'Invalid security token']);
    }

    $capabilities = array(
        'libraries' => sspu_check_image_libraries(),
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size' => ini_get('post_max_size'),
        'memory_limit' => ini_get('memory_limit'),
        'max_execution_time' => ini_get('max_execution_time')
    );

    wp_send_json_success($capabilities);
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
        add_action('wp_ajax_sspu_check_server_capabilities', [$this, 'handle_check_server_capabilities']);
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
    public function handle_refresh_nonce()
    {
        check_ajax_referer('sspu_ajax_nonce', 'sspu_nonce');

        if (!current_user_can('upload_shopify_products')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }

        wp_send_json_success([
            'nonce' => wp_create_nonce('sspu_ajax_nonce')
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
    public function handle_save_draft()
    {
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

    /**
     * Handle auto save draft
     */
    public function handle_auto_save_draft()
    {
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
    public function handle_test_openai_api()
    {
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
    public function handle_test_gemini_api()
    {
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
    public function handle_validate_image()
    {
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
    public function handle_upload_images()
    {
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
     * AJAX handler for creating masked image with coordinates.
     * This handles the rectangular mask selection from the Cropper.js tool
     */
    public function handle_create_masked_image()
    {
        // Verify nonce
        check_ajax_referer('sspu_ajax_nonce', 'nonce');

        // Get parameters
        $image_id = isset($_POST['image_id']) ? intval($_POST['image_id']) : 0;
        $mask_coordinates = isset($_POST['mask_coordinates']) ? $_POST['mask_coordinates'] : null;

        // Validate inputs
        if (!$image_id || !$mask_coordinates) {
            wp_send_json_error(['message' => 'Missing required parameters']);
        }

        // Validate coordinates
        $x = isset($mask_coordinates['x']) ? intval($mask_coordinates['x']) : 0;
        $y = isset($mask_coordinates['y']) ? intval($mask_coordinates['y']) : 0;
        $width = isset($mask_coordinates['width']) ? intval($mask_coordinates['width']) : 0;
        $height = isset($mask_coordinates['height']) ? intval($mask_coordinates['height']) : 0;

        if ($width <= 0 || $height <= 0) {
            wp_send_json_error(['message' => 'Invalid mask dimensions']);
        }

        // Get the original image
        $original_image_path = get_attached_file($image_id);
        if (!$original_image_path || !file_exists($original_image_path)) {
            wp_send_json_error(['message' => 'Original image not found']);
        }

        try {
            // Check if Imagick is available
            if (class_exists('Imagick')) {
                $result = $this->create_masked_image_imagick_rect($original_image_path, $image_id, $x, $y, $width, $height);
            } else {
                // Fallback to GD
                $result = $this->create_masked_image_gd_rect($original_image_path, $image_id, $x, $y, $width, $height);
            }

            if ($result['success']) {
                wp_send_json_success([
                    'background_url' => $result['background_url'],
                    'mask_url' => $result['mask_url'],
                    'message' => 'Design files created successfully'
                ]);
            } else {
                wp_send_json_error(['message' => $result['error']]);
            }

        } catch (Exception $e) {
            wp_send_json_error(['message' => 'Error processing image: ' . $e->getMessage()]);
        }
    }

    /**
     * AJAX handler for creating masked image with a custom mask file and adjustments.
     * UPDATED to use new simplified and reliable logic.
     */
    public function handle_create_masked_image_with_custom_mask()
    {
        try {
            error_log('SSPU: === CUSTOM MASK HANDLER START ===');

            // Verify nonce
            check_ajax_referer('sspu_ajax_nonce', 'nonce');

            // Get parameters
            $image_id = isset($_POST['image_id']) ? intval($_POST['image_id']) : 0;
            $custom_mask_url = isset($_POST['custom_mask_url']) ? esc_url_raw($_POST['custom_mask_url']) : '';
            $mask_adjustments = isset($_POST['mask_adjustments']) ? $_POST['mask_adjustments'] : [];

            error_log('SSPU: Image ID: ' . $image_id);
            error_log('SSPU: Mask URL: ' . $custom_mask_url);

            // Validate inputs
            if (!$image_id || !$custom_mask_url) {
                wp_send_json_error(['message' => 'Missing required parameters']);
                return;
            }

            // Get the original image
            $original_image_path = get_attached_file($image_id);
            if (!$original_image_path || !file_exists($original_image_path)) {
                wp_send_json_error(['message' => 'Original image not found']);
                return;
            }

            // Check if Imagick is available
            if (class_exists('Imagick')) {
                $result = $this->create_masked_image_with_custom_mask_imagick($original_image_path, $image_id, $custom_mask_url, $mask_adjustments);
            } else {
                error_log('SSPU: Imagick not available, using GD');
                $result = $this->create_masked_image_with_custom_mask_gd($original_image_path, $image_id, $custom_mask_url, $mask_adjustments);
            }

            if ($result['success']) {
                wp_send_json_success($result['data']);
            } else {
                wp_send_json_error(['message' => $result['error']]);
            }

        } catch (Exception $e) {
            error_log('SSPU: Exception in mask handler: ' . $e->getMessage());
            wp_send_json_error(['message' => 'Error: ' . $e->getMessage()]);
        }
    }

    /**
     * Handle download external image
     */
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
            'content' => sanitize_textarea_field($template_data->prompt)
        ]);
    }

    // Analytics proxy handlers
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

    // Alibaba queue handlers
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

    // Image retriever handlers
    public function handle_retrieve_alibaba_images()
    {
        if (!class_exists('SSPU_Image_Retriever')) {
            wp_send_json_error(['message' => 'Image Retriever class not found.']);
            return;
        }
        $retriever = new SSPU_Image_Retriever();
        $retriever->handle_retrieve_images();
    }

    // AI handlers
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

    // Template handlers
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

    // Mimic handlers
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
     * Handle AJAX endpoint to check server capabilities (Class Method)
     */
    public function handle_check_server_capabilities()
    {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');

        $capabilities = array(
            'libraries' => $this->check_image_libraries(),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time')
        );

        wp_send_json_success($capabilities);
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Create masked image using Imagick (for rectangular selections)
     */
    private function create_masked_image_imagick_rect($original_path, $image_id, $x, $y, $width, $height)
    {
        try {
            // Load original image
            $original = new Imagick($original_path);
            $original_width = $original->getImageWidth();
            $original_height = $original->getImageHeight();

            // Ensure coordinates are within bounds
            $x = max(0, min($x, $original_width - 1));
            $y = max(0, min($y, $original_height - 1));
            $width = min($width, $original_width - $x);
            $height = min($height, $original_height - $y);

            // Create background image (original with masked area transparent)
            $background = clone $original;
            $background->setImageFormat('png');
            $background->setImageAlphaChannel(Imagick::ALPHACHANNEL_SET);

            // Create a transparent rectangle for the design area
            $transparent_overlay = new Imagick();
            $transparent_overlay->newImage($width, $height, new ImagickPixel('transparent'));
            $transparent_overlay->setImageFormat('png');

            // Apply the transparent rectangle to the background
            $background->compositeImage($transparent_overlay, Imagick::COMPOSITE_COPY, $x, $y);

            // Save background
            $upload_dir = wp_upload_dir();
            $filename_base = 'design-' . $image_id . '-' . time();
            $background_filename = $filename_base . '-background.png';
            $background_path = $upload_dir['path'] . '/' . $background_filename;
            $background->writeImage($background_path);

            // Create visual mask (shows the design area clearly on top of the original)
            $visual_mask = clone $original;
            $visual_mask->setImageFormat('png');

            // Create a semi-transparent white overlay for the design area
            $highlight = new Imagick();
            $highlight->newImage($width, $height, new ImagickPixel('rgba(255, 255, 255, 0.5)'));

            // Composite the highlight onto the visual mask
            $visual_mask->compositeImage($highlight, Imagick::COMPOSITE_OVER, $x, $y);

            // Save visual mask
            $mask_filename = $filename_base . '-mask.png';
            $mask_path = $upload_dir['path'] . '/' . $mask_filename;
            $visual_mask->writeImage($mask_path);

            // Create attachments
            $background_id = $this->create_attachment($background_path, $background_filename, 'Design Background');
            $mask_id = $this->create_attachment($mask_path, $mask_filename, 'Design Mask');

            if (!$background_id || !$mask_id) {
                return ['success' => false, 'error' => 'Failed to create attachment records'];
            }

            return [
                'success' => true,
                'background_url' => wp_get_attachment_url($background_id),
                'mask_url' => wp_get_attachment_url($mask_id)
            ];

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Create masked image using GD (fallback for rectangular selections)
     */
    private function create_masked_image_gd_rect($original_path, $image_id, $x, $y, $width, $height)
    {
        try {
            // Load original image
            $original = imagecreatefromstring(file_get_contents($original_path));

            if (!$original) {
                return ['success' => false, 'error' => 'Failed to load original image using GD'];
            }

            $original_width = imagesx($original);
            $original_height = imagesy($original);

            // Ensure coordinates are within bounds
            $x = max(0, min($x, $original_width - 1));
            $y = max(0, min($y, $original_height - 1));
            $width = min($width, $original_width - $x);
            $height = min($height, $original_height - $y);

            // Create background image with transparency
            $background = imagecreatetruecolor($original_width, $original_height);
            imagealphablending($background, false);
            imagesavealpha($background, true);
            imagecopy($background, $original, 0, 0, 0, 0, $original_width, $original_height);

            // Make design area transparent
            $transparent = imagecolorallocatealpha($background, 0, 0, 0, 127);
            imagefilledrectangle($background, $x, $y, $x + $width, $y + $height, $transparent);

            // Create visual mask
            $mask = imagecreatetruecolor($original_width, $original_height);
            imagecopy($mask, $original, 0, 0, 0, 0, $original_width, $original_height);

            // Highlight design area (semi-transparent white)
            $highlight_color = imagecolorallocatealpha($mask, 255, 255, 255, 64); // ~50% transparent
            imagefilledrectangle($mask, $x, $y, $x + $width, $y + $height, $highlight_color);

            // Save files
            $upload_dir = wp_upload_dir();
            $filename_base = 'design-' . $image_id . '-' . time();

            // Save background
            $background_filename = $filename_base . '-background.png';
            $background_path = $upload_dir['path'] . '/' . $background_filename;
            imagepng($background, $background_path);

            // Save mask
            $mask_filename = $filename_base . '-mask.png';
            $mask_path = $upload_dir['path'] . '/' . $mask_filename;
            imagepng($mask, $mask_path);

            // Clean up
            imagedestroy($original);
            imagedestroy($background);
            imagedestroy($mask);

            // Create attachments
            $background_id = $this->create_attachment($background_path, $background_filename, 'Design Background');
            $mask_id = $this->create_attachment($mask_path, $mask_filename, 'Design Mask');

            if (!$background_id || !$mask_id) {
                return ['success' => false, 'error' => 'Failed to create attachment records'];
            }

            return [
                'success' => true,
                'background_url' => wp_get_attachment_url($background_id),
                'mask_url' => wp_get_attachment_url($mask_id)
            ];

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }


    /**
     * Create masked image with custom mask using Imagick.
     * UPDATED with new simplified logic.
     */
    private function create_masked_image_with_custom_mask_imagick($original_path, $image_id, $mask_url, $mask_adjustments)
    {
        try {
            error_log('SSPU: Starting Imagick processing');

            $mask_size = isset($mask_adjustments['size']) ? floatval($mask_adjustments['size']) : 100;
            $mask_x = isset($mask_adjustments['x']) ? floatval($mask_adjustments['x']) : 50;
            $mask_y = isset($mask_adjustments['y']) ? floatval($mask_adjustments['y']) : 50;

            // Load original image
            $original = new Imagick($original_path);
            $original_width = $original->getImageWidth();
            $original_height = $original->getImageHeight();
            error_log('SSPU: Original dimensions: ' . $original_width . 'x' . $original_height);

            // Load mask image
            $mask = new Imagick();
            if (filter_var($mask_url, FILTER_VALIDATE_URL)) {
                $mask_data = @file_get_contents($mask_url);
                if ($mask_data === false) {
                    throw new Exception('Failed to download mask image');
                }
                $mask->readImageBlob($mask_data);
            } else {
                $local_path = $this->get_local_path_from_url($mask_url);
                if (!$local_path || !file_exists($local_path)) {
                    throw new Exception('Mask file not found');
                }
                $mask->readImage($local_path);
            }

            $mask_width = $mask->getImageWidth();
            $mask_height = $mask->getImageHeight();
            error_log('SSPU: Mask dimensions: ' . $mask_width . 'x' . $mask_height);

            // Scale mask
            $scale = $mask_size / 100;
            $new_width = intval($mask_width * $scale);
            $new_height = intval($mask_height * $scale);
            if ($scale != 1) {
                $mask->resizeImage($new_width, $new_height, Imagick::FILTER_LANCZOS, 1);
                error_log('SSPU: Mask resized to: ' . $new_width . 'x' . $new_height);
            }

            // Create positioned mask canvas (white background)
            $positioned_mask = new Imagick();
            $positioned_mask->newImage($original_width, $original_height, new ImagickPixel('white'));
            $positioned_mask->setImageFormat('png');

            // Calculate position
            $pos_x = intval(($mask_x / 100) * $original_width - ($new_width / 2));
            $pos_y = intval(($mask_y / 100) * $original_height - ($new_height / 2));
            error_log('SSPU: Positioning mask at: ' . $pos_x . ', ' . $pos_y);

            $positioned_mask->compositeImage($mask, Imagick::COMPOSITE_MULTIPLY, $pos_x, $pos_y);
            $positioned_mask->setImageType(Imagick::IMGTYPE_GRAYSCALE);

            // === CREATE BACKGROUND IMAGE (with transparent cutout) ===
            $background = clone $original;
            $background->setImageFormat('png');
            $background->setImageAlphaChannel(Imagick::ALPHACHANNEL_ACTIVATE);

            $mask_for_cutout = clone $positioned_mask;
            $mask_for_cutout->setImageBackgroundColor(new ImagickPixel('white'));
            $mask_for_cutout->setImageAlphaChannel(Imagick::ALPHACHANNEL_REMOVE);
            $mask_for_cutout->negateImage(false);

            // Apply as alpha mask - DSTIN keeps the parts of the original image that overlap with the white areas of the inverted mask.
            $background->compositeImage($mask_for_cutout, Imagick::COMPOSITE_DSTIN, 0, 0);
            $mask_for_cutout->destroy();

            // === CREATE VISUAL MASK (preview showing design area) ===
            $visual_mask = clone $original;
            $visual_mask->setImageFormat('png');

            // Create a semi-transparent red overlay for the design area
            $red_overlay = new Imagick();
            $red_overlay->newImage($original_width, $original_height, new ImagickPixel('transparent'));
            $red_overlay->setImageFormat('png');

            $draw = new ImagickDraw();
            $draw->setFillColor(new ImagickPixel('rgba(255, 0, 0, 0.3)')); // 30% red
            $draw->rectangle($pos_x, $pos_y, $pos_x + $new_width - 1, $pos_y + $new_height - 1);
            $red_overlay->drawImage($draw);

            $visual_mask->compositeImage($red_overlay, Imagick::COMPOSITE_OVER, 0, 0);

            $border_draw = new ImagickDraw();
            $border_draw->setStrokeColor(new ImagickPixel('red'));
            $border_draw->setStrokeWidth(3);
            $border_draw->setFillOpacity(0);
            $border_draw->rectangle($pos_x, $pos_y, $pos_x + $new_width - 1, $pos_y + $new_height - 1);
            $visual_mask->drawImage($border_draw);

            // Add text label
            $text_draw = new ImagickDraw();
            $text_draw->setFillColor(new ImagickPixel('white'));
            $text_draw->setStrokeColor(new ImagickPixel('black'));
            $text_draw->setStrokeWidth(1);
            $text_draw->setFontSize(16);
            $text_x = $pos_x < 5 ? 5 : $pos_x + 5;
            $text_y = $pos_y < 20 ? 20 : $pos_y + 20;

            try {
                $visual_mask->annotateImage($text_draw, $text_x, $text_y, 0, 'DESIGN AREA');
            } catch (Exception $e) {
                error_log('SSPU: Could not add text annotation: ' . $e->getMessage());
            }

            // Save files
            $upload_dir = wp_upload_dir();
            $filename_base = 'design-' . $image_id . '-' . time();

            $bg_filename = $filename_base . '-background.png';
            $bg_path = $upload_dir['path'] . '/' . $bg_filename;
            $background->writeImage($bg_path);
            error_log('SSPU: Background saved to: ' . $bg_path);

            $mask_filename = $filename_base . '-mask.png';
            $mask_path = $upload_dir['path'] . '/' . $mask_filename;
            $visual_mask->writeImage($mask_path);
            error_log('SSPU: Visual mask saved to: ' . $mask_path);

            // Clean up
            $original->destroy();
            $mask->destroy();
            $positioned_mask->destroy();
            $background->destroy();
            $visual_mask->destroy();
            $red_overlay->destroy();

            // Create WordPress attachments
            $bg_id = $this->create_attachment($bg_path, $bg_filename, 'Design Background - Transparent Area');
            $mask_id = $this->create_attachment($mask_path, $mask_filename, 'Design Area Preview');

            if (!$bg_id || !$mask_id) {
                throw new Exception('Failed to create WordPress attachments');
            }

            error_log('SSPU: === PROCESSING COMPLETE ===');

            return [
                'success' => true,
                'data' => [
                    'background_url' => wp_get_attachment_url($bg_id),
                    'mask_url' => wp_get_attachment_url($mask_id),
                    'message' => 'Design files created successfully'
                ]
            ];

        } catch (Exception $e) {
            error_log('SSPU: Imagick error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Create masked image with custom mask using GD (fallback).
     * UPDATED with new simplified logic.
     */
    private function create_masked_image_with_custom_mask_gd($original_image_path, $image_id, $custom_mask_url, $mask_adjustments)
    {
        try {
            error_log('SSPU: Using GD fallback');

            // Load original image
            $original = imagecreatefromstring(file_get_contents($original_image_path));
            if (!$original) throw new Exception('Failed to load original image');

            $orig_width = imagesx($original);
            $orig_height = imagesy($original);

            // Load mask
            $mask_data = @file_get_contents($custom_mask_url);
            if (!$mask_data) throw new Exception('Failed to load mask image');
            $mask = imagecreatefromstring($mask_data);
            if (!$mask) throw new Exception('Failed to create mask image');

            // Get mask dimensions and adjustments
            $mask_width = imagesx($mask);
            $mask_height = imagesy($mask);
            $scale = floatval($mask_adjustments['size']) / 100;
            $mask_x_percent = floatval($mask_adjustments['x']);
            $mask_y_percent = floatval($mask_adjustments['y']);

            // Calculate new dimensions
            $new_width = intval($mask_width * $scale);
            $new_height = intval($mask_height * $scale);

            // Create resized mask
            $resized_mask = imagecreatetruecolor($new_width, $new_height);
            imagealphablending($resized_mask, false);
            imagesavealpha($resized_mask, true);
            $transparent_bg = imagecolorallocatealpha($resized_mask, 0, 0, 0, 127);
            imagefill($resized_mask, 0, 0, $transparent_bg);
            imagecopyresampled($resized_mask, $mask, 0, 0, 0, 0, $new_width, $new_height, $mask_width, $mask_height);

            // Create final mask canvas
            $mask_canvas = imagecreatetruecolor($orig_width, $orig_height);
            $white = imagecolorallocate($mask_canvas, 255, 255, 255);
            imagefill($mask_canvas, 0, 0, $white);

            $pos_x = intval(($mask_x_percent / 100) * $orig_width - ($new_width / 2));
            $pos_y = intval(($mask_y_percent / 100) * $orig_height - ($new_height / 2));

            imagecopy($mask_canvas, $resized_mask, $pos_x, $pos_y, 0, 0, $new_width, $new_height);

            // Create output with transparency
            $output = imagecreatetruecolor($orig_width, $orig_height);
            imagealphablending($output, false);
            imagesavealpha($output, true);
            $transparent_output = imagecolorallocatealpha($output, 0, 0, 0, 127);
            imagefill($output, 0, 0, $transparent_output);

            // Apply mask pixel by pixel
            for ($x = 0; $x < $orig_width; $x++) {
                for ($y = 0; $y < $orig_height; $y++) {
                    $mask_color = imagecolorat($mask_canvas, $x, $y);
                    $mask_brightness = ($mask_color >> 16) & 0xFF; // Red channel as brightness

                    // If mask is black (design area), copy the original pixel
                    if ($mask_brightness < 128) {
                        $rgb = imagecolorat($original, $x, $y);
                        imagesetpixel($output, $x, $y, $rgb);
                    }
                }
            }

            // Create visual mask
            $visual = imagecreatetruecolor($orig_width, $orig_height);
            imagecopy($visual, $original, 0, 0, 0, 0, $orig_width, $orig_height);

            $red_transparent = imagecolorallocatealpha($visual, 255, 0, 0, 80);
            imagefilledrectangle($visual, $pos_x, $pos_y, $pos_x + $new_width, $pos_y + $new_height, $red_transparent);

            $red_solid = imagecolorallocate($visual, 255, 0, 0);
            imagesetthickness($visual, 3);
            imagerectangle($visual, $pos_x, $pos_y, $pos_x + $new_width - 1, $pos_y + $new_height - 1, $red_solid);

            // Add text label
            $white = imagecolorallocate($visual, 255, 255, 255);
            $black = imagecolorallocate($visual, 0, 0, 0);
            $text = 'DESIGN AREA';
            $font_size = 3;
            $text_x = $pos_x < 5 ? 5 : $pos_x + 5;
            $text_y = $pos_y < 5 ? 5 : $pos_y + 5;
            imagestring($visual, $font_size, $text_x + 1, $text_y + 1, $text, $black);
            imagestring($visual, $font_size, $text_x, $text_y, $text, $white);

            // Save files
            $upload_dir = wp_upload_dir();
            $filename_base = 'design-' . $image_id . '-' . time() . '-gd';

            $bg_filename = $filename_base . '-background.png';
            $bg_path = $upload_dir['path'] . '/' . $bg_filename;
            imagepng($output, $bg_path);

            $mask_filename = $filename_base . '-mask.png';
            $mask_path = $upload_dir['path'] . '/' . $mask_filename;
            imagepng($visual, $mask_path);

            // Clean up
            imagedestroy($original);
            imagedestroy($mask);
            imagedestroy($resized_mask);
            imagedestroy($mask_canvas);
            imagedestroy($output);
            imagedestroy($visual);

            $bg_id = $this->create_attachment($bg_path, $bg_filename, 'Design Background');
            $mask_id = $this->create_attachment($mask_path, $mask_filename, 'Design Preview');

            if (!$bg_id || !$mask_id) {
                throw new Exception('Failed to create attachments');
            }

            return [
                'success' => true,
                'data' => [
                    'background_url' => wp_get_attachment_url($bg_id),
                    'mask_url' => wp_get_attachment_url($mask_id),
                    'message' => 'Design files created successfully (GD)'
                ]
            ];

        } catch (Exception $e) {
            error_log('SSPU GD Error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'GD Error: ' . $e->getMessage()];
        }
    }

    /**
     * Helper method to create WordPress attachment
     */
    private function create_attachment($file_path, $filename, $title = '')
    {
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
                foreach ($original['variants'] as $orig_variant) {
                    if (!in_array($orig_variant['id'], $new_variant_ids)) {
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

    /**
     * Additional helper function to test if image libraries are available (Class Method)
     */
    private function check_image_libraries()
    {
        $libraries = [];

        // Check for Imagick
        if (class_exists('Imagick')) {
            $libraries['imagick'] = true;
            $imagick = new Imagick();
            $libraries['imagick_version'] = $imagick->getVersion();
        } else {
            $libraries['imagick'] = false;
        }

        // Check for GD
        if (extension_loaded('gd') && function_exists('gd_info')) {
            $libraries['gd'] = true;
            $libraries['gd_info'] = gd_info();
        } else {
            $libraries['gd'] = false;
        }

        return $libraries;
    }

    /**
     * Helper function to get local file path from URL
     */
    private function get_local_path_from_url($url)
    {
        $upload_dir = wp_upload_dir();

        // Check if URL is from our uploads directory
        if (strpos($url, $upload_dir['baseurl']) !== false) {
            return str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $url);
        }

        // Try to get attachment ID from URL
        $attachment_id = attachment_url_to_postid($url);
        if ($attachment_id) {
            return get_attached_file($attachment_id);
        }

        return false;
    }
}
