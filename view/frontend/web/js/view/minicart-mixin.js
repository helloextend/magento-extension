/**
 * Extend Warranty - Mini-cart items js-mixin
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Extend_Warranty
 * @copyright   Copyright (c) 2022 Extend Inc. (https://www.extend.com/)
 */

define([
    'jquery',
    'underscore',
    'cartItemWarranty'
], function ($, _) {
    var mixin = {
        warrantyClass: 'product-item-warranty',

        /**
         * @override
         */
        initialize: function () {
            var res = this._super();

            var self = this,
                updateHandler = _.debounce(function (e) {
                    self._onContentUpdated(e)
                }, 100);
            $('[data-block="minicart"]').on('contentUpdated', updateHandler);

            return res;
        },

        /**
         * Mini-cart content update handler
         * @param {Event} e
         * @private
         */
        _onContentUpdated: function (e) {
            var cartItems = this.cart.items() || [];
            _.each(cartItems, function (cartItem) {
                var qtyElem = $('#cart-item-' + cartItem.item_id + '-qty', e.currentTarget);
                if (qtyElem.length) {
                    this._initWarrantyOffers(cartItem, qtyElem.closest('[data-role=product-item]'));
                }
            }.bind(this));
        },

        /**
         * Initialize warranty offers simple button
         *
         * @param {object} cartItem
         * @param {jQuery} element
         * @private
         */
        _initWarrantyOffers: function (cartItem, element) {
            var blockID = 'warranty-offers-' + cartItem.product_id;
            var warrantyElem = $('#' + blockID, element);
            let relatedItemId = null;

            if (!cartItem.product_can_add_warranty) {
                warrantyElem.remove();
                return;
            }

            if(cartItem.relatedItemId){
                relatedItemId = cartItem.relatedItemId;
            }
            if (!warrantyElem.length) {
                warrantyElem = $('<div>').attr('id', blockID).addClass(this.warrantyClass);
                $('div.product-item-details', element).append(warrantyElem);
            }

            if (!warrantyElem.data('mageCartItemWarranty')) {
                warrantyElem.cartItemWarranty({
                    isInCartPage: window.location.href === this.shoppingCartUrl,
                    relatedItemId:relatedItemId,
                    productSku: cartItem.product_sku,
                    addToCartUrl: cartItem.warranty_add_url,
                    buttonEnabled: true,
                    trackingEnabled: cartItem.product_is_tracking_enabled
                });
            }
        }
    };

    return function (target) {
        return target.extend(mixin);
    };
});
