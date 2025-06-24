<?php
if(!defined('WPINC'))die;

class SSPU_Admin_Settings {
    
    /**
     * Register plugin settings
     */
    public function register_settings() {
        // Register settings
        register_setting('sspu_settings_group', 'sspu_shopify_store_name');
        register_setting('sspu_settings_group', 'sspu_shopify_access_token');
        register_setting('sspu_settings_group', 'sspu_openai_api_key');
        register_setting('sspu_settings_group', 'sspu_gemini_api_key');
        register_setting('sspu_settings_group', 'sspu_sku_pattern');
        register_setting('sspu_settings_group', 'sspu_volume_tier_multipliers');
        register_setting('sspu_settings_group', 'sspu_seo_template');
        register_setting('sspu_settings_group', 'sspu_slack_webhook_url'); // New setting
        
        // NEW: Register Cloudinary settings
        register_setting('sspu_settings_group', 'sspu_cloudinary_cloud_name');
        register_setting('sspu_settings_group', 'sspu_cloudinary_api_key');
        register_setting('sspu_settings_group', 'sspu_cloudinary_api_secret');

        // Shopify API section
        add_settings_section(
            'sspu_settings_section',
            __('Shopify API Credentials', 'sspu'),
            null,
            'sspu-settings'
        );
        
        add_settings_field(
            'sspu_shopify_store_name',
            __('Shopify Store Name', 'sspu'),
            [$this, 'store_name_field_html'],
            'sspu-settings',
            'sspu_settings_section'
        );
        
        add_settings_field(
            'sspu_shopify_access_token',
            __('Admin API Access Token', 'sspu'),
            [$this, 'access_token_field_html'],
            'sspu-settings',
            'sspu_settings_section'
        );
        
        // AI Configuration section
        add_settings_section(
            'sspu_openai_section',
            __('AI Configuration', 'sspu'),
            null,
            'sspu-settings'
        );
        
        add_settings_field(
            'sspu_openai_api_key',
            __('OpenAI API Key', 'sspu'),
            [$this, 'openai_api_key_field_html'],
            'sspu-settings',
            'sspu_openai_section'
        );
        
        add_settings_field(
            'sspu_gemini_api_key',
            __('Google Gemini API Key', 'sspu'),
            [$this, 'gemini_api_key_field_html'],
            'sspu-settings',
            'sspu_openai_section'
        );

        // NEW: Cloudinary API section
        add_settings_section(
            'sspu_cloudinary_section',
            __('Cloudinary API Credentials', 'sspu'),
            function() {
                echo '<p>' . __('Credentials for uploading design background and mask files to Cloudinary.', 'sspu') . '</p>';
            },
            'sspu-settings'
        );

        add_settings_field(
            'sspu_cloudinary_cloud_name',
            __('Cloudinary Cloud Name', 'sspu'),
            [$this, 'cloudinary_cloud_name_html'],
            'sspu-settings',
            'sspu_cloudinary_section'
        );

        add_settings_field(
            'sspu_cloudinary_api_key',
            __('Cloudinary API Key', 'sspu'),
            [$this, 'cloudinary_api_key_html'],
            'sspu-settings',
            'sspu_cloudinary_section'
        );

        add_settings_field(
            'sspu_cloudinary_api_secret',
            __('Cloudinary API Secret', 'sspu'),
            [$this, 'cloudinary_api_secret_html'],
            'sspu-settings',
            'sspu_cloudinary_section'
        );
        
        // Automation Settings section
        add_settings_section(
            'sspu_automation_section',
            __('Automation Settings', 'sspu'),
            null,
            'sspu-settings'
        );
        
        add_settings_field(
            'sspu_sku_pattern',
            __('SKU Pattern', 'sspu'),
            [$this, 'sku_pattern_field_html'],
            'sspu-settings',
            'sspu_automation_section'
        );
        
        add_settings_field(
            'sspu_volume_tier_multipliers',
            __('Volume Tier Multipliers', 'sspu'),
            [$this, 'volume_tier_multipliers_field_html'],
            'sspu-settings',
            'sspu_automation_section'
        );
        
        add_settings_field(
            'sspu_seo_template',
            __('SEO Template', 'sspu'),
            [$this, 'seo_template_field_html'],
            'sspu-settings',
            'sspu_automation_section'
        );

        // Slack Notification section
        add_settings_section(
            'sspu_slack_section',
            __('Slack Notifications', 'sspu'),
            null,
            'sspu-settings'
        );
        
        add_settings_field(
            'sspu_slack_webhook_url',
            __('Slack Webhook URL', 'sspu'),
            [$this, 'slack_webhook_url_field_html'],
            'sspu-settings',
            'sspu_slack_section'
        );
    }
    
    /**
     * Field HTML generators
     */
    public function store_name_field_html() {
        printf(
            '<input type="text" id="sspu_shopify_store_name" name="sspu_shopify_store_name" value="%s" class="regular-text" />',
            esc_attr(get_option('sspu_shopify_store_name'))
        );
        echo '<p class="description">' . __('Enter your store name (e.g., `mystore` if your URL is mystore.myshopify.com)', 'sspu') . '</p>';
    }
    
    public function access_token_field_html() {
        printf(
            '<input type="password" id="sspu_shopify_access_token" name="sspu_shopify_access_token" value="%s" class="regular-text" />',
            esc_attr(get_option('sspu_shopify_access_token'))
        );
        echo '<p class="description">' . __('Enter your Shopify Admin API access token (shpat_...).', 'sspu') . '</p>';
    }
    
