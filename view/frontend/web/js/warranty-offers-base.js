/**
 * Extend Warranty base widget
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Extend_Warranty
 * @copyright   Copyright (c) 2022 Extend Inc. (https://www.extend.com/)
 */
define([
    'jquery',
    'extendSdk',
    'jquery-ui-modules/widget'
], function ($, Extend) {
    'use strict';

    $.widget('mage.extendWarrantyOffers', {
        options: {
            productSku: null,
            buttonEnabled: true,
            modalEnabled: false
        },

        /**
         * Renders warranty offers block
         */
        renderWarrantyOffers: function () {
            if (!this.options.buttonEnabled)
                return;

            Extend.buttons.render(this.element.get(0), {
                referenceId: this.options.productSku
            });
        },

        /**
         * Renders warranty simple offer button
         *
         * @param {Function|null} addToCartCallback
         */
        renderSimpleOfferButton: function (addToCartCallback) {
            if (!this.options.buttonEnabled)
                return;

            Extend.buttons.renderSimpleOffer(this.element.get(0), {
                referenceId: this.options.productSku,
                onAddToCart: function (data) {
                    var warranty = data.plan;
                    if (warranty && data.product) {
                        warranty.product = data.product.id;
                    }

                    if (typeof (addToCartCallback) === 'function') {
                        addToCartCallback(warranty);
                    }
                }
            });
        },

        /**
         * Returns current warranty offers block instance
         *
         * @return {Object|null}
         */
        getWarrantyOffersInstance: function () {
            return Extend.buttons.instance(this.element.get(0));
        },

        /**
         * Updates warranty offers button
         *
         * @param {String} productSku - new product SKU
         */
        updateWarrantyOffers: function (productSku) {
            var component = this.getWarrantyOffersInstance();
            if (!component)
                return;

            var product = component.getActiveProduct() || { id: '' };
            if (product.id !== productSku) {
                component.setActiveProduct(productSku);
            }
        },

        /**
         * Opens warranty offers modal
         *
         * @param {String} productSku - product SKU
         * @param {Function} closeCallback - function to be invoked after the modal is closed
         */
        openWarrantyOffersModal: function (productSku, closeCallback) {
            if (!this.options.modalEnabled) {
                closeCallback(null);
                return;
            }

            Extend.modal.open({
                referenceId: productSku,
                onClose: closeCallback.bind(this)
            });
        }
    });

    return $.mage.extendWarrantyOffers;
});
