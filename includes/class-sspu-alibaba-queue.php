<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

class SSPU_Alibaba_Queue {

    public function __construct() {
        // Include Queue page handlers
        add_action( 'wp_ajax_sspu_save_alibaba_urls', [ $this, 'handle_save_urls' ] );
        add_action( 'wp_ajax_sspu_get_alibaba_urls', [ $this, 'handle_get_urls' ] );
        add_action( 'wp_ajax_sspu_clear_alibaba_urls', [ $this, 'handle_clear_urls' ] );
        add_action( 'wp_ajax_sspu_manage_alibaba_queue', [ $this, 'handle_queue_action' ] );
        
        // AJAX handlers for uploaders
        add_action( 'wp_ajax_sspu_request_alibaba_url', [ $this, 'handle_request_url' ] );
        add_action( 'wp_ajax_sspu_complete_alibaba_url', [ $this, 'handle_complete_url' ] );
        add_action( 'wp_ajax_sspu_release_alibaba_url', [ $this, 'handle_release_url' ] );
        add_action( 'wp_ajax_sspu_get_current_alibaba_url', [ $this, 'handle_get_current_url' ] );
        
        // Add settings fields
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        
        // Fix any existing records on init
        add_action( 'admin_init', [ $this, 'fix_existing_records' ] );
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
     * Fix any existing records that might have NULL or empty status
     */
    public function fix_existing_records() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sspu_alibaba_queue';
        
        // Only run this once
        if (get_option('sspu_alibaba_queue_fixed', false)) {
            return;
        }
        
        // Fix any NULL or empty status values
        $wpdb->query("
            UPDATE {$table_name} 
            SET status = 'available' 
            WHERE status IS NULL OR status = '' OR status NOT IN ('available', 'assigned', 'completed')
        ");
        
        // Mark as fixed
        update_option('sspu_alibaba_queue_fixed', true);
    }

    /**
     * Handle saving URLs from admin - always appends
     */
    public function handle_save_urls() {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }
        
        $urls = sanitize_textarea_field($_POST['urls']);
        error_log('[SSPU QUEUE DEBUG] Received URLs on server: ' . $urls);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'sspu_alibaba_queue';
        
        // Parse URLs (one per line)
        $url_array = array_filter(array_map('trim', explode("\n", $urls)));
        $valid_urls = [];
        
        foreach ($url_array as $url) {
            $url = trim($url);
            error_log('[SSPU QUEUE DEBUG] Checking URL: ' . $url);
            // Basic validation: just check if it's a valid URL format
            if (filter_var($url, FILTER_VALIDATE_URL)) {
                $valid_urls[] = $url;
                error_log('[SSPU QUEUE DEBUG] URL is VALID: ' . $url);
            } else {
                error_log('[SSPU QUEUE DEBUG] URL is INVALID and was rejected: ' . $url);
            }
        }
        
        if (empty($valid_urls)) {
            wp_send_json_error(['message' => 'No valid URLs found']);
            return;
        }
        
        // Start transaction
        $wpdb->query('START TRANSACTION');
        
        try {
            $inserted_count = 0;
            foreach ($valid_urls as $url) {
                // Check if URL already exists
                $exists = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$table_name} WHERE url = %s",
                    $url
                ));
                
