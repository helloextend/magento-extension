define([
    'jquery',
    'underscore'
], function ($, _) {

    return function (params) {
        /*VK compatibility
        Extend.buttons.render('#extend-offer', {
            referenceId: params.productSku
        });*/
        Extend.buttons.render('#extend-offer', {referenceId: params.productSku}, function(){
            //select extend iframe
            var iframe = document.querySelector('#extend-offer iframe')
            //if we have an iframe we will select that iframes document
            var extendDocument = iframe ? iframe.contentWindow.document : null;
            //if we have a document, we will select all of the buttons, and then we can style those buttons accordingly
            var offerBtns = extendDocument ? extendDocument.querySelectorAll('.btn-offer') : null;
            if(offerBtns){
              offerBtns.forEach(function(btn) {
                //if you are overriding any existing styling be sure to use !important
                btn.style = "padding: 0.3rem !important"
              })
            }
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
                    const productConfig1 = $('#product_addtocart_form').data('mageConfigurable').options.spConfig;
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
            // const component = Extend.buttons.instance('#extend-offer');
            // component.setActiveProduct(productSku);
            const component = Extend.buttons.instance('#extend-offer');
            if(component){
                component.destroy();

                Extend.buttons.render('#extend-offer', {referenceId: productSku}, function(){
                    //select extend iframe
                    var iframe = document.querySelector('#extend-offer iframe')
                    //if we have an iframe we will select that iframes document
                    var extendDocument = iframe ? iframe.contentWindow.document : null;
                    //if we have a document, we will select all of the buttons, and then we can style those buttons accordingly
                    var offerBtns = extendDocument ? extendDocument.querySelectorAll('.btn-offer') : null;
                    if(offerBtns){
                        offerBtns.forEach(function(btn) {
                            //if you are overriding any existing styling be sure to use !important
                            btn.style = "padding: 0.3rem !important"
                        });
                    }
                });
            }
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
                // $('#product_addtocart_form').submit();
            } else {
                /*VK compatibility*/
                $("input[name^='warranty']").remove();

                /*Extend.modal.open({
                    referenceId: sku,
                    onClose: function (plan) {
                        if (plan) {
                            addWarranty(plan,sku)
                        } else {
                            $("input[name^='warranty']").remove();
                        }
                        $('#product_addtocart_form').submit();
                    }
                });*/
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