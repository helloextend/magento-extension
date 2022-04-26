/**
 * Extend Warranty
 *
 * @author      Extend Magento Team <magento@guidance.com>
 * @category    Extend
 * @package     Warranty
 * @copyright   Copyright (c) 2022 Extend Inc. (https://www.extend.com/)
 */

define([
    'jquery',
    'Magento_Ui/js/modal/alert',
    'mage/translate'
], function ($, alert, $t) {
    'use strict';

    $.widget('extend.ordersSync', {
        options: {
            url: '',
            resetFlagUrl: ''
        },

        _create: function () {
            this._super();
        }
    });

    return $.extend.ordersSync;
});
