/**
 * SSPU Utilities & Global State
 *
 * This file establishes the global SSPU object and populates it with
 * shared utilities, state management, and cached DOM element references
 * to be used across all other modules. It must be loaded first.
 */
window.SSPU = window.SSPU || {};

(function($, APP) {
    'use strict';

    // Set debug mode from localized data
    APP.DEBUG_MODE = sspu_ajax.debug_mode || false;

    // Global cache for frequently used jQuery objects
    APP.cache = {
        form: null,
        variantsWrapper: null,
        collectionSelect: null,
        $doc: $(document)
    };

    // Global state management object
    APP.state = {
        variantCounter: 0,
        autoSaveTimer: null,
        aiImageIds: [],
        isSubmitting: false,
        currentAlibabaUrl: null,
        copiedDesignMask: null
    };

    // Central utility functions
    APP.utils = {
        /**
         * Console logger that only runs in debug mode.
         * @param {string} msg - The message to log.
         * @param {*} [data=''] - Optional data to log alongside the message.
         */
        log: (msg, data = '') => {
            if (APP.DEBUG_MODE || window.location.hash === '#debug') {
                console.log('[SSPU] ' + msg, data);
            }
        },

        /**
         * Displays a dismissible admin notification.
         * @param {string} msg - The notification message. Supports newline characters.
         * @param {string} [type='info'] - The type of notice (info, success, warning, error).
         */
        notify: (msg, type = 'info') => {
            APP.utils.log('Notification: ' + type, msg);
            $('.sspu-notification').remove(); // Clear previous notifications
            
            const $notification = $(`
                <div class="sspu-notification notice notice-${type} is-dismissible">
                    <p>${msg.replace(/\n/g, '<br>')}</p>
                </div>
            `).prependTo('#sspu-uploader-wrapper').hide().slideDown(300);

            // Auto-dismiss for non-critical messages
            if (type === 'success' || type === 'info') {
                setTimeout(() => $notification.slideUp(300, () => $notification.remove()), 5000);
            }

            // Manual dismissal
            $notification.on('click', '.notice-dismiss', () => 
                $notification.slideUp(300, () => $notification.remove())
            );
        },

        /**
         * A wrapper for making standardized AJAX calls to the WordPress backend.
         * @param {string} action - The specific 'sspu_' action to call.
         * @param {Object} [data={}] - Additional data to send in the request.
         * @returns {jqXHR} - The jQuery AJAX promise.
         */
        ajax: (action, data = {}) => {
            return $.ajax({
                url: sspu_ajax.ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'sspu_' + action,
                    nonce: sspu_ajax.nonce,
                    ...data
                }
            });
        },

        /**
         * Safely gets content from a TinyMCE editor instance.
         * @param {string} id - The editor ID.
         * @returns {string} - The editor content.
         */
        getEditorContent: (id) => {
            if (typeof tinymce !== 'undefined' && tinymce.get(id) && !tinymce.get(id).isHidden()) {
                return tinymce.get(id).getContent();
            }
            return $(`#${id}`).val();
        },

        /**
         * Safely sets content for a TinyMCE editor instance.
         * @param {string} id - The editor ID.
         * @param {string} content - The content to set.
         */
        setEditorContent: (id, content) => {
            if (typeof tinymce !== 'undefined' && tinymce.get(id)) {
                tinymce.get(id).setContent(content);
            } else {
                $(`#${id}`).val(content);
            }
        }
    };

    // Global debug function for testing upload custom mask
    window.debugUploadCustomMask = function() {
        console.log('=== DEBUG UPLOAD CUSTOM MASK ===');
        console.log('APP object exists:', typeof APP !== 'undefined');
        console.log('APP.variants exists:', typeof APP.variants !== 'undefined');
        console.log('uploadCustomMask method exists:', typeof APP.variants.uploadCustomMask === 'function');
        console.log('wp object exists:', typeof wp !== 'undefined');
        console.log('wp.media exists:', typeof wp !== 'undefined' && typeof wp.media !== 'undefined');

        const $buttons = $('.upload-custom-mask');
        console.log('Upload custom mask buttons found:', $buttons.length);

        $buttons.each(function(index) {
            const $btn = $(this);
            console.log(`Button ${index}:`, {
                element: $btn[0],
                events: $._data($btn[0], 'events'),
                parent: $btn.parent()[0]
            });
        });

        console.log('=== END DEBUG ===');
    };

})(jQuery, window.SSPU);