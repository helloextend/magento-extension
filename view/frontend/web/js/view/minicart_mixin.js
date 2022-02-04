/*
* Minicart add Warranty
*/

define([
    'jquery',
    'underscore',
    'cartAddWarranty'
], function ($, _, extend) {
    'use strict';

    var mixin = {
        addWarranty: function (params) {
            console.log('Warranty');
            console.log(params);
            return extend(params);
        },
        test: function (params) {
            console.log(params);
            console.log('Test click');
        }
    };

    return function (Minicart) {
        return Minicart.extend(mixin);
    };
});
