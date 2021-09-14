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
    'uiRegistry'
], function ($, uiRegistry) {
    'use strict';

    /**
     * Ajax Suite Mixin
     * 
     * @type {{ajaxCartSubmit: ajaxSuiteMixin.ajaxCartSubmit}}
     */
    var ajaxSuiteMixin = {

        /**
         * Add warranty
         *
         * @param form
         */
        ajaxCartSubmit: function (form) {
            if (uiRegistry.has('productSku')) {
                const component = Extend.buttons.instance('#extend-offer');
                const plan = component.getPlanSelection();

                var sku = uiRegistry.get('productSku');

                if (plan) {
                    this.addWarranty(plan, sku);
                    this._super(form);
                } else {
                    var deferred,
                        _super = this._super.bind(this);

                    deferred = this.openModal(sku);
                    deferred.done(function () {
                        _super(form);
                    });
                }
            } else {
                this._super(form);
            }
        },

        /**
         * Open modal
         *
         * @param sku
         * @returns {*}
         */
        openModal: function (sku) {
            var deferred = new $.Deferred(),
                self = this;

            Extend.modal.open({
                referenceId: sku,
                onClose: function (plan) {
                    if (plan) {
                        self.addWarranty(plan, sku);
                    } else {
                        $("input[name^='warranty']").remove();
                    }
                    deferred.resolve();
                }
            });

            return deferred.promise();
        },

        /**
         * Add warranty
         *
         * @param plan
         * @param sku
         */
        addWarranty: function (plan, sku) {
            $.each(plan, (attribute, value) => {
                $('<input />').attr('type', 'hidden')
                    .attr('name', 'warranty[' + attribute + ']')
                    .attr('value', value)
                    .appendTo('#product_addtocart_form');
            });

            $('<input />').attr('type', 'hidden')
                .attr('name', 'warranty[product]')
                .attr('value', sku)
                .appendTo('#product_addtocart_form');
        }
    };

    return function (targetWidget) {
        $.widget('rokanthemes.ajaxsuite', targetWidget, ajaxSuiteMixin);

        return $.rokanthemes.ajaxsuite;
    };
});