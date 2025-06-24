/**
 * SSPU Main Application Entry Point
 *
 * This script initializes the entire Shopify Product Uploader application.
 * It ensures all dependencies are met and then calls the `init` method
 * on each individual module in the correct order.
 */
window.SSPU = window.SSPU || {};

(function($, APP) {
    'use strict';

    // This function runs once the entire DOM is ready.
    $(document).ready(function() {

        // Log initialization status for debugging purposes.
        APP.utils.log('Initializing SSPU App', {
            ajaxurl: sspu_ajax.ajaxurl,
            openai: sspu_ajax.openai_configured,
            shopify: sspu_ajax.shopify_configured
        });

        // Check for required Shopify configuration. If missing, disable submission and notify the user.
        if (!sspu_ajax.shopify_configured) {
            $('#sspu-submit-button').prop('disabled', true).attr('title', sspu_ajax.strings.no_shopify_creds);
            APP.utils.notify(sspu_ajax.strings.no_shopify_creds, 'warning');
        }

        // Only initialize the full application if the main uploader wrapper element exists on the page.
        if ($('#sspu-uploader-wrapper').length) {

            // --- Caching Global Elements ---
            // Cache frequently accessed elements to avoid repeated DOM queries.
            APP.cache.form = $('#sspu-product-form');
            APP.cache.variantsWrapper = $('#sspu-variants-wrapper');
            APP.cache.collectionSelect = $('#sspu-collection-select');

            // --- Module Initialization ---
            // Initialize each module.
            if (APP.alibaba) {
                APP.utils.log('Initializing Alibaba Module...');
                APP.alibaba.init();
            }
            if (APP.collections) {
                APP.utils.log('Initializing Collections Module...');
                APP.collections.init();
            }
            if (APP.variants) {
                APP.utils.log('Initializing Variants Module...');
                APP.variants.init();
            }
            if (APP.ai) {
                APP.utils.log('Initializing AI Module...');
                APP.ai.init();
            }
            if (APP.form) {
                APP.utils.log('Initializing Form Management Module...');
                APP.form.init();
            }

            APP.utils.log('SSPU Application Initialized Successfully.');
        }
    });

})(jQuery, window.SSPU);