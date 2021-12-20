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
    'Magento_Customer/js/section-config',
    'Magento_Customer/js/customer-data',
    'jquery/jquery-storageapi'
], function ($, sectionConfig, customerData) {
    'use strict';

    $(document).ready(function() {
        let eventData = customerData.get('extend-tracking');
        eventData.subscribe(function(events) {
            if (events && events.data) {
                let data = events.data;
                if (data.length > 0 && window.Extend) {
                    for (let i = 0; i < data.length; i++) {
                        let event = data[i];
                        switch (event.eventName) {
                            case 'trackProductAddedToCart':
                                if (typeof window.Extend.trackProductAddedToCart === 'function') {
                                    window.Extend.trackProductAddedToCart({
                                        'productId': event.productId,
                                        'productQuantity': parseInt(event.productQuantity)
                                    });
                                }
                                break;
                            case 'trackOfferAddedToCart':
                                if (typeof window.Extend.trackOfferAddedToCart === 'function') {
                                    window.Extend.trackOfferAddedToCart({
                                        'productId': event.productId,
                                        'productQuantity': parseInt(event.productQuantity),
                                        'warrantyQuantity': parseInt(event.warrantyQuantity),
                                        'planId': event.planId,
                                        'offerType': {
                                            'area': event.area,
                                            'component': event.component
                                        }
                                    });
                                }
                                break;
                            case 'trackProductRemovedFromCart':
                                if (typeof window.Extend.trackProductRemovedFromCart === 'function') {
                                    window.Extend.trackProductRemovedFromCart({
                                        'productId': event.productId
                                    });
                                }
                                break;
                            case 'trackOfferRemovedFromCart':
                                if (typeof window.Extend.trackOfferRemovedFromCart === 'function') {
                                    window.Extend.trackOfferRemovedFromCart({
                                        'productId': event.productId,
                                        'planId': event.planId
                                    });
                                }
                                break;
                            case 'trackProductUpdated':
                                if (typeof window.Extend.trackProductUpdated === 'function') {
                                    window.Extend.trackProductUpdated({
                                        'productId': event.productId,
                                        'updates': {
                                            'productQuantity': parseInt(event.productQuantity)
                                        }
                                    });
                                }
                                break;
                            case 'trackOfferUpdated':
                                if (typeof window.Extend.trackOfferUpdated === 'function') {
                                    window.Extend.trackOfferUpdated({
                                        'productId': event.productId,
                                        'planId': event.planId,
                                        'updates': {
                                            'warrantyQuantity': parseInt(event.warrantyQuantity),
                                            'productQuantity': parseInt(event.productQuantity)
                                        }
                                    });
                                }
                                break;
                        }
                    }
                    customerData.set('extend-tracking', {});
                }
            }
        });
    });

    $(document).on('submit', function(event) {
        if (event.target.method.match(/post|put|delete/i)) {
            let sections = sectionConfig.getAffectedSections(event.target.action);
            if (sections && $.inArray('extend-tracking', sections) !== -1) {
                customerData.invalidate(['extend-tracking']);
            }
        }
    });
});
