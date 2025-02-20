/**
 * Admin JavaScript for Mesmeric Commerce
 *
 * @package    Mesmeric_Commerce
 * @subpackage Mesmeric_Commerce/admin/js
 */

(function ($) {
    'use strict';

    const MesmericCommerceAdmin = {
        /**
         * Initialize the admin interface
         */
        init: function () {
            this.bindEvents();
            this.initTooltips();
            this.initTabs();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function () {
            $('.clear-logs').on('click', this.handleClearLogs);
            $('.mc-admin-tabs .tab').on('click', this.handleTabClick);
            $('form').on('submit', this.handleFormSubmit);
        },

        /**
         * Initialize tooltips
         */
        initTooltips: function () {
            // DaisyUI tooltips are CSS-based, no initialization needed
        },

        /**
         * Initialize tabs
         */
        initTabs: function () {
            const hash = window.location.hash;
            if (hash) {
                this.switchTab(hash.substring(1));
            }
        },

        /**
         * Handle tab clicks
         * @param {Event} e Click event
         */
        handleTabClick: function (e) {
            e.preventDefault();
            const tab = $(this).data('tab');
            MesmericCommerceAdmin.switchTab(tab);
            window.location.hash = tab;
        },

        /**
         * Switch active tab
         * @param {string} tabId Tab identifier
         */
        switchTab: function (tabId) {
            $('.tab-content').hide();
            $(`#${tabId}`).show();
            $('.mc-admin-tabs .tab').removeClass('tab-active');
            $(`.mc-admin-tabs .tab[data-tab="${tabId}"]`).addClass('tab-active');
        },

        /**
         * Handle form submissions
         * @param {Event} e Submit event
         */
        handleFormSubmit: function (e) {
            const $form = $(this);
            const $submitButton = $form.find('[type="submit"]');

            $submitButton.addClass('loading').prop('disabled', true);

            // Add loading state
            $submitButton.html('<span class="loading loading-spinner"></span> ' +
                wp.i18n.__('Saving...', 'mesmeric-commerce'));

            // Form will submit normally, this just handles the UI
            setTimeout(() => {
                $submitButton.removeClass('loading').prop('disabled', false);
            }, 2000);
        },

        /**
         * Handle clearing logs
         * @param {Event} e Click event
         */
        handleClearLogs: function (e) {
            e.preventDefault();
            const $button = $(this);

            if (confirm(wp.i18n.__('Are you sure you want to clear all logs?', 'mesmeric-commerce'))) {
                $button.addClass('loading').prop('disabled', true);

                wp.ajax.post('mc_clear_logs', {
                    nonce: mcAdminData.nonce
                }).done(function (response) {
                    // Show success alert
                    const alert = $('<div class="alert alert-success mb-4">' +
                        '<svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">' +
                        '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />' +
                        '</svg>' +
                        '<span>' + wp.i18n.__('Logs cleared successfully.', 'mesmeric-commerce') + '</span>' +
                        '</div>');

                    $('.card-body').prepend(alert);
                    setTimeout(() => alert.fadeOut(), 3000);

                    // Clear the logs table
                    $('.table tbody').empty();
                }).fail(function (response) {
                    // Show error alert
                    const alert = $('<div class="alert alert-error mb-4">' +
                        '<svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">' +
                        '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />' +
                        '</svg>' +
                        '<span>' + wp.i18n.__('Error clearing logs. Please try again.', 'mesmeric-commerce') + '</span>' +
                        '</div>');

                    $('.card-body').prepend(alert);
                    setTimeout(() => alert.fadeOut(), 3000);
                }).always(function () {
                    $button.removeClass('loading').prop('disabled', false);
                });
            }
        },

        /**
         * Show a toast notification
         * @param {string} message Message to display
         * @param {string} type Notification type (success, error, warning, info)
         */
        showToast: function (message, type = 'info') {
            const toast = $('<div class="toast toast-end">' +
                `<div class="alert alert-${type}">` +
                '<span>' + message + '</span>' +
                '</div>' +
                '</div>');

            $('body').append(toast);
            setTimeout(() => toast.fadeOut(() => toast.remove()), 3000);
        },

        /**
         * Update module status
         * @param {string} moduleId Module identifier
         * @param {boolean} enabled Whether to enable or disable
         */
        updateModuleStatus: function (moduleId, enabled) {
            const $card = $(`#module-${moduleId}`);
            const $badge = $card.find('.badge');
            const $button = $card.find('.btn');

            $button.addClass('loading').prop('disabled', true);

            wp.ajax.post('mc_update_module_status', {
                nonce: mcAdminData.nonce,
                module: moduleId,
                enabled: enabled
            }).done(function (response) {
                $badge.text(enabled ?
                    wp.i18n.__('Active', 'mesmeric-commerce') :
                    wp.i18n.__('Inactive', 'mesmeric-commerce')
                );

                MesmericCommerceAdmin.showToast(
                    wp.i18n.__('Module status updated successfully.', 'mesmeric-commerce'),
                    'success'
                );
            }).fail(function (response) {
                MesmericCommerceAdmin.showToast(
                    wp.i18n.__('Error updating module status.', 'mesmeric-commerce'),
                    'error'
                );
            }).always(function () {
                $button.removeClass('loading').prop('disabled', false);
            });
        }
    };

    // Initialize when document is ready
    $(document).ready(function () {
        MesmericCommerceAdmin.init();
    });

})(jQuery);
