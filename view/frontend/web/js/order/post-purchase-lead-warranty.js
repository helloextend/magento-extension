/**
 * Extend Warranty - Order item widget (Create Lead Order)
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Extend_Warranty
 * @copyright   Copyright (c) 2022 Extend Inc. (https://www.extend.com/)
 */
define([
    'jquery',
    'extendSdk',
    'mage/cookies',
    'leadOrderWarranty',
    'domReady!'
], function ($, Extend) {
    'use strict';

    $.widget('mage.postPurchaseLeadWarranty', $.mage.leadOrderWarranty, {
        options: {
            leadToken: null,
            buttonEnabled: false,
            addLeadUrl: null
        },

        /**
         * Post purchase lead token item warranty offers creation
         * @protected
         */
        _create: function () {
            var self = this;
            Extend.aftermarketModal.open({
                leadToken: self.options.leadToken,
                onClose: function(plan, product, qty) {
                    self.options.qty = qty;
                    if (plan && product) {
                        self._addToCart({
                            leadToken: self.options.leadToken,
                            planId: plan.planId,
                            price: plan.price,
                            term: plan.term,
                            product: product.id,
                            formKey: $.mage.cookies.get('form_key')
                        })
                    }
                }
            });
        }
    });

    return $.mage.postPurchaseLeadWarranty;
});
