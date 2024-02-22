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
            productInfo: {},
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
                referenceId: this.options.productSku,
                category: this.sanitizeValue(this.options.productInfo.category),
                price:this.options.productInfo.price
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
                category: this.sanitizeValue(this.options.productInfo.category),
                price:this.options.productInfo.price,
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
         * Returns sanitized value for payload
         * @param {String} theString
         * @return {string|null}
         */
        sanitizeValue: function (theString ) {
            if (!theString)
                return;

            // Use a regular expression to find HTML-encoded sections (e.g., %25)
            var encodedSectionRegex = /%[0-9A-Fa-f]{2}/g;

            // Replace each HTML-encoded section with its decoded equivalent
            var decodedString = theString.replace(encodedSectionRegex, function(match) {
                return decodeURIComponent(match);
            });

            // replace breaking characters
            var theSanitizedString = decodedString.replace(/%/g, "pct ").replace(/\?/g, ".").replace(/#/g, ".").replace(/&/g, "and");

            return theSanitizedString;
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
                let activeProduct = {
                    referenceId:productSku,
                    price: product.price,
                    category: this.sanitizeValue(product.category)
                };
                component.setActiveProduct(activeProduct);
            }
        },

        /**
         * Opens warranty offers modal
         *
         * @param {String} productSku - product SKU
         * @param {Object} productInfo
         * @param {Function} closeCallback - function to be invoked after the modal is closed
         */
        openOffersModal: function (productSku,productInfo, closeCallback) {
            if (!this.options.modalEnabled) {
                closeCallback(null);
                return;
            }

            Extend.modal.open({
                referenceId: productSku,
                price:productInfo.price,
                category: this.sanitizeValue(productInfo.category),
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
