/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2021 Extend Inc. (https://www.extend.com/)
 */
define([
    'jquery',
    'Magento_Customer/js/customer-data'
], function ($, customerData) {

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
                        }).done(function (response) {
                            if (
                                response.status
                                && param.isTrackingEnabled
                                && response.trackingData !== undefined
                                && typeof Extend.trackOfferAddedToCart === 'function'
                            ) {
                                var trackingData = response.trackingData;
                                Extend.trackOfferAddedToCart({
                                    'productId': trackingData.productId,
                                    'productQuantity': parseInt(trackingData.productQuantity),
                                    'warrantyQuantity': parseInt(trackingData.warrantyQuantity),
                                    'planId': trackingData.planId,
                                    'offerType': {
                                        'area': trackingData.area,
                                        'component': trackingData.component
                                    }
                                });
                            }

                            customerData.reload();
                        });
                    }
                }
            }
        );
    }
});
