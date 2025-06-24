<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

class SSPU_Analytics {

    public function __construct() {
        add_action( 'wp_ajax_sspu_get_analytics', [ $this, 'handle_get_analytics' ] );
        add_action( 'wp_ajax_sspu_get_user_activity', [ $this, 'handle_get_user_activity' ] );
    }

    public function log_activity($user_id, $action, $metadata = []) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sspu_activity_log';
        
        $wpdb->insert($table_name, [
            'user_id' => $user_id,
            'action' => $action,
            'metadata' => json_encode($metadata),
            'timestamp' => current_time('mysql'),
            'ip_address' => $this->get_user_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    }

    public function handle_get_analytics() {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');
        
        $period = absint($_POST['period'] ?? 30);
        $analytics_data = $this->get_analytics_data($period);
        
        wp_send_json_success($analytics_data);
    }

    public function handle_get_user_activity() {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');
        
        $period = absint($_POST['period'] ?? 30);
        $user_id = absint($_POST['user_id'] ?? 0);
        $activity_data = $this->get_user_activity_data($period, $user_id);
        
        wp_send_json_success($activity_data);
    }

    public function get_analytics_data($period = 30) {
        global $wpdb;
        
        $product_log_table = $wpdb->prefix . 'sspu_product_log';
        $activity_log_table = $wpdb->prefix . 'sspu_activity_log';
        $users_table = $wpdb->prefix . 'users';
        
        $date_condition = $period > 0 ? $wpdb->prepare("WHERE log.upload_timestamp >= DATE_SUB(CURDATE(), INTERVAL %d DAY)", $period) : '';
        $activity_date_condition = $period > 0 ? $wpdb->prepare("WHERE DATE(timestamp) >= DATE_SUB(CURDATE(), INTERVAL %d DAY)", $period) : '';

        // Enhanced User Performance Query
        $user_performance_query = "
            SELECT
                u.ID as user_id,
                u.display_name,
                COUNT(log.log_id) as total_completed,
                SUM(CASE WHEN log.upload_timestamp >= DATE_SUB(NOW(), INTERVAL 1 DAY) THEN 1 ELSE 0 END) as 'uploads_today',
                SUM(CASE WHEN log.upload_timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as 'uploads_week',
                SUM(CASE WHEN log.upload_timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as 'uploads_month'
            FROM {$users_table} u
            LEFT JOIN {$product_log_table} log ON u.ID = log.wp_user_id AND log.status = 'success'
            GROUP BY u.ID
            HAVING total_completed > 0
            ORDER BY total_completed DESC
        ";
        $user_performance = $wpdb->get_results($user_performance_query);

        // Calculate averages in PHP
        foreach ($user_performance as &$user) {
            $user->avg_day = round($user->uploads_month / 30, 2);
            $user->avg_week = round($user->uploads_month / 4, 2);
        }
        
        // Upload performance data
        $upload_performance = $wpdb->get_results("
            SELECT 
                DATE(upload_timestamp) as date,
                COUNT(*) as total_uploads,
                SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as successful_uploads,
                SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) as failed_uploads,
                AVG(upload_duration) as avg_duration
            FROM {$product_log_table}
            {$date_condition}
            GROUP BY DATE(upload_timestamp)
            ORDER BY date DESC
        ");
        
        // User comparison data
        $user_comparison = $wpdb->get_results("
            SELECT 
                u.display_name,
                COUNT(pl.log_id) as total_uploads,
                SUM(CASE WHEN pl.status = 'success' THEN 1 ELSE 0 END) as successful_uploads,
                AVG(pl.upload_duration) as avg_duration,
                MAX(pl.upload_timestamp) as last_upload
            FROM {$users_table} u
            LEFT JOIN {$product_log_table} pl ON u.ID = pl.wp_user_id
            {$date_condition}
            GROUP BY u.ID
            HAVING total_uploads > 0
            ORDER BY total_uploads DESC
            LIMIT 10
        ");
        
        // Error patterns
        $error_patterns = $wpdb->get_results("
            SELECT 
                error_data,
                COUNT(*) as error_count,
                DATE(upload_timestamp) as error_date
            FROM {$product_log_table}
            WHERE status = 'error' AND error_data IS NOT NULL
            {$date_condition}
            GROUP BY error_data, DATE(upload_timestamp)
            ORDER BY error_count DESC
        ");
        
        // Time tracking stats
        $time_stats = $wpdb->get_row("
            SELECT 
                AVG(upload_duration) as avg_duration,
                MIN(upload_duration) as min_duration,
                MAX(upload_duration) as max_duration,
                STDDEV(upload_duration) as stddev_duration
            FROM {$product_log_table}
            WHERE status = 'success'
            {$date_condition}
        ");
        
        // Activity breakdown
        $activity_breakdown = $wpdb->get_results("
            SELECT 
                action,
                COUNT(*) as action_count,
                AVG(JSON_EXTRACT(metadata, '$.duration')) as avg_duration
            FROM {$activity_log_table}
            {$activity_date_condition}
            GROUP BY action
            ORDER BY action_count DESC
        ");
        
        // Peak usage hours
        $peak_hours = $wpdb->get_results("
            SELECT 
                HOUR(timestamp) as hour,
                COUNT(*) as activity_count
            FROM {$activity_log_table}
            {$activity_date_condition}
            GROUP BY HOUR(timestamp)
            ORDER BY activity_count DESC
        ");
        
        return [
            'user_performance' => $user_performance,
            'upload_performance' => $upload_performance,
            'user_comparison' => $user_comparison,
            'error_patterns' => $error_patterns,
            'time_stats' => $time_stats,
            'activity_breakdown' => $activity_breakdown,
            'peak_hours' => $peak_hours
        ];
    }

    public function get_user_activity_data($period = 30, $user_id = 0) {
        global $wpdb;
        
        $activity_log_table = $wpdb->prefix . 'sspu_activity_log';
        $users_table = $wpdb->prefix . 'users';
        
        $date_condition = $period > 0 ? $wpdb->prepare("AND DATE(al.timestamp) >= DATE_SUB(CURDATE(), INTERVAL %d DAY)", $period) : '';
        $user_condition = $user_id > 0 ? $wpdb->prepare("AND al.user_id = %d", $user_id) : '';
        
        $activity_log = $wpdb->get_results("
            SELECT 
                al.*,
                u.display_name,
                u.user_login
            FROM {$activity_log_table} al
            JOIN {$users_table} u ON al.user_id = u.ID
            WHERE 1=1 {$date_condition} {$user_condition}
            ORDER BY al.timestamp DESC
            LIMIT 500
        ");
        
        return $activity_log;
    }

    private function get_user_ip() {
        $ip_keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
}