<?php
if (!defined('WPINC')) die;

class SSPU_Admin_Scripts {
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_scripts($hook) {
        // Only load on our plugin pages.
        if (strpos($hook, 'sspu') === false && strpos($hook, 'shopify_page') === false) {
            return;
        }
        
        // Core dependencies
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_script('jquery-ui-tabs');
        wp_enqueue_media();
        
        // TinyMCE
        wp_enqueue_editor();
        
        // Admin styles
        wp_enqueue_style(
            'sspu-admin-style',
            SSPU_PLUGIN_URL . 'assets/css/admin-style.css',
            [],
            SSPU_VERSION
        );
        
        // Utility scripts (must load first)
        wp_enqueue_script(
            'sspu-utils',
            SSPU_PLUGIN_URL . 'assets/js/utils.js',
            ['jquery'],
            SSPU_VERSION,
            true
        );
        
        // Enqueue all module scripts
        $modules = ['ai', 'alibaba', 'collections', 'form', 'variants'];
        foreach ($modules as $module) {
            wp_enqueue_script(
                "sspu-module-{$module}",
                SSPU_PLUGIN_URL . "assets/js/modules/{$module}.js",
                ['jquery', 'sspu-utils'],
                SSPU_VERSION,
                true
            );
        }

        // Define module handles for dependency management
        $module_handles = array_map(function($m) { return "sspu-module-{$m}"; }, $modules);

        // Main application entry point
        wp_enqueue_script(
            'sspu-main',
            SSPU_PLUGIN_URL . 'assets/js/main.js',
            array_merge(['jquery', 'sspu-utils'], $module_handles),
            SSPU_VERSION,
            true
        );

        // Standalone scripts for specific pages/features
        wp_enqueue_script(
            'sspu-image-retriever',
            SSPU_PLUGIN_URL . 'assets/js/image-retriever.js', // Correct path
            ['jquery', 'sspu-utils'],
            SSPU_VERSION,
            true
        );
        
        // AI Image Editor
        if (in_array($hook, ['post.php', 'post-new.php', 'upload.php']) || strpos($hook, 'sspu') !== false) {
            wp_enqueue_script(
                'sspu-ai-image-editor',
                SSPU_PLUGIN_URL . 'assets/js/ai-image-editor.js',
                ['jquery'],
                SSPU_VERSION,
                true
            );
            
            // Cropper.js for mask creation
            wp_enqueue_script(
                'cropperjs',
                'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js',
                [],
                '1.5.12',
                true
            );
            
            wp_enqueue_style(
                'cropperjs',
                'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css',
                [],
                '1.5.12'
            );
        }
        
        // Alibaba Queue page specific assets
        if (isset($_GET['page']) && $_GET['page'] === 'sspu-alibaba-queue') {
            wp_enqueue_script(
                'sspu-alibaba-queue',
                SSPU_PLUGIN_URL . 'assets/js/alibaba-queue.js',
                ['jquery'],
                SSPU_VERSION,
                true
            );
        }
        
        // Live Editor specific assets
        if (isset($_GET['page']) && $_GET['page'] === 'sspu-live-editor') {
            wp_enqueue_style(
                'sspu-live-editor-style',
                SSPU_PLUGIN_URL . 'assets/css/live-editor.css',
                [],
                SSPU_VERSION
            );
            
            wp_enqueue_script(
                'sspu-live-editor',
                SSPU_PLUGIN_URL . 'assets/js/live-editor.js',
                ['jquery', 'jquery-ui-tabs', 'jquery-ui-autocomplete', 'jquery-ui-dialog', 'jquery-ui-sortable'],
                SSPU_VERSION,
                true
            );
            
            wp_enqueue_editor();
            wp_enqueue_style('wp-jquery-ui-dialog');
        }
        
        // Localize script data
        $localized_data = [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sspu_ajax_nonce'),
            'plugin_url' => SSPU_PLUGIN_URL,
            'admin_url' => admin_url(),
            'store_url' => 'https://' . get_option('sspu_shopify_store_name', '') . '.myshopify.com',
            'currency' => get_option('sspu_currency_symbol', '$'),
            'debug_mode' => WP_DEBUG,
            'openai_configured' => !empty(get_option('sspu_openai_api_key')),
            'shopify_configured' => !empty(get_option('sspu_shopify_store_name')) && !empty(get_option('sspu_shopify_access_token')),
            'vertex_ai_enabled' => !empty(get_option('sspu_vertex_ai_config')),
            'allowed_image_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            'max_file_size' => wp_max_upload_size(),
            'strings' => [
                'no_openai_key' => __('OpenAI API key not configured. Please add it in Settings.', 'sspu'),
                'no_shopify_creds' => __('Shopify credentials not configured. Please add them in Settings.', 'sspu'),
                'uploading' => __('Uploading...', 'sspu'),
                'upload_complete' => __('Upload complete!', 'sspu'),
                'upload_failed' => __('Upload failed. Please try again.', 'sspu'),
                'confirm_delete' => __('Are you sure you want to delete this?', 'sspu'),
                'saving' => __('Saving...', 'sspu'),
                'saved' => __('Saved!', 'sspu'),
                'error' => __('An error occurred. Please try again.', 'sspu')
            ]
        ];
        
        wp_localize_script('sspu-utils', 'sspu_ajax', $localized_data);
        wp_localize_script('sspu-live-editor', 'sspu_ajax', $localized_data);
        wp_localize_script('sspu-ai-image-editor', 'sspu_ajax', $localized_data);
        wp_localize_script('sspu-alibaba-queue', 'sspu_ajax', $localized_data);
    }
}