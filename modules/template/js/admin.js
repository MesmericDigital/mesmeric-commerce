/**
 * Template Module - Admin JavaScript
 *
 * @package    Mesmeric_Commerce
 * @subpackage Mesmeric_Commerce/modules/template/js
 */

(function ($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function () {
        initTemplateAdmin();
    });

    /**
     * Initialize the template admin functionality
     */
    function initTemplateAdmin() {
        // Check if we're on the right admin page
        if (!$('.mc-template-admin').length) {
            return;
        }

        // Example: Initialize form submission
        initFormSubmission();

        // Log initialization
        console.log('Template admin initialized');
    }

    /**
     * Initialize form submission
     */
    function initFormSubmission() {
        const $form = $('.mc-template-admin__form');

        if (!$form.length) {
            return;
        }

        $form.on('submit', function (e) {
            e.preventDefault();

            const formData = $(this).serialize();

            // Example: Send AJAX request
            $.ajax({
                url: mcTemplateAdminData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mc_template_save_settings',
                    nonce: mcTemplateAdminData.nonce,
                    formData: formData
                },
                beforeSend: function () {
                    // Show loading indicator
                    $('.mc-template-admin__loading').show();
                },
                success: function (response) {
                    if (response.success) {
                        // Show success message
                        $('.mc-template-admin__message')
                            .removeClass('error')
                            .addClass('updated')
                            .text(response.data.message)
                            .show();
                    } else {
                        // Show error message
                        $('.mc-template-admin__message')
                            .removeClass('updated')
                            .addClass('error')
                            .text(response.data.message)
                            .show();
                    }
                },
                error: function (xhr, status, error) {
                    // Show error message
                    $('.mc-template-admin__message')
                        .removeClass('updated')
                        .addClass('error')
                        .text('An error occurred: ' + error)
                        .show();
                },
                complete: function () {
                    // Hide loading indicator
                    $('.mc-template-admin__loading').hide();

                    // Hide message after 3 seconds
                    setTimeout(function () {
                        $('.mc-template-admin__message').fadeOut();
                    }, 3000);
                }
            });
        });
    }
})(jQuery);
