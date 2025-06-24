<?php
// Updated class-sspu-search.php with proper URL handling

class SSPU_Search {

    public function __construct() {
        add_action( 'wp_ajax_sspu_global_search', [ $this, 'handle_global_search' ] );
        add_action( 'wp_ajax_sspu_get_search_filters', [ $this, 'handle_get_search_filters' ] );
    }

    public function handle_global_search() {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');
        
        $query = sanitize_text_field($_POST['query'] ?? '');
        $type = sanitize_text_field($_POST['type'] ?? 'all');
        $date_range = absint($_POST['date_range'] ?? 0);
        $user_id = absint($_POST['user_id'] ?? 0);
        $status = sanitize_text_field($_POST['status'] ?? 'all');
        $page = absint($_POST['page'] ?? 1);
        $per_page = absint($_POST['per_page'] ?? 50);
        $show_all = filter_var($_POST['show_all'] ?? false, FILTER_VALIDATE_BOOLEAN);
        
        $results = $this->perform_search($query, $type, $date_range, $user_id, $status, $page, $per_page, $show_all);
        
        // Add store information to results
        $results['store_info'] = [
            'store_name' => get_option('sspu_shopify_store_name', ''),
            'store_domain' => get_option('sspu_shopify_store_domain', '') // Add this option if you have it
        ];
        
        wp_send_json_success($results);
    }

    public function handle_get_search_filters() {
        check_ajax_referer('sspu_ajax_nonce', 'nonce');
        
        $filters = $this->get_search_filters();
        
        wp_send_json_success($filters);
    }

    private function perform_search($query, $type = 'all', $date_range = 0, $user_id = 0, $status = 'all', $page = 1, $per_page = 50, $show_all = false) {
        global $wpdb;
        
        $results = [];
        
        // Search products (with pagination)
        if ($type === 'all' || $type === 'products') {
            $product_results = $this->search_products($query, $date_range, $user_id, $status, $page, $per_page, $show_all);
            $results['products'] = $product_results['items'];
            $results['pagination'] = $product_results['pagination'];
        }
        
        // Search collections (from Shopify API) - only if searching
        if (!$show_all && ($type === 'all' || $type === 'collections')) {
            $results['collections'] = $this->search_collections($query);
        }
        
        // Search variants (from product log and activity) - only if searching
        if (!$show_all && ($type === 'all' || $type === 'variants')) {
            $results['variants'] = $this->search_variants($query, $date_range, $user_id);
        }
        
        return $results;
    }

