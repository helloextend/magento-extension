/**
 * Extend Warranty - tracking actions
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Extend_Warranty
 * @copyright   Copyright (c) 2022 Extend Inc. (https://www.extend.com/)
 */

define([], function () {
    'use strict';

    return {
        /**
         * Send data to Extend when product is added to cart
         *
         * @param {Object} data - data from customer-data
         */
        trackProductAddToCart: function (data) {
            if (typeof (window.Extend) === 'undefined' || typeof (window.Extend.trackProductAddedToCart) !== 'function')
                return;

            if (!data.productId || !data.productQuantity) {
                console.error('Extend Warranty tracking: Invalid data for "trackProductAddedToCart" action');
                return;
            }

            window.Extend.trackProductAddedToCart({
                'productId': data.productId,
                'productQuantity': parseInt(data.productQuantity)
            });
        },

        /**
         * Send data to Extend when warranty offer is added to cart
         *
         * @param {Object} data - data from customer-data
         */
        trackOfferAddToCart: function (data) {
            if (typeof (window.Extend) === 'undefined' || typeof (window.Extend.trackOfferAddedToCart) !== 'function')
                return;

            if (!data.productId || !data.productQuantity || !data.warrantyQuantity || !data.planId) {
                console.error('Extend Warranty tracking: Invalid data for "trackOfferAddedToCart" action');
                return;
            }

            window.Extend.trackOfferAddedToCart({
                'productId': data.productId,
                'productQuantity': parseInt(data.productQuantity),
                'warrantyQuantity': parseInt(data.warrantyQuantity),
                'planId': data.planId,
                'offerType': {
                    'area': data.area,
                    'component': data.component
                }
            });
        },

        /**
         * Send data to Extend when product is removed from cart
         *
         * @param {Object} data - data from customer-data
         */
        trackProductRemoveFromCart: function (data) {
            if (typeof (window.Extend) === 'undefined' || typeof (window.Extend.trackProductRemovedFromCart) !== 'function')
                return;

            if (!data.productId) {
                console.error('Extend Warranty tracking: Invalid data for "trackProductRemovedFromCart" action');
                return;
            }

            window.Extend.trackProductRemovedFromCart({
                'productId': data.productId
            });

        },

        /**
         * Send data to Extend when warranty offer is removed from cart
         *
         * @param {Object} data - data from customer-data
         */
        trackOfferRemoveFromCart: function (data) {
            if (typeof (window.Extend) === 'undefined' || typeof (window.Extend.trackOfferRemovedFromCart) !== 'function')
                return;

            if (!data.productId || !data.planId) {
                console.error('Extend Warranty tracking: Invalid data for "trackOfferRemovedFromCart" action');
                return;
            }

            window.Extend.trackOfferRemovedFromCart({
                'productId': data.productId,
                'planId': data.planId
            });
        },

        /**
         * Send data to Extend when product cart item qty is updated
         *
         * @param {Object} data - data from customer-data
         */
        trackProductQtyUpdate: function (data) {
            if (typeof (window.Extend) === 'undefined' || typeof (window.Extend.trackProductUpdated) !== 'function')
                return;

            if (!data.productId || !data.productQuantity) {
                console.error('Extend Warranty tracking: Invalid data for "trackProductUpdated" action');
                return;
            }

            window.Extend.trackProductUpdated({
                'productId': data.productId,
                'updates': {
                    'productQuantity': parseInt(data.productQuantity)
                }
            });
        },

        /**
         * Send data to Extend when warranty offer cart item qty is updated
         *
         * @param {Object} data - data from customer-data
         */
        trackOfferQtyUpdate: function (data) {
            if (typeof (window.Extend) === 'undefined' || typeof (window.Extend.trackOfferUpdated) !== 'function')
                return;

            if (!data.productId || !data.productQuantity || !data.warrantyQuantity || !data.planId) {
                console.error('Extend Warranty tracking: Invalid data for "trackOfferUpdated" action');
                return;
            }

            window.Extend.trackOfferUpdated({
                'productId': data.productId,
                'planId': data.planId,
                'updates': {
                    'warrantyQuantity': parseInt(data.warrantyQuantity),
                    'productQuantity': parseInt(data.productQuantity)
                }
            });
        },

        /**
         * Send data to Extend on checkout success
         *
         * @param {Object} data - data with cart total
         */
        trackCheckoutSuccess: function (data) {
            if (typeof (window.Extend) === 'undefined' || typeof (window.Extend.trackCartCheckout) !== 'function')
                return;

            if (!data.cartTotal) {
                console.error('Extend Warranty tracking: Invalid data for "trackCartCheckout" action');
                return;
            }

            window.Extend.trackCartCheckout({
                'cartTotal': data.cartTotal
            });
        }
    }
});
