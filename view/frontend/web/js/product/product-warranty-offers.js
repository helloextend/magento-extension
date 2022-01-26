/**
 * Extend Warranty - PDP/PLP widget
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
    'domReady!'
], function ($, _) {
    'use strict';

    $.widget('mage.productWarrantyOffers', $.mage.extendWarrantyOffers, {
        options: {
            isInProductView: true,
            productSku: null,
            buttonEnabled: true,
            modalEnabled: false,
            selectors: {
                addToCartForm: '#product_addtocart_form',
                addToCartButton: '#product-addtocart-button',
                optionsWrap: 'div.product-options-wrapper'
            }
        },

        mainWrap: null,
        addToCartForm: null,
        addToCartButton: null,
        useNativeSubmit: false,

        /**
         * Product warranty offers creation
         * @protected
         */
        _create: function () {
            this.mainWrap = this.options.isInProductView ?
                this.element.parents('.column.main') :
                this.element.parents('.product-item-info');

            this.addToCartForm = $(this.options.selectors.addToCartForm, this.mainWrap);
            this.addToCartButton = $(this.options.selectors.addToCartButton, this.mainWrap);

            this._bind();
            this.renderWarrantyOffers();
        },

        /**
         * Bind events
         * @protected
         */
        _bind: function () {
            if (!this.options.buttonEnabled && !this.options.modalEnabled)
                return;

            if (this.options.selectors.optionsWrap) {
                $(this.options.selectors.optionsWrap, this.mainWrap).on('change', this._onOptionsChanged.bind(this));
            }

            if (this.addToCartForm && this.addToCartForm.length) {
                this.addToCartButton.on('click', this._onAddToCart.bind(this));
            }
        },

        /**
         * Handles product options `change` event
         * @protected
         * @param {Event} event - The event arguments
         */
        _onOptionsChanged: function (event) {
            if (!this.options.buttonEnabled)
                return;

            var productSku = this._getSelectedProductSku();
            this.updateWarrantyOffers(productSku);
        },

        /**
         * Returns currently selected simple product SKU (when product is configurable)
         * @protected
         */
        _getSelectedProductSku: function () {
            var swatches = $('div.swatch-attribute', this.mainWrap);
            var selectedSku = null;

            if (swatches.length > 0 ) {
                var swatchesElem = this.options.isInProductView ?
                    $('[data-role=swatch-options]', this.mainWrap) :
                    $('[data-role^=swatch-option-]', this.mainWrap);
                var swatchRenderer = swatchesElem.data('mageSwatchRenderer');

                if (swatchRenderer) {
                    var selectedId = swatchRenderer.getProductId();
                    if (selectedId && selectedId !== '') {
                        selectedSku = swatchRenderer.options.jsonConfig.skus[selectedId];
                    }
                }
            } else if (this.options.isInProductView) {
                var selectedId = $('input[name=selected_configurable_option]', this.mainWrap).val();
                if (selectedId && selectedId !== '') {
                    var spConfig = this.addToCartForm.data('mageConfigurable').options.spConfig;
                    selectedSku = spConfig && spConfig.skus ? spConfig.skus[selectedId] : null;
                }
            }

            return selectedSku ? selectedSku : this.options.productSku;
        },

        /**
         * Handles "Add To Cart" form `submit` event.
         * @protected
         * @param {Event} event - The event arguments
         * @return {Boolean}
         */
        _onAddToCart: function (event) {
            this._removeWarrantyInputs();

            if (this.useNativeSubmit || (!this.options.buttonEnabled && !this.options.modalEnabled))
                return true;

            var productSku = this.options.productSku ? this.options.productSku : this._getSelectedProductSku();

            // Product warranty offers block enabled
            if (this.options.buttonEnabled) {
                // get the warranty component instance & plan selection
                var component = this.getWarrantyOffersInstance();
                var plan = component ? component.getPlanSelection() : null;

                if (plan) {
                    this._appendWarrantyInputs(plan, productSku, 'buttons');
                    this._submitAddToCartForm();

                    event.preventDefault();
                    event.stopPropagation();
                    return false;
                }
            }

            // Plan is not selected & warranty offers modal is enabled
            if (this.options.modalEnabled) {
                this._addToCartFromModal(productSku);

                event.preventDefault();
                event.stopPropagation();
                return false;
            }

            return true;
        },

        /**
         * Opens Extend Warranty modal
         * @protected
         * @param {String} productSku - currently selected product SKU
         */
        _addToCartFromModal: function (productSku) {
            this.openWarrantyOffersModal(productSku, function (plan) {
                if (plan) {
                    this._appendWarrantyInputs(plan, productSku, 'modal');
                }
                this._submitAddToCartForm();
            }.bind(this));
        },

        /**
         * Appends warranty inputs to the "Add To Cart" form
         * @protected
         * @param {Object} plan - selected warranty offer plan
         * @param {String} productSku - currently selected product SKU
         * @param {String} componentName - component name for tracking (`button` or `modal`)
         */
        _appendWarrantyInputs: function (plan, productSku, componentName) {
            this._removeWarrantyInputs();

            $('<input />').attr('type', 'hidden')
                .attr('name', 'warranty[product]')
                .attr('value', productSku)
                .appendTo(this.addToCartForm);

            $.each(plan, function (attribute, value) {
                $('<input />').attr('type', 'hidden')
                    .attr('name', 'warranty[' + attribute + ']')
                    .attr('value', value)
                    .appendTo(this.addToCartForm);
            }.bind(this));

            // add hidden field for tracking
            if (componentName) {
                $('<input />').attr('type', 'hidden')
                    .attr('name', 'warranty[component]')
                    .attr('value', componentName)
                    .appendTo(this.addToCartForm);
            }
        },

        /**
         * Removes warranty inputs from the "Add To Cart" form
         * @protected
         */
        _removeWarrantyInputs: function () {
            this.addToCartForm.children("input[type='hidden'][name^='warranty']").remove();
        },

        /**
         * Submits "Add To Cart" form
         * @protected
         */
        _submitAddToCartForm: function () {
            this.useNativeSubmit = true;
            this.addToCartForm.trigger('submit');
            this.useNativeSubmit = false;
        }
    });

    return $.mage.productWarrantyOffers;
});
