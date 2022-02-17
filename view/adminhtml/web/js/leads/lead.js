define([
    'jquery'
], function ($) {
    'use strict';

    $.widget('extend.addWarranty', {
        options: {
            sku: '',
            url: '',
            leadToken: '',
            parentId: ''
        },

        _create: function () {
            this._super();
            this._bind();
        },

        _bind: function () {
            $(this.element).click(this.addWarranty.bind(this));
        },

        addWarranty() {
            console.log('add Warranty');
            console.log(this.options.leadToken);
            if (this.options.leadToken) {
                Extend.buttons.renderSimpleOffer('#extend-offer-' + this.options.sku, {
                    referenceId: this.options.sku,
                    onAddToCart: function (opts) {

                        const plan = opts.plan;
                        if (plan) {
                            let parentId = this.options.parentId;
                            let url      = this.options.url;
                            plan.product = this.options.sku;

                            $.post(url, {
                                warranty: plan,
                                option: parentId
                            }).done(function (data) {
                                if (data.status == "success") {
                                    order.itemsUpdate();
                                } else {
                                    console.log("Oops! There was an error adding the protection plan.");
                                }
                            });
                        }
                    }
                });



                // Extend.aftermarketModal.open({
                //     leadToken: this.options.leadToken,
                //     onClose: function(plan, product, quantity) {
                //     //    @TODO
                //     },
                // })
            }
        }
    });

    return $.extend.addWarranty;
});
