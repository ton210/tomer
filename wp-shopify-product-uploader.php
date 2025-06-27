<?php
/**
 * Plugin Name:         Shopify Product Uploader
 * Description:         Allows staff to upload products to a Shopify store directly from the WordPress dashboard with AI-powered image editing.
 * Version:             4.2.0
 * Author:              Your Name
 * License:             GPL-2.0-or-later
 * License URI:         https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:         sspu
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Define constants for easy access to paths and URLs
define( 'SSPU_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'SSPU_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SSPU_VERSION', '4.2.0' );

// Include our class files
require_once SSPU_PLUGIN_PATH . 'includes/class-sspu-roles.php';
require_once SSPU_PLUGIN_PATH . 'includes/class-sspu-shopify-api.php';
// NEW: Include the Cloudinary API class
require_once SSPU_PLUGIN_PATH . 'includes/class-sspu-cloudinary-api.php';
require_once SSPU_PLUGIN_PATH . 'includes/class-sspu-admin.php';
require_once SSPU_PLUGIN_PATH . 'includes/class-sspu-openai.php';
require_once SSPU_PLUGIN_PATH . 'includes/class-sspu-drafts.php';
require_once SSPU_PLUGIN_PATH . 'includes/class-sspu-analytics.php';
require_once SSPU_PLUGIN_PATH . 'includes/class-sspu-search.php';
require_once SSPU_PLUGIN_PATH . 'includes/class-sspu-alibaba-queue.php';
require_once SSPU_PLUGIN_PATH . 'includes/class-sspu-sku-generator.php';
require_once SSPU_PLUGIN_PATH . 'includes/class-sspu-image-retriever.php';
require_once SSPU_PLUGIN_PATH . 'includes/class-sspu-ai-image-editor.php';
require_once SSPU_PLUGIN_PATH . 'includes/class-sspu-image-templates.php';

/**
 * Register activation and deactivation hooks.
 */
register_activation_hook( __FILE__, 'sspu_activate_plugin' );
register_deactivation_hook( __FILE__, [ 'SSPU_Roles', 'deactivate' ] );

/**
 * Plugin activation callback
 */
function sspu_activate_plugin() {
    SSPU_Roles::activate();
    sspu_create_ai_tables();
}

/**
 * Create AI-related database tables
 */
function sspu_create_ai_tables() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // Chat history table
    $chat_table = $wpdb->prefix . 'sspu_ai_chat_history';
    $sql_chat = "CREATE TABLE IF NOT EXISTS $chat_table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        session_id varchar(255) NOT NULL,
        user_id bigint(20) NOT NULL,
        message_type varchar(50) NOT NULL,
        message longtext,
        image_data longtext,
        timestamp datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY session_id (session_id),
        KEY user_id (user_id)
    ) $charset_collate;";
    
    // AI templates table (enhanced)
    $templates_table = $wpdb->prefix . 'sspu_ai_templates';
    $sql_templates = "CREATE TABLE IF NOT EXISTS $templates_table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        content text NOT NULL,
        category varchar(100),
        tags text,
        usage_count int DEFAULT 0,
        created_by bigint(20),
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY category (category)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_chat);
    dbDelta($sql_templates);
    
    // Update database version
    update_option('sspu_db_version', '2.0.0');
}

/**
 * Initializes the plugin by creating instances of the necessary classes.
 */
function sspu_run_plugin() {
    // Initialize core classes
    new SSPU_Admin();
    new SSPU_Drafts();
    new SSPU_Analytics();
    new SSPU_Search();
    new SSPU_Alibaba_Queue();
    new SSPU_Image_Retriever();
    new SSPU_Image_Templates();
    
    // Initialize the enhanced AI Image Editor
    $GLOBALS['sspu_ai_editor'] = SSPU_AI_Image_Editor::get_instance();

    
    // Initialize the SKU generator singleton
    SSPU_SKU_Generator::getInstance();
    
    // Add cache clearing handler
    add_action('wp_ajax_sspu_clear_cache', 'sspu_handle_clear_cache');
    
    // Add image download handler
    add_action('wp_ajax_sspu_download_external_image', 'sspu_handle_download_external_image');
    
    // Add AI Image Editor AJAX handlers
    add_action('wp_ajax_sspu_ai_edit_image', 'sspu_handle_ai_edit_image');
    add_action('wp_ajax_sspu_save_edited_image', 'sspu_handle_save_edited_image');
    add_action('wp_ajax_sspu_get_image_templates', 'sspu_handle_get_templates');
    add_action('wp_ajax_sspu_get_single_template_content', 'sspu_handle_get_single_template');
    add_action('wp_ajax_sspu_get_chat_history', 'sspu_handle_get_chat_history');
}
add_action( 'plugins_loaded', 'sspu_run_plugin' );

