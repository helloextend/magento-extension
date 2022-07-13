/**
 * Extend Warranty - tracking actions
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Extend_Warranty
 * @copyright   Copyright (c) 2022 Extend Inc. (https://www.extend.com/)
 */

define([
    'jquery',
    'Magento_Customer/js/section-config',
    'Magento_Customer/js/customer-data',
    'Extend_Warranty/js/extendtrk/actions',
    'jquery/jquery-storageapi',
    'jquery/ui'
], function ($, sectionConfig, customerData, actions) {
    'use strict';

    $.widget('mage.extendTrackingService', {
        options: {
            sectionName: 'extend-tracking'
        },

        /**
         * Product warranty offers creation
         * @protected
         */
        _create: function () {
            // check form submission and trigger "extend-tracking" customer data update
            $(document).on('submit', this.checkSubmitEvent.bind(this));

            // listen for changes in "extend-tracking" customer data
            this.extendSection = customerData.get('extend-tracking');
            this.extendSection.subscribe(this.customerDataHandler.bind(this));
        },

        /**
         * Detect whether track recent action (by invalidating "extend-tracking" section)
         *
         * @param {Event} event - The event arguments
         */
        checkSubmitEvent: function (event) {
            if (event.target.method.match(/post|put|delete/i)) {
                var sections = sectionConfig.getAffectedSections(event.target.action);
                if (sections && $.inArray(this.options.sectionName, sections) !== -1) {
                    customerData.invalidate([this.options.sectionName]);
                }
            }
        },

        /**
         * Handler of the "extend-tracking" customer data
         *
         * @param {Object} section
         */
        customerDataHandler: function (section) {
            var sectionData = (section || {}).data || [];
            if (!sectionData.length || typeof(window.Extend) === 'undefined')
                return;

            for (var i = 0; i < sectionData.length; i++) {
                var data = sectionData[i];
                switch (data.eventName) {
                    case 'trackProductAddedToCart':
                        actions.trackProductAddToCart(data);
                        break;
                    case 'trackOfferAddedToCart':
                        actions.trackOfferAddToCart(data);
                        break;
                    case 'trackProductRemovedFromCart':
                        actions.trackProductRemoveFromCart(data);
                        break;
                    case 'trackOfferRemovedFromCart':
                        actions.trackOfferRemoveFromCart(data);
                        break;
                    case 'trackProductUpdated':
                        actions.trackProductQtyUpdate(data);
                        break;
                    case 'trackOfferUpdated':
                        actions.trackOfferQtyUpdate(data);
                        break;
                }
            }
            customerData.set(this.options.sectionName, {});
        }
    });

    return $.mage.extendTrackingService;
});
