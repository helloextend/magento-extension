/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2021 Extend Inc. (https://www.extend.com/)
 */
define([
    'jquery'
], function ($) {

    return function (param) {
        Extend.buttons.renderSimpleOffer(
            '#extend-offer-' + param.itemId, {
                referenceId: param.productSku,
                onAddToCart: function (opts) {
                    var plan = opts.plan;
                    if (plan) {
                        plan.product = opts.product.id;
                        $.post(param.url, {
                            warranty: plan,
                            form_key: $.cookie('form_key')
                        }).done(function () {
                            location.reload();
                        });
                    }
                }
            }
        );
    }
});