/**
 * AI Image Editor AJAX Handlers
 */
function sspu_handle_ai_edit_image() {
    if (isset($GLOBALS['sspu_ai_editor'])) {
        $GLOBALS['sspu_ai_editor']->handle_ai_edit();
    } else {
        wp_send_json_error(['message' => 'AI Editor not initialized']);
    }
}

function sspu_handle_save_edited_image() {
    if (isset($GLOBALS['sspu_ai_editor'])) {
        $GLOBALS['sspu_ai_editor']->handle_save_edited_image();
    } else {
        wp_send_json_error(['message' => 'AI Editor not initialized']);
    }
}

function sspu_handle_get_templates() {
    if (isset($GLOBALS['sspu_ai_editor'])) {
        $GLOBALS['sspu_ai_editor']->handle_get_templates();
    } else {
        wp_send_json_error(['message' => 'AI Editor not initialized']);
    }
}

function sspu_handle_get_single_template() {
    if (isset($GLOBALS['sspu_ai_editor'])) {
        $GLOBALS['sspu_ai_editor']->handle_get_single_template();
    } else {
        wp_send_json_error(['message' => 'AI Editor not initialized']);
    }
}

function sspu_handle_get_chat_history() {
    if (isset($GLOBALS['sspu_ai_editor'])) {
        $GLOBALS['sspu_ai_editor']->handle_get_chat_history();
    } else {
        wp_send_json_error(['message' => 'AI Editor not initialized']);
    }
}

/**
 * Schedule daily Slack notifications
 */
add_action('init', function() {
    if (!wp_next_scheduled('sspu_daily_slack_summary')) {
        wp_schedule_event(strtotime('today 6:00 PM'), 'daily', 'sspu_daily_slack_summary');
    }
});

/**
 * Hook for daily Slack summary
 */
add_action('sspu_daily_slack_summary', 'sspu_send_daily_slack_summary');

function sspu_send_daily_slack_summary() {
    $webhook_url = get_option('sspu_slack_webhook_url');
    
    if (empty($webhook_url)) {
        error_log('SSPU: Slack webhook URL not configured for daily summary');
        return;
    }
    
    // Get today's uploads from analytics
    global $wpdb;
    $table_name = $wpdb->prefix . 'sspu_activity_log';
    
    // Check if table exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
        error_log('SSPU: Activity log table does not exist');
        return;
    }
    
    $today = current_time('Y-m-d');
    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT user_id, COUNT(*) as count, 
         GROUP_CONCAT(JSON_UNQUOTE(JSON_EXTRACT(activity_data, '$.product_title')) SEPARATOR ', ') as products
         FROM {$table_name}
         WHERE activity_type = 'product_created'
         AND DATE(activity_time) = %s
         GROUP BY user_id",
        $today
    ));
    
    if (empty($results)) {
        // No products uploaded today
        $message = [
            'text' => sprintf('📊 *Daily Upload Summary for %s*\n\nNo products were uploaded today.', $today),
            'username' => 'Shopify Daily Summary',
            'icon_emoji' => ':chart_with_downwards_trend:',
            'mrkdwn' => true
        ];
    } else {
        $total_products = 0;
        $summary_lines = [];
        
        foreach ($results as $row) {
            $user = get_userdata($row->user_id);
            $username = $user ? ($user->display_name ?: $user->user_login) : 'Unknown User';
            $total_products += $row->count;
            
            // Clean up product names
            $products = $row->products ? str_replace(['\\', '"'], '', $row->products) : '';
            $summary_lines[] = sprintf('• *%s* uploaded %d product(s): %s', 
                $username, 
                $row->count,
                $products ?: 'N/A'
            );
        }
        
        $message = [
            'text' => sprintf("📊 *Daily Upload Summary for %s*\n\n*Total products uploaded:* %d\n\n%s",
                $today,
                $total_products,
                implode("\n", $summary_lines)
            ),
            'username' => 'Shopify Daily Summary',
            'icon_emoji' => ':chart_with_upwards_trend:',
            'mrkdwn' => true
        ];
    }
    
    $args = [
        'body' => json_encode($message),
        'headers' => [
            'Content-Type' => 'application/json',
        ],
        'timeout' => 30,
        'sslverify' => true,
    ];
    
    $response = wp_remote_post($webhook_url, $args);
    
    if (is_wp_error($response)) {
        error_log('SSPU: Failed to send daily Slack summary: ' . $response->get_error_message());
    } else {
        error_log('SSPU: Daily Slack summary sent successfully');
    }
}

