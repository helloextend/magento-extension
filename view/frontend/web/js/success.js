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
    'domReady!'
], function($) {
    'use strict';

    return function(params) {
        if (window.Extend && typeof window.Extend.trackCartCheckout === 'function') {
            window.Extend.trackCartCheckout({
                'cartTotal': params.cartTotal
            });
        }
    };
});
