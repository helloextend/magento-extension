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
            Extend.buttons.render('#extend-offer', {
                referenceId: params.productSku
            });
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
            params.isProductHasOffers = true;
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

            let sku = params.productSku !== '' ? params.productSku : selectedProduct();

            if (params.isPdpOffersEnabled) {
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
                    $('#product_addtocart_form').submit();
                } else if (params.isInterstitialCartOffersEnabled && params.isProductHasOffers) {
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
                            $('#product_addtocart_form').submit();
                        }
                    });
                } else {
                    $('#product_addtocart_form').submit();
                }
            } else if (params.isInterstitialCartOffersEnabled && params.isProductHasOffers) {
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
                        $('#product_addtocart_form').submit();
                    }
                });
            } else {
                $('#product_addtocart_form').submit();
            }
        });

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
    };
});