/**
 * Enqueue admin scripts and styles
 */
add_action('admin_enqueue_scripts', 'sspu_enqueue_admin_assets');
function sspu_enqueue_admin_assets($hook) {
    // Load on all relevant admin pages
    $allowed_pages = ['post.php', 'post-new.php', 'upload.php', 'media-upload.php'];
    $is_sspu_page = strpos($hook, 'sspu') !== false;
    
    if (!in_array($hook, $allowed_pages) && !$is_sspu_page) {
        return;
    }
    
    // Enqueue the enhanced AI Image Editor JavaScript
    wp_enqueue_script(
        'sspu-ai-image-editor',
        SSPU_PLUGIN_URL . 'assets/js/ai-image-editor.js',
        ['jquery'],
        SSPU_VERSION,
        true
    );
    
    // ** THE CONFLICTING CODE HAS BEEN REMOVED FROM HERE **
    
    // Ensure media scripts are loaded
    wp_enqueue_media();
}

/**
 * Add AI Editor settings to the existing settings page
 */
add_filter('sspu_settings_tabs', 'sspu_add_ai_settings_tab');
function sspu_add_ai_settings_tab($tabs) {
    $tabs['ai_editor'] = 'AI Image Editor';
    $tabs['notifications'] = 'Notifications';
    return $tabs;
}

