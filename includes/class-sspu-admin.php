<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

class SSPU_Admin {

    public function __construct() {
        // Admin menu and settings
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
        add_action( 'wp_ajax_sspu_debug_alibaba_page', [ $this, 'handle_debug_alibaba_page' ] );
        add_action( 'wp_ajax_sspu_get_single_template_content', [ $this, 'handle_get_single_template_content' ] );



        // Main AJAX handlers
        add_action( 'wp_ajax_sspu_submit_product', [ $this, 'handle_product_submission' ] );
        add_action( 'wp_ajax_sspu_generate_description', [ $this, 'handle_description_generation' ] );
        add_action( 'wp_ajax_sspu_get_collections', [ $this, 'handle_get_collections' ] );
        add_action( 'wp_ajax_sspu_create_collection', [ $this, 'handle_create_collection' ] );

        // SKU and pricing handlers
        add_action( 'wp_ajax_sspu_generate_sku', [ $this, 'handle_sku_generation' ] );
        add_action( 'wp_ajax_sspu_calculate_volume_tiers', [ $this, 'handle_volume_tier_calculation' ] );
        add_action( 'wp_ajax_sspu_suggest_price', [ $this, 'handle_price_suggestion' ] );

        // SEO handlers
        add_action( 'wp_ajax_sspu_generate_seo', [ $this, 'handle_seo_generation' ] );

        // Draft handlers
        add_action( 'wp_ajax_sspu_save_draft', [ $this, 'handle_save_draft' ] );
        add_action( 'wp_ajax_sspu_load_draft', [ $this, 'handle_load_draft' ] );
        add_action( 'wp_ajax_sspu_auto_save_draft', [ $this, 'handle_auto_save_draft' ] );

        // AI and formatting handlers
        add_action( 'wp_ajax_sspu_format_product_name', [ $this, 'handle_format_product_name' ] );

        // Variant handlers
        add_action( 'wp_ajax_sspu_generate_variants', [ $this, 'handle_generate_variants' ] );
        add_action( 'wp_ajax_sspu_apply_pricing_to_all', [ $this, 'handle_apply_pricing_to_all' ] );

        // Utility handlers
        add_action( 'wp_ajax_sspu_test_openai_api', [ $this, 'handle_test_openai_api' ] );
        add_action( 'wp_ajax_sspu_test_gemini_api', [ $this, 'handle_test_gemini_api' ] );
        add_action( 'wp_ajax_sspu_validate_image', [ $this, 'handle_validate_image' ] );
        add_action( 'wp_ajax_sspu_upload_images', [ $this, 'handle_upload_images' ] );

        // AI Pricing handler
        add_action( 'wp_ajax_sspu_ai_suggest_all_pricing', [ $this, 'handle_ai_suggest_all_pricing' ] );

        add_action( 'wp_ajax_sspu_get_current_alibaba_url', [ $this, 'handle_get_current_alibaba_url' ] );

        // Weight estimation handler
        add_action( 'wp_ajax_sspu_estimate_weight', [ $this, 'handle_estimate_weight' ] );

        // Analytics handlers
        add_action( 'wp_ajax_sspu_get_analytics', [ $this, 'handle_get_analytics_proxy' ] );
        add_action( 'wp_ajax_sspu_export_analytics', [ $this, 'handle_export_analytics' ] );
        add_action( 'wp_ajax_sspu_get_user_activity', [ $this, 'handle_get_user_activity_proxy' ] );

        // Search handlers
        add_action( 'wp_ajax_sspu_global_search', [ $this, 'handle_global_search_proxy' ] );
        add_action( 'wp_ajax_sspu_get_search_filters', [ $this, 'handle_get_search_filters_proxy' ] );

        // Alibaba Queue handlers
        add_action( 'wp_ajax_sspu_request_alibaba_url', [ $this, 'handle_request_alibaba_url' ] );
        add_action( 'wp_ajax_sspu_complete_alibaba_url', [ $this, 'handle_complete_alibaba_url' ] );
        add_action( 'wp_ajax_sspu_release_alibaba_url', [ $this, 'handle_release_alibaba_url' ] );

        // Image retrieval handlers
        add_action( 'wp_ajax_sspu_retrieve_alibaba_images', [ $this, 'handle_retrieve_alibaba_images' ] );
        add_action( 'wp_ajax_sspu_download_external_image', [ $this, 'handle_download_external_image' ] );

        // AI Image editing handlers
        add_action( 'wp_ajax_sspu_ai_edit_image', [ $this, 'handle_ai_edit_image' ] );
        add_action( 'wp_ajax_sspu_get_chat_history', [ $this, 'handle_get_chat_history' ] );
        add_action( 'wp_ajax_sspu_save_edited_image', [ $this, 'handle_save_edited_image' ] );

        // Template handlers
        add_action( 'wp_ajax_sspu_get_image_templates', [ $this, 'handle_get_image_templates' ] );
        add_action( 'wp_ajax_sspu_save_image_template', [ $this, 'handle_save_image_template' ] );
        add_action( 'wp_ajax_sspu_delete_image_template', [ $this, 'handle_delete_image_template' ] );
    }

    public function enqueue_scripts( $hook ) {
        // Debug: Log the current hook
        error_log('SSPU: Current admin hook: ' . $hook);
        error_log('SSPU: Current page: ' . ($_GET['page'] ?? 'none'));

        // More flexible page detection
        $is_sspu_page = false;
        $current_page = $_GET['page'] ?? '';

        // Check if we're on any SSPU page
        if (strpos($current_page, 'sspu-') === 0) {
            $is_sspu_page = true;
        }

        // Also check the hook patterns
        $valid_hooks = [
            'toplevel_page_sspu-uploader',
            'shopify_page_sspu-leaderboard',
            'shopify_page_sspu-analytics',
            'shopify_page_sspu-search',
            'shopify_page_sspu-settings',
            'shopify_page_sspu-alibaba-queue',
            'shopify_page_sspu-image-templates',
            'admin_page_sspu-uploader', // Alternative pattern
            'dashboard_page_sspu-uploader' // Another possible pattern
        ];

        if (!$is_sspu_page && !in_array($hook, $valid_hooks)) {
            // Also check if hook contains sspu
            if (strpos($hook, 'sspu') === false) {
                return;
            }
        }

        // Always load CSS on SSPU pages
        wp_enqueue_style('sspu-admin-style', SSPU_PLUGIN_URL . 'assets/css/admin-style.css', [], SSPU_VERSION);

        // Load scripts for uploader page
        if ($current_page === 'sspu-uploader' || $hook === 'toplevel_page_sspu-uploader') {
            error_log('SSPU: Loading scripts for uploader page');

            wp_enqueue_media();
            wp_enqueue_script('jquery-ui-sortable');
            wp_enqueue_script('jquery-ui-tabs');

            // Enqueue with wp_editor support
            wp_enqueue_editor();

            wp_enqueue_script(
                'sspu-admin-script',
                SSPU_PLUGIN_URL . 'assets/js/admin-script.js',
                [ 'jquery', 'jquery-ui-sortable', 'jquery-ui-tabs', 'wp-util' ],
                SSPU_VERSION,
                true
            );

            // Add image retriever and AI editor scripts
            wp_enqueue_script(
                'sspu-image-retriever',
                SSPU_PLUGIN_URL . 'assets/js/image-retriever.js',
                ['jquery'],
                SSPU_VERSION,
                true
            );

            wp_enqueue_script(
                'sspu-ai-image-editor',
                SSPU_PLUGIN_URL . 'assets/js/ai-image-editor.js',
                ['jquery'],
                SSPU_VERSION,
                true
            );

            wp_enqueue_script(
                'sspu-image-templates',
                SSPU_PLUGIN_URL . 'assets/js/image-templates.js',
                ['jquery'],
                SSPU_VERSION,
                true
            );

            // Add styles for image features
            wp_enqueue_style('sspu-image-retriever', SSPU_PLUGIN_URL . 'assets/css/image-retriever.css', [], SSPU_VERSION);
            wp_enqueue_style('sspu-ai-image-editor', SSPU_PLUGIN_URL . 'assets/css/ai-image-editor.css', [], SSPU_VERSION);

            // Localize script with proper data
            $localize_data = [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('sspu_ajax_nonce'),
                'user_id' => get_current_user_id(),
                'debug_mode' => defined('WP_DEBUG') && WP_DEBUG,
                'openai_configured' => !empty(get_option('sspu_openai_api_key')),
                'gemini_configured' => !empty(get_option('sspu_gemini_api_key')),
                'shopify_configured' => !empty(get_option('sspu_shopify_store_name')) && !empty(get_option('sspu_shopify_access_token')),
                'strings' => [
                    'confirm_delete_variant' => __('Are you sure you want to remove this variant?', 'sspu'),
                    'confirm_clear_variants' => __('Are you sure you want to clear all variants?', 'sspu'),
                    'invalid_file_type' => __('Invalid file type', 'sspu'),
                    'file_too_large' => __('File too large (max %s)', 'sspu'),
                    'no_openai_key' => __('OpenAI API key not configured. Please add it in Settings.', 'sspu'),
                    'no_gemini_key' => __('Google Gemini API key not configured. Please add it in Settings.', 'sspu'),
                    'no_shopify_creds' => __('Shopify credentials not configured. Please add them in Settings.', 'sspu'),
                    'retrieving_images' => __('Retrieving images from Alibaba...', 'sspu'),
                    'downloading_images' => __('Downloading images...', 'sspu'),
                    'processing_image' => __('Processing image with AI...', 'sspu'),
                ],
                'allowed_image_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
                'max_file_size' => 5 * 1024 * 1024, // 5MB
                
            ];

            wp_localize_script('sspu-image-retriever', 'sspu_ajax', $localize_data);
            wp_localize_script('sspu-ai-image-editor', 'sspu_ajax', $localize_data);
            wp_localize_script('sspu-image-templates', 'sspu_ajax', $localize_data);
            wp_localize_script('sspu-admin-script', 'sspu_ajax', $localize_data);

            // Also add inline script to verify loading
            wp_add_inline_script('sspu-admin-script', 'console.log("SSPU Admin Script loaded successfully!");', 'after');

                
        }

        // Load scripts for Alibaba Queue page
        if ($current_page === 'sspu-alibaba-queue' || $hook === 'shopify_page_sspu-alibaba-queue') {
            wp_enqueue_script(
                'sspu-alibaba-queue',
                SSPU_PLUGIN_URL . 'assets/js/alibaba-queue.js',
                ['jquery'],
                SSPU_VERSION,
                true
            );

            wp_localize_script('sspu-alibaba-queue', 'sspu_ajax', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('sspu_ajax_nonce'),
            ]);
        }

