define([
    'jquery',
    'underscore'
], function ($, _) {

    return function (params) {

        Extend.buttons.render('#extend-offer', {
            referenceId: params.productSku
        });

        $(document).ready(function () {
            $('div.product-options-wrapper').on('change',(event) => {
                let sku = selectedProduct();

                if(sku !== ''){
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

            if ($('div.swatch-attribute').length === 0
                || ($('div.swatch-attribute').length > 0
                    && $('div.swatch-attribute').find('.mageworx-swatch-container').length > 0)
            ) {
                if ($('#product_addtocart_form [name=selected_configurable_option]')[0].value !== ''){
                    let productId1 = $('#product_addtocart_form [name=selected_configurable_option]')[0].value,
                        options;

                    if ($('#product_addtocart_form').data('magictoolboxConfigurable')) {
                        options = $('#product_addtocart_form').data('magictoolboxConfigurable').options;
                    } else {
                        options = $('#product_addtocart_form').data('mageConfigurable').options;
                    }

                    const productConfig1 = options.spConfig;

                    return productConfig1.skus[productId1];
                } else {
                    return params.productSku;
                }
            }else{
                let selected_options = {};
                let options = $('div.swatch-attribute');
                options.each((index, value) => {
                    let attribute_id = $(value).attr('attribute-id');
                    let option_selected = $(value).attr('option-selected');
                    if (!attribute_id || !option_selected) {
                        return '';
                    }
                    selected_options[attribute_id] = option_selected;
                });

                if ($('[data-role=swatch-options]').data('mageSwatchRenderer')) {
                    productConfig = $('[data-role=swatch-options]').data('mageSwatchRenderer').options.jsonConfig;
                } else if ($('#product_addtocart_form').data('magictoolboxConfigurable')) {
                    productConfig = $('#product_addtocart_form').data('magictoolboxConfigurable').options.spConfig;
                } else {
                    productConfig = $('#product_addtocart_form').data('mageConfigurable').options.spConfig;
                }
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
            if (component) {
                component.setActiveProduct(productSku);
            }
        }

        $('#product-addtocart-button').click((event) => {
            event.preventDefault();

            /** get the component instance rendered previously */
            const component = Extend.buttons.instance('#extend-offer');

            if (component) {
                /** get the users plan selection */
                const plan = component.getPlanSelection();

                let sku = params.productSku !== '' ? params.productSku : selectedProduct();

                if (plan) {
                    addWarranty(plan, sku);
                    $('#product_addtocart_form').submit();
                } else {
                    Extend.modal.open({
                        referenceId: sku,
                        onClose: function (plan) {
                            if (plan) {
                                addWarranty(plan, sku)
                            } else {
                                $("input[name^='warranty']").remove();
                            }
                            $('#product_addtocart_form').submit();
                        }
                    });
                }
            } else {
                $('#product_addtocart_form').submit();
            }
        });

        function addWarranty(plan, sku){

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
