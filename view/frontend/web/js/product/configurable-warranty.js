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

    $.widget('mage.configurableProductWarranty', $.mage.simpleProductWarranty, {
        options: {
            isInProductView: true,
            productId: null,
            productSku: null,
            buttonEnabled: true,
            modalEnabled: false,
            variationPrices: {},
            blockClass: 'product-warranty-offers',
            insertionPoint: 'div.actions',
            insertionLogic: 'before',
            formInputName: 'warranty',
            formInputClass: 'extend-warranty-input',
            selectors: {
                addToCartForm: '#product_addtocart_form',
                addToCartButton: '#product-addtocart-button',
                optionsWrap: 'div.product-options-wrapper'
            }
        },

        /**
         * Bind events
         * @protected
         */
        _bind: function () {
            this._super();

            if (this.options.selectors.optionsWrap) {
                $(this.options.selectors.optionsWrap, this.mainWrap).on('change', this._onOptionsChanged.bind(this));
            }
        },

        /**
         * Handles product options `change` event
         * @protected
         * @param {Event} event - The event arguments
         */
        _onOptionsChanged: function (event) {
            if (!this.options.buttonEnabled && !this.options.modalEnabled)
                return;

            var productSku = this._getWarrantyProductSku();

            // the parent SKU is not rendered for configurable products on the PLP, so a
            // non-swatch selection leaves us without a SKU to price the offer with
            if (!productSku && !this.options.isInProductView) {
                productSku = this._getSelectedConfigurableSku();
            }

            var price = this._getVariationPrice(productSku);

            // keeps the interstitial modal on the price of the selected variation
            if (price !== null) {
                this.options.productInfo.price = price;
            }

            if (this.options.buttonEnabled) {
                this.warrantyBlock.extendWarrantyOffers('updateActiveProduct', productSku, price);
            }
        },

        /**
         * Returns the offer price of the given variation, `null` when it is unknown
         *
         * Variation prices are rendered server side, so special, catalog rule and
         * customer group prices of the child product are all taken into account.
         *
         *
         * @protected
         * @param {String} productSku
         * @return {Number|null}
         */
        _getVariationPrice: function (productSku) {
            var prices = this.options.variationPrices || {};

            return productSku && prices.hasOwnProperty(productSku) ? prices[productSku] : null;
        },

        /**
         * Returns currently selected simple product SKU
         * @protected
         */
        _getWarrantyProductSku: function () {
            var swatches = $('div.swatch-attribute', this.mainWrap);
            var selectedSku = null;

            if (swatches.length > 0 ) {
                var swatchesElem = this.options.isInProductView ?
                    $('[data-role=swatch-options]', this.mainWrap) :
                    $('[data-role^=swatch-option-]', this.mainWrap);
		
                var swatchRenderer = swatchesElem.data('mageSwatchRenderer') ? swatchesElem.data('mageSwatchRenderer') : swatchesElem.data('mage-SwatchRenderer');

                if (swatchRenderer) {
                    var selectedProducts = swatchRenderer._CalcProducts();
                    var selectedId = _.isArray(selectedProducts) && selectedProducts.length === 1 ? selectedProducts[0] : null;
                    if (selectedId && selectedId !== '') {
                        selectedSku = swatchRenderer.options.jsonConfig.skus[selectedId];
                    }
                }
            } else if (this.options.isInProductView) {
                selectedSku = this._getSelectedConfigurableSku();
            }

            return selectedSku ? selectedSku : this.options.productSku;
        },

        /**
         * Returns the SKU of the currently selected configurable option, `null` when
         * nothing is selected or the configurable widget is unavailable
         *
         * @protected
         * @return {String|null}
         */
        _getSelectedConfigurableSku: function () {
            var selectedId = $('input[name=selected_configurable_option]', this.mainWrap).val();

            if (!selectedId || selectedId === '') {
                return null;
            }

            var configurable = this.addToCartForm.data('mageConfigurable');
            var spConfig = configurable ? configurable.options.spConfig : null;

            return spConfig && spConfig.skus && spConfig.skus[selectedId] ? spConfig.skus[selectedId] : null;
        }
    });

    return $.mage.configurableProductWarranty;
});
