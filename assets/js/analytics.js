(function($) {
    'use strict';

    let charts = {};
    let currentPage = 1;
    let totalPages = 1;
    let searchQuery = '';

    $(document).ready(function() {
        if ($('#upload-performance-chart').length) {
            initializeAnalytics();
        }

        if ($('#global-search-input').length) {
            initializeSearch();
            // Load all products by default
            performSearch(true);
        }
    });

    function initializeAnalytics() {
        loadAnalyticsData();

        $('#refresh-analytics').on('click', function() {
            loadAnalyticsData();
        });

        $('#analytics-period').on('change', function() {
            loadAnalyticsData();
        });
    }

    function initializeSearch() {
        loadSearchFilters();

        $('#global-search-btn').on('click', function() {
            searchQuery = $('#global-search-input').val().trim();
            currentPage = 1;
            performSearch(false);
        });

        $('#clear-search-btn').on('click', function() {
            $('#global-search-input').val('');
            searchQuery = '';
            currentPage = 1;
            performSearch(true);
        });

        $('#global-search-input').on('keypress', function(e) {
            if (e.which === 13) {
                searchQuery = $(this).val().trim();
                currentPage = 1;
                performSearch(false);
            }
        });

        // Filter changes trigger search
        $('#search-type, #search-date-range, #search-user, #search-status, #results-per-page').on('change', function() {
            currentPage = 1;
            performSearch(searchQuery === '');
        });

        // Pagination handlers
        $(document).on('click', '.pagination-link', function(e) {
            e.preventDefault();
            currentPage = parseInt($(this).data('page'));
            performSearch(searchQuery === '');
        });
    }

    function loadAnalyticsData() {
        const period = $('#analytics-period').val() || 30;

        $.post(ajaxurl, {
            action: 'sspu_get_analytics',
            nonce: sspu_ajax.nonce,
            period: period
        })
        .done(function(response) {
            if (response.success) {
                renderCharts(response.data);
                renderTimeStats(response.data.time_stats);
                renderUserActivity(response.data);
            }
        })
        .fail(function() {
            console.error('Failed to load analytics data');
        });
    }

    function renderCharts(data) {
        // Upload Performance Chart
        if (charts.uploadPerformance) {
            charts.uploadPerformance.destroy();
        }

        const uploadCtx = document.getElementById('upload-performance-chart').getContext('2d');
        charts.uploadPerformance = new Chart(uploadCtx, {
            type: 'line',
            data: {
                labels: data.upload_performance.map(item => item.date),
                datasets: [{
                    label: 'Successful Uploads',
                    data: data.upload_performance.map(item => item.successful_uploads),
                    borderColor: '#46b450',
                    backgroundColor: 'rgba(70, 180, 80, 0.1)',
                    tension: 0.4
                }, {
                    label: 'Failed Uploads',
                    data: data.upload_performance.map(item => item.failed_uploads),
                    borderColor: '#dc3232',
                    backgroundColor: 'rgba(220, 50, 50, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // User Comparison Chart
        if (charts.userComparison) {
            charts.userComparison.destroy();
        }

        const userCtx = document.getElementById('user-comparison-chart').getContext('2d');
        charts.userComparison = new Chart(userCtx, {
            type: 'bar',
            data: {
                labels: data.user_comparison.map(item => item.display_name),
                datasets: [{
                    label: 'Total Uploads',
                    data: data.user_comparison.map(item => item.total_uploads),
                    backgroundColor: '#0073aa',
                    borderColor: '#005a87',
                    borderWidth: 1
                }, {
                    label: 'Success Rate (%)',
                    data: data.user_comparison.map(item =>
                        Math.round((item.successful_uploads / item.total_uploads) * 100)
                    ),
                    backgroundColor: '#46b450',
                    borderColor: '#3e9f42',
                    borderWidth: 1,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        beginAtZero: true
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        beginAtZero: true,
                        max: 100,
                        grid: {
                            drawOnChartArea: false,
                        },
                    }
                }
            }
        });

        // Error Patterns Chart
        if (charts.errorPatterns) {
            charts.errorPatterns.destroy();
        }

        const errorData = data.error_patterns.reduce((acc, item) => {
            const errorType = extractErrorType(item.error_data);
            acc[errorType] = (acc[errorType] || 0) + parseInt(item.error_count);
            return acc;
        }, {});

        const errorCtx = document.getElementById('error-patterns-chart').getContext('2d');
        charts.errorPatterns = new Chart(errorCtx, {
            type: 'doughnut',
            data: {
                labels: Object.keys(errorData),
                datasets: [{
                    data: Object.values(errorData),
                    backgroundColor: [
                        '#dc3232',
                        '#f56e28',
                        '#ffb900',
                        '#00a32a',
                        '#0073aa',
                        '#8b5cf6'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Activity Breakdown Chart
        if (data.activity_breakdown && data.activity_breakdown.length > 0) {
            if (charts.activityBreakdown) {
                charts.activityBreakdown.destroy();
            }

            const activityCtx = document.getElementById('activity-breakdown-chart').getContext('2d');
            charts.activityBreakdown = new Chart(activityCtx, {
                type: 'pie',
                data: {
                    labels: data.activity_breakdown.map(item => formatActionName(item.action)),
                    datasets: [{
                        data: data.activity_breakdown.map(item => item.action_count),
                        backgroundColor: [
                            '#0073aa',
                            '#46b450',
                            '#ffb900',
                            '#dc3232',
                            '#8b5cf6',
                            '#f56e28'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }

        // Peak Hours Chart
        if (data.peak_hours && data.peak_hours.length > 0) {
            if (charts.peakHours) {
                charts.peakHours.destroy();
            }

            const hoursCtx = document.getElementById('peak-hours-chart');
            if (hoursCtx) {
                const hourLabels = data.peak_hours.map(item => {
                    const hour = parseInt(item.hour);
                    return hour === 0 ? '12 AM' :
                           hour < 12 ? `${hour} AM` :
                           hour === 12 ? '12 PM' :
                           `${hour - 12} PM`;
                });

                charts.peakHours = new Chart(hoursCtx.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: hourLabels,
                        datasets: [{
                            label: 'Activity Count',
                            data: data.peak_hours.map(item => item.activity_count),
                            backgroundColor: '#0073aa'
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }
        }
    }

    function renderTimeStats(timeStats) {
        if (!timeStats) return;

        const avgMinutes = Math.round(timeStats.avg_duration / 60 * 10) / 10;
        const minMinutes = Math.round(timeStats.min_duration / 60 * 10) / 10;
        const maxMinutes = Math.round(timeStats.max_duration / 60 * 10) / 10;
        const stdDevMinutes = timeStats.stddev_duration ? Math.round(timeStats.stddev_duration / 60 * 10) / 10 : 0;

        $('#time-tracking-stats').html(`
            <div class="time-stats-grid">
                <div class="time-stat">
                    <h4>Average Duration</h4>
                    <span class="time-value">${avgMinutes} min</span>
                </div>
                <div class="time-stat">
                    <h4>Fastest Upload</h4>
                    <span class="time-value">${minMinutes} min</span>
                </div>
                <div class="time-stat">
                    <h4>Slowest Upload</h4>
                    <span class="time-value">${maxMinutes} min</span>
                </div>
                <div class="time-stat">
                    <h4>Standard Deviation</h4>
                    <span class="time-value">${stdDevMinutes} min</span>
                </div>
            </div>
        `);
    }

    function renderUserActivity(data) {
        const $activityLog = $('#user-activity-log');
        $activityLog.empty();

        // Request detailed user activity
        $.post(ajaxurl, {
            action: 'sspu_get_user_activity',
            nonce: sspu_ajax.nonce,
            period: $('#analytics-period').val() || 30,
            user_id: 0 // All users
        })
        .done(function(response) {
            if (response.success && response.data.length > 0) {
                let html = '<table class="wp-list-table widefat striped">';
                html += '<thead><tr><th>Time</th><th>User</th><th>Action</th><th>Details</th><th>IP Address</th></tr></thead>';
                html += '<tbody>';

                response.data.forEach(function(activity) {
                    const metadata = JSON.parse(activity.metadata || '{}');
                    const details = formatActivityDetails(activity.action, metadata);

                    html += `<tr>
                        <td>${formatDateTime(activity.timestamp)}</td>
                        <td>${activity.display_name}</td>
                        <td>${formatActionName(activity.action)}</td>
                        <td>${details}</td>
                        <td>${activity.ip_address || 'Unknown'}</td>
                    </tr>`;
                });

                html += '</tbody></table>';
                $activityLog.html(html);
            } else {
                $activityLog.html('<p>No activity data available for the selected period.</p>');
            }
        });
    }

    function extractErrorType(errorData) {
        try {
            const parsed = JSON.parse(errorData);
            if (typeof parsed === 'string') {
                if (parsed.includes('API')) return 'API Error';
                if (parsed.includes('timeout')) return 'Timeout';
                if (parsed.includes('network')) return 'Network Error';
                if (parsed.includes('validation')) return 'Validation Error';
                return 'Other Error';
            } else if (parsed.errors) {
                // Handle Shopify API error format
                const errorKey = Object.keys(parsed.errors)[0];
                if (errorKey) {
                    return `Shopify: ${errorKey}`;
                }
            }
            return 'Unknown Error';
        } catch (e) {
            return 'Parse Error';
        }
    }

    function formatActionName(action) {
        return action
            .replace(/_/g, ' ')
            .replace(/\b\w/g, l => l.toUpperCase());
    }

    function formatActivityDetails(action, metadata) {
        switch(action) {
            case 'product_upload':
                return `Product: ${metadata.product_title || 'Unknown'} (${metadata.status})`;
            case 'ai_generation':
                return `Type: ${metadata.type || 'Description'} (${formatDuration(metadata.duration)})`;
            case 'get_collections':
                return `${metadata.status === 'success' ? 'Success' : 'Failed'} (${formatDuration(metadata.duration)})`;
            case 'create_collection':
                return `Name: ${metadata.collection_name || 'Unknown'}`;
            default:
                return metadata.product_title || formatDuration(metadata.duration) || '';
        }
    }

    function formatDuration(seconds) {
        if (!seconds) return '';
        return `${Math.round(seconds * 10) / 10}s`;
    }

    function formatDateTime(dateTimeString) {
        const date = new Date(dateTimeString);
        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
    }

    function loadSearchFilters() {
        $.post(ajaxurl, {
            action: 'sspu_get_search_filters',
            nonce: sspu_ajax.nonce
        })
        .done(function(response) {
            if (response.success) {
                const $userSelect = $('#search-user');
                $userSelect.find('option:not(:first)').remove();

                response.data.users.forEach(function(user) {
                    $userSelect.append(`<option value="${user.ID}">${user.display_name}</option>`);
                });
            }
        });
    }

    function performSearch(isDefault = false) {
        const query = isDefault ? '' : searchQuery;
        const type = $('#search-type').val();
        const dateRange = $('#search-date-range').val();
        const userId = $('#search-user').val();
        const status = $('#search-status').val();
        const perPage = $('#results-per-page').val();

        $('#search-results').html('<div class="loading-indicator" style="text-align: center; padding: 20px;"><span class="spinner is-active" style="float: none;"></span><p>Loading...</p></div>');

        $.post(ajaxurl, {
            action: 'sspu_global_search',
            nonce: sspu_ajax.nonce,
            query: query,
            type: type,
            date_range: dateRange,
            user_id: userId,
            status: status,
            page: currentPage,
            per_page: perPage,
            show_all: isDefault
        })
        .done(function(response) {
            if (response.success) {
                renderSearchResults(response.data);
                if (response.data.pagination) {
                    totalPages = response.data.pagination.total_pages;
                    renderPagination(response.data.pagination);
                }
            } else {
                $('#search-results').html('<p>Search failed. Please try again.</p>');
            }
        })
        .fail(function() {
            $('#search-results').html('<p>Search failed. Please try again.</p>');
        });
    }

    function renderSearchResults(results) {
        let html = '';

        if (results.products && results.products.length > 0) {
            html += '<h3>Products</h3>';
            html += '<table class="wp-list-table widefat striped">';
            html += '<thead><tr><th>Product</th><th>User</th><th>Date</th><th>Status</th><th>Shopify Admin</th><th>Live URL</th></tr></thead>';
            html += '<tbody>';

            results.products.forEach(function(product) {
                const statusClass = product.status === 'success' ? 'success' : 'error';
                const shopifyAdminLink = product.shopify_product_id ?
                    `<a href="https://admin.shopify.com/store/${getStoreName()}/products/${product.shopify_product_id}" target="_blank">View in Admin</a>` :
                    'N/A';
                
                // Generate live URL
                const liveUrl = product.shopify_handle ? 
                    `<a href="https://qstomize.com/products/${product.shopify_handle}" target="_blank">View Live</a>` :
                    'N/A';

                html += `<tr>
                    <td><strong>${escapeHtml(product.product_title)}</strong></td>
                    <td>${escapeHtml(product.display_name)}</td>
                    <td>${formatDate(product.upload_timestamp)}</td>
                    <td><span class="status-badge ${statusClass}">${product.status}</span></td>
                    <td>${shopifyAdminLink}</td>
                    <td>${liveUrl}</td>
                </tr>`;
            });

            html += '</tbody></table>';
        }

        if (results.collections && results.collections.length > 0) {
            html += '<h3>Collections</h3>';
            html += '<table class="wp-list-table widefat striped">';
            html += '<thead><tr><th>Collection</th><th>Handle</th><th>Products Count</th><th>Shopify ID</th></tr></thead>';
            html += '<tbody>';

            results.collections.forEach(function(collection) {
                html += `<tr>
                    <td><strong>${escapeHtml(collection.title)}</strong></td>
                    <td>${escapeHtml(collection.handle)}</td>
                    <td>${collection.products_count || 0}</td>
                    <td><a href="https://admin.shopify.com/store/${getStoreName()}/collections/${collection.id}" target="_blank">${collection.id}</a></td>
                </tr>`;
            });

            html += '</tbody></table>';
        }

        if (results.variants && results.variants.length > 0) {
            html += '<h3>Recent Activity</h3>';
            html += '<table class="wp-list-table widefat striped">';
            html += '<thead><tr><th>Activity</th><th>User</th><th>Date</th><th>Details</th></tr></thead>';
            html += '<tbody>';

            results.variants.forEach(function(activity) {
                const metadata = JSON.parse(activity.metadata || '{}');
                html += `<tr>
                    <td><strong>${escapeHtml(formatActionName(activity.action))}</strong></td>
                    <td>${escapeHtml(activity.display_name)}</td>
                    <td>${formatDate(activity.timestamp)}</td>
                    <td>${escapeHtml(metadata.product_title || 'N/A')}</td>
                </tr>`;
            });

            html += '</tbody></table>';
        }

        if (!html) {
            html = '<p>No results found.</p>';
        }

        $('#search-results').html(html);
    }

    function renderPagination(pagination) {
        if (pagination.total_pages <= 1) {
            $('.pagination-controls').empty();
            return;
        }

        let html = '<div class="tablenav-pages">';
        html += `<span class="displaying-num">${pagination.total_items} items</span>`;
        html += '<span class="pagination-links">';

        // First page
        if (currentPage > 1) {
            html += `<a class="first-page button pagination-link" href="#" data-page="1"><span aria-hidden="true">«</span></a>`;
            html += `<a class="prev-page button pagination-link" href="#" data-page="${currentPage - 1}"><span aria-hidden="true">‹</span></a>`;
        } else {
            html += `<span class="tablenav-pages-navspan button disabled" aria-hidden="true">«</span>`;
            html += `<span class="tablenav-pages-navspan button disabled" aria-hidden="true">‹</span>`;
        }

        html += `<span class="screen-reader-text">Current Page</span>`;
        html += `<span class="paging-input">`;
        html += `<span class="tablenav-paging-text">${currentPage} of <span class="total-pages">${pagination.total_pages}</span></span>`;
        html += `</span>`;

        // Next/Last page
        if (currentPage < pagination.total_pages) {
            html += `<a class="next-page button pagination-link" href="#" data-page="${currentPage + 1}"><span aria-hidden="true">›</span></a>`;
            html += `<a class="last-page button pagination-link" href="#" data-page="${pagination.total_pages}"><span aria-hidden="true">»</span></a>`;
        } else {
            html += `<span class="tablenav-pages-navspan button disabled" aria-hidden="true">›</span>`;
            html += `<span class="tablenav-pages-navspan button disabled" aria-hidden="true">»</span>`;
        }

        html += '</span></div>';

        $('.pagination-controls').html(html);
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
    }

    function getStoreName() {
        // This should be passed from PHP or retrieved from settings
        return sspu_store_name && sspu_store_name.name ? sspu_store_name.name : 'your-store';
    }

    // Add a utility function for export functionality
    $('#export-analytics-btn').on('click', function() {
        const period = $('#analytics-period').val() || 30;

        $.post(ajaxurl, {
            action: 'sspu_export_analytics',
            nonce: sspu_ajax.nonce,
            period: period,
            format: 'csv' // or 'json' if implemented
        })
        .done(function(response) {
            if (response.success && response.data.download_url) {
                window.location.href = response.data.download_url;
            } else {
                alert('Failed to generate export. Please try again.');
            }
        })
        .fail(function() {
            alert('Export request failed. Please try again.');
        });
    });

})(jQuery);