    public function openai_api_key_field_html() {
        $key = get_option('sspu_openai_api_key');
        printf(
            '<input type="password" id="sspu_openai_api_key" name="sspu_openai_api_key" value="%s" class="regular-text" />',
            esc_attr($key)
        );
        echo '<p class="description">' . __('Enter your OpenAI API key for AI features.', 'sspu') . '</p>';
        
        if(!empty($key)) {
            echo '<button type="button" id="test-openai-api" class="button">' . __('Test API Connection', 'sspu') . '</button>';
            echo '<span id="openai-api-test-result" style="margin-left: 10px;"></span>';
            $this->render_api_test_script('openai');
        }
    }
    
    public function gemini_api_key_field_html() {
        $key = get_option('sspu_gemini_api_key');
        printf(
            '<input type="password" id="sspu_gemini_api_key" name="sspu_gemini_api_key" value="%s" class="regular-text" />',
            esc_attr($key)
        );
        echo '<p class="description">' . __('Enter your Google Gemini API key for AI image editing.', 'sspu') . '</p>';
        
        if(!empty($key)) {
            echo '<button type="button" id="test-gemini-api" class="button">' . __('Test API Connection', 'sspu') . '</button>';
            echo '<span id="gemini-api-test-result" style="margin-left: 10px;"></span>';
            $this->render_api_test_script('gemini');
        }
    }
    
    // NEW: HTML generators for Cloudinary fields
    public function cloudinary_cloud_name_html() {
        printf(
            '<input type="text" id="sspu_cloudinary_cloud_name" name="sspu_cloudinary_cloud_name" value="%s" class="regular-text" />',
            esc_attr(get_option('sspu_cloudinary_cloud_name'))
        );
    }

    public function cloudinary_api_key_html() {
        printf(
            '<input type="text" id="sspu_cloudinary_api_key" name="sspu_cloudinary_api_key" value="%s" class="regular-text" />',
            esc_attr(get_option('sspu_cloudinary_api_key'))
        );
    }

    public function cloudinary_api_secret_html() {
        printf(
            '<input type="password" id="sspu_cloudinary_api_secret" name="sspu_cloudinary_api_secret" value="%s" class="regular-text" />',
            esc_attr(get_option('sspu_cloudinary_api_secret'))
        );
    }
    
    public function sku_pattern_field_html() {
        $pattern = get_option('sspu_sku_pattern', '{PRODUCT_NAME}-{VARIANT_VALUE}');
        printf(
            '<input type="text" id="sspu_sku_pattern" name="sspu_sku_pattern" value="%s" class="regular-text" />',
            esc_attr($pattern)
        );
        echo '<p class="description">' . __('SKU pattern. Available tokens: {PRODUCT_NAME}, {VARIANT_VALUE}, {VARIANT_NAME}, {RANDOM}', 'sspu') . '</p>';
    }
    
    public function volume_tier_multipliers_field_html() {
        $multipliers = get_option('sspu_volume_tier_multipliers', '0.95,0.90,0.85,0.80,0.75');
        printf(
            '<input type="text" id="sspu_volume_tier_multipliers" name="sspu_volume_tier_multipliers" value="%s" class="regular-text" />',
            esc_attr($multipliers)
        );
        echo '<p class="description">' . __('Comma-separated multipliers for volume tiers (e.g., 0.95,0.90,0.85)', 'sspu') . '</p>';
    }
    
    public function seo_template_field_html() {
        $template = get_option('sspu_seo_template', 'Buy {PRODUCT_NAME} - High Quality {CATEGORY} | Your Store');
        printf(
            '<textarea id="sspu_seo_template" name="sspu_seo_template" class="large-text" rows="3">%s</textarea>',
            esc_textarea($template)
        );
        echo '<p class="description">' . __('SEO title template. Available tokens: {PRODUCT_NAME}, {CATEGORY}, {BRAND}', 'sspu') . '</p>';
    }

    public function slack_webhook_url_field_html() {
        printf(
            '<input type="url" id="sspu_slack_webhook_url" name="sspu_slack_webhook_url" value="%s" class="large-text" placeholder="https://hooks.slack.com/services/..." />',
            esc_attr(get_option('sspu_slack_webhook_url'))
        );
        echo '<p class="description">' . __('Enter the incoming webhook URL from your Slack app to receive daily reports.', 'sspu') . '</p>';
    }
    
    /**
     * Render API test script
     */
    private function render_api_test_script($service) {
        $nonce = wp_create_nonce('sspu_ajax_nonce');
        ?>
        <script>
        jQuery('#test-<?php echo $service; ?>-api').on('click', function(){
            const button = jQuery(this);
            const result = jQuery('#<?php echo $service; ?>-api-test-result');
            button.prop('disabled', true);
            result.text('Testing...');
            
            jQuery.post(ajaxurl, {
                action: 'sspu_test_<?php echo $service; ?>_api',
                nonce: '<?php echo $nonce; ?>'
            }).done(function(response){
                if(response.success){
                    result.html('<span style="color: green;">✓ API connection successful!</span>');
                } else {
                    result.html('<span style="color: red;">✗ ' + response.data.message + '</span>');
                }
            }).fail(function(){
                result.html('<span style="color: red;">✗ Connection failed</span>');
            }).always(function(){
                button.prop('disabled', false);
            });
        });
        </script>
        <?php
    }
}