                if (!$exists) {
                    $result = $wpdb->insert($table_name, [
                        'url' => $url,
                        'status' => 'available',
                        'created_at' => current_time('mysql'),
                        'created_by' => get_current_user_id(),
                        'assigned_to' => null,
                        'assigned_at' => null,
                        'completed_at' => null
                    ]);
                    
                    if ($result) {
                        $inserted_count++;
                    } else {
                        error_log('SSPU: Failed to insert URL: ' . $url . ' - Error: ' . $wpdb->last_error);
                    }
                }
            }
            
            $wpdb->query('COMMIT');
            
            $stats = $this->get_queue_stats();
            
            if (class_exists('SSPU_Analytics')) {
                $analytics = new SSPU_Analytics();
                $analytics->log_activity(get_current_user_id(), 'alibaba_urls_updated', [
                    'action' => 'append',
                    'urls_added' => $inserted_count,
                    'total_urls' => $stats['total']
                ]);
            }
            
            wp_send_json_success([
                'message' => sprintf('%d URLs added to queue', $inserted_count),
                'stats' => $stats
            ]);
            
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            error_log('SSPU: Database error - ' . $e->getMessage());
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
                    ELSE 4
                END,
                q.created_at DESC
        ");
        
        // Get stats
        $stats = $this->get_queue_stats();
        
        // Debug log
        error_log('SSPU: Retrieved ' . count($urls) . ' URLs from queue');
        
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
        if (class_exists('SSPU_Analytics')) {
            $analytics = new SSPU_Analytics();
            $analytics->log_activity(get_current_user_id(), 'alibaba_urls_cleared', [
                'clear_type' => $clear_type,
                'urls_removed' => $result
            ]);
        }
        
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
            $result = $wpdb->update($table_name, [
                'assigned_to' => $user_id,
                'assigned_at' => current_time('mysql'),
                'status' => 'assigned'
            ], [
                'queue_id' => $next_url->queue_id
            ], [
                '%d', // assigned_to
                '%s', // assigned_at
                '%s'  // status
            ], [
                '%d'  // queue_id
            ]);
            
            if ($result === false) {
                throw new Exception('Failed to update URL assignment');
            }
            
            $wpdb->query('COMMIT');
            
            // Log activity
            if (class_exists('SSPU_Analytics')) {
                $analytics = new SSPU_Analytics();
                $analytics->log_activity($user_id, 'alibaba_url_requested', [
                    'url' => $next_url->url,
                    'queue_id' => $next_url->queue_id
                ]);
            }
            
            wp_send_json_success([
                'url' => $next_url->url,
                'queue_id' => $next_url->queue_id,
                'assigned_at' => current_time('mysql'),
                'message' => 'URL assigned successfully'
            ]);
            
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            error_log('SSPU: Failed to assign URL - ' . $e->getMessage());
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
        
        // Update status to completed
        $result = $wpdb->update($table_name, [
            'status' => 'completed',
            'completed_at' => current_time('mysql')
        ], [
            'queue_id' => $queue_id
        ], [
            '%s', // status
            '%s'  // completed_at
        ], [
            '%d'  // queue_id
        ]);
        
        if ($result !== false) {
            // Log activity
            if (class_exists('SSPU_Analytics')) {
                $analytics = new SSPU_Analytics();
                $analytics->log_activity($user_id, 'alibaba_url_completed', [
                    'url' => $url_data->url,
                    'queue_id' => $queue_id,
                    'time_spent' => time() - strtotime($url_data->assigned_at)
                ]);
            }
            
            wp_send_json_success([
                'message' => 'URL marked as complete',
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
        ], [
            'queue_id' => $queue_id
        ], [
            '%d', // assigned_to (NULL)
            '%s', // assigned_at (NULL)
            '%s'  // status
        ], [
            '%d'  // queue_id
        ]);
        
        if ($result !== false) {
            // Log activity
            if (class_exists('SSPU_Analytics')) {
                $analytics = new SSPU_Analytics();
                $analytics->log_activity($user_id, 'alibaba_url_released', [
                    'url' => $url_data->url,
                    'queue_id' => $queue_id
                ]);
            }
            
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
     * Handle queue actions from the admin page
     */
    public function handle_queue_action() {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }
        
        $action = sanitize_text_field($_POST['queue_action']);
        $queue_id = absint($_POST['queue_id']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'sspu_alibaba_queue';
        
        switch ($action) {
            case 'assign':
                $result = $wpdb->update($table_name, [
                    'assigned_to' => get_current_user_id(),
                    'assigned_at' => current_time('mysql'),
                    'status' => 'assigned'
                ], ['queue_id' => $queue_id]);
                break;
                
            case 'release':
                $result = $wpdb->update($table_name, [
                    'assigned_to' => null,
                    'assigned_at' => null,
                    'status' => 'available'
                ], ['queue_id' => $queue_id]);
                break;
                
            case 'complete':
                $result = $wpdb->update($table_name, [
                    'status' => 'completed',
                    'completed_at' => current_time('mysql')
                ], ['queue_id' => $queue_id]);
                break;
                
            case 'delete':
                $result = $wpdb->delete($table_name, ['queue_id' => $queue_id]);
                break;
                
            default:
                wp_send_json_error(['message' => 'Invalid action']);
                return;
        }
        
        if ($result !== false) {
            wp_send_json_success(['message' => 'Action completed successfully']);
        } else {
            wp_send_json_error(['message' => 'Action failed']);
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
            SET assigned_to = NULL, 
                assigned_at = NULL, 
                status = 'available'
            WHERE status = 'assigned' 
            AND assigned_at < DATE_SUB(NOW(), INTERVAL %d MINUTE)
        ", $expiry_minutes));
        
        if ($expired > 0) {
            error_log('SSPU: Released ' . $expired . ' expired URL assignments');
            
            // Log expired releases
            if (class_exists('SSPU_Analytics')) {
                $analytics = new SSPU_Analytics();
                $analytics->log_activity(0, 'alibaba_urls_expired', [
                    'count' => $expired
                ]);
            }
        }
        
        return $expired;
    }

    /**
     * Get queue statistics
     */
    private function get_queue_stats() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sspu_alibaba_queue';
        
        // First, let's debug what's actually in the table
        $debug_query = "SELECT status, COUNT(*) as count FROM {$table_name} GROUP BY status";
        $status_breakdown = $wpdb->get_results($debug_query);
        error_log('SSPU Queue Status Breakdown: ' . print_r($status_breakdown, true));
        
        // Get the stats with defensive handling for NULL or empty status
        $stats = $wpdb->get_row("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'available' OR status IS NULL OR status = '' THEN 1 ELSE 0 END) as available,
                SUM(CASE WHEN status = 'assigned' THEN 1 ELSE 0 END) as assigned,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
            FROM {$table_name}
        ", ARRAY_A);
        
        // Ensure all values are integers
        $stats = array_map('intval', $stats);
        
        error_log('SSPU Queue Stats: ' . print_r($stats, true));
        
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

    /**
     * Debug function to check queue data
     */
    public function debug_queue_data() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sspu_alibaba_queue';
        
        // Get sample records
        $sample_records = $wpdb->get_results("
            SELECT queue_id, url, status, created_at, assigned_to, assigned_at
            FROM {$table_name} 
            ORDER BY queue_id DESC 
            LIMIT 10
        ");
        
        error_log('SSPU Queue Sample Records: ' . print_r($sample_records, true));
        
        // Get table structure
        $table_structure = $wpdb->get_results("DESCRIBE {$table_name}");
        error_log('SSPU Queue Table Structure: ' . print_r($table_structure, true));
        
        return [
            'sample_records' => $sample_records,
            'table_structure' => $table_structure,
            'stats' => $this->get_queue_stats()
        ];
    }
}