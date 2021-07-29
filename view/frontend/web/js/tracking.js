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

    /**
     * Events listener
     */
    $(document).on('ajaxComplete', function(event, xhr, settings) {

        if (settings.type.match(/post|put|delete/i)) {
            var sections = sectionConfig.getAffectedSections(settings.url);
            if (sections && $.inArray('extend-tracking', sections) !== -1) {
                let eventData = customerData.get('extend-tracking');
                eventData.subscribe(function(events) {
                    if (events) {
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
                                }
                            }
                        }
                    }
                });
            }
        }
    });

    /**
     * Events listener
     */
    $(document).on('submit', function(event) {
        var sections;

        if (event.target.method.match(/post|put|delete/i)) {
            sections = sectionConfig.getAffectedSections(event.target.action);
            if (sections) {
                alert(sections);
            }
        }
    });
});
