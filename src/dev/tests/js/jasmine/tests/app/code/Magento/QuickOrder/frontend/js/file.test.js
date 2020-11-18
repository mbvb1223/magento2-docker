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
        params,
        obj;

    describe('Magento_QuickOrder/js/file', function () {

        beforeEach(function (done) {
            injector.mock(mocks);
            injector.require(['Magento_QuickOrder/js/file'], function (File) {
                params = {
                    options: {
                        urlSku: ''
                    }
                };
                obj = new File(params);
                done();
            });

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

        describe('"_displaySkus" method', function () {
            it('Check for defined', function () {
                expect(obj._displaySkus).toBeDefined();
                expect(obj._displaySkus).toEqual(jasmine.any(Function));
            });

            it('Check post request sending successfully', function () {
                var contents = 'sku,qty\nvalue1,1\nvalue2,1',
                    localParams = {
                        items: JSON.stringify([
                            {
                                'sku': 'value1',
                                'qty': '1'
                            },
                            {
                                'sku': 'value2',
                                'qty': '1'
                            }
                        ])
                    };

                obj._displaySkus(contents);
                expect(jQuery.post).toHaveBeenCalledWith('', localParams);
            });
        });
    });
});
