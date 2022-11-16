/**
 * Extend Warranty - PDP/PLP widget for bundle product
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Extend_Warranty
 * @copyright   Copyright (c) 2022 Extend Inc. (https://www.extend.com/)
 */
define([
    'jquery',
    'underscore',
    'priceUtils',
    'extendWarrantyOffers',
    'simpleProductWarranty',
    'priceBundle',
    'domReady!'
], function ($, _, priceUtils) {
    'use strict';

    $.widget('mage.bundleProductWarranty', $.mage.simpleProductWarranty, {
        options: {
            isInProductView: true,
            bundleSkus: [],
            buttonEnabled: true,
            modalEnabled: false,

            insertionPoint: 'div.field.option',
            insertionLogic: 'after',
            formInputName: 'warranty_%s',
            bundleInputName: '.bundle-option-%s',
            formInputClass: 'extend-warranty-input',
            selectors: {
                addToCartForm: '#product_addtocart_form',
                addToCartButton: '#product-addtocart-button',
                optionsWrap: 'input.bundle.option, select.bundle.option, textarea.bundle.option'
            }
        },
        warrantyBlocks: {},

        /**
         * Product warranty offers creation
         * @protected
         */
        _create: function () {
            this._initElements();
            this._bind();
        },

        /**
         * Bind events
         * @protected
         */
        _bind: function () {
            this._super();

            if (this.options.selectors.optionsWrap) {
                $(this.options.selectors.optionsWrap, this.mainWrap).on('change', this._updateProductsWarrantyOffers.bind(this));
                $(this.options.selectors.optionsWrap, this.mainWrap).trigger('change');
            }
        },

        /**
         * Update the current warranty offers block for each associated product based on their selected configuration
         * @protected
         */
        _updateProductsWarrantyOffers: function (event) {
            var newSkus = [],
                bundleOptionElement = $(event.target),
                optionId = priceUtils.findOptionId(bundleOptionElement),
                optionValue = $(bundleOptionElement).val()
            ;



            if(this.options.bundleSkus[optionId] && this.options.bundleSkus[optionId][optionValue]){
                let product = this.options.bundleSkus[optionId][optionValue];
                if(this.warrantyBlocks[optionId]){
                    this.warrantyBlocks[optionId].extendWarrantyOffers('updateActiveProduct', product.sku);
                }else{
                    this.warrantyBlocks[optionId] = this._initWarrantyOffersBlock(optionId, product.sku);
                }
            }
        },

        /**
         * Returns information about warranty offers block insertion
         * @protected
         * @param {String} productId - product ID
         * @param {String} productSku - product SKU
         * @return {Object} - contains `element` and `method`
         */
        _getWarrantyOffersInsertion: function (productId, productSku) {
            var elem;
            if (this.options.insertionPoint) {
                elem = $(this.options.bundleInputName.replace('%s', productId), this.element);
                elem = elem.closest(this.options.insertionPoint);
                if (!elem.length) {
                    elem = this.element;
                }
            }

            return {
                element: elem,
                method: 'appendTo'
            };
        },

        /**
         * Handles "Add To Cart" form `submit` event.
         * @protected
         * @param {Event} event - The event arguments
         * @return {Boolean}
         */
        _onAddToCart: function (event) {
            this._removeWarrantyInputs();

            if (this.useNativeSubmit || !this.options.buttonEnabled)
                return true;

            // Product warranty offers block enabled
            if (this.options.buttonEnabled) {
                _.each(this.warrantyBlocks, function (warrantyBlock) {
                    // get the warranty component instance & plan selection
                    var component = warrantyBlock.extendWarrantyOffers('getButtonInstance');
                    var sku = component.getActiveProduct();
                    var plan = component ? component.getPlanSelection() : null;

                    if (sku && plan) {
                        this._appendWarrantyInputs(warrantyBlock, sku.id, plan, 'buttons');
                    }
                }.bind(this));

                this._submitAddToCartForm();

                event.preventDefault();
                event.stopPropagation();
                return false;
            }

            return true;
        }
    });

    return $.mage.bundleProductWarranty;
});
