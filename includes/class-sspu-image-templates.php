<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

class SSPU_Image_Templates {
    
    public function __construct() {
        // AJAX handlers
        add_action( 'wp_ajax_sspu_get_image_templates', [ $this, 'handle_get_templates' ] );
        add_action( 'wp_ajax_sspu_save_image_template', [ $this, 'handle_save_template' ] );
        add_action( 'wp_ajax_sspu_delete_image_template', [ $this, 'handle_delete_template' ] );
        add_action( 'wp_ajax_sspu_use_image_template', [ $this, 'handle_use_template' ] );
        add_action( 'wp_ajax_sspu_update_template_usage', [ $this, 'handle_update_usage' ] );
    }
    
    /**
     * Get all templates
     */
    public function handle_get_templates() {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');
        
        if (!current_user_can('upload_shopify_products')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }
        
        $category = sanitize_text_field($_POST['category'] ?? 'all');
        $user_id = get_current_user_id();
        
        $templates = $this->get_templates($user_id, $category);
        
        wp_send_json_success(['templates' => $templates]);
    }
    
    /**
     * Save a new template
     */
    public function handle_save_template() {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');
        
        if (!current_user_can('upload_shopify_products')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }
        
        $name = sanitize_text_field($_POST['name']);
        $prompt = sanitize_textarea_field($_POST['prompt']);
        $category = sanitize_text_field($_POST['category'] ?? 'custom');
        $ai_service = sanitize_text_field($_POST['ai_service'] ?? 'chatgpt');
        $example_images = isset($_POST['example_images']) ? array_map('absint', $_POST['example_images']) : [];
        $is_global = current_user_can('manage_options') && isset($_POST['is_global']) ? 1 : 0;
        
        if (empty($name) || empty($prompt)) {
            wp_send_json_error(['message' => 'Name and prompt are required']);
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'sspu_image_templates';
        
        $result = $wpdb->insert($table_name, [
            'user_id' => get_current_user_id(),
            'name' => $name,
            'prompt' => $prompt,
            'example_images' => json_encode($example_images),
            'ai_service' => $ai_service,
            'category' => $category,
            'is_global' => $is_global,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ]);
        
        if ($result === false) {
            wp_send_json_error(['message' => 'Failed to save template']);
            return;
        }
        
        $template_id = $wpdb->insert_id;
        
        // Log activity
        $analytics = new SSPU_Analytics();
        $analytics->log_activity(get_current_user_id(), 'template_created', [
            'template_id' => $template_id,
            'template_name' => $name,
            'category' => $category
        ]);
        
        wp_send_json_success([
            'template_id' => $template_id,
            'message' => 'Template saved successfully'
        ]);
    }
    
    /**
     * Delete a template
     */
    public function handle_delete_template() {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');
        
        if (!current_user_can('upload_shopify_products')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }
        
        $template_id = absint($_POST['template_id']);
        $user_id = get_current_user_id();
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'sspu_image_templates';
        
        // Check ownership (unless admin)
        $template = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE template_id = %d",
            $template_id
        ));
        
        if (!$template) {
            wp_send_json_error(['message' => 'Template not found']);
            return;
        }
        
