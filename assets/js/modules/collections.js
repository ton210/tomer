/**
 * SSPU Collections Module
 *
 * Handles fetching, filtering, selecting, and creating Shopify collections.
 */
window.SSPU = window.SSPU || {};

(function($, APP) {
    'use strict';

    APP.collections = {
        /**
         * Initializes the module by caching the select element, binding events, and loading initial data.
         */
        init() {
            APP.cache.collectionSelect = $('#sspu-collection-select');
            if (APP.cache.collectionSelect.length === 0) return; // Don't run if element doesn't exist
            
            this.bindEvents();
            this.load();
        },

        /**
         * Binds all event listeners for the collections section.
         */
        bindEvents() {
            $('#sspu-collection-search').on('input', e => this.filter(e.target.value.toLowerCase()));
            APP.cache.collectionSelect.on('change', () => this.updateCount());
            $('#sspu-select-all-collections').on('click', () => this.selectAll());
            $('#sspu-clear-collections').on('click', () => this.clear());
            $('#sspu-refresh-collections').on('click', () => this.refresh());
            $('#sspu-create-collection').on('click', () => $('#sspu-new-collection').slideDown());
            $('#sspu-cancel-collection').on('click', () => this.cancelCreate());
            $('#sspu-save-collection').on('click', () => this.create());
        },

        /**
         * Filters the collection options based on a search term.
         * @param {string} term - The search term.
         */
        filter(term) {
            APP.cache.collectionSelect.find('option').each(function() {
                const $option = $(this);
                const isMatch = !term || $option.text().toLowerCase().includes(term);
                $option.toggle(isMatch);
            });
        },

        /**
         * Updates the display count of selected collections.
         */
        updateCount() {
            const count = APP.cache.collectionSelect.val()?.length || 0;
            $('#selected-collections-count').text(count);
        },

        /**
         * Selects all currently visible collections in the list.
         */
        selectAll() {
            APP.cache.collectionSelect.find('option:visible').prop('selected', true);
            APP.cache.collectionSelect.trigger('change');
            APP.utils.notify(`${APP.cache.collectionSelect.val()?.length || 0} collections selected.`, 'info');
        },

        /**
         * Clears the current collection selection.
         */
        clear() {
            APP.cache.collectionSelect.val([]).trigger('change');
            APP.utils.notify('Collection selection cleared.', 'info');
        },

        /**
         * Refreshes the collection list from the server.
         */
        refresh() {
            const $btn = $('#sspu-refresh-collections').prop('disabled', true).addClass('loading');
            $('#sspu-collection-search').val('');
            this.load(() => {
                $btn.prop('disabled', false).removeClass('loading');
                APP.utils.notify('Collections refreshed successfully!', 'success');
            });
        },

        /**
         * Loads collections from the server via AJAX and populates the select element.
         * @param {Function} [callback] - An optional callback to run after loading.
         */
        load(callback) {
            const currentSelections = APP.cache.collectionSelect.val() || [];
            APP.cache.collectionSelect.prop('disabled', true).html('<option value="" disabled>Loading collections...</option>');

            APP.utils.ajax('get_collections').done(response => {
                if (response.success) {
                    APP.cache.collectionSelect.empty();
                    response.data.forEach(col => {
                        const selected = currentSelections.includes(col.id.toString());
                        const type = col.hasOwnProperty('rules') && col.rules.length > 0 ? ' (Smart)' : '';
                        APP.cache.collectionSelect.append(`<option value="${col.id}" ${selected ? 'selected' : ''}>${col.title}${type}</option>`);
                    });
                    this.updateCount();
                    if (callback) callback();
                } else {
                    APP.utils.notify('Failed to load collections.', 'error');
                }
            }).always(() => APP.cache.collectionSelect.prop('disabled', false));
        },

        /**
         * Creates a new collection via an AJAX request.
         */
        create() {
            const name = $('#sspu-new-collection-name').val().trim();
            if (!name) {
                APP.utils.notify('Please enter a collection name.', 'warning');
                return;
            }

            const $btn = $('#sspu-save-collection').prop('disabled', true).addClass('loading');

            APP.utils.ajax('create_collection', { collection_name: name }).done(response => {
                if (response.success && response.data) {
                    const col = response.data;
                    const newOption = `<option value="${col.id}" selected>${col.title}</option>`;
                    APP.cache.collectionSelect.append(newOption);
                    const current = APP.cache.collectionSelect.val() || [];
                    if (!current.includes(col.id.toString())) {
                         current.push(col.id.toString());
                    }
                    APP.cache.collectionSelect.val(current).trigger('change');
                    this.cancelCreate();
                    APP.utils.notify('Collection created and selected!', 'success');
                } else {
                    APP.utils.notify('Error: ' + (response.data.message || 'Could not create collection.'), 'error');
                }
            }).always(() => $btn.prop('disabled', false).removeClass('loading'));
        },

        /**
         * Hides the new collection input form.
         */
        cancelCreate() {
            $('#sspu-new-collection').slideUp();
            $('#sspu-new-collection-name').val('');
        }
    };

})(jQuery, window.SSPU);
