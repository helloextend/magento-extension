var config = {
    map: {
        '*': {
            extendWarranties: 'Extend_Warranty/js/warranties',
            cartAddWarranty: 'Extend_Warranty/js/cart/addToCart',
            cartAddLeadWarranty : 'Extend_Warranty/js/cart/addLead',
            'Magento_Catalog/template/product/image_with_borders.html':
                'Extend_Warranty/template/product/image_with_borders.html'
        },
    },
    config: {
        mixins: {
            'Magento_Checkout/js/view/minicart': {
                'Extend_Warranty/js/view/minicart_mixin': true
            }
        }
    }
};
