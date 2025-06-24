(function($) {
    'use strict';
    
    // Cache DOM elements
    const $urlInput = $('#alibaba-urls-input');
    const $addBtn = $('#add-urls-btn');
    const $refreshBtn = $('#refresh-queue-btn');
    const $clearCompletedBtn = $('#clear-completed-btn');
    const $clearUnassignedBtn = $('#clear-unassigned-btn');
    const $clearAllBtn = $('#clear-all-btn');
    const $queueList = $('#queue-list-container');
    const $spinner = $('.spinner');
    
    // Statistics elements
    const $statTotal = $('#stat-total');
    const $statAvailable = $('#stat-available');
    const $statAssigned = $('#stat-assigned');
    const $statCompleted = $('#stat-completed');
    
    // Initialize
    $(document).ready(function() {
        loadQueue();
        bindEvents();
    });
    
    function bindEvents() {
        // Only one button now, which always appends
        $addBtn.on('click', function() {
            addUrls();
        });
        
        $refreshBtn.on('click', loadQueue);
        $clearCompletedBtn.on('click', clearCompleted);
        $clearUnassignedBtn.on('click', clearUnassigned);
        $clearAllBtn.on('click', clearAll);
        
        // Delegated event for queue item actions
        $queueList.on('click', '.queue-action-btn', handleQueueAction);
    }
    
    function addUrls() {
        const urls = $urlInput.val().trim();
        console.log('[SSPU QUEUE DEBUG] Raw URLs from textarea:', urls);
        
        if (!urls) {
            alert('Please enter at least one URL');
            return;
        }
        
        // Split by newlines and filter empty lines
        const urlArray = urls.split('\n').filter(url => url.trim());
        
        if (urlArray.length === 0) {
            alert('No valid URLs to add');
            return;
        }
        
        showLoading();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'sspu_save_alibaba_urls',
                urls: urls,
                nonce: sspu_ajax.nonce
            },
            success: function(response) {
                hideLoading();
                
                if (response.success) {
                    $urlInput.val('');
                    showNotification(response.data.message, 'success');
                    loadQueue();
                } else {
                    showNotification(response.data.message || 'Failed to add URLs', 'error');
                }
            },
            error: function() {
                hideLoading();
                showNotification('An error occurred while adding URLs', 'error');
            }
        });
    }
    
    function loadQueue() {
        showLoading();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'sspu_get_alibaba_urls',
                nonce: sspu_ajax.nonce
            },
            success: function(response) {
                hideLoading();
                
                if (response.success && response.data.urls) {
                    updateStats(response.data.stats);
                    renderQueue(response.data.urls);
                } else {
                    $queueList.html('<p>Failed to load queue</p>');
                }
            },
            error: function() {
                hideLoading();
                $queueList.html('<p>Error loading queue</p>');
            }
        });
    }
    
    function updateStats(stats) {
        $statTotal.text(stats.total || 0);
        $statAvailable.text(stats.available || 0);
        $statAssigned.text(stats.assigned || 0);
        $statCompleted.text(stats.completed || 0);
    }
    
    function renderQueue(queue) {
        if (!queue || queue.length === 0) {
            $queueList.html('<p>No URLs in queue</p>');
            return;
        }
        
        let html = '<table class="wp-list-table widefat striped">';
        html += '<thead><tr>';
        html += '<th style="width: 50px;">ID</th>';
        html += '<th>URL</th>';
        html += '<th style="width: 120px;">Status</th>';
        html += '<th style="width: 150px;">Assigned To</th>';
        html += '<th style="width: 180px;">Date</th>';
        html += '<th style="width: 150px;">Actions</th>';
        html += '</tr></thead><tbody>';
        
        queue.forEach(function(item) {
            const statusClass = 'status-' + item.status;
            const statusLabel = item.status.charAt(0).toUpperCase() + item.status.slice(1);
            
            html += '<tr>';
            html += '<td>' + item.queue_id + '</td>';
            html += '<td><a href="' + item.url + '" target="_blank">' + truncateUrl(item.url) + '</a></td>';
            html += '<td><span class="' + statusClass + '">' + statusLabel + '</span></td>';
            html += '<td>' + (item.assigned_user_name || '-') + '</td>';
            html += '<td>' + formatDate(item.created_at) + '</td>';
            html += '<td>';
            
            if (item.status === 'available') {
                html += '<button class="button button-small queue-action-btn" data-action="assign" data-id="' + item.queue_id + '">Assign to Me</button> ';
                html += '<button class="button button-small button-link-delete queue-action-btn" data-action="delete" data-id="' + item.queue_id + '">Delete</button>';
            } else if (item.status === 'assigned') {
                html += '<button class="button button-small queue-action-btn" data-action="release" data-id="' + item.queue_id + '">Release</button> ';
                html += '<button class="button button-small queue-action-btn" data-action="complete" data-id="' + item.queue_id + '">Complete</button>';
            } else if (item.status === 'completed') {
                html += '<button class="button button-small button-link-delete queue-action-btn" data-action="delete" data-id="' + item.queue_id + '">Delete</button>';
            }
            
            html += '</td></tr>';
        });
        
        html += '</tbody></table>';
        
        $queueList.html(html);
    }
    
    function handleQueueAction(e) {
        e.preventDefault();
        
        const $btn = $(this);
        const action = $btn.data('action');
        const queueId = $btn.data('id');
        
        if (action === 'delete' && !confirm('Are you sure you want to delete this URL?')) {
            return;
        }
        
        $btn.prop('disabled', true);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'sspu_manage_alibaba_queue',
                queue_action: action,
                queue_id: queueId,
                nonce: sspu_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotification(response.data.message, 'success');
                    loadQueue();
                } else {
                    showNotification(response.data.message || 'Action failed', 'error');
                    $btn.prop('disabled', false);
                }
            },
            error: function() {
                showNotification('An error occurred', 'error');
                $btn.prop('disabled', false);
            }
        });
    }
    
    function clearCompleted() {
        if (!confirm('Are you sure you want to clear all completed URLs?')) {
            return;
        }
        
        performBulkAction('completed');
    }
    
    function clearUnassigned() {
        if (!confirm('Are you sure you want to clear all unassigned URLs?')) {
            return;
        }
        
        performBulkAction('unassigned');
    }
    
    function clearAll() {
        if (!confirm('WARNING: This will delete ALL URLs from the queue. Are you sure?')) {
            return;
        }
        
        if (!confirm('This action cannot be undone. Are you absolutely sure?')) {
            return;
        }
        
        performBulkAction('all');
    }
    
    function performBulkAction(bulkAction) {
        showLoading();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'sspu_clear_alibaba_urls',
                clear_type: bulkAction,
                nonce: sspu_ajax.nonce
            },
            success: function(response) {
                hideLoading();
                
                if (response.success) {
                    showNotification(response.data.message, 'success');
                    loadQueue();
                } else {
                    showNotification(response.data.message || 'Action failed', 'error');
                }
            },
            error: function() {
                hideLoading();
                showNotification('An error occurred', 'error');
            }
        });
    }
    
    function truncateUrl(url, maxLength = 60) {
        if (url.length <= maxLength) {
            return url;
        }
        
        const start = url.substring(0, 30);
        const end = url.substring(url.length - 25);
        return start + '...' + end;
    }
    
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleString();
    }
    
    function showLoading() {
        $spinner.addClass('is-active');
        $('.button').prop('disabled', true);
    }
    
    function hideLoading() {
        $spinner.removeClass('is-active');
        $('.button').prop('disabled', false);
    }
    
    function showNotification(message, type = 'info') {
        const $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
        
        $('.wrap h1').after($notice);
        
        if (type === 'success') {
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        }
        
        $notice.on('click', '.notice-dismiss', function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        });
    }
    
})(jQuery);