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
    'underscore'
], function ($, _) {
    return function (originalWidget) {
        /**
         * Extends catalogAddToCart widget.
         */
        $.widget('mage.sidebar', originalWidget, {

            /**
             * @override
             */
            _removeItemAfter: function (elem) {
                var productData = this._getProductById(Number(elem.data('cart-item')));

                if (!_.isUndefined(productData)) {
                    $(document).trigger('ajax:removeFromCart', {
                        productIds: [productData['product_id']],
                        productInfo: [
                            {
                                'id': productData['product_id']
                            }
                        ]
                    });

                    this._showOffersButton(productData.options);

                    if (window.location.href.indexOf(this.shoppingCartUrl) === 0) {
                        window.location.reload();
                    }
                }
            },

            /**
             * Show offers button
             *
             * @param options
             * @private
             */
            _showOffersButton: function(options) {
                if (_.isArray(options)) {
                    $.each(options, function(ix, option) {
                        if (option.label === 'Product' && option.value) {
                            var escapedSku = option.value.replace(' ', '');
                            escapedSku = escapedSku.replace('"', '');
                            jQuery('[data-product-sku-escaped=' + escapedSku + ']').removeClass('hidden');
                        }
                    });
                }
            }

        });

        return $.mage.sidebar;
    }
});
