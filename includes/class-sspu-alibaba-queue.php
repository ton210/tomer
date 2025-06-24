<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

class SSPU_Alibaba_Queue {

    public function __construct() {
        // AJAX handlers for admin
        add_action( 'wp_ajax_sspu_save_alibaba_urls', [ $this, 'handle_save_urls' ] );
        add_action( 'wp_ajax_sspu_get_alibaba_urls', [ $this, 'handle_get_urls' ] );
        add_action( 'wp_ajax_sspu_clear_alibaba_urls', [ $this, 'handle_clear_urls' ] );
        
        // AJAX handlers for uploaders
        add_action( 'wp_ajax_sspu_request_alibaba_url', [ $this, 'handle_request_url' ] );
        add_action( 'wp_ajax_sspu_complete_alibaba_url', [ $this, 'handle_complete_url' ] );
        add_action( 'wp_ajax_sspu_release_alibaba_url', [ $this, 'handle_release_url' ] );
        add_action( 'wp_ajax_sspu_get_current_alibaba_url', [ $this, 'handle_get_current_url' ] );
        
        // Add settings fields
        add_action( 'admin_init', [ $this, 'register_settings' ] );
    }

    public function register_settings() {
        add_settings_section('sspu_alibaba_section', __('Alibaba URL Queue', 'sspu'), null, 'sspu-settings');
        add_settings_field('sspu_alibaba_url_expiry', __('URL Assignment Expiry (minutes)', 'sspu'), [ $this, 'url_expiry_field_html' ], 'sspu-settings', 'sspu_alibaba_section');
        register_setting('sspu_settings_group', 'sspu_alibaba_url_expiry');
    }

    public function url_expiry_field_html() {
        $expiry = get_option('sspu_alibaba_url_expiry', 60);
        printf('<input type="number" id="sspu_alibaba_url_expiry" name="sspu_alibaba_url_expiry" value="%s" class="small-text" min="5" max="1440" />', esc_attr($expiry));
        echo '<p class="description">' . __('How long a URL stays assigned to a user before being released back to the queue (default: 60 minutes)', 'sspu') . '</p>';
    }

    /**
     * Handle saving URLs from admin
     */
    public function handle_save_urls() {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }
        
        $urls = sanitize_textarea_field($_POST['urls']);
        $action = sanitize_text_field($_POST['action_type'] ?? 'replace');
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'sspu_alibaba_queue';
        
        // Parse URLs (one per line)
        $url_array = array_filter(array_map('trim', explode("\n", $urls)));
        $valid_urls = [];
        
        foreach ($url_array as $url) {
            // Basic URL validation
            if (filter_var($url, FILTER_VALIDATE_URL) && 
                (strpos($url, 'alibaba.com') !== false || strpos($url, '1688.com') !== false)) {
                $valid_urls[] = $url;
            }
        }
        
        if (empty($valid_urls)) {
            wp_send_json_error(['message' => 'No valid Alibaba URLs found']);
            return;
        }
        
        // Start transaction
        $wpdb->query('START TRANSACTION');
        