        if ($template->user_id != $user_id && !current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'You can only delete your own templates']);
            return;
        }
        
        $result = $wpdb->delete($table_name, ['template_id' => $template_id]);
        
        if ($result === false) {
            wp_send_json_error(['message' => 'Failed to delete template']);
            return;
        }
        
        // Log activity
        $analytics = new SSPU_Analytics();
        $analytics->log_activity($user_id, 'template_deleted', [
            'template_id' => $template_id,
            'template_name' => $template->name
        ]);
        
        wp_send_json_success(['message' => 'Template deleted successfully']);
    }
    
    /**
     * Use a template (get its details)
     */
    public function handle_use_template() {
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
        
        // Increment usage count
        $wpdb->update(
            $table_name,
            ['usage_count' => $template->usage_count + 1],
            ['template_id' => $template_id]
        );
        
        // Parse example images
        $example_images = json_decode($template->example_images, true) ?: [];
        $image_urls = [];
        
        foreach ($example_images as $image_id) {
            $image_urls[] = [
                'id' => $image_id,
                'url' => wp_get_attachment_url($image_id),
                'thumb' => wp_get_attachment_thumb_url($image_id)
            ];
        }
        
        wp_send_json_success([
            'template' => [
                'template_id' => $template->template_id,
                'name' => $template->name,
                'prompt' => $template->prompt,
                'ai_service' => $template->ai_service,
                'category' => $template->category,
                'example_images' => $image_urls
            ]
        ]);
    }
    
    /**
     * Update template usage count
     */
    public function handle_update_usage() {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');
        
        $template_id = absint($_POST['template_id']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'sspu_image_templates';
        
        $wpdb->query($wpdb->prepare(
            "UPDATE {$table_name} SET usage_count = usage_count + 1 WHERE template_id = %d",
            $template_id
        ));
        
        wp_send_json_success();
    }
    
    /**
     * Get templates for a user
     */
    private function get_templates($user_id, $category = 'all') {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sspu_image_templates';
        
        $where_conditions = ["(user_id = %d OR is_global = 1)"];
        $params = [$user_id];
        
        if ($category !== 'all') {
            $where_conditions[] = "category = %s";
            $params[] = $category;
        }
        
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        
        $templates = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name} 
            {$where_clause}
            ORDER BY is_global DESC, usage_count DESC, created_at DESC",
            ...$params
        ));
        
        // Format templates for frontend
        $formatted_templates = [];
        
        foreach ($templates as $template) {
            $example_images = json_decode($template->example_images, true) ?: [];
            $image_urls = [];
            
            foreach ($example_images as $image_id) {
                $thumb_url = wp_get_attachment_thumb_url($image_id);
                if ($thumb_url) {
                    $image_urls[] = $thumb_url;
                }
            }
            
            $formatted_templates[] = [
                'template_id' => $template->template_id,
                'name' => $template->name,
                'prompt' => $template->prompt,
                'category' => $template->category,
                'ai_service' => $template->ai_service,
                'is_global' => (bool)$template->is_global,
                'is_owner' => ($template->user_id == $user_id),
                'usage_count' => $template->usage_count,
                'example_images' => $image_urls,
                'created_at' => $template->created_at
            ];
        }
        
        return $formatted_templates;
    }
    
    /**
     * Get popular templates
     */
    public function get_popular_templates($limit = 5) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sspu_image_templates';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name} 
            WHERE is_global = 1 
            ORDER BY usage_count DESC 
            LIMIT %d",
            $limit
        ));
    }
    
    /**
     * Get templates by category
     */
    public function get_templates_by_category($category) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sspu_image_templates';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name} 
            WHERE category = %s AND is_global = 1 
            ORDER BY usage_count DESC",
            $category
        ));
    }
    
    /**
     * Clone a template
     */
    public function clone_template($template_id, $user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sspu_image_templates';
        
        $template = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE template_id = %d",
            $template_id
        ));
        
        if (!$template) {
            return false;
        }
        
        $result = $wpdb->insert($table_name, [
            'user_id' => $user_id,
            'name' => $template->name . ' (Copy)',
            'prompt' => $template->prompt,
            'example_images' => $template->example_images,
            'ai_service' => $template->ai_service,
            'category' => 'custom',
            'is_global' => 0,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ]);
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Search templates
     */
    public function search_templates($query, $user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sspu_image_templates';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name} 
            WHERE (user_id = %d OR is_global = 1) 
            AND (name LIKE %s OR prompt LIKE %s)
            ORDER BY is_global DESC, usage_count DESC",
            $user_id,
            '%' . $wpdb->esc_like($query) . '%',
            '%' . $wpdb->esc_like($query) . '%'
        ));
    }
}