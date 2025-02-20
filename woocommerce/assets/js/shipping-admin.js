/* global mcShippingAdmin */
(function ($) {
    'use strict';

    // Custom rates table functionality
    const customRatesTable = {
        init() {
            this.bindEvents();
            this.toggleCustomRatesTable();
        },

        bindEvents() {
            $('#enable_custom_rates').on('change', this.toggleCustomRatesTable);
            $('.add-rate').on('click', this.addRate);
            $('#mc-custom-rates').on('click', '.remove-rate', this.removeRate);
        },

        toggleCustomRatesTable() {
            const isEnabled = $('#enable_custom_rates').is(':checked');
            $('#custom-rates-table').toggleClass('hidden', !isEnabled);
        },

        addRate(e) {
            e.preventDefault();
            const template = $('#rate-row-template').html();
            $('#mc-custom-rates tbody').append(template);
        },

        removeRate(e) {
            e.preventDefault();
            $(this).closest('tr').remove();
        }
    };

    // Shipping label generation functionality
    const shippingLabels = {
        init() {
            this.bindEvents();
        },

        bindEvents() {
            $('.generate-label').on('click', this.generateLabel);
            $('.track-shipment').on('click', this.trackShipment);
        },

        generateLabel(e) {
            e.preventDefault();
            const $button = $(this);
            const orderId = $button.data('order-id');
            const $status = $('.mc-shipping-status');

            $button.prop('disabled', true);
            $status.html('<p class="loading">' + wp.i18n.__('Generating shipping label...', 'mesmeric-commerce') + '</p>');

            wp.apiFetch({
                path: '/wp/v2/mesmeric-commerce/shipping/generate-label',
                method: 'POST',
                data: {
                    order_id: orderId,
                    nonce: mcShippingAdmin.nonce
                }
            }).then(response => {
                if (response.success) {
                    $status.html('<p class="success">' + response.message + '</p>');
                    if (response.tracking_number) {
                        $('#mc_tracking_number').val(response.tracking_number);
                    }
                    if (response.label_url) {
                        window.open(response.label_url, '_blank');
                    }
                } else {
                    $status.html('<p class="error">' + response.message + '</p>');
                }
            }).catch(error => {
                $status.html('<p class="error">' + error.message + '</p>');
            }).finally(() => {
                $button.prop('disabled', false);
            });
        },

        trackShipment(e) {
            e.preventDefault();
            const $link = $(this);
            const trackingNumber = $link.data('tracking');
            const $status = $('.mc-shipping-status');

            $link.prop('disabled', true);
            $status.html('<p class="loading">' + wp.i18n.__('Fetching tracking information...', 'mesmeric-commerce') + '</p>');

            wp.apiFetch({
                path: '/wp/v2/mesmeric-commerce/shipping/track',
                method: 'POST',
                data: {
                    tracking_number: trackingNumber,
                    nonce: mcShippingAdmin.nonce
                }
            }).then(response => {
                if (response.success) {
                    let trackingHtml = '<div class="tracking-info">';
                    trackingHtml += '<h4>' + wp.i18n.__('Tracking Information', 'mesmeric-commerce') + '</h4>';
                    trackingHtml += '<ul>';
                    response.tracking_info.forEach(info => {
                        trackingHtml += '<li>';
                        trackingHtml += '<strong>' + info.date + '</strong>: ';
                        trackingHtml += info.status;
                        if (info.location) {
                            trackingHtml += ' - ' + info.location;
                        }
                        trackingHtml += '</li>';
                    });
                    trackingHtml += '</ul></div>';
                    $status.html(trackingHtml);
                } else {
                    $status.html('<p class="error">' + response.message + '</p>');
                }
            }).catch(error => {
                $status.html('<p class="error">' + error.message + '</p>');
            }).finally(() => {
                $link.prop('disabled', false);
            });
        }
    };

    // Initialize when document is ready
    $(document).ready(() => {
        customRatesTable.init();
        shippingLabels.init();
    });

})(jQuery);
