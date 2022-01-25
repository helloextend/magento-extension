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
            modalEnabled: false,
            simpleOffers: {
                enabled: false,
                addToCartCallback: null
            }
        },

        /** @inheritdoc */
        _create: function () {
            this.initWarrantyOffers();
        },

        /**
         * Renders warranty offers block
         */
        initWarrantyOffers: function () {
            if (!this.options.buttonEnabled)
                return;

            if (this.options.simpleOffers && this.options.simpleOffers.enabled) {
                Extend.buttons.renderSimpleOffer(this.element.get(0), {
                    referenceId: this.options.productSku,
                    onAddToCart: function (data) {
                        var plan = data.plan;
                        if (plan && data.product) {
                            plan.product = data.product.id;
                        }

                        if (typeof (this.options.simpleOffers.addToCartCallback) === 'function') {
                            this.options.simpleOffers.addToCartCallback(plan);
                        }
                    }.bind(this)
                });
            } else {
                Extend.buttons.render(this.element.get(0), {
                    referenceId: this.options.productSku
                });
            }
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