        try {
            if ($action === 'replace') {
                // Delete all unassigned URLs
                $wpdb->query("DELETE FROM {$table_name} WHERE assigned_to IS NULL");
            }
            
            // Insert new URLs
            foreach ($valid_urls as $url) {
                // Check if URL already exists
                $exists = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$table_name} WHERE url = %s",
                    $url
                ));
                
                if (!$exists) {
                    $wpdb->insert($table_name, [
                        'url' => $url,
                        'created_at' => current_time('mysql'),
                        'created_by' => get_current_user_id()
                    ]);
                }
            }
            
            $wpdb->query('COMMIT');
            
            // Get updated stats
            $stats = $this->get_queue_stats();
            
            // Log activity
            $analytics = new SSPU_Analytics();
            $analytics->log_activity(get_current_user_id(), 'alibaba_urls_updated', [
                'action' => $action,
                'urls_added' => count($valid_urls),
                'total_urls' => $stats['total']
            ]);
            
            wp_send_json_success([
                'message' => sprintf('%d URLs added to queue', count($valid_urls)),
                'stats' => $stats
            ]);
            
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            wp_send_json_error(['message' => 'Database error: ' . $e->getMessage()]);
        }
    }

    /**
     * Handle getting all URLs for admin view
     */
    public function handle_get_urls() {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'sspu_alibaba_queue';
        $users_table = $wpdb->prefix . 'users';
        
        // Get all URLs with assignment info
        $urls = $wpdb->get_results("
            SELECT 
                q.*,
                u.display_name as assigned_user_name,
                c.display_name as created_user_name
            FROM {$table_name} q
            LEFT JOIN {$users_table} u ON q.assigned_to = u.ID
            LEFT JOIN {$users_table} c ON q.created_by = c.ID
            ORDER BY 
                CASE 
                    WHEN q.status = 'available' THEN 1
                    WHEN q.status = 'assigned' THEN 2
                    WHEN q.status = 'completed' THEN 3
                END,
                q.created_at DESC
        ");
        
        // Get stats
        $stats = $this->get_queue_stats();
        
        wp_send_json_success([
            'urls' => $urls,
            'stats' => $stats
        ]);
    }

    /**
     * Handle clearing URLs
     */
    public function handle_clear_urls() {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }
        
        $clear_type = sanitize_text_field($_POST['clear_type'] ?? 'all');
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'sspu_alibaba_queue';
        
        switch ($clear_type) {
            case 'completed':
                $result = $wpdb->query("DELETE FROM {$table_name} WHERE status = 'completed'");
                break;
            case 'unassigned':
                $result = $wpdb->query("DELETE FROM {$table_name} WHERE status = 'available'");
                break;
            case 'all':
            default:
                $result = $wpdb->query("TRUNCATE TABLE {$table_name}");
                break;
        }
        
        // Log activity
        $analytics = new SSPU_Analytics();
        $analytics->log_activity(get_current_user_id(), 'alibaba_urls_cleared', [
            'clear_type' => $clear_type,
            'urls_removed' => $result
        ]);
        
        wp_send_json_success([
            'message' => sprintf('%d URLs removed', $result),
            'stats' => $this->get_queue_stats()
        ]);
    }

    /**
     * Handle URL request from uploader
     */
    public function handle_request_url() {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');
        
        if (!current_user_can('upload_shopify_products')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'sspu_alibaba_queue';
        $user_id = get_current_user_id();
        
        // Release any expired assignments
        $this->release_expired_urls();
        
        // Check if user already has an assigned URL
        $existing = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$table_name} 
            WHERE assigned_to = %d AND status = 'assigned'
            ORDER BY assigned_at DESC
            LIMIT 1
        ", $user_id));
        
        if ($existing) {
            wp_send_json_success([
                'url' => $existing->url,
                'queue_id' => $existing->queue_id,
                'assigned_at' => $existing->assigned_at,
                'message' => 'You already have an assigned URL'
            ]);
            return;
        }
        
        // Get next available URL
        $wpdb->query('START TRANSACTION');
        
        try {
            // Lock and get the first available URL
            $next_url = $wpdb->get_row("
                SELECT * FROM {$table_name} 
                WHERE status = 'available' 
                ORDER BY created_at ASC 
                LIMIT 1 
                FOR UPDATE
            ");
            
            if (!$next_url) {
                $wpdb->query('COMMIT');
                wp_send_json_error(['message' => 'No URLs available in queue']);
                return;
            }
            
            // Assign URL to user
            $wpdb->update($table_name, [
                'assigned_to' => $user_id,
                'assigned_at' => current_time('mysql'),
                'status' => 'assigned'
            ], ['queue_id' => $next_url->queue_id]);
            
            $wpdb->query('COMMIT');
            
            // Log activity
            $analytics = new SSPU_Analytics();
            $analytics->log_activity($user_id, 'alibaba_url_requested', [
                'url' => $next_url->url,
                'queue_id' => $next_url->queue_id
            ]);
            
            wp_send_json_success([
                'url' => $next_url->url,
                'queue_id' => $next_url->queue_id,
                'assigned_at' => current_time('mysql'),
                'message' => 'URL assigned successfully'
            ]);
            
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            wp_send_json_error(['message' => 'Failed to assign URL: ' . $e->getMessage()]);
        }
    }

    /**
     * Handle marking URL as complete
     */
    public function handle_complete_url() {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');
        
        if (!current_user_can('upload_shopify_products')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }
        
        $queue_id = absint($_POST['queue_id']);
        $user_id = get_current_user_id();
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'sspu_alibaba_queue';
        
        // Verify ownership
        $url_data = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$table_name} 
            WHERE queue_id = %d AND assigned_to = %d AND status = 'assigned'
        ", $queue_id, $user_id));
        
        if (!$url_data) {
            wp_send_json_error(['message' => 'URL not found or not assigned to you']);
            return;
        }
        
        // Mark as completed and remove from queue
        $result = $wpdb->delete($table_name, ['queue_id' => $queue_id]);
        
        if ($result) {
            // Log activity
            $analytics = new SSPU_Analytics();
            $analytics->log_activity($user_id, 'alibaba_url_completed', [
                'url' => $url_data->url,
                'queue_id' => $queue_id,
                'time_spent' => time() - strtotime($url_data->assigned_at)
            ]);
            
            wp_send_json_success([
                'message' => 'URL marked as complete and removed from queue',
                'stats' => $this->get_queue_stats()
            ]);
        } else {
            wp_send_json_error(['message' => 'Failed to complete URL']);
        }
    }

    /**
     * Handle releasing URL back to queue
     */
    public function handle_release_url() {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');
        
        if (!current_user_can('upload_shopify_products')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }
        
        $queue_id = absint($_POST['queue_id']);
        $user_id = get_current_user_id();
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'sspu_alibaba_queue';
        
        // Verify ownership
        $url_data = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$table_name} 
            WHERE queue_id = %d AND assigned_to = %d AND status = 'assigned'
        ", $queue_id, $user_id));
        
        if (!$url_data) {
            wp_send_json_error(['message' => 'URL not found or not assigned to you']);
            return;
        }
        
        // Release URL back to queue
        $result = $wpdb->update($table_name, [
            'assigned_to' => null,
            'assigned_at' => null,
            'status' => 'available'
        ], ['queue_id' => $queue_id]);
        
        if ($result !== false) {
            // Log activity
            $analytics = new SSPU_Analytics();
            $analytics->log_activity($user_id, 'alibaba_url_released', [
                'url' => $url_data->url,
                'queue_id' => $queue_id
            ]);
            
            wp_send_json_success([
                'message' => 'URL released back to queue',
                'stats' => $this->get_queue_stats()
            ]);
        } else {
            wp_send_json_error(['message' => 'Failed to release URL']);
        }
    }

    /**
     * Get current user's assigned Alibaba URL
     */
    public function handle_get_current_url() {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');
        
        if (!current_user_can('upload_shopify_products')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }
        
        $user_id = get_current_user_id();
        $assignment = self::get_user_assignment($user_id);
        
        if ($assignment) {
            wp_send_json_success([
                'url' => $assignment->url,
                'queue_id' => $assignment->queue_id,
                'assigned_at' => $assignment->assigned_at
            ]);
        } else {
            wp_send_json_error(['message' => 'No URL assigned']);
        }
    }

    /**
     * Release expired URL assignments
     */
    private function release_expired_urls() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sspu_alibaba_queue';
        
        $expiry_minutes = get_option('sspu_alibaba_url_expiry', 60);
        
        $expired = $wpdb->query($wpdb->prepare("
            UPDATE {$table_name} 
            SET assigned_to = NULL, assigned_at = NULL, status = 'available'
            WHERE status = 'assigned' 
            AND assigned_at < DATE_SUB(NOW(), INTERVAL %d MINUTE)
        ", $expiry_minutes));
        
        if ($expired > 0) {
            // Log expired releases
            $analytics = new SSPU_Analytics();
            $analytics->log_activity(0, 'alibaba_urls_expired', [
                'count' => $expired
            ]);
        }
        
        return $expired;
    }

    /**
     * Get queue statistics
     */
    private function get_queue_stats() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sspu_alibaba_queue';
        
        $stats = $wpdb->get_row("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available,
                SUM(CASE WHEN status = 'assigned' THEN 1 ELSE 0 END) as assigned,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
            FROM {$table_name}
        ", ARRAY_A);
        
        return $stats;
    }

    /**
     * Get user's current assignment
     */
    public static function get_user_assignment($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sspu_alibaba_queue';
        
        return $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$table_name} 
            WHERE assigned_to = %d AND status = 'assigned'
            ORDER BY assigned_at DESC
            LIMIT 1
        ", $user_id));
    }
}