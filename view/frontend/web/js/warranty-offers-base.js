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
    'jquery/ui'
], function ($, Extend) {
    'use strict';

    $.widget('mage.extendWarrantyOffers', {
        options: {
            productSku: null,
            buttonEnabled: true,
            modalEnabled: false,
            formInputName: 'warranty'
        },

        /**
         * Renders warranty offers block
         */
        renderOffersButton: function () {
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
        renderSimpleButton: function (addToCartCallback) {
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
        getButtonInstance: function () {
            return Extend.buttons.instance(this.element.get(0));
        },

        /**
         * Updates warranty offers product
         *
         * @param {String} productSku - new product SKU
         */
        updateActiveProduct: function (productSku) {
            var component = this.getButtonInstance();
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
        openOffersModal: function (productSku, closeCallback) {
            if (!this.options.modalEnabled) {
                closeCallback(null);
                return;
            }

            Extend.modal.open({
                referenceId: productSku,
                onClose: closeCallback.bind(this)
            });
        },

        /**
         * Get warranty inputs for the "Add To Cart" form
         * @protected
         * @param {String} productSku - currently selected product SKU
         * @param {Object} plan - selected warranty offer plan
         * @param {String} componentName - component name for tracking (`button` or `modal`)
         */
        getWarrantyFormInputs: function (productSku, plan, componentName) {
            var inputs = [];
            var data = $.extend({
                product: productSku,
                component: componentName
            }, plan);

            $.each(data, function (attribute, value) {
                inputs.push(
                    $('<input>').attr('type', 'hidden')
                    .attr('name', this.options.formInputName + '[' + attribute + ']')
                    .attr('value', value)
                );
            }.bind(this));

            return inputs;
        }
    });

    return $.mage.extendWarrantyOffers;
});
