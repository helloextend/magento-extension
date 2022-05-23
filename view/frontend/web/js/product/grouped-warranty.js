/**
 * Extend Warranty - PDP/PLP widget for configurable product
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Extend_Warranty
 * @copyright   Copyright (c) 2022 Extend Inc. (https://www.extend.com/)
 */
define([
    'jquery',
    'underscore',
    'extendWarrantyOffers',
    'simpleProductWarranty',
    'domReady!'
], function ($, _) {
    'use strict';

    $.widget('mage.groupedProductWarranty', $.mage.simpleProductWarranty, {
        options: {
            isInProductView: true,
            products: [],
            buttonEnabled: true,
            modalEnabled: false,
            insertionPoint: 'input.qty[name="super_group[%s]"]',
            insertionLogic: 'after',
            formInputName: 'warranty_%s',
            formInputClass: 'extend-warranty-input',
            selectors: {
                addToCartForm: '#product_addtocart_form',
                addToCartButton: '#product-addtocart-button'
            }
        },
        warrantyBlocks: [],

        /**
         * Product warranty offers creation
         * @protected
         */
        _create: function () {
            this._initElements();
            this._bind();

            this._initProductsWarrantyOffers();
        },

        /**
         * Initialize warranty offers block for each associated product
         * @protected
         */
        _initProductsWarrantyOffers: function () {
            if (this.warrantyBlocks.length) {
                this.warrantyBlocks.remove();
            }

            _.each(this.options.products || [], function (product) {
                var warrantyBlock = this._initWarrantyOffersBlock(product.id, product.sku);
                this.warrantyBlocks.push(warrantyBlock);
            }.bind(this));
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
                elem = $(this.options.insertionPoint.replace('%s', productId), this.element);
                if (!elem.length) {
                    elem = this.element;
                }
            }

            // create new row for the warranty offers right after product row
            var productRow = elem.closest('tr');
            if (productRow.length) {
                var newRow = $('<tr>').addClass('warranty-offers-row');
                var newCol = $('<td>').attr('colspan', productRow.children().length);

                newRow.append(newCol)
                .insertAfter(productRow);

                elem = newCol;
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

    return $.mage.configurableProductWarranty;
});
