/**
 * Extend Warranty - Cart page widget
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Extend_Warranty
 * @copyright   Copyright (c) 2022 Extend Inc. (https://www.extend.com/)
 */
define([
    'jquery',
    'mage/translate',
    'Magento_Ui/js/modal/alert',
    'extendSdk',
    'extendWarrantyOffers',
    'domReady!'
], function ($, $t, alert, Extend) {
    'use strict';

    $.widget('mage.cartWarrantyOffers', $.mage.extendWarrantyOffers, {
        options: {
            productSku: null,
            addToCartUrl: null,
            addToCartEvent: null,
            buttonEnabled: true,
            trackingEnabled: true
        },

        /**
         * Cart warranty offers creation
         * @protected
         */
        _create: function () {
            this.renderSimpleOfferButton(this._addToCart.bind(this));
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
                url: this.options.addToCartUrl,
                data: {
                    warranty: warranty,
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
            if (this.options.trackingEnabled
                && typeof (response.trackingData) !== 'undefined'
                && typeof (Extend.trackOfferAddedToCart) === 'function'
            ) {
                var trackingData = response.trackingData;
                Extend.trackOfferAddedToCart({
                    'productId': trackingData.productId,
                    'productQuantity': parseInt(trackingData.productQuantity),
                    'warrantyQuantity': parseInt(trackingData.warrantyQuantity),
                    'planId': trackingData.planId,
                    'offerType': {
                        'area': trackingData.area,
                        'component': trackingData.component
                    }
                });
            }

            // trigger warranty 'Add To Cart' event
            if (this.options.addToCartEvent) {
                $(document).trigger('ajax:' + this.options.addToCartEvent);
            }

            // reload Cart page
            window.location.reload(false);
        },

        /**
         * Warranty "Add To Cart" failed
         * @protected
         */
        _onAddToCartError: function (errorMessage) {
            alert({
                content: errorMessage
            });
        }
    });

    return $.mage.cartWarrantyOffers;
});