add_action('sspu_settings_content_ai_editor', 'sspu_render_ai_settings_content');
function sspu_render_ai_settings_content() {
    if (isset($_POST['submit_ai_settings'])) {
        check_admin_referer('sspu_ai_settings');
        
        update_option('sspu_openai_api_key', sanitize_text_field($_POST['openai_api_key']));
        update_option('sspu_gemini_api_key', sanitize_text_field($_POST['gemini_api_key']));
        update_option('sspu_anthropic_api_key', sanitize_text_field($_POST['anthropic_api_key']));
        
        echo '<div class="notice notice-success"><p>AI settings saved!</p></div>';
    }
    
    $openai_key = get_option('sspu_openai_api_key', '');
    $gemini_key = get_option('sspu_gemini_api_key', '');
    $anthropic_key = get_option('sspu_anthropic_api_key', '');
    
    // Test connections
    $ai_editor = SSPU_AI_Image_Editor::get_instance();
    $connections = $ai_editor->test_connections();
    ?>
    <h2>AI Image Editor Settings</h2>
    
    <form method="post" action="">
        <?php wp_nonce_field('sspu_ai_settings'); ?>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="openai_api_key">OpenAI API Key</label>
                </th>
                <td>
                    <input type="password" id="openai_api_key" name="openai_api_key" 
                           value="<?php echo esc_attr($openai_key); ?>" class="regular-text" />
                    <p class="description">
                        Required for GPT-4 Vision and DALL-E 3 image generation. 
                        <a href="https://platform.openai.com/api-keys" target="_blank">Get your API key</a>
                    </p>
                    <?php if (!empty($openai_key)): ?>
                        <p class="description">
                            Status: <?php echo isset($connections['openai']) && $connections['openai'] ? 
                                '<span style="color:green;">✓ Connected</span>' : 
                                '<span style="color:red;">✗ Connection Failed</span>'; ?>
                        </p>
                    <?php endif; ?>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="gemini_api_key">Google Gemini API Key</label>
                </th>
                <td>
                    <input type="password" id="gemini_api_key" name="gemini_api_key" 
                           value="<?php echo esc_attr($gemini_key); ?>" class="regular-text" />
                    <p class="description">
                        Required for Gemini vision models. 
                        <a href="https://makersuite.google.com/app/apikey" target="_blank">Get your API key</a>
                    </p>
                    <?php if (!empty($gemini_key)): ?>
                        <p class="description">
                            Status: <?php echo isset($connections['gemini']) && $connections['gemini'] ? 
                                '<span style="color:green;">✓ Connected</span>' : 
                                '<span style="color:red;">✗ Connection Failed</span>'; ?>
                        </p>
                    <?php endif; ?>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="anthropic_api_key">Anthropic API Key (Optional)</label>
                </th>
                <td>
                    <input type="password" id="anthropic_api_key" name="anthropic_api_key" 
                           value="<?php echo esc_attr($anthropic_key); ?>" class="regular-text" />
                    <p class="description">
                        Optional - Reserved for future Claude vision support
                    </p>
                </td>
            </tr>
        </table>
        
        <h3>Available AI Models</h3>
        <?php 
        $available_models = $ai_editor->get_available_models();
        if (!empty($available_models)): ?>
            <ul>
                <?php foreach ($available_models as $provider => $models): ?>
                    <li><strong><?php echo ucfirst($provider); ?>:</strong> 
                        <?php echo implode(', ', $models); ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p style="color: #666;">No models available. Please add API keys above.</p>
        <?php endif; ?>
        
        <p class="submit">
            <input type="submit" name="submit_ai_settings" id="submit" class="button-primary" value="Save AI Settings">
        </p>
    </form>
    
    <hr>
    
    <h3>AI Image Editor Features</h3>
    <div class="sspu-features-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 20px;">
        <div class="feature-box" style="border: 1px solid #ddd; padding: 15px; border-radius: 5px;">
            <h4>🎨 Image Generation</h4>
            <p>Generate new product images with DALL-E 3 based on descriptions</p>
        </div>
        <div class="feature-box" style="border: 1px solid #ddd; padding: 15px; border-radius: 5px;">
            <h4>🔍 Smart Analysis</h4>
            <p>Analyze images with GPT-4 Vision or Gemini to get improvement suggestions</p>
        </div>
        <div class="feature-box" style="border: 1px solid #ddd; padding: 15px; border-radius: 5px;">
            <h4>🏠 Background Replacement</h4>
            <p>Extract products and place them on lifestyle or white backgrounds</p>
        </div>
        <div class="feature-box" style="border: 1px solid #ddd; padding: 15px; border-radius: 5px;">
            <h4>🏷️ Logo Addition</h4>
            <p>Intelligently add company logos and branding to product images</p>
        </div>
        <div class="feature-box" style="border: 1px solid #ddd; padding: 15px; border-radius: 5px;">
            <h4>📦 Batch Processing</h4>
            <p>Generate multiple variations for A/B testing</p>
        </div>
        <div class="feature-box" style="border: 1px solid #ddd; padding: 15px; border-radius: 5px;">
            <h4>💡 Smart Enhancement</h4>
            <p>Improve lighting, composition, and colors automatically</p>
        </div>
    </div>
    <?php
}

/**
 * Add Notifications settings tab content
 */
