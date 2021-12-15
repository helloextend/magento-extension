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
    'underscore'
], function ($, _) {

    return function (params) {

        if (params.isPdpOffersEnabled) {
            if  (typeof params.itemId !== 'undefined') {
                Extend.buttons.render('#extend-offer-' + params.itemId, {
                    referenceId: params.productSku
                });
            } else if (params.productSku !== 'grouped') {
                Extend.buttons.render('#extend-offer', {
                    referenceId: params.productSku
                });
            }
        }

        $(document).ready(function () {
            $('div.product-options-wrapper').on('change', (event) => {
                let sku = selectedProduct();

                if(sku !== '' && params.isPdpOffersEnabled) {
                    if (sku !== params.productSku) {
                        renderWarranties(sku);
                    } else {
                        event.preventDefault();
                    }
                }
            });
        });

        function match(attributes, selected_options) {
            return _.isEqual(attributes, selected_options);
        }

        function selectedProduct() {
            if ($('div.swatch-attribute').length === 0 ) {
                if ($('#product_addtocart_form [name=selected_configurable_option]')[0].value !== '') {
                    let productId1 = $('#product_addtocart_form [name=selected_configurable_option]')[0].value;
                    const productConfig1 = $('#product_addtocart_form').data('mageConfigurable').options.spConfig;
                    return productConfig1.skus[productId1];
                } else {
                    return params.productSku;
                }
            }else{
                let selected_options = {};
                let options = $('div.swatch-attribute');
                options.each((index, value) => {
                    let attribute_id = $(value).attr('data-attribute-id');
                    let option_selected = $(value).attr('data-option-selected');
                    if (!attribute_id || !option_selected) {
                        return '';
                    }
                    selected_options[attribute_id] = option_selected;
                });

                const productConfig = $('[data-role=swatch-options]').data('mageSwatchRenderer').options.jsonConfig;

                for (let [productId, attributes] of Object.entries(productConfig.index)) {
                    if (match(attributes, selected_options)) {
                        return productConfig.skus[productId];
                    }
                }
            }
        }

        function renderWarranties(productSku){
            const component = Extend.buttons.instance('#extend-offer');
            component.setActiveProduct(productSku);
        }

        $('#product-addtocart-button').click((event) => {
            event.preventDefault();

            let form = $('#product_addtocart_form');

            if  (typeof params.groupedProducts !== 'undefined') {

                multipleWarrantySubmit(form, params.groupedProducts, params.isProductHasOffers);

            } else if (typeof params.itemId === 'undefined') {
                let sku = params.productSku !== '' ? params.productSku : selectedProduct(),
                    hasOffers = false;

                if (params.isProductHasOffers.hasOwnProperty(sku)) {
                    hasOffers = params.isProductHasOffers[sku];
                }

                singleWarrantySubmit(form, sku, hasOffers, params.isPdpOffersEnabled,params.isInterstitialCartOffersEnabled);
            }
        });

        function singleWarrantySubmit(form, sku, hasOffers, isPdpOffersEnabled,isInterstitialCartOffersEnabled)
        {
            if (isPdpOffersEnabled) {
                /** get the component instance rendered previously */
                const component = Extend.buttons.instance('#extend-offer');
                /** get the users plan selection */
                const plan = component.getPlanSelection();

                if (plan) {
                    addWarranty(plan, sku);
                    //add hidden field for tracking
                    $('<input />').attr('type', 'hidden')
                        .attr('name', 'warranty[component]')
                        .attr('value', 'buttons')
                        .appendTo('#product_addtocart_form');
                    form.submit();
                } else if (isInterstitialCartOffersEnabled && hasOffers) {
                    Extend.modal.open({
                        referenceId: sku,
                        onClose: function (plan) {
                            if (plan) {
                                addWarranty(plan, sku);
                                //add hidden field for tracking
                                $('<input />').attr('type', 'hidden')
                                    .attr('name', 'warranty[component]')
                                    .attr('value', 'modal')
                                    .appendTo('#product_addtocart_form');
                            } else {
                                $("input[name^='warranty']").remove();
                            }
                            form.submit();
                        }
                    });
                } else {
                    form.submit();
                }
            } else if (isInterstitialCartOffersEnabled && hasOffers) {
                Extend.modal.open({
                    referenceId: sku,
                    onClose: function (plan) {
                        if (plan) {
                            addWarranty(plan, sku);
                            //add hidden field for tracking
                            $('<input />').attr('type', 'hidden')
                                .attr('name', 'warranty[component]')
                                .attr('value', 'modal')
                                .appendTo('#product_addtocart_form');
                        } else {
                            $("input[name^='warranty']").remove();
                        }
                        form.submit();
                    }
                });
            } else {
                form.submit();
            }
        }

        function multipleWarrantySubmit(form,groupedProducts, isProductHasOffers)
        {
            $.each(groupedProducts, (id, sku) => {
                let component = Extend.buttons.instance('#extend-offer-' + id),
                    plan = component.getPlanSelection(),
                    hasOffers = false;
                if (isProductHasOffers.hasOwnProperty(id)) {
                    hasOffers = isProductHasOffers[id];
                }
                if (hasOffers && plan) {
                    addWarrantyGrouped(plan, sku, id);
                }
            });

            form.submit();
        }

        function addWarranty(plan, sku) {
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

        function addWarrantyGrouped(plan, sku, itemId) {
            $.each(plan, (attribute, value) => {
                $('<input />').attr('type', 'hidden')
                    .attr('name', 'warranty_' + itemId + '[' + attribute + ']')
                    .attr('value', value)
                    .appendTo('#product_addtocart_form');
            });
            $('<input />').attr('type', 'hidden')
                .attr('name', 'warranty_' + itemId + '[product]')
                .attr('value', sku)
                .appendTo('#product_addtocart_form');
        }
    };
});
