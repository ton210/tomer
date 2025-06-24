<?php
/**
 * Analytics Page Template
 */
if(!defined('WPINC'))die;
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
        <button id="refresh-analytics" class="button">
            <?php esc_html_e('Refresh', 'sspu'); ?>
        </button>
        <button id="export-analytics-btn" class="button">
            <?php esc_html_e('Export Data', 'sspu'); ?>
        </button>
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
    
    <div class="detailed-analytics" style="margin-top: 40px;">
        <h2><?php esc_html_e('Detailed User Activity', 'sspu'); ?></h2>
        <div id="user-activity-log"></div>
    </div>

    <div class="detailed-analytics" style="margin-top: 40px;">
        <h2>User Performance Breakdown</h2>
        <div id="user-performance-table-container">
            <p>Loading user performance data...</p>
        </div>
    </div>
</div>