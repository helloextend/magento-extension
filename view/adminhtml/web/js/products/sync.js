define(
    [
        'jquery',
        'Magento_Ui/js/modal/alert',
        'mage/translate'
    ],
    function ($, alert, $t) {
        'use strict';

        var currentBatchesProcessed = 1;
        var totalBatches = 0;
        var shouldAbort = false;
        var synMsg = $("#sync-msg");
        var cancelSync = $("#cancel_sync");

        function restore(button) {
            button.text('Sync Products');
            button.removeClass("syncing");
            button.attr("disabled", false);
            synMsg.show();
            cancelSync.hide();
            currentBatchesProcessed = 1;
            totalBatches = 0;
            shouldAbort = false;
        }

        async function syncAllProducts(url, scope, scopeId, button) {
            do {
                var data = await batchBeingProcessed(shouldAbort, url, scope, scopeId).then(data => {
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
                if (currentBatchesProcessed > totalBatches) {
                    if (totalBatches > 0) {
                        $("#sync-time").text(data.msg);
                    }
                }
            } while (currentBatchesProcessed <= totalBatches);
            restore(button);
        }

        function batchBeingProcessed(shouldAbort, url, scope, scopeId) {
            if (!shouldAbort) {
                return new Promise((resolve, reject) => {
                    $.get({
                        url: url,
                        dataType: 'json',
                        async: true,
                        data: {
                            currentBatchesProcessed: currentBatchesProcessed,
                            scope: scope,
                            scopeId: scopeId
                        },
                        success: function (data) {
                            resolve(data)
                        },
                        error: function (data) {
                            reject(data);
                        }
                    })
                })
            } else {
                return new Promise((resolve, reject) => {
                    resolve({
                        'totalBatches': 0,
                        'currentBatchesProcessed': 1
                    });
                })
            }
        }

        $.widget('extend.productSync', {
            options: {
                url: '',
                scope: '',
                scopeId: ''
            },

            _create: function () {
                this._super();
                this._bind();
            },

            _bind: function () {
                $(this.element).click(this.syncProducts.bind(this));
                var cancelSync = $("#cancel_sync");
                $(cancelSync).bind("click", function () {
                    shouldAbort = true
                });

            },
            syncProducts: function (event) {
                event.preventDefault();
                var button = $(this.element);
                button.text('Sync in progress...');
                button.addClass("syncing");
                button.attr("disabled", true);

                synMsg.hide();
                cancelSync.show();

                syncAllProducts(this.options.url, this.options.scope, this.options.scopeId, button);

            }
        });

        return $.extend.productSync;
    });
