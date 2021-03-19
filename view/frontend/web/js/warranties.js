define([
    'jquery',
    'underscore'
], function ($, _) {

    return function (params) {

        Extend.buttons.render('#extend-offer', {
            referenceId: params.productSku
        });

        $(document).ready(function () {
            $('div.product-options-wrapper').on('change',() => {
                let sku = selectedProduct();

                if(sku !== ''){
                    renderWarranties(sku);
                }
            });
        });

        function match(attributes, selected_options) {
            return _.isEqual(attributes, selected_options);
        }

        function selectedProduct() {

            if ($('div.swatch-attribute').length === 0 ){
                if ($('#product_addtocart_form [name=selected_configurable_option]')[0].value !== ''){
                    let productId1 = $('#product_addtocart_form [name=selected_configurable_option]')[0].value;
                    const productConfig1 = $('#product_addtocart_form').data('firebearConfig_icp').options.spConfig;
                    return productConfig1.skus[productId1];
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

            /** get the component instance rendered previously */
            const component = Extend.buttons.instance('#extend-offer');
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
                            addWarranty(plan,sku)
                        } else {
                            $("input[name^='warranty']").remove();
                        }
                        $('#product_addtocart_form').submit();
                    }
                });
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