    private function search_products($query, $date_range = 0, $user_id = 0, $status = 'all', $page = 1, $per_page = 50, $show_all = false) {
        global $wpdb;
        
        $product_log_table = $wpdb->prefix . 'sspu_product_log';
        $users_table = $wpdb->prefix . 'users';
        
        $where_conditions = [];
        $query_params = [];
        
        // Only add search condition if not showing all and query is provided
        if (!$show_all && !empty($query)) {
            $where_conditions[] = "pl.product_title LIKE %s";
            $query_params[] = '%' . $wpdb->esc_like($query) . '%';
        }
        
        if ($date_range > 0) {
            $where_conditions[] = "DATE(pl.upload_timestamp) >= DATE_SUB(CURDATE(), INTERVAL %d DAY)";
            $query_params[] = $date_range;
        }
        
        if ($user_id > 0) {
            $where_conditions[] = "pl.wp_user_id = %d";
            $query_params[] = $user_id;
        }
        
        if ($status !== 'all') {
            $where_conditions[] = "pl.status = %s";
            $query_params[] = $status;
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        // Calculate offset
        $offset = ($page - 1) * $per_page;
        
        // Get total count
        $count_query = "SELECT COUNT(*) FROM {$product_log_table} pl {$where_clause}";
        if (!empty($query_params)) {
            $total_items = $wpdb->get_var($wpdb->prepare($count_query, ...$query_params));
        } else {
            $total_items = $wpdb->get_var($count_query);
        }
        
        // Get paginated results with handle and additional data
        $products_query = "
            SELECT 
                pl.*,
                u.display_name,
                u.user_login
            FROM {$product_log_table} pl
            JOIN {$users_table} u ON pl.wp_user_id = u.ID
            {$where_clause}
            ORDER BY pl.upload_timestamp DESC
            LIMIT %d OFFSET %d
        ";
        
        // Add pagination params
        $query_params[] = $per_page;
        $query_params[] = $offset;
        
        if (!empty($query_params)) {
            $products = $wpdb->get_results($wpdb->prepare($products_query, ...$query_params));
        } else {
            $products = $wpdb->get_results($wpdb->prepare($products_query, $per_page, $offset));
        }
        
        // Process each product to extract handle and other data
        foreach ($products as &$product) {
            // Try to decode shopify_data if it exists
            if (!empty($product->shopify_data)) {
                $shopify_data = json_decode($product->shopify_data, true);
                if ($shopify_data && isset($shopify_data['handle'])) {
                    $product->shopify_handle = $shopify_data['handle'];
                } else {
                    $product->shopify_handle = $this->generate_handle_from_title($product->product_title);
                }
            } else {
                // Generate handle from title if no Shopify data
                $product->shopify_handle = $this->generate_handle_from_title($product->product_title);
            }
            
            // Ensure we have the Shopify product ID
            if (empty($product->shopify_product_id) && !empty($shopify_data) && isset($shopify_data['id'])) {
                $product->shopify_product_id = $shopify_data['id'];
            }
        }
        
        // Calculate pagination data
        $total_pages = ceil($total_items / $per_page);
        
        return [
            'items' => $products,
            'pagination' => [
                'total_items' => $total_items,
                'total_pages' => $total_pages,
                'current_page' => $page,
                'per_page' => $per_page
            ]
        ];
    }
    
    private function generate_handle_from_title($title) {
        // Convert title to handle format (lowercase, hyphenated)
        $handle = strtolower($title);
        $handle = preg_replace('/[^a-z0-9]+/', '-', $handle);
        $handle = trim($handle, '-');
        return $handle;
    }

    private function search_collections($query) {
        // This would typically make a Shopify API call
        $store_name = get_option('sspu_shopify_store_name');
        $access_token = get_option('sspu_shopify_access_token');
        
        if (empty($store_name) || empty($access_token)) {
            return [];
        }
        
        $url = "https://{$store_name}.myshopify.com/admin/api/2024-10/custom_collections.json";
        $response = wp_remote_get($url, [
            'headers' => [
                'X-Shopify-Access-Token' => $access_token
            ],
            'timeout' => 30
        ]);
        
        if (is_wp_error($response)) {
            return [];
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!isset($data['custom_collections'])) {
            return [];
        }
        
        // Filter collections by query
        $filtered_collections = array_filter($data['custom_collections'], function($collection) use ($query) {
            return stripos($collection['title'], $query) !== false || 
                   stripos($collection['handle'], $query) !== false;
        });
        
        return array_values($filtered_collections);
    }

    private function search_variants($query, $date_range = 0, $user_id = 0) {
        global $wpdb;
        
        $activity_log_table = $wpdb->prefix . 'sspu_activity_log';
        $users_table = $wpdb->prefix . 'users';
        
        $where_conditions = ["(al.action LIKE %s OR JSON_EXTRACT(al.metadata, '$.product_title') LIKE %s)"];
        $query_params = ['%' . $wpdb->esc_like($query) . '%', '%' . $wpdb->esc_like($query) . '%'];
        
        if ($date_range > 0) {
            $where_conditions[] = "DATE(al.timestamp) >= DATE_SUB(CURDATE(), INTERVAL %d DAY)";
            $query_params[] = $date_range;
        }
        
        if ($user_id > 0) {
            $where_conditions[] = "al.user_id = %d";
            $query_params[] = $user_id;
        }
        
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        
        $activities = $wpdb->get_results($wpdb->prepare("
            SELECT 
                al.*,
                u.display_name,
                u.user_login
            FROM {$activity_log_table} al
            JOIN {$users_table} u ON al.user_id = u.ID
            {$where_clause}
            ORDER BY al.timestamp DESC
            LIMIT 50
        ", ...$query_params));
        
        return $activities;
    }

    private function get_search_filters() {
        global $wpdb;
        
        $users_table = $wpdb->prefix . 'users';
        $user_meta_table = $wpdb->prefix . 'usermeta';
        
        // Get users with upload_shopify_products capability
        $users = $wpdb->get_results("
            SELECT DISTINCT u.ID, u.display_name
            FROM {$users_table} u
            JOIN {$user_meta_table} um ON u.ID = um.user_id
            WHERE (um.meta_key = '{$wpdb->prefix}capabilities' AND um.meta_value LIKE '%upload_shopify_products%')
            ORDER BY u.display_name
        ");
        
        return [
            'users' => $users
        ];
    }
}