add_action('sspu_settings_content_notifications', 'sspu_render_notifications_settings_content');
function sspu_render_notifications_settings_content() {
    if (isset($_POST['submit_notification_settings'])) {
        check_admin_referer('sspu_notification_settings');
        
        update_option('sspu_slack_webhook_url', esc_url_raw($_POST['slack_webhook_url']));
        update_option('sspu_slack_notifications_enabled', isset($_POST['slack_notifications_enabled']) ? '1' : '0');
        update_option('sspu_slack_daily_summary_enabled', isset($_POST['slack_daily_summary_enabled']) ? '1' : '0');
        update_option('sspu_slack_daily_summary_time', sanitize_text_field($_POST['slack_daily_summary_time']));
        
        // Reschedule daily summary if time changed
        wp_clear_scheduled_hook('sspu_daily_slack_summary');
        if (get_option('sspu_slack_daily_summary_enabled') === '1') {
            $time = get_option('sspu_slack_daily_summary_time', '18:00');
            wp_schedule_event(strtotime('today ' . $time), 'daily', 'sspu_daily_slack_summary');
        }
        
        echo '<div class="notice notice-success"><p>Notification settings saved!</p></div>';
        
        // Test notification if requested
        if (isset($_POST['test_slack_notification'])) {
            sspu_send_test_slack_notification();
        }
    }
    
    $webhook_url = get_option('sspu_slack_webhook_url', '');
    $notifications_enabled = get_option('sspu_slack_notifications_enabled', '1');
    $daily_summary_enabled = get_option('sspu_slack_daily_summary_enabled', '1');
    $daily_summary_time = get_option('sspu_slack_daily_summary_time', '18:00');
    ?>
    <h2>Notification Settings</h2>
    
    <form method="post" action="">
        <?php wp_nonce_field('sspu_notification_settings'); ?>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="slack_webhook_url">Slack Webhook URL</label>
                </th>
                <td>
                    <input type="url" id="slack_webhook_url" name="slack_webhook_url" 
                           value="<?php echo esc_attr($webhook_url); ?>" 
                           class="regular-text" placeholder="https://hooks.slack.com/services/..." />
                    <p class="description">
                        Enter your Slack webhook URL for notifications. 
                        <a href="https://api.slack.com/messaging/webhooks" target="_blank">Learn how to create a webhook</a>
                    </p>
                    <?php if (!empty($webhook_url)): ?>
                        <p>
                            <button type="submit" name="test_slack_notification" class="button">
                                Test Notification
                            </button>
                        </p>
                    <?php endif; ?>
                </td>
            </tr>
            
            <tr>
                <th scope="row">Instant Notifications</th>
                <td>
                    <label>
                        <input type="checkbox" name="slack_notifications_enabled" value="1" 
                               <?php checked($notifications_enabled, '1'); ?> />
                        Enable instant Slack notifications when products are uploaded
                    </label>
                    <p class="description">
                        Sends a notification immediately when a user uploads a product to Shopify
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">Daily Summary</th>
                <td>
                    <label>
                        <input type="checkbox" name="slack_daily_summary_enabled" value="1" 
                               <?php checked($daily_summary_enabled, '1'); ?> />
                        Enable daily summary notifications
                    </label>
                    <p class="description">
                        Sends a summary of all products uploaded during the day
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="slack_daily_summary_time">Daily Summary Time</label>
                </th>
                <td>
                    <input type="time" id="slack_daily_summary_time" name="slack_daily_summary_time" 
                           value="<?php echo esc_attr($daily_summary_time); ?>" />
                    <p class="description">
                        Time to send the daily summary (server time: <?php echo current_time('H:i'); ?>)
                    </p>
                </td>
            </tr>
        </table>
        
        <p class="submit">
            <input type="submit" name="submit_notification_settings" id="submit" class="button-primary" value="Save Notification Settings">
        </p>
    </form>
    
    <hr>
    
    <h3>Notification Preview</h3>
    <div style="background: #f4f4f4; padding: 15px; border-radius: 5px; margin-top: 20px;">
        <strong>Instant notification format:</strong><br>
        <code>[Username] just uploaded [Product URL]</code><br><br>
        
        <strong>Daily summary format:</strong><br>
        <code>📊 Daily Upload Summary for [Date]<br>
        Total products uploaded: [Number]<br>
        • [Username] uploaded [Count] product(s): [Product Names]</code>
    </div>
    <?php
}

/**
 * Send test Slack notification
 */
function sspu_send_test_slack_notification() {
    $webhook_url = get_option('sspu_slack_webhook_url');
    
    if (empty($webhook_url)) {
        echo '<div class="notice notice-error"><p>Slack webhook URL is not configured.</p></div>';
        return;
    }
    
    $user = wp_get_current_user();
    $username = $user->display_name ?: $user->user_login;
    
    $message = [
        'text' => sprintf('🧪 *Test Notification*\n\nThis is a test notification from %s.\nYour Slack integration is working correctly!', $username),
        'username' => 'Shopify Product Uploader',
        'icon_emoji' => ':white_check_mark:',
        'mrkdwn' => true
    ];
    
    $args = [
        'body' => json_encode($message),
        'headers' => [
            'Content-Type' => 'application/json',
        ],
        'timeout' => 30,
        'sslverify' => true,
    ];
    
    $response = wp_remote_post($webhook_url, $args);
    
    if (is_wp_error($response)) {
        echo '<div class="notice notice-error"><p>Failed to send test notification: ' . esc_html($response->get_error_message()) . '</p></div>';
    } else {
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code === 200) {
            echo '<div class="notice notice-success"><p>Test notification sent successfully! Check your Slack channel.</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>Slack returned an error. Response code: ' . esc_html($response_code) . '</p></div>';
        }
    }
}

