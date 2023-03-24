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

    var currentBatchesProcessed = 1;
    var syncedOrderAmount = 0;
    var totalBatches = 0;
    var shouldAbort = false;
    var synMsg = $("#orders-sync-msg");
    var cancelSync = $("#orders_cancel_sync");
    var resetFlagUrl = '';

    function restore(button) {
        button.text('Send Historical Orders');
        button.removeClass("syncing");
        button.attr("disabled", false);
        synMsg.show();
        cancelSync.hide();
        currentBatchesProcessed = 1;
        totalBatches = 0;
        shouldAbort = false;
    }

    async function syncAllHistoricalOrders(url, button) {
        do {
            var data = await batchBeingProcessed(shouldAbort, url).then(data => {
                return data;            //success
            }, data => {
                return {                //fail
                    'totalBatches': 0,
                    'currentBatchesProcessed': 1
                };
            }).catch(e => {
                console.log(e);
            });
            currentBatchesProcessed = data.currentBatchesProcessed;
            totalBatches = data.totalBatches;

            if (data.ordersCount) {
                syncedOrderAmount += data.ordersCount;
            }

            if (!syncedOrderAmount && data.message) {
                synMsg.text(data.message);
            } else if (syncedOrderAmount > 0) {
                synMsg.text('Synced ' + syncedOrderAmount + ' Orders');
            }

        } while (currentBatchesProcessed <= totalBatches);
    }

    function batchBeingProcessed(shouldAbort, url) {
        if (!shouldAbort) {
            return new Promise((resolve, reject) => {
                $.get({
                    url: url,
                    dataType: 'json',
                    async: true,
                    data: {
                        currentBatchesProcessed: currentBatchesProcessed
                    },
                    success: function (data) {
                        if (data.hasOwnProperty('status') && data.status === 'COMPLETE') {
                            resolve(data)
                        }
                        resolve(data)
                    },
                    error: function (data) {
                        reject(data);
                    }
                })
            })
        } else {
            return new Promise((resolve, reject) => {
                var data = {
                    'totalBatches': 0,
                    'currentBatchesProcessed': 1
                };
                $.get({
                    url: resetFlagUrl,
                    dataType: 'json',
                    success: function (data) {
                        console.log(data.message);
                        resolve(data);
                    },
                    error: function (data) {
                        reject(data);
                    }
                })
            })
        }
    }

    $.widget('extend.ordersSync', {
        options: {
            syncUrls: [],
            resetFlagUrl: ''
        },

        _create: function () {
            this._super();
            this._bind();
        },

        _bind: function () {
            $(this.element).click(this.syncHistoricalOrders.bind(this));
            var self = this;
            $(cancelSync).bind("click", function () {
                shouldAbort = true;
                resetFlagUrl = self.options.resetFlagUrl;
            });

        },
        syncHistoricalOrders: async function (event) {
            event.preventDefault();
            var button = $(this.element);
            syncedOrderAmount = 0;
            button.text('Sync in progress...');
            button.addClass("syncing");
            button.attr("disabled", true);

            synMsg.hide();
            cancelSync.show();

            for (let url of this.options.syncUrls) {
                let storeSync = new Promise(function (resolve) {
                    resolve(syncAllHistoricalOrders(url, button));
                });
                await storeSync;
            }
            restore(button);
        }
    });

    return $.extend.ordersSync;
});
