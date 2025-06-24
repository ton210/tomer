(function($) {
    'use strict';

    let queueData = [];

    $(document).ready(function() {
        if ($('.alibaba-queue-container').length) {
            initializeQueueManagement();
            loadQueueData();
        }
    });

    function initializeQueueManagement() {
        // Add URLs button
        $('#add-urls-btn').on('click', function() {
            saveUrls('replace');
        });

        // Append URLs button
        $('#append-urls-btn').on('click', function() {
            saveUrls('append');
        });

        // Refresh queue
        $('#refresh-queue-btn').on('click', function() {
            loadQueueData();
        });

        // Clear completed
        $('#clear-completed-btn').on('click', function() {
            if (confirm('Are you sure you want to clear all completed URLs?')) {
                clearUrls('completed');
            }
        });

        // Clear unassigned
        $('#clear-unassigned-btn').on('click', function() {
            if (confirm('Are you sure you want to clear all unassigned URLs?')) {
                clearUrls('unassigned');
            }
        });

        // Clear all
        $('#clear-all-btn').on('click', function() {
            if (confirm('WARNING: This will clear ALL URLs from the queue. Are you sure?')) {
                clearUrls('all');
            }
        });

        // Auto-refresh every 30 seconds
        setInterval(function() {
            loadQueueData();
        }, 30000);
    }

    function saveUrls(actionType) {
        const urls = $('#alibaba-urls-input').val().trim();
        
        if (!urls) {
            alert('Please enter at least one URL');
            return;
        }

        const $spinner = $('.url-actions .spinner');
        const $buttons = $('.url-actions button');

        $buttons.prop('disabled', true);
        $spinner.addClass('is-active');

        $.ajax({
            url: sspu_ajax.ajaxurl,
            type: 'POST',
            data: {
                action: 'sspu_save_alibaba_urls',
                nonce: sspu_ajax.nonce,
                urls: urls,
                action_type: actionType
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showNotification(response.data.message, 'success');
                    $('#alibaba-urls-input').val('');
                    updateStats(response.data.stats);
                    loadQueueData();
                } else {
                    showNotification('Error: ' + response.data.message, 'error');
                }
            },
            error: function(xhr) {
                showNotification('Failed to save URLs. Please try again.', 'error');
                console.error('Save URLs error:', xhr.responseText);
            },
            complete: function() {
                $buttons.prop('disabled', false);
                $spinner.removeClass('is-active');
            }
        });
    }

    function loadQueueData() {
        $.ajax({
            url: sspu_ajax.ajaxurl,
            type: 'POST',
            data: {
                action: 'sspu_get_alibaba_urls',
                nonce: sspu_ajax.nonce
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    queueData = response.data.urls;
                    updateStats(response.data.stats);
                    renderQueueList();
                } else {
                    showNotification('Error loading queue data', 'error');
                }
            },
            error: function(xhr) {
                console.error('Load queue error:', xhr.responseText);
            }
        });
    }

    function clearUrls(clearType) {
        $.ajax({
            url: sspu_ajax.ajaxurl,
            type: 'POST',
            data: {
                action: 'sspu_clear_alibaba_urls',
                nonce: sspu_ajax.nonce,
                clear_type: clearType
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showNotification(response.data.message, 'success');
                    updateStats(response.data.stats);
                    loadQueueData();
                } else {
                    showNotification('Error: ' + response.data.message, 'error');
                }
            },
            error: function(xhr) {
                showNotification('Failed to clear URLs. Please try again.', 'error');
                console.error('Clear URLs error:', xhr.responseText);
            }
        });
    }

    function updateStats(stats) {
        $('#stat-total').text(stats.total || 0);
        $('#stat-available').text(stats.available || 0);
        $('#stat-assigned').text(stats.assigned || 0);
        $('#stat-completed').text(stats.completed || 0);
    }

    function renderQueueList() {
        const $container = $('#queue-list-container');
        
        if (queueData.length === 0) {
            $container.html('<p>No URLs in queue.</p>');
            return;
        }

        let html = '<table class="wp-list-table widefat striped">';
        html += '<thead><tr>';
        html += '<th>URL</th>';
        html += '<th>Status</th>';
        html += '<th>Created</th>';
        html += '<th>Assigned To</th>';
        html += '<th>Assigned At</th>';
        html += '</tr></thead>';
        html += '<tbody>';

        queueData.forEach(function(item) {
            const statusClass = item.status === 'available' ? 'status-available' : 
                               item.status === 'assigned' ? 'status-assigned' : 'status-completed';
            
            html += '<tr>';
            html += '<td><a href="' + escapeHtml(item.url) + '" target="_blank">' + truncateUrl(item.url) + '</a></td>';
            html += '<td><span class="status-badge ' + statusClass + '">' + item.status + '</span></td>';
            html += '<td>' + formatDate(item.created_at) + '</td>';
            html += '<td>' + (item.assigned_user_name || '-') + '</td>';
            html += '<td>' + (item.assigned_at ? formatDate(item.assigned_at) : '-') + '</td>';
            html += '</tr>';
        });

        html += '</tbody></table>';
        $container.html(html);
    }

    function truncateUrl(url) {
        if (url.length > 60) {
            return url.substring(0, 60) + '...';
        }
        return url;
    }

    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function showNotification(message, type = 'info') {
        // Remove any existing notifications
        $('.sspu-notification').remove();

        const $notification = $(`
            <div class="sspu-notification notice notice-${type} is-dismissible">
                <p>${message}</p>
            </div>
        `);

        $('.alibaba-queue-container').prepend($notification);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            $notification.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);

        // Make dismissible
        $notification.on('click', '.notice-dismiss', function() {
            $notification.fadeOut(function() {
                $(this).remove();
            });
        });
    }

})(jQuery);