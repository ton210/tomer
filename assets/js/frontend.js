(function($) {
    'use strict';

    const SSPU_Frontend = {
        init: function() {
            // Login form handler
            this.handleLoginForm();

            // Dashboard handler
            if ($('#sspu-frontend-app').length) {
                this.initDashboard();
            }
        },

        handleLoginForm: function() {
            const $loginForm = $('#sspu-login-form');
            if (!$loginForm.length) return;

            $loginForm.on('submit', function(e) {
                e.preventDefault();
                const $submitBtn = $('#sspu-login-submit');
                const $status = $('#sspu-login-status');
                $submitBtn.prop('disabled', true).text('Logging In...');
                $status.text('').removeClass('error success');

                $.ajax({
                    type: 'POST',
                    url: sspu_frontend_ajax.ajax_url,
                    data: {
                        action: 'sspu_frontend_login',
                        nonce: sspu_frontend_ajax.nonce,
                        log: $('#sspu-user-login').val(),
                        pwd: $('#sspu-user-pass').val(),
                    },
                    success: function(response) {
                        if (response.success) {
                            $status.text(response.data.message).addClass('success');
                            window.location.reload();
                        } else {
                            $status.text(response.data.message).addClass('error');
                            $submitBtn.prop('disabled', false).text('Log In');
                        }
                    },
                    error: function() {
                        $status.text('An unknown error occurred.').addClass('error');
                        $submitBtn.prop('disabled', false).text('Log In');
                    }
                });
            });
        },

        initDashboard: function() {
            this.router(); // Handle routing on initial load and hash changes
            $(window).on('hashchange', this.router);

            // Handle navigation clicks
            $('.sspu-nav-item').on('click', function(e) {
                e.preventDefault();
                const hash = $(this).attr('href');
                if (window.location.hash !== hash) {
                    window.location.hash = hash;
                }
            });
        },

        router: function() {
            const hash = window.location.hash || '#leaderboard';
            const $activeLink = $(`.sspu-nav-item[href="${hash}"]`);

            if (!$activeLink.length) return;

            $('.sspu-nav-item').removeClass('active');
            $activeLink.addClass('active');

            const partial = $activeLink.data('partial');
            SSPU_Frontend.loadPartial(partial);
        },

        loadPartial: function(partial) {
            const $contentArea = $('#sspu-main-content');
            $contentArea.addClass('loading');

            $.ajax({
                type: 'POST',
                url: sspu_frontend_ajax.ajax_url,
                data: {
                    action: 'sspu_get_dashboard_partial',
                    nonce: sspu_frontend_ajax.nonce,
                    partial: partial,
                },
                success: function(response) {
                    if (response.success) {
                        $contentArea.html(response.data.html);
                        // We need to re-initialize scripts for the loaded content
                        SSPU_Frontend.reinitScriptsForPartial(partial);
                    } else {
                        $contentArea.html(`<div class="sspu-error"><p>${response.data.message}</p></div>`);
                    }
                },
                error: function() {
                    $contentArea.html('<div class="sspu-error"><p>Failed to load content.</p></div>');
                },
                complete: function() {
                    $contentArea.removeClass('loading');
                }
            });
        },

        // This is a crucial step. After loading HTML via AJAX, the JS for that HTML needs to be re-initialized.
        reinitScriptsForPartial: function(partial) {
            console.log(`Re-initializing scripts for: ${partial}`);
            // This is a simplified example. In a real application, you'd call the `init`
            // method of the corresponding JS module for the loaded partial.
            if (partial === 'leaderboard-page' && typeof initializeAnalytics === 'function') {
                // Assuming leaderboard uses the analytics script
                initializeAnalytics();
            } else if (partial === 'uploader-page' && window.SSPU && SSPU.form) {
                // Re-init all modules needed for the uploader
                SSPU.collections.init();
                SSPU.variants.init();
                SSPU.ai.init();
                SSPU.form.init();
            } else if (partial === 'live-editor-page' && window.LiveEditor) {
                LiveEditor.init();
            }
            // Add other initializations here for search, analytics, queue, etc.
        }
    };

    $(document).ready(function() {
        SSPU_Frontend.init();
    });

})(jQuery);