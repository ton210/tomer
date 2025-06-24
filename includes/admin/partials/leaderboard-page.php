<?php
/**
 * Leaderboard Page Template
 */
if(!defined('WPINC'))die;

global $wpdb;

// Get period from query
$period = isset($_GET['period']) ? absint($_GET['period']) : 7;

// Database queries
$product_log_table = $wpdb->prefix . 'sspu_product_log';
$users_table = $wpdb->prefix . 'users';

$where_clause = '';
if($period > 0) {
    $where_clause = $wpdb->prepare("WHERE log.upload_timestamp >= DATE_SUB(NOW(), INTERVAL %d DAY)", $period);
}

// Get leaderboard data
$data = $wpdb->get_results("
    SELECT 
        COUNT(log.log_id) as product_count, 
        u.display_name, 
        log.wp_user_id,
        AVG(log.upload_duration) as avg_duration,
        SUM(CASE WHEN log.status = 'success' THEN 1 ELSE 0 END) as success_count,
        SUM(CASE WHEN log.status = 'error' THEN 1 ELSE 0 END) as error_count
    FROM {$product_log_table} as log
    JOIN {$users_table} as u ON log.wp_user_id = u.ID
    {$where_clause}
    GROUP BY log.wp_user_id
    ORDER BY product_count DESC
");

// Calculate stats
$total_products = 0;
$total_errors = 0;
foreach($data as $row) {
    $total_products += $row->product_count;
    $total_errors += $row->error_count;
}

$total_users = count($data);
$success_rate = $total_products > 0 ? round((($total_products - $total_errors) / $total_products) * 100, 1) : 0;
$top_uploader = $total_users > 0 ? $data[0]->display_name . ' (' . $data[0]->product_count . ')' : 'N/A';
?>

<div class="wrap sspu-dashboard">
    <h1><?php esc_html_e('Dashboard & Stats', 'sspu'); ?></h1>
    
    <!-- Period Filter -->
    <div class="sspu-dashboard-filters">
        <a href="?page=sspu-leaderboard&period=7" class="<?php echo $period === 7 ? 'current' : ''; ?>">
            <?php esc_html_e('Last 7 Days', 'sspu'); ?>
        </a> | 
        <a href="?page=sspu-leaderboard&period=30" class="<?php echo $period === 30 ? 'current' : ''; ?>">
            <?php esc_html_e('Last 30 Days', 'sspu'); ?>
        </a> | 
        <a href="?page=sspu-leaderboard&period=0" class="<?php echo $period === 0 ? 'current' : ''; ?>">
            <?php esc_html_e('All Time', 'sspu'); ?>
        </a>
    </div>
    
    <!-- Stats Boxes -->
    <div class="sspu-stat-boxes">
        <div class="stat-box">
            <h4><?php esc_html_e('Total Products', 'sspu'); ?></h4>
            <p><?php echo esc_html($total_products); ?></p>
        </div>
        <div class="stat-box">
            <h4><?php esc_html_e('Success Rate', 'sspu'); ?></h4>
            <p><?php echo esc_html($success_rate); ?>%</p>
        </div>
        <div class="stat-box">
            <h4><?php esc_html_e('Active Users', 'sspu'); ?></h4>
            <p><?php echo esc_html($total_users); ?></p>
        </div>
        <div class="stat-box">
            <h4><?php esc_html_e('Top Uploader', 'sspu'); ?></h4>
            <p><?php echo esc_html($top_uploader); ?></p>
        </div>
    </div>
    
    <!-- Detailed Leaderboard -->
    <h2><?php esc_html_e('Detailed Leaderboard', 'sspu'); ?></h2>
    <table class="wp-list-table widefat striped">
        <thead>
            <tr>
                <th style="width: 50px;"><?php esc_html_e('Rank', 'sspu'); ?></th>
                <th><?php esc_html_e('User', 'sspu'); ?></th>
                <th><?php esc_html_e('Products', 'sspu'); ?></th>
                <th><?php esc_html_e('Success Rate', 'sspu'); ?></th>
                <th><?php esc_html_e('Avg Duration', 'sspu'); ?></th>
                <th><?php esc_html_e('Performance', 'sspu'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($data)): ?>
                <tr>
                    <td colspan="6"><?php esc_html_e('No products have been uploaded in this period.', 'sspu'); ?></td>
                </tr>
            <?php else: ?>
                <?php foreach($data as $key => $row): ?>
                    <?php 
                    $user_success_rate = $row->product_count > 0 ? round(($row->success_count / $row->product_count) * 100, 1) : 0;
                    $avg_duration = $row->avg_duration ? round($row->avg_duration / 60, 1) : 0;
                    $performance_class = $user_success_rate >= 95 ? 'excellent' : ($user_success_rate >= 80 ? 'good' : 'needs-improvement');
                    ?>
                    <tr>
                        <td><?php echo $key + 1; ?></td>
                        <td><?php echo esc_html($row->display_name); ?></td>
                        <td><?php echo esc_html($row->product_count); ?></td>
                        <td><?php echo esc_html($user_success_rate); ?>%</td>
                        <td><?php echo esc_html($avg_duration); ?> <?php esc_html_e('min', 'sspu'); ?></td>
                        <td>
                            <span class="performance-badge <?php echo $performance_class; ?>">
                                <?php echo ucfirst(str_replace('-', ' ', $performance_class)); ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>