/**
 * Add "Edit with AI" button to media library
 */
add_filter('media_row_actions', 'sspu_add_ai_edit_link', 10, 2);
function sspu_add_ai_edit_link($actions, $post) {
    if (strpos($post->post_mime_type, 'image/') === 0) {
        $url = wp_get_attachment_url($post->ID);
        $actions['ai_edit'] = sprintf(
            '<a href="#" onclick="window.AIImageEditor.open(%d, \'%s\'); return false;" style="color: #0073aa;">Edit with AI</a>',
            $post->ID,
            esc_js($url)
        );
    }
    return $actions;
}

/**
 * Add AI Editor button to attachment details modal
 */
add_filter('attachment_fields_to_edit', 'sspu_add_ai_edit_button', 10, 2);
function sspu_add_ai_edit_button($fields, $post) {
    if (strpos($post->post_mime_type, 'image/') === 0) {
        $url = wp_get_attachment_url($post->ID);
        $fields['ai_edit'] = [
            'label' => 'AI Editor',
            'input' => 'html',
            'html' => sprintf(
                '<button type="button" class="button button-primary" onclick="window.AIImageEditor.open(%d, \'%s\'); return false;">🎨 Open AI Image Editor</button>',
                $post->ID,
                esc_js($url)
            )
        ];
    }
    return $fields;
}

/**
 * Add AI Editor integration to product uploader page
 */
add_action('sspu_after_image_fields', 'sspu_add_ai_editor_buttons');
function sspu_add_ai_editor_buttons() {
    ?>
    <div class="sspu-ai-editor-integration" style="margin-top: 20px; padding: 15px; background: #f0f8ff; border-radius: 5px;">
        <h4 style="margin-top: 0;">AI Image Enhancement</h4>
        <p>After uploading images, you can enhance them with AI:</p>
        <div class="ai-actions" style="display: flex; gap: 10px; flex-wrap: wrap;">
            <button type="button" class="button" onclick="sspu_openAIEditorForMain()">
                🎨 Edit Main Image
            </button>
            <button type="button" class="button" onclick="sspu_openAIEditorForGallery()">
                🖼️ Edit Gallery Images
            </button>
        </div>
        <script>
        function sspu_openAIEditorForMain() {
            const mainImageId = jQuery('#sspu-main-image-id').val();
            const mainImageUrl = jQuery('#sspu-main-image-preview img').attr('src');
            if (mainImageId && mainImageUrl) {
                window.AIImageEditor.open(mainImageId, mainImageUrl);
            } else {
                alert('Please select a main image first.');
            }
        }
        
        function sspu_openAIEditorForGallery() {
            const galleryImages = jQuery('#sspu-additional-images-preview img');
            if (galleryImages.length === 0) {
                alert('Please add gallery images first.');
                return;
            }
            
            // Open editor for the first gallery image
            const firstImage = galleryImages.first();
            const imageId = firstImage.data('id');
            const imageUrl = firstImage.attr('src');
            if (imageId && imageUrl) {
                window.AIImageEditor.open(imageId, imageUrl);
            }
        }
        </script>
    </div>
    <?php
}

/**
 * Handle cache clearing
 */
function sspu_handle_clear_cache() {
    if (!check_ajax_referer('sspu_ajax_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => 'Invalid security token']);
    }
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permission denied']);
    }
    
    global $wpdb;
    
    // Clear all transients with our prefix
    $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_sspu_%'");
    $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_timeout_sspu_%'");
    
    // Clear any cached AI responses
    delete_transient('sspu_ai_cache');
    
    wp_send_json_success(['message' => 'Cache cleared successfully']);
}

/**
 * Handle downloading external images to WordPress Media Library
 */
