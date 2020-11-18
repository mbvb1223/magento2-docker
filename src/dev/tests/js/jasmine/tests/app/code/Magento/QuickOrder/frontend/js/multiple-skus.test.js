/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/* eslint-disable max-nested-callbacks */
define([
    'jquery',
    'squire'
], function ($, Squire) {
    'use strict';

    var injector = new Squire(),
        mocks = {
            'Magento_QuickOrder/js/item-table/mass-add-rows': {
                addNewRows: jasmine.createSpy().and.returnValue({
                    done: jasmine.createSpy().and.returnValue($.Deferred().promise())
                })
            }
        },
        tplElement = $('<textarea name="multiple_skus" ' +
            'data-role="multiple-skus" id="multiple_skus" class="input-text"></textarea>'),
        params,
        obj;

    describe('Magento_QuickOrder/js/multiple-skus', function () {

        beforeEach(function (done) {
            injector.mock(mocks);
            injector.require(['Magento_QuickOrder/js/multiple-skus'], function (MultipleSku) {
                params = {
                    options: {
                        urlSku: '',
                        textArea: '[data-role="multiple-skus"]'
                    }
                };
                obj = new MultipleSku(params);
                done();
            });

            tplElement.appendTo(document.body);

            jQuery.post = jasmine.createSpy().and.callFake(function () {
                var d = $.Deferred();

                d.resolve([
                    {
                        items: {
                            'value1': {
                                sku: 'value1'
                            }
                        }
                    },
                    'success'
                ]);

                return d.promise();
            });
        });

        describe('"_moveSkusToSingleInputs" method', function () {
            it('Check for defined', function () {
                expect(obj._moveSkusToSingleInputs).toBeDefined();
                expect(obj._moveSkusToSingleInputs).toEqual(jasmine.any(Function));
            });

            it('Check post request sending', function () {
                var localParams = {
                    items: JSON.stringify([
                        {
                            'sku': 'value1',
                            'qty': 1
                        }
                    ])
                };

                tplElement.val('value1');
                obj._moveSkusToSingleInputs();
                expect(jQuery.post).toHaveBeenCalledWith('', localParams);
            });
        });
    });
});
