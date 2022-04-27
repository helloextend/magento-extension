var config = {
    map: {
        '*': {
            extendWarrantyOffers: 'Extend_Warranty/js/warranty-offers-base',
            simpleProductWarranty: 'Extend_Warranty/js/product/simple-warranty',
            configurableProductWarranty: 'Extend_Warranty/js/product/configurable-warranty',
            groupedProductWarranty: 'Extend_Warranty/js/product/grouped-warranty',

            cartItemWarranty: 'Extend_Warranty/js/cart/cart-item-warranty',
            leadOrderWarranty: 'Extend_Warranty/js/order/lead-order-warranty',
            postPurchaseLeadWarranty: 'Extend_Warranty/js/order/post-purchase-lead-warranty',

            'Magento_Catalog/template/product/image_with_borders.html':
                'Extend_Warranty/template/product/image_with_borders.html'
        }
    },
    config: {
        mixins: {
            'Magento_Checkout/js/view/minicart': {
                'Extend_Warranty/js/view/minicart-mixin': true
            },
            'Magento_Checkout/js/sidebar': {
                'Extend_Warranty/js/sidebar-mixin': true
            },
        }
    }
};
