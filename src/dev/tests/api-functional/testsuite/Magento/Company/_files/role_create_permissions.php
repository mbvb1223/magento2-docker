<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

return [
    [
        'permissions' => [
            ['resource_id' => 'Magento_Company::index', 'permission' => 'deny'],
            ['resource_id' => 'Magento_Sales::all', 'permission' => 'deny'],
            ['resource_id' => 'Magento_Sales::place_order', 'permission' => 'deny'],
            ['resource_id' => 'Magento_Sales::payment_account', 'permission' => 'deny'],
            ['resource_id' => 'Magento_Sales::view_orders', 'permission' => 'deny'],
            ['resource_id' => 'Magento_Sales::view_orders_sub', 'permission' => 'deny'],
            ['resource_id' => 'Magento_NegotiableQuote::all', 'permission' => 'deny'],
            ['resource_id' => 'Magento_NegotiableQuote::view_quotes', 'permission' => 'deny'],
            ['resource_id' => 'Magento_NegotiableQuote::manage', 'permission' => 'deny'],
            ['resource_id' => 'Magento_NegotiableQuote::checkout', 'permission' => 'deny'],
            ['resource_id' => 'Magento_NegotiableQuote::view_quotes_sub', 'permission' => 'deny'],
            ['resource_id' => 'Magento_PurchaseOrder::all','permission' => 'deny'],
            ['resource_id' => 'Magento_PurchaseOrder::view_purchase_orders','permission' => 'deny'],
            ['resource_id' => 'Magento_PurchaseOrder::view_purchase_orders_for_subordinates','permission' => 'deny'],
            ['resource_id' => 'Magento_PurchaseOrder::view_purchase_orders_for_company','permission' => 'deny'],
            ['resource_id' => 'Magento_PurchaseOrder::autoapprove_purchase_order','permission' => 'deny'],
            ['resource_id' => 'Magento_PurchaseOrderRule::super_approve_purchase_order','permission' => 'deny'],
            ['resource_id' => 'Magento_PurchaseOrderRule::view_approval_rules','permission' => 'deny'],
            ['resource_id' => 'Magento_PurchaseOrderRule::manage_approval_rules','permission' => 'deny'],
            ['resource_id' => 'Magento_Company::view', 'permission' => 'deny'],
            ['resource_id' => 'Magento_Company::view_account', 'permission' => 'deny'],
            ['resource_id' => 'Magento_Company::edit_account', 'permission' => 'deny'],
            ['resource_id' => 'Magento_Company::view_address', 'permission' => 'deny'],
            ['resource_id' => 'Magento_Company::edit_address', 'permission' => 'deny'],
            ['resource_id' => 'Magento_Company::contacts', 'permission' => 'deny'],
            ['resource_id' => 'Magento_Company::payment_information', 'permission' => 'deny'],
            ['resource_id' => 'Magento_Company::shipping_information', 'permission' => 'deny'],
            ['resource_id' => 'Magento_Company::user_management', 'permission' => 'deny'],
            ['resource_id' => 'Magento_Company::roles_view', 'permission' => 'deny'],
            ['resource_id' => 'Magento_Company::roles_edit', 'permission' => 'deny'],
            ['resource_id' => 'Magento_Company::users_view', 'permission' => 'deny'],
            ['resource_id' => 'Magento_Company::users_edit', 'permission' => 'deny'],
            ['resource_id' => 'Magento_Company::credit', 'permission' => 'deny'],
            ['resource_id' => 'Magento_Company::credit_history', 'permission' => 'deny'],
        ],
    ],
];