function sspu_handle_download_external_image() {
    check_ajax_referer('sspu_ajax_nonce', 'nonce');
    
    if (!current_user_can('upload_files')) {
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
        'post_content' => 'Downloaded from external source',
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
    $analytics->log_activity(get_current_user_id(), 'external_image_downloaded', [
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

/**
 * Check and update database on admin init
 */
add_action('admin_init', function() {
    $current_db_version = get_option('sspu_db_version', '1.0.0');
    
    if (version_compare($current_db_version, '2.0.0', '<')) {
        SSPU_Roles::update_database_schema();
        sspu_create_ai_tables();
    }
});

/**
 * Add custom image sizes for AI processing
 */
add_action('after_setup_theme', function() {
    add_image_size('sspu-ai-preview', 512, 512, true);
    add_image_size('sspu-ai-full', 1024, 1024, true);
    add_image_size('sspu-ai-thumbnail', 256, 256, true);
});

/**
 * Register plugin text domain for translations
 */
add_action('init', function() {
    load_plugin_textdomain('sspu', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

/**
 * Add plugin action links
 */
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function($links) {
    $settings_link = '<a href="admin.php?page=sspu-settings">' . __('Settings', 'sspu') . '</a>';
    $uploader_link = '<a href="admin.php?page=sspu-uploader">' . __('Upload Product', 'sspu') . '</a>';
    $ai_link = '<a href="admin.php?page=sspu-settings&tab=ai_editor" style="color: #0073aa; font-weight: bold;">' . __('AI Editor', 'sspu') . '</a>';
    
    array_unshift($links, $settings_link, $uploader_link, $ai_link);
    
    return $links;
});

/**
 * Clean up on uninstall
 */
register_uninstall_hook(__FILE__, 'sspu_uninstall');

function sspu_uninstall() {
    // Only run if explicitly uninstalling
    if (!defined('WP_UNINSTALL_PLUGIN')) {
        return;
    }
    
    global $wpdb;
    
    // Remove all plugin options
    delete_option('sspu_shopify_store_name');
    delete_option('sspu_shopify_access_token');
    delete_option('sspu_openai_api_key');
    delete_option('sspu_gemini_api_key');
    delete_option('sspu_anthropic_api_key');
    delete_option('sspu_slack_webhook_url');
    delete_option('sspu_slack_notifications_enabled');
    delete_option('sspu_slack_daily_summary_enabled');
    delete_option('sspu_slack_daily_summary_time');
    delete_option('sspu_sku_pattern');
    delete_option('sspu_volume_tier_multipliers');
    delete_option('sspu_seo_template');
    delete_option('sspu_alibaba_url_expiry');
    delete_option('sspu_db_version');
    
    // Remove scheduled events
    wp_clear_scheduled_hook('sspu_daily_slack_summary');
    
    // Remove all plugin tables (optional - uncomment if you want complete removal)
    /*
    $tables = [
        $wpdb->prefix . 'sspu_product_log',
        $wpdb->prefix . 'sspu_drafts',
        $wpdb->prefix . 'sspu_activity_log',
        $wpdb->prefix . 'sspu_alibaba_queue',
        $wpdb->prefix . 'sspu_image_templates',
        $wpdb->prefix . 'sspu_ai_chat_history'
    ];
    
    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS {$table}");
    }
    */
    
    // Remove custom role
    remove_role('shopify_uploader');
    
    // Remove capabilities from admin
    $admin_role = get_role('administrator');
    if ($admin_role) {
        $admin_role->remove_cap('upload_shopify_products');
    }
    
    // Clean up AI-related transients
    $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_sspu_ai_%'");
    $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_timeout_sspu_ai_%'");
}

/**
 * Add admin notices for AI setup
 */
add_action('admin_notices', 'sspu_ai_setup_notices');
function sspu_ai_setup_notices() {
    // Only show on SSPU pages
    if (!isset($_GET['page']) || strpos($_GET['page'], 'sspu') !== 0) {
        return;
    }
    
    $openai_key = get_option('sspu_openai_api_key', '');
    $gemini_key = get_option('sspu_gemini_api_key', '');
    
    if (empty($openai_key) && empty($gemini_key)) {
        ?>
        <div class="notice notice-info is-dismissible">
            <p><strong>AI Image Editor Setup:</strong> To use the AI-powered image editing features, please configure your API keys in the <a href="admin.php?page=sspu-settings&tab=ai_editor">AI Editor settings</a>.</p>
        </div>
        <?php
    }
}

/**
 * Log AI usage for analytics
 */
add_action('sspu_ai_request_complete', 'sspu_log_ai_usage', 10, 3);
function sspu_log_ai_usage($service, $model, $user_id) {
    $analytics = new SSPU_Analytics();
    $analytics->log_activity($user_id, 'ai_image_edit', [
        'service' => $service,
        'model' => $model,
        'timestamp' => current_time('mysql')
    ]);
}