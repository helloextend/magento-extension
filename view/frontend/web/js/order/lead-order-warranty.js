/**
 * Extend Warranty - Order item widget (Create Lead Order)
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Extend_Warranty
 * @copyright   Copyright (c) 2022 Extend Inc. (https://www.extend.com/)
 */
define([
    'jquery',
    'underscore',
    'mage/translate',
    'Magento_Ui/js/modal/alert',
    'Magento_Customer/js/customer-data',
    'Extend_Warranty/js/tracking/actions',
    'extendWarrantyOffers',
    'domReady!'
], function ($, _, $t, alert, customerData, trackActions) {
    'use strict';

    $.widget('mage.leadOrderWarranty', $.mage.extendWarrantyOffers, {
        options: {
            productSku: null,
            qty: 0,
            leadToken: '',
            addLeadUrl: null,
            addLeadEvent: null,
            buttonEnabled: true,
            trackingEnabled: true,
            isWarrantyInLaterOrders: false,
            isWarrantyInQuote: false,
        },

        /**
         * Order item warranty offers creation
         * @protected
         */
        _create: function () {
            if (!this.options.isWarrantyInLaterOrders) {
                this.renderSimpleButton(this._addToCart.bind(this));
                var $element = $(this.element.get(0));
                this._addProductSku($element);

                if (this.options.isWarrantyInQuote) {
                    this._hideOffersButton($element);
                }

                this._bind();
            }
        },

        /**
         * Bind events
         * @protected
         */
        _bind: function () {
            $(document).on('extend:removeFromCart', this._onRemoveFromCart.bind(this));
        },

        /**
         * Warranty "Add To Cart" handler
         * @protected
         * @param {Object|null} warranty - warranty plan data
         */
        _addToCart: function (warranty) {
            if (!warranty)
                return;

            $.ajax({
                url: this.options.addLeadUrl,
                data: {
                    warranty: warranty,
                    qty: this.options.qty,
                    leadToken: this.options.leadToken,
                    form_key: $.mage.cookies.get('form_key')
                },
                type: 'post',
                dataType: 'json',
                context: this,

                /** @inheritdoc */
                beforeSend: function () {
                    $(document.body).trigger('processStart');
                },

                /** @inheritdoc */
                complete: function () {
                    $(document.body).trigger('processStop');
                }
            })
            .done(function (response) {
                if (response.status) {
                    this._onAddToCartSuccess(response);
                } else {
                    this._onAddToCartError(response.error);
                }
            })
            .fail(function (xhr, status, error) {
                this._onAddToCartError($t('Sorry, there has been an error processing your request. Please try again or contact our support.'));
            });
        },

        /**
         * Warranty "Add To Cart" succeed
         * @protected
         * @param {Object} response - ajax-response data
         */
        _onAddToCartSuccess: function (response) {
            // track warranty 'Add To Cart'
            if (this.options.trackingEnabled && typeof (response.trackingData) !== 'undefined') {
                trackActions.trackOfferAddToCart(response.trackingData);
            }

            // trigger warranty 'Add To Cart' event
            if (this.options.addLeadEvent) {
                $(document).trigger('ajax:' + this.options.addLeadEvent);
            }

            // reload Customer cart data
            customerData.reload(['cart'], false);

            var $element = $(this.element.get(0));
            this._hideOffersButton($element);
        },

        /**
         * Warranty "Add To Cart" failed
         * @protected
         */
        _onAddToCartError: function (errorMessage) {
            alert({
                content: errorMessage
            });
        },

        /**
         * Add product sku to data attribute
         *
         * @param {jQuery.element} $element
         * @private
         */
        _addProductSku: function ($element) {
            var sku = this.options.productSku;
            $element.attr('data-product-sku', sku);
            if (sku) {
                var escapedSku = sku.replace(' ', '');
                escapedSku = escapedSku.replace('"', '');
                $element.attr('data-product-sku-escaped', escapedSku);
            }

        },

        /**
         * Callback executes when product is removed from the cart
         *
         * @param {Event} e
         * @param {Object>} productData
         * @private
         */
        _onRemoveFromCart: function (e, productData) {
            if (productData && productData.options && _.isArray(productData.options)) {
                var $element = $(this.element.get(0));
                $.each(productData.options, function(i, option) {
                    if ((option.label === 'Product' || option.label === 'SKU') && option.value) {
                        var escapedSku = option.value.replace(' ', '');
                        escapedSku = escapedSku.replace('"', '');
                        if ($element.attr('data-product-sku-escaped') === escapedSku) {
                            this._showOffersButton($element);
                        }
                    }
                }.bind(this));
            }
        },

        /**
         * Hide offers button
         *
         * @param {jQuery.element} $element
         * @private
         */
        _hideOffersButton: function ($element) {
            $element.addClass('hidden');
        },

        /**
         * Show offers button
         *
         * @param {jQuery.element} $element
         * @private
         */
        _showOffersButton: function ($element) {
            $element.removeClass('hidden');
        }
    });

    return $.mage.leadOrderWarranty;
});