        // Load scripts for image templates page
        if ($current_page === 'sspu-image-templates' || $hook === 'shopify_page_sspu-image-templates') {
            wp_enqueue_script(
                'sspu-image-templates-admin',
                SSPU_PLUGIN_URL . 'assets/js/image-templates-admin.js',
                ['jquery'],
                SSPU_VERSION,
                true
            );

            wp_localize_script('sspu-image-templates-admin', 'sspu_ajax', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('sspu_ajax_nonce'),
            ]);
        }

        // Load scripts for analytics and search pages
        if (in_array($current_page, ['sspu-analytics', 'sspu-search']) ||
            in_array($hook, ['shopify_page_sspu-analytics', 'shopify_page_sspu-search'])) {

            wp_enqueue_script('chart-js', 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js', [], '3.9.1', true);
            wp_enqueue_script('sspu-analytics', SSPU_PLUGIN_URL . 'assets/js/analytics.js', ['jquery', 'chart-js'], SSPU_VERSION, true);

            wp_localize_script('sspu-analytics', 'sspu_ajax', [
                'nonce' => wp_create_nonce('sspu_ajax_nonce'),
            ]);

            // Pass store name for Shopify links
            wp_localize_script('sspu-analytics', 'sspu_store_name', [
                'name' => get_option('sspu_shopify_store_name', '')
            ]);
        }
    }
    public function handle_get_current_alibaba_url() {
    check_ajax_referer('sspu_ajax_nonce', 'nonce');
    
    if (!current_user_can('upload_shopify_products')) {
        wp_send_json_error(['message' => 'Permission denied']);
        return;
    }
    
    $user_id = get_current_user_id();
    
    // Check if user has an assigned Alibaba URL
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

    public function add_admin_menu() {
        add_menu_page(
            __('Shopify Uploader', 'sspu'), __('Shopify', 'sspu'), 'upload_shopify_products',
            'sspu-uploader', [ $this, 'uploader_page_html' ], 'dashicons-cart', 25
        );
        add_submenu_page(
            'sspu-uploader', __('Dashboard & Stats', 'sspu'), __('Dashboard & Stats', 'sspu'),
            'upload_shopify_products', 'sspu-leaderboard', [ $this, 'leaderboard_page_html' ]
        );
        add_submenu_page(
            'sspu-uploader', __('Analytics', 'sspu'), __('Analytics', 'sspu'),
            'upload_shopify_products', 'sspu-analytics', [ $this, 'analytics_page_html' ]
        );
        add_submenu_page(
            'sspu-uploader', __('Product Search & Listing', 'sspu'), __('Product Search & Listing', 'sspu'),
            'upload_shopify_products', 'sspu-search', [ $this, 'search_page_html' ]
        );
        // Add new submenu for Alibaba Queue (admin only)
        add_submenu_page(
            'sspu-uploader', __('Alibaba Queue', 'sspu'), __('Alibaba Queue', 'sspu'),
            'manage_options', 'sspu-alibaba-queue', [ $this, 'alibaba_queue_page_html' ]
        );
        // Add new submenu for Image Templates
        add_submenu_page(
            'sspu-uploader', __('Image Templates', 'sspu'), __('Image Templates', 'sspu'),
            'upload_shopify_products', 'sspu-image-templates', [ $this, 'image_templates_page_html' ]
        );
        add_submenu_page(
            'sspu-uploader', __('Settings', 'sspu'), __('Settings', 'sspu'),
            'manage_options', 'sspu-settings', [ $this, 'settings_page_html' ]
        );
    }

    public function register_settings() {
        register_setting('sspu_settings_group', 'sspu_shopify_store_name');
        register_setting('sspu_settings_group', 'sspu_shopify_access_token');
        register_setting('sspu_settings_group', 'sspu_openai_api_key');
        register_setting('sspu_settings_group', 'sspu_gemini_api_key');
        register_setting('sspu_settings_group', 'sspu_sku_pattern');
        register_setting('sspu_settings_group', 'sspu_volume_tier_multipliers');
        register_setting('sspu_settings_group', 'sspu_seo_template');

        add_settings_section('sspu_settings_section', __('Shopify API Credentials', 'sspu'), null, 'sspu-settings');
        add_settings_field('sspu_shopify_store_name', __('Shopify Store Name', 'sspu'), [ $this, 'store_name_field_html' ], 'sspu-settings', 'sspu_settings_section');
        add_settings_field('sspu_shopify_access_token', __('Admin API Access Token', 'sspu'), [ $this, 'access_token_field_html' ], 'sspu-settings', 'sspu_settings_section');

        add_settings_section('sspu_openai_section', __('AI Configuration', 'sspu'), null, 'sspu-settings');
        add_settings_field('sspu_openai_api_key', __('OpenAI API Key', 'sspu'), [ $this, 'openai_api_key_field_html' ], 'sspu-settings', 'sspu_openai_section');
        add_settings_field('sspu_gemini_api_key', __('Google Gemini API Key', 'sspu'), [ $this, 'gemini_api_key_field_html' ], 'sspu-settings', 'sspu_openai_section');

        add_settings_section('sspu_automation_section', __('Automation Settings', 'sspu'), null, 'sspu-settings');
        add_settings_field('sspu_sku_pattern', __('SKU Pattern', 'sspu'), [ $this, 'sku_pattern_field_html' ], 'sspu-settings', 'sspu_automation_section');
        add_settings_field('sspu_volume_tier_multipliers', __('Volume Tier Multipliers', 'sspu'), [ $this, 'volume_tier_multipliers_field_html' ], 'sspu-settings', 'sspu_automation_section');
        add_settings_field('sspu_seo_template', __('SEO Template', 'sspu'), [ $this, 'seo_template_field_html' ], 'sspu-settings', 'sspu_automation_section');
    }

    public function store_name_field_html() {
        $store_name = get_option('sspu_shopify_store_name');
        printf('<input type="text" id="sspu_shopify_store_name" name="sspu_shopify_store_name" value="%s" class="regular-text" />', esc_attr($store_name));
        echo '<p class="description">' . __('Enter your store name (e.g., `mystore` if your URL is mystore.myshopify.com)', 'sspu') . '</p>';
    }

    public function access_token_field_html() {
        $access_token = get_option('sspu_shopify_access_token');
        printf('<input type="password" id="sspu_shopify_access_token" name="sspu_shopify_access_token" value="%s" class="regular-text" />', esc_attr($access_token));
        echo '<p class="description">' . __('Enter your Shopify Admin API access token (shpat_...).', 'sspu') . '</p>';
    }

    public function openai_api_key_field_html() {
        $api_key = get_option('sspu_openai_api_key');
        printf('<input type="password" id="sspu_openai_api_key" name="sspu_openai_api_key" value="%s" class="regular-text" />', esc_attr($api_key));
        echo '<p class="description">' . __('Enter your OpenAI API key for AI features.', 'sspu') . '</p>';

        // Add test button
        if (!empty($api_key)) {
            echo '<button type="button" id="test-openai-api" class="button">' . __('Test API Connection', 'sspu') . '</button>';
            echo '<span id="openai-api-test-result" style="margin-left: 10px;"></span>';
            ?>
            <script>
            jQuery('#test-openai-api').on('click', function() {
                const $button = jQuery(this);
                const $result = jQuery('#openai-api-test-result');

                $button.prop('disabled', true);
                $result.text('Testing...');

                jQuery.post(ajaxurl, {
                    action: 'sspu_test_openai_api',
                    nonce: '<?php echo wp_create_nonce('sspu_ajax_nonce'); ?>'
                })
                .done(function(response) {
                    if (response.success) {
                        $result.html('<span style="color: green;">✓ API connection successful!</span>');
                    } else {
                        $result.html('<span style="color: red;">✗ ' + response.data.message + '</span>');
                    }
                })
                .fail(function() {
                    $result.html('<span style="color: red;">✗ Connection failed</span>');
                })
                .always(function() {
                    $button.prop('disabled', false);
                });
            });
            </script>
            <?php
        }
    }

    public function gemini_api_key_field_html() {
        $api_key = get_option('sspu_gemini_api_key');
        printf('<input type="password" id="sspu_gemini_api_key" name="sspu_gemini_api_key" value="%s" class="regular-text" />', esc_attr($api_key));
        echo '<p class="description">' . __('Enter your Google Gemini API key for AI image editing.', 'sspu') . '</p>';

        // Add test button
        if (!empty($api_key)) {
            echo '<button type="button" id="test-gemini-api" class="button">' . __('Test API Connection', 'sspu') . '</button>';
            echo '<span id="gemini-api-test-result" style="margin-left: 10px;"></span>';
            ?>
            <script>
            jQuery('#test-gemini-api').on('click', function() {
                const $button = jQuery(this);
                const $result = jQuery('#gemini-api-test-result');

                $button.prop('disabled', true);
                $result.text('Testing...');

                jQuery.post(ajaxurl, {
                    action: 'sspu_test_gemini_api',
                    nonce: '<?php echo wp_create_nonce('sspu_ajax_nonce'); ?>'
                })
                .done(function(response) {
                    if (response.success) {
                        $result.html('<span style="color: green;">✓ API connection successful!</span>');
                    } else {
                        $result.html('<span style="color: red;">✗ ' + response.data.message + '</span>');
                    }
                })
                .fail(function() {
                    $result.html('<span style="color: red;">✗ Connection failed</span>');
                })
                .always(function() {
                    $button.prop('disabled', false);
                });
            });
            </script>
            <?php
        }
    }

    public function sku_pattern_field_html() {
        $pattern = get_option('sspu_sku_pattern', '{PRODUCT_NAME}-{VARIANT_VALUE}');
        printf('<input type="text" id="sspu_sku_pattern" name="sspu_sku_pattern" value="%s" class="regular-text" />', esc_attr($pattern));
        echo '<p class="description">' . __('SKU pattern. Available tokens: {PRODUCT_NAME}, {VARIANT_VALUE}, {VARIANT_NAME}, {RANDOM}', 'sspu') . '</p>';
    }

    public function volume_tier_multipliers_field_html() {
        $multipliers = get_option('sspu_volume_tier_multipliers', '0.95,0.90,0.85,0.80,0.75');
        printf('<input type="text" id="sspu_volume_tier_multipliers" name="sspu_volume_tier_multipliers" value="%s" class="regular-text" />', esc_attr($multipliers));
        echo '<p class="description">' . __('Comma-separated multipliers for volume tiers (e.g., 0.95,0.90,0.85)', 'sspu') . '</p>';
    }

    public function seo_template_field_html() {
        $template = get_option('sspu_seo_template', 'Buy {PRODUCT_NAME} - High Quality {CATEGORY} | Your Store');
        printf('<textarea id="sspu_seo_template" name="sspu_seo_template" class="large-text" rows="3">%s</textarea>', esc_textarea($template));
        echo '<p class="description">' . __('SEO title template. Available tokens: {PRODUCT_NAME}, {CATEGORY}, {BRAND}', 'sspu') . '</p>';
    }

    public function uploader_page_html() {
        ?>
        <div class="wrap" id="sspu-uploader-wrapper">
            <h1><?php echo esc_html__('Upload New Product to Shopify', 'sspu'); ?></h1>

            <!-- Draft Controls -->
            <div class="sspu-draft-controls">
                <button type="button" id="sspu-save-draft" class="button"><?php esc_html_e('Save Draft', 'sspu'); ?></button>
                <button type="button" id="sspu-load-draft" class="button"><?php esc_html_e('Load Draft', 'sspu'); ?></button>
                <span class="sspu-auto-save-status"></span>
            </div>

            <!-- Alibaba URL Section (new) -->
            <div class="sspu-alibaba-url-section">
                <h3><?php esc_html_e('Alibaba Product URL', 'sspu'); ?></h3>
                <div id="alibaba-url-container">
                    <div id="no-url-assigned" style="display: none;">
                        <p><?php esc_html_e('No Alibaba URL currently assigned.', 'sspu'); ?></p>
                        <button type="button" id="request-alibaba-url" class="button button-primary"><?php esc_html_e('Request New URL', 'sspu'); ?></button>
                    </div>
                    <div id="url-assigned" style="display: none;">
                        <p><?php esc_html_e('Current Alibaba URL:', 'sspu'); ?></p>
                        <div class="alibaba-url-display">
                            <input type="text" id="current-alibaba-url" readonly class="regular-text" style="width: 70%;" />
                            <a href="#" id="open-alibaba-url" target="_blank" class="button"><?php esc_html_e('Open', 'sspu'); ?></a>
                        </div>
                        <p class="description">
                            <?php esc_html_e('Assigned at:', 'sspu'); ?> <span id="url-assigned-time"></span>
                        </p>
                        <div class="url-actions">
                            <button type="button" id="complete-alibaba-url" class="button button-primary"><?php esc_html_e('Mark as Complete', 'sspu'); ?></button>
                            <button type="button" id="release-alibaba-url" class="button"><?php esc_html_e('Release Back to Queue', 'sspu'); ?></button>
                        </div>
                    </div>
                    <div class="spinner" id="alibaba-url-spinner"></div>
                </div>
            </div>

            <div id="sspu-status-box" class="notice" style="display:none;">
                <h3 id="sspu-status-heading"></h3>
                <div id="sspu-progress-bar" style="display:none;">
                    <div class="progress-bar">
                        <div class="progress-fill" id="sspu-progress-fill"></div>
                    </div>
                    <div class="progress-text" id="sspu-progress-text">0%</div>
                </div>
                <pre id="sspu-status-log"></pre>
            </div>

            <!-- Tabbed Interface -->
            <div id="sspu-tabs">
                <ul>
                    <li><a href="#tab-basic"><?php esc_html_e('Basic Info', 'sspu'); ?></a></li>
                    <li><a href="#tab-seo"><?php esc_html_e('SEO', 'sspu'); ?></a></li>
                    <li><a href="#tab-images"><?php esc_html_e('Images', 'sspu'); ?></a></li>
                    <li><a href="#tab-metafields"><?php esc_html_e('Metafields', 'sspu'); ?></a></li>
                    <li><a href="#tab-variants"><?php esc_html_e('Variants', 'sspu'); ?></a></li>
                </ul>

                <form id="sspu-product-form" method="post">
                    <?php wp_nonce_field('sspu_create_product', 'sspu_nonce'); ?>

                    <!-- Basic Info Tab -->
                    <div id="tab-basic">
                        <table class="form-table">
                            <tr valign="top">
                                <th scope="row"><?php esc_html_e('Product Name', 'sspu'); ?></th>
                                <td>
                                    <div class="product-name-section">
                                        <input type="text" name="product_name" id="product-name-input" class="regular-text" required />
                                        <button type="button" id="format-product-name" class="button button-secondary"><?php esc_html_e('Format with AI', 'sspu'); ?></button>
                                        <div class="spinner" id="format-name-spinner"></div>
                                    </div>
                                    <div class="seo-feedback">
                                        <span class="title-length"></span>
                                        <span class="seo-suggestions"></span>
                                    </div>
                                    <p class="description"><?php esc_html_e('AI can reformat long product names into 4-7 words suitable for Shopify.', 'sspu'); ?></p>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row"><?php esc_html_e('Collections', 'sspu'); ?></th>
                                <td>
                                    <div class="collections-search-container" style="margin-bottom: 10px;">
                                        <input type="text" id="sspu-collection-search" placeholder="<?php esc_attr_e('Search collections...', 'sspu'); ?>" class="regular-text" />
                                    </div>
                                    <select name="product_collections[]" id="sspu-collection-select" multiple style="width: 100%; min-height: 150px;">
                                        <option value="" disabled><?php esc_html_e('Loading collections...', 'sspu'); ?></option>
                                    </select>
                                    <div class="collection-controls" style="margin-top: 10px;">
                                        <button type="button" id="sspu-refresh-collections" class="button"><?php esc_html_e('Refresh', 'sspu'); ?></button>
                                        <button type="button" id="sspu-select-all-collections" class="button"><?php esc_html_e('Select All', 'sspu'); ?></button>
                                        <button type="button" id="sspu-clear-collections" class="button"><?php esc_html_e('Clear Selection', 'sspu'); ?></button>
                                        <button type="button" id="sspu-create-collection" class="button"><?php esc_html_e('Create New', 'sspu'); ?></button>
                                        <span style="margin-left: 10px;">Selected: <strong id="selected-collections-count">0</strong></span>
                                    </div>
                                    <div id="sspu-new-collection" style="display:none; margin-top:10px;">
                                        <input type="text" id="sspu-new-collection-name" placeholder="<?php esc_attr_e('Collection Name', 'sspu'); ?>" />
                                        <button type="button" id="sspu-save-collection" class="button button-primary"><?php esc_html_e('Save', 'sspu'); ?></button>
                                        <button type="button" id="sspu-cancel-collection" class="button"><?php esc_html_e('Cancel', 'sspu'); ?></button>
                                    </div>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row"><?php esc_html_e('Description', 'sspu'); ?></th>
                                <td>
                                    <div class="ai-description-section">
                                        <textarea id="sspu-ai-input-text" placeholder="<?php esc_attr_e('Enter product details, features, specifications...', 'sspu'); ?>" rows="4" style="width: 100%;"></textarea>
                                        <div class="ai-image-upload">
                                            <button type="button" id="sspu-upload-ai-images" class="button"><?php esc_html_e('Upload Product Images for AI Analysis', 'sspu'); ?></button>
                                            <div id="sspu-ai-images-preview"></div>
                                        </div>
                                        <div class="ai-buttons">
                                            <button type="button" id="sspu-generate-description" class="button button-primary"><?php esc_html_e('Generate Description', 'sspu'); ?></button>
                                            <button type="button" id="sspu-generate-tags" class="button"><?php esc_html_e('Generate Tags', 'sspu'); ?></button>
                                            <button type="button" id="sspu-suggest-price" class="button"><?php esc_html_e('Suggest Price', 'sspu'); ?></button>
                                        </div>
                                        <div class="spinner" id="sspu-ai-spinner"></div>
                                    </div>
                                    <?php wp_editor('', 'product_description', ['textarea_name' => 'product_description', 'media_buttons' => true]); ?>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row"><?php esc_html_e('Tags', 'sspu'); ?></th>
                                <td>
                                    <input type="text" name="product_tags" class="regular-text" placeholder="<?php esc_attr_e('Comma-separated tags', 'sspu'); ?>" />
                                    <p class="description"><?php esc_html_e('AI can auto-generate tags based on your description.', 'sspu'); ?></p>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- SEO Tab -->
                    <div id="tab-seo">
                        <table class="form-table">
                            <tr valign="top">
                                <th scope="row"><?php esc_html_e('SEO Title', 'sspu'); ?></th>
                                <td>
                                    <input type="text" name="seo_title" class="regular-text" maxlength="60" />
                                    <div class="seo-feedback">
                                        <span class="char-count">0/60</span>
                                        <button type="button" id="sspu-generate-seo-title" class="button button-small"><?php esc_html_e('Auto-Generate', 'sspu'); ?></button>
                                    </div>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row"><?php esc_html_e('Meta Description', 'sspu'); ?></th>
                                <td>
                                    <textarea name="meta_description" class="large-text" rows="3" maxlength="160"></textarea>
                                    <div class="seo-feedback">
                                        <span class="char-count">0/160</span>
                                        <button type="button" id="sspu-generate-meta-desc" class="button button-small"><?php esc_html_e('Auto-Generate', 'sspu'); ?></button>
                                    </div>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row"><?php esc_html_e('URL Handle', 'sspu'); ?></th>
                                <td>
                                    <input type="text" name="url_handle" class="regular-text" />
                                    <p class="description"><?php esc_html_e('Auto-generated from product name if left empty.', 'sspu'); ?></p>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- Images Tab -->
                    <div id="tab-images">
                        <div class="image-drop-zone" id="sspu-image-drop-zone">
                            <p><?php esc_html_e('Drag and drop images here or click to select', 'sspu'); ?></p>
                        </div>

                        <table class="form-table">
                            <tr valign="top">
                                <th scope="row"><?php esc_html_e('Main Image', 'sspu'); ?></th>
                                <td>
                                    <div class="sspu-image-preview sortable-images" id="sspu-main-image-preview"></div>
                                    <input type="hidden" name="main_image_id" id="sspu-main-image-id" />
                                    <button type="button" class="button sspu-upload-image-btn" data-target-id="sspu-main-image-id" data-target-preview="sspu-main-image-preview"><?php esc_html_e('Select Main Image', 'sspu'); ?></button>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row"><?php esc_html_e('Additional Images', 'sspu'); ?></th>
                                <td>
                                    <div class="sspu-image-preview sortable-images" id="sspu-additional-images-preview"></div>
                                    <input type="hidden" name="additional_image_ids" id="sspu-additional-image-ids" />
                                    <button type="button" class="button sspu-upload-image-btn" data-target-id="sspu-additional-image-ids" data-target-preview="sspu-additional-images-preview" data-multiple="true"><?php esc_html_e('Select Additional Images', 'sspu'); ?></button>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- Metafields Tab -->
                    <div id="tab-metafields">
                        <table class="form-table">
                            <tr valign="top">
                                <th scope="row"><?php esc_html_e('Print Methods', 'sspu'); ?></th>
                                <td>
                                    <div class="print-methods-grid">
                                        <label><input type="checkbox" name="print_methods[]" value="silkscreen" /> <?php esc_html_e('Silkscreen', 'sspu'); ?></label>
                                        <label><input type="checkbox" name="print_methods[]" value="uvprint" /> <?php esc_html_e('UV Print', 'sspu'); ?></label>
                                        <label><input type="checkbox" name="print_methods[]" value="embroidery" /> <?php esc_html_e('Embroidery', 'sspu'); ?></label>
                                        <label><input type="checkbox" name="print_methods[]" value="emboss" /> <?php esc_html_e('Emboss', 'sspu'); ?></label>
                                        <label><input type="checkbox" name="print_methods[]" value="sublimation" /> <?php esc_html_e('Sublimation', 'sspu'); ?></label>
                                        <label><input type="checkbox" name="print_methods[]" value="laserengrave" /> <?php esc_html_e('Laser Engrave', 'sspu'); ?></label>
                                    </div>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row"><?php esc_html_e('Minimum Quantity', 'sspu'); ?></th>
                                <td><input type="number" name="product_min" class="small-text" min="1" /></td>
                            </tr>
                            <tr valign="top">
                                <th scope="row"><?php esc_html_e('Maximum Quantity', 'sspu'); ?></th>
                                <td><input type="number" name="product_max" class="small-text" min="1" /></td>
                            </tr>
                        </table>
                    </div>

                    <!-- Variants Tab -->
                    <div id="tab-variants">
                        <div class="variant-generator-section">
                            <h3><?php esc_html_e('Generate Variants', 'sspu'); ?></h3>
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php esc_html_e('Option Name', 'sspu'); ?></th>
                                    <td>
                                        <input type="text" id="variant-option-name" placeholder="<?php esc_attr_e('e.g., Color, Size, Material', 'sspu'); ?>" class="regular-text" />
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php esc_html_e('Option Values', 'sspu'); ?></th>
                                    <td>
                                        <textarea id="variant-option-values" rows="3" class="large-text" placeholder="<?php esc_attr_e('Enter values separated by commas or new lines, e.g.:\nRed, Blue, Green, Black\nOr:\nRed\nBlue\nGreen\nBlack', 'sspu'); ?>"></textarea>
                                        <p class="description"><?php esc_html_e('Enter each variant value separated by commas or on separate lines.', 'sspu'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php esc_html_e('Base Price', 'sspu'); ?></th>
                                    <td>
                                        <input type="number" id="variant-base-price" step="0.01" min="0" class="regular-text" placeholder="<?php esc_attr_e('19.99', 'sspu'); ?>" />
                                        <p class="description"><?php esc_html_e('This price will be applied to all generated variants.', 'sspu'); ?></p>
                                    </td>
                                </tr>
                            </table>
                            <div class="variant-generator-controls">
                                <button type="button" id="generate-variants-btn" class="button button-primary"><?php esc_html_e('Generate Variants', 'sspu'); ?></button>
                                <button type="button" id="clear-variants-btn" class="button"><?php esc_html_e('Clear All Variants', 'sspu'); ?></button>
                            </div>
                        </div>

                        <div class="variant-pricing-controls">
                            <h3><?php esc_html_e('Bulk Pricing Actions', 'sspu'); ?></h3>
                            <div class="bulk-actions">
                                <button type="button" id="apply-price-to-all" class="button"><?php esc_html_e('Apply First Variant Price to All', 'sspu'); ?></button>
                                <button type="button" id="apply-tiers-to-all" class="button"><?php esc_html_e('Apply First Variant Tiers to All', 'sspu'); ?></button>
                                <button type="button" id="apply-weight-to-all" class="button"><?php esc_html_e('Apply First Variant Weight to All', 'sspu'); ?></button>
                                <button type="button" id="auto-generate-all-skus" class="button"><?php esc_html_e('Auto-Generate All SKUs', 'sspu'); ?></button>
                                <button type="button" id="ai-suggest-all-pricing" class="button button-primary">
                                    <?php esc_html_e('AI Suggest All Pricing', 'sspu'); ?>
                                    <span class="spinner"></span>
                                </button>
                                <button type="button" id="ai-estimate-weight" class="button button-primary">
                                    <?php esc_html_e('AI Estimate Weight', 'sspu'); ?>
                                    <span class="spinner"></span>
                                </button>
                            </div>
                        </div>

                        <div id="sspu-variants-wrapper" class="sortable-variants"></div>
                        <button type="button" id="sspu-add-variant-btn" class="button"><?php esc_html_e('Add Individual Variant', 'sspu'); ?></button>
                    </div>

                    <?php submit_button(__('Upload Product to Shopify', 'sspu'), 'primary', 'sspu-submit-button'); ?>
                    <span class="spinner"></span>
                </form>
            </div>

            <!-- Templates -->
            <div id="sspu-variant-template" style="display: none;">
                <div class="sspu-variant-row">
                    <div class="variant-header">
                        <h4>Variant <span class="variant-number">1</span></h4>
                        <div class="variant-controls">
                            <button type="button" class="button button-small sspu-duplicate-variant"><?php esc_html_e('Duplicate', 'sspu'); ?></button>
                            <button type="button" class="button button-link-delete sspu-remove-variant-btn"><?php esc_html_e('Remove', 'sspu'); ?></button>
                            <span class="drag-handle">⋮⋮</span>
                        </div>
                    </div>

                    <table class="form-table">
                        <tr>
                            <th><?php esc_html_e('Option Name', 'sspu'); ?></th>
                            <td><input type="text" name="variant_options[0][name]" class="sspu-variant-option-name" placeholder="e.g., Size"/></td>
                            <th><?php esc_html_e('Option Value', 'sspu'); ?></th>
                            <td><input type="text" name="variant_options[0][value]" class="sspu-variant-option-value" placeholder="e.g., Large"/></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Price', 'sspu'); ?></th>
                            <td>
                                <input type="number" step="0.01" name="variant_options[0][price]" class="sspu-variant-price" placeholder="e.g., 19.99"/>
                                <button type="button" class="button button-small suggest-price" data-variant="0"><?php esc_html_e('Suggest', 'sspu'); ?></button>
                            </td>
                            <th><?php esc_html_e('SKU', 'sspu'); ?></th>
                            <td>
                                <input type="text" name="variant_options[0][sku]" class="sspu-variant-sku" placeholder="e.g., TSHIRT-LG-RED"/>
                                <button type="button" class="button button-small generate-sku" data-variant="0"><?php esc_html_e('Generate', 'sspu'); ?></button>
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Weight (lbs)', 'sspu'); ?></th>
                            <td>
                                <input type="number" step="0.01" name="variant_options[0][weight]" class="sspu-variant-weight" placeholder="e.g., 1.5"/>
                                <button type="button" class="button button-small suggest-weight" data-variant="0"><?php esc_html_e('AI Estimate', 'sspu'); ?></button>
                            </td>
                            <th><?php esc_html_e('Variant Image', 'sspu'); ?></th>
                            <td>
                                <div class="sspu-image-preview sspu-variant-image-preview sortable-images"></div>
                                <input type="hidden" name="variant_options[0][image_id]" class="sspu-variant-image-id" />
                                <button type="button" class="button sspu-upload-image-btn" data-target-preview-class="sspu-variant-image-preview" data-target-id-class="sspu-variant-image-id"><?php esc_html_e('Select Variant Image', 'sspu'); ?></button>
                            </td>
                        </tr>
                    </table>

                    <h4><?php esc_html_e('Volume Pricing Tiers', 'sspu'); ?></h4>
                    <div class="volume-pricing-controls">
                        <button type="button" class="button auto-calculate-tiers"><?php esc_html_e('Auto-Calculate Tiers', 'sspu'); ?></button>
                        <button type="button" class="button add-volume-tier"><?php esc_html_e('Add Tier', 'sspu'); ?></button>
                    </div>
                    <div class="volume-pricing-tiers">
                        <table class="volume-tier-table">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Min Quantity', 'sspu'); ?></th>
                                    <th><?php esc_html_e('Price', 'sspu'); ?></th>
                                    <th><?php esc_html_e('Action', 'sspu'); ?></th>
                                </tr>
                            </thead>
                            <tbody class="volume-tiers-body sortable-tiers">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Volume Tier Template -->
            <div id="sspu-tier-template" style="display: none;">
                <tr class="volume-tier-row">
                    <td><input type="number" name="variant_options[0][tiers][0][min_quantity]" class="tier-min-quantity" min="1" /></td>
                    <td><input type="number" name="variant_options[0][tiers][0][price]" class="tier-price" step="0.01" min="0" /></td>
                    <td>
                        <button type="button" class="button button-link-delete remove-volume-tier"><?php esc_html_e('Remove', 'sspu'); ?></button>
                        <span class="drag-handle">⋮⋮</span>
                    </td>
                </tr>
            </div>
        </div>
        <?php
    }

    public function image_templates_page_html() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Image Templates', 'sspu'); ?></h1>
            
            <div class="template-management">
                <div class="template-categories">
                    <button class="button category-filter active" data-category="all"><?php esc_html_e('All', 'sspu'); ?></button>
                    <button class="button category-filter" data-category="background"><?php esc_html_e('Background', 'sspu'); ?></button>
                    <button class="button category-filter" data-category="lifestyle"><?php esc_html_e('Lifestyle', 'sspu'); ?></button>
                    <button class="button category-filter" data-category="variations"><?php esc_html_e('Variations', 'sspu'); ?></button>
                    <button class="button category-filter" data-category="branding"><?php esc_html_e('Branding', 'sspu'); ?></button>
                    <button class="button category-filter" data-category="enhancement"><?php esc_html_e('Enhancement', 'sspu'); ?></button>
                    <button class="button category-filter" data-category="hero"><?php esc_html_e('Hero Shots', 'sspu'); ?></button>
                    <button class="button category-filter" data-category="custom"><?php esc_html_e('Custom', 'sspu'); ?></button>
                </div>
                
                <div class="template-actions">
                    <button id="create-new-template" class="button button-primary"><?php esc_html_e('Create New Template', 'sspu'); ?></button>
                </div>
                
                <div id="templates-list">
                    <!-- Templates will be loaded here via AJAX -->
                </div>
            </div>
        </div>
        <?php
    }

    public function alibaba_queue_page_html() {
        if (!current_user_can('manage_options')) return;
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Alibaba URL Queue Management', 'sspu'); ?></h1>
            
            <div class="alibaba-queue-container">
                <div class="queue-stats">
                    <h2><?php esc_html_e('Queue Statistics', 'sspu'); ?></h2>
                    <div class="stat-boxes">
                        <div class="stat-box">
                            <h4><?php esc_html_e('Total URLs', 'sspu'); ?></h4>
                            <p id="stat-total">0</p>
                        </div>
                        <div class="stat-box">
                            <h4><?php esc_html_e('Available', 'sspu'); ?></h4>
                            <p id="stat-available">0</p>
                        </div>
                        <div class="stat-box">
                            <h4><?php esc_html_e('Assigned', 'sspu'); ?></h4>
                            <p id="stat-assigned">0</p>
                        </div>
                        <div class="stat-box">
                            <h4><?php esc_html_e('Completed', 'sspu'); ?></h4>
                            <p id="stat-completed">0</p>
                        </div>
                    </div>
                </div>

                <div class="url-input-section">
                    <h2><?php esc_html_e('Add URLs to Queue', 'sspu'); ?></h2>
                    <p class="description"><?php esc_html_e('Paste Alibaba URLs below, one per line. Only Alibaba.com and 1688.com URLs will be accepted.', 'sspu'); ?></p>
                    
                    <textarea id="alibaba-urls-input" rows="10" style="width: 100%; font-family: monospace;" placeholder="https://www.alibaba.com/product-detail/...
https://detail.1688.com/offer/..."></textarea>
                    
                    <div class="url-actions">
                        <button type="button" id="add-urls-btn" class="button button-primary"><?php esc_html_e('Add URLs to Queue', 'sspu'); ?></button>
                        <button type="button" id="append-urls-btn" class="button"><?php esc_html_e('Append to Existing', 'sspu'); ?></button>
                        <span class="spinner"></span>
                    </div>
                </div>

                <div class="queue-management">
                    <h2><?php esc_html_e('Queue Management', 'sspu'); ?></h2>
                    <div class="management-actions">
                        <button type="button" id="refresh-queue-btn" class="button"><?php esc_html_e('Refresh', 'sspu'); ?></button>
                        <button type="button" id="clear-completed-btn" class="button"><?php esc_html_e('Clear Completed', 'sspu'); ?></button>
                        <button type="button" id="clear-unassigned-btn" class="button"><?php esc_html_e('Clear Unassigned', 'sspu'); ?></button>
                        <button type="button" id="clear-all-btn" class="button button-link-delete"><?php esc_html_e('Clear All', 'sspu'); ?></button>
                    </div>
                </div>

                <div class="current-queue">
                    <h2><?php esc_html_e('Current Queue', 'sspu'); ?></h2>
                    <div id="queue-list-container">
                        <p><?php esc_html_e('Loading...', 'sspu'); ?></p>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    public function analytics_page_html() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Analytics & Performance', 'sspu'); ?></h1>

            <div class="analytics-filters">
                <select id="analytics-period">
                    <option value="7"><?php esc_html_e('Last 7 Days', 'sspu'); ?></option>
                    <option value="30" selected><?php esc_html_e('Last 30 Days', 'sspu'); ?></option>
                    <option value="90"><?php esc_html_e('Last 90 Days', 'sspu'); ?></option>
                    <option value="365"><?php esc_html_e('Last Year', 'sspu'); ?></option>
                </select>
                <button id="refresh-analytics" class="button"><?php esc_html_e('Refresh', 'sspu'); ?></button>
                <button id="export-analytics-btn" class="button"><?php esc_html_e('Export Data', 'sspu'); ?></button>
            </div>

            <div class="analytics-grid">
                <div class="analytics-card">
                    <h3><?php esc_html_e('Upload Performance', 'sspu'); ?></h3>
                    <canvas id="upload-performance-chart"></canvas>
                </div>

                <div class="analytics-card">
                    <h3><?php esc_html_e('User Comparison', 'sspu'); ?></h3>
                    <canvas id="user-comparison-chart"></canvas>
                </div>

                <div class="analytics-card">
                    <h3><?php esc_html_e('Error Patterns', 'sspu'); ?></h3>
                    <canvas id="error-patterns-chart"></canvas>
                </div>

                <div class="analytics-card">
                    <h3><?php esc_html_e('Activity Breakdown', 'sspu'); ?></h3>
                    <canvas id="activity-breakdown-chart"></canvas>
                </div>

                <div class="analytics-card">
                    <h3><?php esc_html_e('Peak Hours', 'sspu'); ?></h3>
                    <canvas id="peak-hours-chart"></canvas>
                </div>

                <div class="analytics-card">
                    <h3><?php esc_html_e('Time Tracking', 'sspu'); ?></h3>
                    <div id="time-tracking-stats"></div>
                </div>
            </div>

            <div class="detailed-analytics">
                <h2><?php esc_html_e('Detailed User Activity', 'sspu'); ?></h2>
                <div id="user-activity-log"></div>
            </div>
        </div>
        <?php
    }

    public function search_page_html() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Product Search & Listing', 'sspu'); ?></h1>

            <div class="search-interface">
                <div class="search-form">
                    <input type="text" id="global-search-input" placeholder="<?php esc_attr_e('Search products, variants, collections...', 'sspu'); ?>" />
                    <button id="global-search-btn" class="button button-primary"><?php esc_html_e('Search', 'sspu'); ?></button>
                    <button id="clear-search-btn" class="button"><?php esc_html_e('Clear', 'sspu'); ?></button>
                </div>

                <div class="search-filters">
                    <select id="search-type">
                        <option value="all"><?php esc_html_e('All', 'sspu'); ?></option>
                        <option value="products" selected><?php esc_html_e('Products', 'sspu'); ?></option>
                        <option value="variants"><?php esc_html_e('Variants', 'sspu'); ?></option>
                        <option value="collections"><?php esc_html_e('Collections', 'sspu'); ?></option>
                    </select>

                    <select id="search-date-range">
                        <option value="all"><?php esc_html_e('All Time', 'sspu'); ?></option>
                        <option value="7"><?php esc_html_e('Last 7 Days', 'sspu'); ?></option>
                        <option value="30"><?php esc_html_e('Last 30 Days', 'sspu'); ?></option>
                        <option value="90"><?php esc_html_e('Last 90 Days', 'sspu'); ?></option>
                    </select>

                    <select id="search-user">
                        <option value="all"><?php esc_html_e('All Users', 'sspu'); ?></option>
                        <!-- Populated via JS -->
                    </select>

                    <select id="search-status">
                        <option value="all"><?php esc_html_e('All Status', 'sspu'); ?></option>
                        <option value="success"><?php esc_html_e('Published', 'sspu'); ?></option>
                        <option value="error"><?php esc_html_e('Failed', 'sspu'); ?></option>
                    </select>

                    <select id="results-per-page">
                        <option value="20">20 per page</option>
                        <option value="50" selected>50 per page</option>
                        <option value="100">100 per page</option>
                        <option value="200">200 per page</option>
                    </select>
                </div>

                <div class="pagination-controls" id="pagination-top" style="margin: 20px 0; text-align: center;">
                    <!-- Pagination will be inserted here -->
                </div>
            </div>

            <div id="search-results">
                <div class="loading-indicator" style="text-align: center; padding: 20px;">
                    <span class="spinner is-active" style="float: none;"></span>
                    <p><?php esc_html_e('Loading products...', 'sspu'); ?></p>
                </div>
            </div>

            <div class="pagination-controls" id="pagination-bottom" style="margin: 20px 0; text-align: center;">
                <!-- Pagination will be inserted here -->
            </div>
        </div>
        <?php
    }

       public function leaderboard_page_html() {
        global $wpdb;

        $period = isset($_GET['period']) ? absint($_GET['period']) : 7;
        $table_name = $wpdb->prefix . 'sspu_product_log';
        $user_table = $wpdb->prefix . 'users';

        $where_clause = '';
        if ($period > 0) {
            $where_clause = $wpdb->prepare("WHERE log.upload_timestamp >= DATE_SUB(NOW(), INTERVAL %d DAY)", $period);
        }

        $leaderboard_data = $wpdb->get_results(
            "SELECT COUNT(log.log_id) as product_count, u.display_name, log.wp_user_id,
                    AVG(log.upload_duration) as avg_duration,
                    SUM(CASE WHEN log.status = 'success' THEN 1 ELSE 0 END) as success_count,
                    SUM(CASE WHEN log.status = 'error' THEN 1 ELSE 0 END) as error_count
            FROM {$table_name} as log
            JOIN {$user_table} as u ON log.wp_user_id = u.ID
            {$where_clause}
            GROUP BY log.wp_user_id
            ORDER BY product_count DESC"
        );

        $total_products = 0;
        $total_errors = 0;
        foreach ($leaderboard_data as $row) {
            $total_products += $row->product_count;
            $total_errors += $row->error_count;
        }
        $total_staff = count($leaderboard_data);
        $success_rate = $total_products > 0 ? round((($total_products - $total_errors) / $total_products) * 100, 1) : 0;
        $top_uploader = $total_staff > 0 ? $leaderboard_data[0]->display_name . ' (' . $leaderboard_data[0]->product_count . ')' : 'N/A';

        ?>
        <div class="wrap sspu-dashboard">
            <h1><?php esc_html_e('Dashboard & Stats', 'sspu'); ?></h1>

            <div class="sspu-dashboard-filters">
                <a href="?page=sspu-leaderboard&period=7" class="<?php echo $period === 7 ? 'current' : ''; ?>">Last 7 Days</a> |
                <a href="?page=sspu-leaderboard&period=30" class="<?php echo $period === 30 ? 'current' : ''; ?>">Last 30 Days</a> |
                <a href="?page=sspu-leaderboard&period=0" class="<?php echo $period === 0 ? 'current' : ''; ?>">All Time</a>
            </div>

            <div class="sspu-stat-boxes">
                <div class="stat-box">
                    <h4>Total Products</h4>
                    <p><?php echo esc_html($total_products); ?></p>
                </div>
                <div class="stat-box">
                    <h4>Success Rate</h4>
                    <p><?php echo esc_html($success_rate); ?>%</p>
                </div>
                <div class="stat-box">
                    <h4>Active Users</h4>
                    <p><?php echo esc_html($total_staff); ?></p>
                </div>
                <div class="stat-box">
                    <h4>Top Uploader</h4>
                    <p><?php echo esc_html($top_uploader); ?></p>
                </div>
            </div>

            <h2>Detailed Leaderboard</h2>
            <table class="wp-list-table widefat striped">
                <thead>
                    <tr>
                        <th style="width: 50px;">Rank</th>
                        <th>User</th>
                        <th>Products</th>
                        <th>Success Rate</th>
                        <th>Avg Duration</th>
                        <th>Performance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($leaderboard_data)) : ?>
                        <tr>
                            <td colspan="6">No products have been uploaded in this period.</td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($leaderboard_data as $rank => $row) : ?>
                            <?php
                            $user_success_rate = $row->product_count > 0 ? round(($row->success_count / $row->product_count) * 100, 1) : 0;
                            $avg_duration = $row->avg_duration ? round($row->avg_duration / 60, 1) : 0;
                            $performance_class = $user_success_rate >= 95 ? 'excellent' : ($user_success_rate >= 80 ? 'good' : 'needs-improvement');
                            ?>
                            <tr>
                                <td><?php echo $rank + 1; ?></td>
                                <td><?php echo esc_html($row->display_name); ?></td>
                                <td><?php echo esc_html($row->product_count); ?></td>
                                <td><?php echo esc_html($user_success_rate); ?>%</td>
                                <td><?php echo esc_html($avg_duration); ?> min</td>
                                <td><span class="performance-badge <?php echo $performance_class; ?>"><?php echo ucfirst(str_replace('-', ' ', $performance_class)); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function handle_get_single_template_content() {
    check_ajax_referer('sspu_ajax_nonce', 'nonce');
    
    if (!current_user_can('upload_shopify_products')) {
        wp_send_json_error(['message' => 'Permission denied']);
        return;
    }
    
    $template_id = absint($_POST['template_id']);
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'sspu_image_templates';
    
    $template = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$table_name} WHERE template_id = %d",
        $template_id
    ));
    
    if (!$template) {
        wp_send_json_error(['message' => 'Template not found']);
        return;
    }
    
    wp_send_json_success([
        'name' => $template->name,
        'content' => $template->prompt
    ]);
}

    public function settings_page_html() {
        if (!current_user_can('manage_options')) return;
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php settings_fields('sspu_settings_group'); do_settings_sections('sspu-settings'); submit_button('Save Settings'); ?>
            </form>
        </div>
        <?php
    }

    // AJAX Handlers with proper error handling

    public function handle_format_product_name() {
        try {
            check_ajax_referer('sspu_ajax_nonce', 'nonce');

            if (!current_user_can('upload_shopify_products')) {
                wp_send_json_error(['message' => 'Permission denied']);
                return;
            }

            error_log('SSPU: handle_format_product_name called');

            $product_name = sanitize_text_field($_POST['product_name']);

            if (empty($product_name)) {
                wp_send_json_error(['message' => 'Product name is required']);
                return;
            }

            $openai = new SSPU_OpenAI();
            $formatted_name = $openai->format_product_name($product_name);

            if ($formatted_name) {
                wp_send_json_success(['formatted_name' => $formatted_name]);
            } else {
                $error_message = $openai->get_last_error() ?: 'Failed to format product name';
                error_log('SSPU: Format name error - ' . $error_message);
                wp_send_json_error(['message' => $error_message]);
            }
        } catch (Exception $e) {
            error_log('SSPU: Exception in handle_format_product_name - ' . $e->getMessage());
            wp_send_json_error(['message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    public function handle_generate_variants() {
        try {
            check_ajax_referer('sspu_ajax_nonce', 'nonce');

            if (!current_user_can('upload_shopify_products')) {
                wp_send_json_error(['message' => 'Permission denied']);
                return;
            }

            error_log('SSPU: handle_generate_variants called');

            $option_name = sanitize_text_field($_POST['option_name']);
            $option_values = sanitize_textarea_field($_POST['option_values']);
            $base_price = floatval($_POST['base_price']);

            if (empty($option_name) || empty($option_values)) {
                wp_send_json_error(['message' => 'Option name and values are required']);
                return;
            }

            // Parse option values (comma or newline separated)
            $values = preg_split('/[,\n\r]+/', $option_values);
            $values = array_map('trim', $values);
            $values = array_filter($values); // Remove empty values

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
                    'sku' => '' // Will be generated later
                ];
            }

            wp_send_json_success(['variants' => $variants]);
        } catch (Exception $e) {
            error_log('SSPU: Exception in handle_generate_variants - ' . $e->getMessage());
            wp_send_json_error(['message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    public function handle_apply_pricing_to_all() {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');

        if (!current_user_can('upload_shopify_products')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }

        $action_type = sanitize_text_field($_POST['action_type']); // 'price' or 'tiers'
        $source_data = $_POST['source_data'];

        wp_send_json_success(['action_type' => $action_type, 'data' => $source_data]);
    }

    public function handle_sku_generation() {
        try {
            check_ajax_referer('sspu_ajax_nonce', 'nonce');

            if (!current_user_can('upload_shopify_products')) {
                wp_send_json_error(['message' => 'Permission denied']);
                return;
            }

            error_log('SSPU: handle_sku_generation called');

            $product_name = sanitize_text_field($_POST['product_name']);
            $variant_name = sanitize_text_field($_POST['variant_name']);
            $variant_value = sanitize_text_field($_POST['variant_value']);

            // Use the new intelligent SKU generator
            $sku_generator = SSPU_SKU_Generator::getInstance();
            $sku = $sku_generator->generate_sku($product_name, $variant_name, $variant_value);

            if (!$sku) {
                $error_message = $sku_generator->get_last_error() ?: 'Failed to generate SKU';
                wp_send_json_error(['message' => $error_message]);
                return;
            }

            wp_send_json_success(['sku' => $sku]);
            
        } catch (Exception $e) {
            error_log('SSPU: Exception in handle_sku_generation - ' . $e->getMessage());
            wp_send_json_error(['message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    public function handle_volume_tier_calculation() {
        try {
            check_ajax_referer('sspu_ajax_nonce', 'nonce');

            if (!current_user_can('upload_shopify_products')) {
                wp_send_json_error(['message' => 'Permission denied']);
                return;
            }

            error_log('SSPU: handle_volume_tier_calculation called');

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
            error_log('SSPU: Exception in handle_volume_tier_calculation - ' . $e->getMessage());
            wp_send_json_error(['message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    public function handle_seo_generation() {
        try {
            check_ajax_referer('sspu_ajax_nonce', 'nonce');

            if (!current_user_can('upload_shopify_products')) {
                wp_send_json_error(['message' => 'Permission denied']);
                return;
            }

            error_log('SSPU: handle_seo_generation called');

            $product_name = sanitize_text_field($_POST['product_name']);
            $description = sanitize_textarea_field($_POST['description']);
            $type = sanitize_text_field($_POST['type']); // 'title' or 'meta'

            $openai = new SSPU_OpenAI();

            if ($type === 'title') {
                $result = $openai->generate_seo_title($product_name, $description);
            } else {
                $result = $openai->generate_meta_description($product_name, $description);
            }

            if ($result) {
                wp_send_json_success(['content' => $result]);
            } else {
                $error_message = $openai->get_last_error() ?: 'Failed to generate SEO content';
                error_log('SSPU: SEO generation error - ' . $error_message);
                wp_send_json_error(['message' => $error_message]);
            }
        } catch (Exception $e) {
            error_log('SSPU: Exception in handle_seo_generation - ' . $e->getMessage());
            wp_send_json_error(['message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    public function handle_price_suggestion() {
        try {
            check_ajax_referer('sspu_ajax_nonce', 'nonce');

            if (!current_user_can('upload_shopify_products')) {
                wp_send_json_error(['message' => 'Permission denied']);
                return;
            }

            error_log('SSPU: handle_price_suggestion called');

            $product_name = sanitize_text_field($_POST['product_name']);
            $description = sanitize_textarea_field($_POST['description']);
            $variant_info = sanitize_text_field($_POST['variant_info']);

            $openai = new SSPU_OpenAI();
            $suggested_price = $openai->suggest_price($product_name, $description, $variant_info);

            if ($suggested_price) {
                wp_send_json_success(['price' => $suggested_price]);
            } else {
                $error_message = $openai->get_last_error() ?: 'Failed to suggest price';
                error_log('SSPU: Price suggestion error - ' . $error_message);
                wp_send_json_error(['message' => $error_message]);
            }
        } catch (Exception $e) {
            error_log('SSPU: Exception in handle_price_suggestion - ' . $e->getMessage());
            wp_send_json_error(['message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    public function handle_save_draft() {
        try {
            check_ajax_referer('sspu_ajax_nonce', 'nonce');

            if (!current_user_can('upload_shopify_products')) {
                wp_send_json_error(['message' => 'Permission denied']);
                return;
            }

            $draft_data = $_POST['draft_data'];
            $user_id = get_current_user_id();

            $drafts = new SSPU_Drafts();
            $draft_id = $drafts->save_draft($user_id, $draft_data);

            if ($draft_id) {
                wp_send_json_success(['draft_id' => $draft_id]);
            } else {
                wp_send_json_error(['message' => 'Failed to save draft']);
            }
        } catch (Exception $e) {
            error_log('SSPU: Exception in handle_save_draft - ' . $e->getMessage());
            wp_send_json_error(['message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    public function handle_load_draft() {
        try {
            check_ajax_referer('sspu_ajax_nonce', 'nonce');

            if (!current_user_can('upload_shopify_products')) {
                wp_send_json_error(['message' => 'Permission denied']);
                return;
            }

            $user_id = get_current_user_id();
            $drafts = new SSPU_Drafts();
            $draft_data = $drafts->get_latest_draft($user_id);

            if ($draft_data) {
                wp_send_json_success(['draft_data' => $draft_data]);
            } else {
                wp_send_json_error(['message' => 'No draft found']);
            }
        } catch (Exception $e) {
            error_log('SSPU: Exception in handle_load_draft - ' . $e->getMessage());
            wp_send_json_error(['message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    public function handle_auto_save_draft() {
        try {
            check_ajax_referer('sspu_ajax_nonce', 'nonce');

            if (!current_user_can('upload_shopify_products')) {
                wp_send_json_error(['message' => 'Permission denied']);
                return;
            }

            $draft_data = $_POST['draft_data'];
            $user_id = get_current_user_id();

            $drafts = new SSPU_Drafts();
            $draft_id = $drafts->save_draft($user_id, $draft_data, true);

            if ($draft_id) {
                wp_send_json_success(['draft_id' => $draft_id, 'timestamp' => current_time('mysql')]);
            } else {
                wp_send_json_error(['message' => 'Failed to auto-save draft']);
            }
        } catch (Exception $e) {
            error_log('SSPU: Exception in handle_auto_save_draft - ' . $e->getMessage());
            wp_send_json_error(['message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    public function handle_get_collections() {
        try {
            check_ajax_referer('sspu_ajax_nonce', 'nonce');

            if (!current_user_can('upload_shopify_products')) {
                wp_send_json_error(['message' => 'Permission denied']);
                return;
            }

            error_log('SSPU: handle_get_collections called');

            $start_time = microtime(true);
            
            // Get all collections (both custom and smart) with pagination
            $all_collections = [];
            $has_more = true;
            $page_info = null;
            
            // First get custom collections
            while ($has_more) {
                $endpoint = 'custom_collections.json?limit=250';
                if ($page_info) {
                    $endpoint .= '&page_info=' . $page_info;
                }
                
                $response = $this->send_shopify_request($endpoint, 'GET');
                
                if (isset($response['custom_collections'])) {
                    $all_collections = array_merge($all_collections, $response['custom_collections']);
                }
                
                // Check for next page
                $has_more = false;
                if (isset($response['headers']['link'])) {
                    $links = $this->parse_link_header($response['headers']['link']);
                    if (isset($links['next'])) {
                        $page_info = $links['next']['page_info'];
                        $has_more = true;
                    }
                }
            }
            
            // Reset for smart collections
            $has_more = true;
            $page_info = null;
            
            // Then get smart collections
            while ($has_more) {
                $endpoint = 'smart_collections.json?limit=250';
                if ($page_info) {
                    $endpoint .= '&page_info=' . $page_info;
                }
                
                $response = $this->send_shopify_request($endpoint, 'GET');
                
                if (isset($response['smart_collections'])) {
                    $all_collections = array_merge($all_collections, $response['smart_collections']);
                }
                
                // Check for next page
                $has_more = false;
                if (isset($response['headers']['link'])) {
                    $links = $this->parse_link_header($response['headers']['link']);
                    if (isset($links['next'])) {
                        $page_info = $links['next']['page_info'];
                        $has_more = true;
                    }
                }
            }
            
            // Sort collections alphabetically by title
            usort($all_collections, function($a, $b) {
                return strcasecmp($a['title'], $b['title']);
            });
            
            $duration = microtime(true) - $start_time;

            // Log activity
            $analytics = new SSPU_Analytics();
            $analytics->log_activity(get_current_user_id(), 'get_collections', [
                'duration' => $duration,
                'collection_count' => count($all_collections),
                'status' => 'success'
            ]);

            error_log('SSPU: Found ' . count($all_collections) . ' total collections');
            wp_send_json_success($all_collections);
            
        } catch (Exception $e) {
            error_log('SSPU: Exception in handle_get_collections - ' . $e->getMessage());
            wp_send_json_error(['message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    // Helper function to parse Link header for pagination
    private function parse_link_header($link_header) {
        $links = [];
        
        if (empty($link_header)) {
            return $links;
        }
        
        $parts = explode(',', $link_header);
        
        foreach ($parts as $part) {
            if (preg_match('/<([^>]+)>; rel="([^"]+)"/', trim($part), $matches)) {
                $url = $matches[1];
                $rel = $matches[2];
                
                // Parse page_info from URL
                if (preg_match('/page_info=([^&]+)/', $url, $page_matches)) {
                    $links[$rel] = [
                        'url' => $url,
                        'page_info' => $page_matches[1]
                    ];
                }
            }
        }
        
        return $links;
    }

    public function handle_create_collection() {
        try {
            check_ajax_referer('sspu_ajax_nonce', 'nonce');

            if (!current_user_can('upload_shopify_products')) {
                wp_send_json_error(['message' => 'Permission denied']);
                return;
            }

            $collection_name = sanitize_text_field($_POST['collection_name']);

            $payload = [
                'custom_collection' => [
                    'title' => $collection_name,
                    'published' => true
                ]
            ];

            $start_time = microtime(true);
            $response = $this->send_shopify_request('custom_collections.json', 'POST', $payload);
            $duration = microtime(true) - $start_time;

            // Log activity
            $analytics = new SSPU_Analytics();
            $analytics->log_activity(get_current_user_id(), 'create_collection', [
                'collection_name' => $collection_name,
                'duration' => $duration,
                'status' => isset($response['errors']) ? 'error' : 'success'
            ]);

            if (isset($response['errors'])) {
                error_log('SSPU: Create collection error - ' . json_encode($response['errors']));
                wp_send_json_error(['message' => $response['errors']]);
            }

            wp_send_json_success($response['custom_collection']);
        } catch (Exception $e) {
            error_log('SSPU: Exception in handle_create_collection - ' . $e->getMessage());
            wp_send_json_error(['message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    public function handle_description_generation() {
        try {
            check_ajax_referer('sspu_ajax_nonce', 'nonce');

            if (!current_user_can('upload_shopify_products')) {
                wp_send_json_error(['message' => 'Permission denied']);
                return;
            }

            error_log('SSPU: handle_description_generation called');

            $input_text = sanitize_textarea_field($_POST['input_text']);
            $image_ids = isset($_POST['image_ids']) ? array_map('absint', $_POST['image_ids']) : [];
            $type = sanitize_text_field($_POST['type'] ?? 'description');

            if (empty($input_text) && empty($image_ids)) {
                wp_send_json_error(['message' => 'Please provide text or images for AI analysis']);
                return;
            }

            $start_time = microtime(true);
            $openai = new SSPU_OpenAI();

            switch ($type) {
                case 'tags':
                    $result = $openai->generate_tags($input_text);
                    break;
                case 'price':
                    $result = $openai->suggest_price_from_description($input_text);
                    break;
                default:
                    $result = $openai->generate_product_description($input_text, $image_ids);
            }

            $duration = microtime(true) - $start_time;

            // Log activity
            $analytics = new SSPU_Analytics();
            $analytics->log_activity(get_current_user_id(), 'ai_generation', [
                'type' => $type,
                'duration' => $duration,
                'status' => $result ? 'success' : 'error',
                'input_length' => strlen($input_text),
                'image_count' => count($image_ids)
            ]);

            if ($result !== false) {
                wp_send_json_success([$type => $result]);
            } else {
                $error_message = $openai->get_last_error() ?: 'Failed to generate ' . $type;
                error_log('SSPU: AI generation error - ' . $error_message);
                wp_send_json_error(['message' => $error_message]);
            }
        } catch (Exception $e) {
            error_log('SSPU: Exception in handle_description_generation - ' . $e->getMessage());
            wp_send_json_error(['message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    public function handle_test_openai_api() {
        try {
            check_ajax_referer('sspu_ajax_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                wp_send_json_error(['message' => 'Permission denied']);
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
            error_log('SSPU: Exception in handle_test_openai_api - ' . $e->getMessage());
            wp_send_json_error(['message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    public function handle_test_gemini_api() {
        try {
            check_ajax_referer('sspu_ajax_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                wp_send_json_error(['message' => 'Permission denied']);
                return;
            }

            $ai_editor = new SSPU_AI_Image_Editor();
            if ($ai_editor->test_gemini_connection()) {
                wp_send_json_success(['message' => 'API connection successful']);
            } else {
                wp_send_json_error(['message' => 'Connection test failed']);
            }
        } catch (Exception $e) {
            error_log('SSPU: Exception in handle_test_gemini_api - ' . $e->getMessage());
            wp_send_json_error(['message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    public function handle_validate_image() {
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
            error_log('SSPU: Exception in handle_validate_image - ' . $e->getMessage());
            wp_send_json_error(['message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    public function handle_upload_images() {
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
        } catch (Exception $e) {
            error_log('SSPU: Exception in handle_upload_images - ' . $e->getMessage());
            wp_send_json_error(['message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    public function handle_ai_suggest_all_pricing() {
        try {
            check_ajax_referer('sspu_ajax_nonce', 'nonce');

            if (!current_user_can('upload_shopify_products')) {
                wp_send_json_error(['message' => 'Permission denied']);
                return;
            }

            error_log('SSPU: handle_ai_suggest_all_pricing called');

            // Get parameters
            $product_name = sanitize_text_field($_POST['product_name']);
            $main_image_id = absint($_POST['main_image_id']);
            $min_quantity = absint($_POST['min_quantity']);

            // Validate required fields
            if (empty($product_name) || empty($main_image_id) || empty($min_quantity)) {
                wp_send_json_error(['message' => 'Product name, main image, and minimum quantity are required']);
                return;
            }

            // Initialize OpenAI
            $openai = new SSPU_OpenAI();

            // Get base price suggestion
            $base_price = $openai->suggest_base_price_with_image($product_name, $main_image_id, $min_quantity);

            if ($base_price === false) {
                $error_message = $openai->get_last_error() ?: 'Failed to generate base price';
                error_log('SSPU: AI pricing error - ' . $error_message);
                wp_send_json_error(['message' => $error_message]);
                return;
            }

            // Get volume tiers suggestion
            $tiers = $openai->suggest_volume_tiers($product_name, $base_price, $min_quantity);

            if ($tiers === false) {
                // If tiers fail, at least return the base price
                wp_send_json_success([
                    'base_price' => $base_price,
                    'tiers' => [],
                    'rationale' => 'Base price generated successfully, but volume tiers generation failed.'
                ]);
                return;
            }

            // Return the complete pricing structure
            wp_send_json_success([
                'base_price' => $base_price,
                'tiers' => $tiers,
                'rationale' => "AI-generated pricing based on product analysis. Base price for {$min_quantity} units, with volume discounts for larger orders."
            ]);

        } catch (Exception $e) {
            error_log('SSPU: Exception in handle_ai_suggest_all_pricing - ' . $e->getMessage());
            wp_send_json_error(['message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    public function handle_estimate_weight() {
        try {
            check_ajax_referer('sspu_ajax_nonce', 'nonce');

            if (!current_user_can('upload_shopify_products')) {
                wp_send_json_error(['message' => 'Permission denied']);
                return;
            }

            error_log('SSPU: handle_estimate_weight called');

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

            $openai = new SSPU_OpenAI();
            $estimated_weight = $openai->estimate_product_weight($product_name, $main_image_id);

            if ($estimated_weight !== false) {
                wp_send_json_success(['weight' => $estimated_weight]);
            } else {
                $error_message = $openai->get_last_error() ?: 'Failed to estimate weight';
                error_log('SSPU: Weight estimation error - ' . $error_message);
                wp_send_json_error(['message' => $error_message]);
            }
        } catch (Exception $e) {
            error_log('SSPU: Exception in handle_estimate_weight - ' . $e->getMessage());
            wp_send_json_error(['message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    // Alibaba Queue handlers
    public function handle_request_alibaba_url() {
        $alibaba_queue = new SSPU_Alibaba_Queue();
        $alibaba_queue->handle_request_url();
    }

    public function handle_complete_alibaba_url() {
        $alibaba_queue = new SSPU_Alibaba_Queue();
        $alibaba_queue->handle_complete_url();
    }

    public function handle_release_alibaba_url() {
        $alibaba_queue = new SSPU_Alibaba_Queue();
        $alibaba_queue->handle_release_url();
    }

    // Image retrieval handlers
    public function handle_retrieve_alibaba_images() {
        $image_retriever = new SSPU_Image_Retriever();
        $image_retriever->handle_retrieve_images();
    }

    public function handle_download_external_image() {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');
        
        if (!current_user_can('upload_shopify_products')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }
        
        $image_url = esc_url_raw($_POST['image_url']);
        $filename = sanitize_file_name($_POST['filename']);
        
        if (empty($image_url)) {
            wp_send_json_error(['message' => 'Invalid image URL']);
            return;
        }
        
        // Include required files
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        // Download image to temp location
        $tmp = download_url($image_url, 300); // 5 minute timeout
        
        if (is_wp_error($tmp)) {
            wp_send_json_error(['message' => 'Failed to download image: ' . $tmp->get_error_message()]);
            return;
        }
        
        // Get file info
        $file_info = wp_check_filetype($tmp);
        if (!$file_info['ext']) {
            // If no extension found, assume jpg
            $file_info['ext'] = 'jpg';
            $file_info['type'] = 'image/jpeg';
        }
        
        $file_array = [
            'name' => $filename . '.' . $file_info['ext'],
            'type' => $file_info['type'],
            'tmp_name' => $tmp,
            'error' => 0,
            'size' => filesize($tmp),
        ];
        
        // Check for upload errors
        if ($file_array['size'] > wp_max_upload_size()) {
            @unlink($tmp);
            wp_send_json_error(['message' => 'File too large']);
            return;
        }
        
        // Do the validation and storage stuff
        $attachment_id = media_handle_sideload($file_array, 0, null, [
            'post_title' => $filename,
            'post_content' => 'Downloaded from Alibaba',
            'post_status' => 'inherit'
        ]);
        
        // Clean up temp file
        @unlink($tmp);
        
        if (is_wp_error($attachment_id)) {
            wp_send_json_error(['message' => 'Failed to create attachment: ' . $attachment_id->get_error_message()]);
            return;
        }
        
        // Get attachment URLs
        $attachment_url = wp_get_attachment_url($attachment_id);
        $thumb_url = wp_get_attachment_thumb_url($attachment_id);
        
        // Log the activity
        $analytics = new SSPU_Analytics();
        $analytics->log_activity(get_current_user_id(), 'alibaba_image_downloaded', [
            'attachment_id' => $attachment_id,
            'source_url' => $image_url,
            'filename' => $filename
        ]);
        
        wp_send_json_success([
            'attachment_id' => $attachment_id,
            'url' => $attachment_url,
            'thumb_url' => $thumb_url,
            'filename' => $filename
        ]);
    }

    // AI Image editing handlers
    public function handle_ai_edit_image() {
        $ai_editor = new SSPU_AI_Image_Editor();
        $ai_editor->handle_ai_edit();
    }

    public function handle_get_chat_history() {
        $ai_editor = new SSPU_AI_Image_Editor();
        $ai_editor->handle_get_chat_history();
    }

    public function handle_save_edited_image() {
        $ai_editor = new SSPU_AI_Image_Editor();
        $ai_editor->handle_save_edited_image();
    }

    // Template handlers
    public function handle_get_image_templates() {
        $templates = new SSPU_Image_Templates();
        $templates->handle_get_templates();
    }

    public function handle_save_image_template() {
        $templates = new SSPU_Image_Templates();
        $templates->handle_save_template();
    }

    public function handle_delete_image_template() {
        $templates = new SSPU_Image_Templates();
        $templates->handle_delete_template();
    }

    // Proxy methods for analytics and search
    public function handle_get_analytics_proxy() {
        $analytics = new SSPU_Analytics();
        $analytics->handle_get_analytics();
    }

    public function handle_export_analytics() {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');

        if (!current_user_can('upload_shopify_products')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }

        // For now, return a simple message
        // You can implement CSV export functionality here later
        wp_send_json_error(['message' => 'Export functionality not yet implemented']);
    }

    public function handle_get_user_activity_proxy() {
        $analytics = new SSPU_Analytics();
        $analytics->handle_get_user_activity();
    }

    public function handle_global_search_proxy() {
        $search = new SSPU_Search();
        $search->handle_global_search();
    }

    public function handle_get_search_filters_proxy() {
        $search = new SSPU_Search();
        $search->handle_get_search_filters();
    }

    public function handle_product_submission() {
        try {
            check_ajax_referer('sspu_create_product', 'sspu_nonce');

            if (!current_user_can('upload_shopify_products')) {
                wp_send_json_error(['log' => ['Permission Denied.']]);
            }

            $start_time = microtime(true);
            $log = ["✅ Security checks passed."];
            $title = sanitize_text_field($_POST['product_name']);
            $description = wp_kses_post($_POST['product_description']);

            // Validate required fields
            if (empty($title)) {
                wp_send_json_error(['log' => ['❌ ERROR: Product name is required.']]);
                return;
            }

            // Handle multiple collections
            $collection_ids = isset($_POST['product_collections']) && is_array($_POST['product_collections']) 
                ? array_map('absint', $_POST['product_collections']) 
                : [];

            // Handle SEO fields
            $seo_title = sanitize_text_field($_POST['seo_title'] ?? '');
            $meta_description = sanitize_textarea_field($_POST['meta_description'] ?? '');
            $url_handle = sanitize_title($_POST['url_handle'] ?? sanitize_title($title));
            $tags = sanitize_text_field($_POST['product_tags'] ?? '');

            $variants_data = [];
            $variant_metafields = [];

            if (isset($_POST['variant_options']) && is_array($_POST['variant_options'])) {
                foreach ($_POST['variant_options'] as $index => $v) {
                    $variant_data = [
                        'option1'  => sanitize_text_field($v['value']),
                        'price'    => sanitize_text_field($v['price']),
                        'sku'      => sanitize_text_field($v['sku']),
                        'weight'   => !empty($v['weight']) ? floatval($v['weight']) : null,
                        'weight_unit' => 'lb', // Always use pounds
                    ];
                    
                    // Only include weight fields if weight is provided
                    if ($variant_data['weight'] === null) {
                        unset($variant_data['weight']);
                        unset($variant_data['weight_unit']);
                    }
                    
                    $variants_data[] = $variant_data;

                    // Handle volume tiers for this variant
                    if (isset($v['tiers']) && is_array($v['tiers'])) {
                        $tiers = [];
                        foreach ($v['tiers'] as $tier) {
                            if (!empty($tier['min_quantity']) && !empty($tier['price'])) {
                                $tiers[] = [
                                    'min_quantity' => absint($tier['min_quantity']),
                                    'price' => floatval($tier['price'])
                                ];
                            }
                        }
                        if (!empty($tiers)) {
                            $variant_metafields[$index] = $tiers;
                        }
                    }
                }
            }

            if (empty($variants_data)) {
                wp_send_json_error(['log' => ['❌ ERROR: At least one variant is required.']]);
                return;
            }

            $log[] = "✅ Product data sanitized.";

            // Prepare product metafields
            $product_metafields = [];

            // Print methods
            $print_methods = isset($_POST['print_methods']) ? $_POST['print_methods'] : [];
            foreach (['silkscreen', 'uvprint', 'embroidery', 'emboss', 'sublimation', 'laserengrave'] as $method) {
                $product_metafields[] = [
                    'key' => $method,
                    'value' => in_array($method, $print_methods) ? 'true' : 'false',
                    'namespace' => 'custom',
                    'type' => 'boolean'
                ];
            }

            // Min/Max quantities
            if (!empty($_POST['product_min'])) {
                $product_metafields[] = [
                    'key' => 'min',
                    'value' => strval(absint($_POST['product_min'])),
                    'namespace' => 'custom',
                    'type' => 'number_integer'
                ];
            }

            if (!empty($_POST['product_max'])) {
                $product_metafields[] = [
                    'key' => 'max',
                    'value' => strval(absint($_POST['product_max'])),
                    'namespace' => 'custom',
                    'type' => 'number_integer'
                ];
            }

            // SEO metafields
            if (!empty($seo_title)) {
                $product_metafields[] = [
                    'key' => 'title_tag',
                    'value' => $seo_title,
                    'namespace' => 'global',
                    'type' => 'single_line_text_field'
                ];
            }

            if (!empty($meta_description)) {
                $product_metafields[] = [
                    'key' => 'description_tag',
                    'value' => $meta_description,
                    'namespace' => 'global',
                    'type' => 'multi_line_text_field'
                ];
            }

            // Create product with ACTIVE status
            $shopify_product_payload = [
                'product' => [
                    'title' => $title,
                    'body_html' => $description,
                    'status' => 'active', // Changed from 'draft' to 'active'
                    'variants' => $variants_data,
                    'options' => [['name' => sanitize_text_field($_POST['variant_options'][0]['name'] ?: 'Title')]],
                    'metafields' => $product_metafields,
                    'handle' => $url_handle,
                    'tags' => $tags,
                    'published' => true, // Ensure it's published
                    'published_at' => current_time('c'), // Published at current time
                ]
            ];

            $log[] = "⏳ Creating product on Shopify...";
            $response = $this->send_shopify_request('products.json', 'POST', $shopify_product_payload);

            if (isset($response['errors'])) {
                $duration = microtime(true) - $start_time;
                $this->log_upload_attempt($title, 'error', $duration, $response['errors']);
                $log[] = "❌ ERROR: " . json_encode($response['errors']);
                wp_send_json_error(['log' => $log]);
            }

            $shopify_product = $response['product'];
            $product_id = $shopify_product['id'];
            $product_handle = $shopify_product['handle'];
            $log[] = "✅ Product created successfully! Shopify Product ID: $product_id";

            // Add to collections if specified
            if (!empty($collection_ids)) {
                foreach ($collection_ids as $collection_id) {
                    $collect_payload = [
                        'collect' => [
                            'product_id' => $product_id,
                            'collection_id' => $collection_id
                        ]
                    ];
                    $this->send_shopify_request('collects.json', 'POST', $collect_payload);
                }
                $log[] = "✅ Product added to " . count($collection_ids) . " collection(s).";
            }

            // Publish to all sales channels
            $this->publish_to_all_channels($product_id, $log);

            // Add variant metafields (volume tiers)
            foreach ($variant_metafields as $variant_index => $tiers) {
                if (isset($shopify_product['variants'][$variant_index])) {
                    $variant_id = $shopify_product['variants'][$variant_index]['id'];
                    $metafield_payload = [
                        'metafield' => [
                            'namespace' => 'custom',
                            'key' => 'volume_tiers',
                            'value' => json_encode($tiers),
                            'type' => 'json'
                        ]
                    ];
                    $this->send_shopify_request("variants/{$variant_id}/metafields.json", 'POST', $metafield_payload);
                    $log[] = "✅ Volume tiers added to variant " . ($variant_index + 1);
                }
            }

            $log[] = "⏳ Processing images...";
            $all_image_posts = [];
            $main_image_id = absint($_POST['main_image_id']);
            if ($main_image_id > 0) $all_image_posts[] = ['id' => $main_image_id, 'is_variant' => false, 'variant_id' => null];

            $additional_ids = array_filter(array_map('absint', explode(',', $_POST['additional_image_ids'])));
            foreach ($additional_ids as $id) $all_image_posts[] = ['id' => $id, 'is_variant' => false, 'variant_id' => null];

            foreach ($_POST['variant_options'] as $index => $variant) {
                $variant_image_id = isset($variant['image_id']) ? absint($variant['image_id']) : 0;
                if ($variant_image_id > 0 && isset($shopify_product['variants'][$index])) {
                     $all_image_posts[] = ['id' => $variant_image_id, 'is_variant' => true, 'variant_shopify_id' => $shopify_product['variants'][$index]['id']];
                }
            }

            foreach ($all_image_posts as $image_post) {
                $image_url = wp_get_attachment_url($image_post['id']);
                if ($image_url) {
                    $image_payload = ['image' => ['src' => $image_url]];
                    if ($image_post['is_variant']) {
                        $image_payload['image']['variant_ids'] = [$image_post['variant_shopify_id']];
                    }
                    $this->send_shopify_request("products/{$product_id}/images.json", 'POST', $image_payload);
                    $log[] = "⬆️ Uploaded/associated image: " . basename($image_url);
                }
            }
            $log[] = "✅ All images processed.";

            $duration = microtime(true) - $start_time;
            
            // Store handle with product data for later retrieval
            $this->log_upload_attempt($title, 'success', $duration, null, $product_id, $product_handle);

            $store_name = get_option('sspu_shopify_store_name');
            $admin_url = "https://admin.shopify.com/store/{$store_name}/products/{$product_id}";
            $live_url = "https://qstomize.com/products/{$product_handle}";
            
            $log[] = "\n🎉 All Done!";
            $log[] = "Admin URL: $admin_url";
            $log[] = "Live URL: $live_url";
            
            wp_send_json_success([
                'log' => $log, 
                'product_url' => $admin_url,
                'live_url' => $live_url,
                'handle' => $product_handle
            ]);
        } catch (Exception $e) {
            error_log('SSPU: Exception in handle_product_submission - ' . $e->getMessage());
            wp_send_json_error(['log' => ['❌ ERROR: ' . $e->getMessage()]]);
        }
    }

    // New method to publish product to all sales channels
    private function publish_to_all_channels($product_id, &$log) {
        // Get all available sales channels
        $channels_response = $this->send_shopify_request('publications.json', 'GET');
        
        if (isset($channels_response['publications'])) {
            $published_count = 0;
            
            foreach ($channels_response['publications'] as $publication) {
                // Publish product to each channel
                $publish_payload = [
                    'product_publication' => [
                        'product_id' => $product_id,
                        'published' => true,
                        'published_at' => current_time('c')
                    ]
                ];
                
                $publish_response = $this->send_shopify_request(
                    "publications/{$publication['id']}/product_listings.json", 
                    'PUT', 
                    ['product_listing' => ['product_id' => $product_id]]
                );
                
                if (!isset($publish_response['errors'])) {
                    $published_count++;
                }
            }
            
            if ($published_count > 0) {
                $log[] = "✅ Product published to {$published_count} sales channel(s).";
            }
        }
        
        // Ensure product is available on Online Store specifically
        $online_store_payload = [
            'product' => [
                'id' => $product_id,
                'published_scope' => 'global' // Makes it available everywhere
            ]
        ];
        
        $this->send_shopify_request("products/{$product_id}.json", 'PUT', $online_store_payload);
        $log[] = "✅ Product set to global availability (all markets).";
    }

    // Updated log_upload_attempt to include handle
    private function log_upload_attempt($title, $status, $duration, $error_data = null, $product_id = null, $handle = null) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sspu_product_log';

        // Store handle in shopify_data JSON column
        $shopify_data = $handle ? json_encode(['handle' => $handle]) : null;

        $wpdb->insert($table_name, [
            'upload_timestamp' => current_time('mysql'),
            'wp_user_id' => get_current_user_id(),
            'shopify_product_id' => $product_id,
            'product_title' => $title,
            'status' => $status,
            'upload_duration' => $duration,
            'error_data' => $error_data ? json_encode($error_data) : null,
            'shopify_data' => $shopify_data
        ]);

        // Also log to analytics
        $analytics = new SSPU_Analytics();
        $analytics->log_activity(get_current_user_id(), 'product_upload', [
            'product_title' => $title,
            'status' => $status,
            'duration' => $duration,
            'error_data' => $error_data,
            'product_id' => $product_id,
            'handle' => $handle
        ]);
    }

    private function send_shopify_request($endpoint, $method = 'POST', $payload = [], $include_headers = false) {
        $store_name = get_option('sspu_shopify_store_name');
        $access_token = get_option('sspu_shopify_access_token');

        if (empty($store_name) || empty($access_token)) {
            error_log('SSPU: Shopify credentials not configured');
            return ['errors' => 'API credentials are not set in settings.'];
        }

        $url = "https://{$store_name}.myshopify.com/admin/api/2024-10/{$endpoint}";
        $args = [
            'method'  => $method,
            'headers' => ['Content-Type' => 'application/json', 'X-Shopify-Access-Token' => $access_token],
            'body'    => $method !== 'GET' ? json_encode($payload) : null,
            'timeout' => 30,
        ];

        error_log('SSPU: Sending Shopify request to ' . $endpoint);

        $response = $method === 'GET' ? wp_remote_get($url, $args) : wp_remote_post($url, $args);

        if (is_wp_error($response)) {
            error_log('SSPU: Shopify request error - ' . $response->get_error_message());
            return ['errors' => $response->get_error_message()];
        }

        $body = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);

        if (!$decoded) {
            error_log('SSPU: Invalid Shopify response - ' . substr($body, 0, 500));
            return ['errors' => 'Invalid response from Shopify API'];
        }

        // If we need headers (for pagination), include them
        if ($include_headers) {
            $headers = wp_remote_retrieve_headers($response);
            $decoded['headers'] = [
                'link' => isset($headers['link']) ? $headers['link'] : null
            ];
        }

        return $decoded;
    }
}