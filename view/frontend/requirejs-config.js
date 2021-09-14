/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2021 Extend Inc. (https://www.extend.com/)
 */
var config = {
    map: {
        '*': {
            extendWarranties: 'Extend_Warranty/js/warranties',
            cartAddWarranty: 'Extend_Warranty/js/cart/addToCart',
            'Magento_Catalog/template/product/image_with_borders.html':
                'Extend_Warranty/template/product/image_with_borders.html'
        }
    },
    config: {
        mixins: {
            'Rokanthemes_AjaxSuite/js/ajaxsuite': {
                'Extend_Warranty/js/ajaxsuite-mixin': true
            }
        }
    }
};