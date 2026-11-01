<?php

return [
    'subject' => [
        'customer' => [
            'placed' => 'We received your order :number',
            'confirmed' => 'Your order :number is confirmed',
            'processing' => 'We are preparing your order :number',
            'shipped' => 'Your order :number is on the way',
            'delivered' => 'Your order :number has been delivered',
            'cancelled' => 'Your order :number has been cancelled',
            'returned' => 'Your return for order :number is updated',
            'refunded' => 'Your refund for order :number is updated',
        ],
        'admin' => [
            'placed' => 'New order :number',
            'confirmed' => 'Order :number is confirmed',
            'processing' => 'Order :number is being prepared',
            'shipped' => 'Order :number has been shipped',
            'delivered' => 'Order :number has been delivered',
            'cancelled' => 'Order :number has been cancelled',
            'returned' => 'Order :number has a return update',
            'refunded' => 'Order :number has a refund update',
        ],
    ],
    'intro' => [
        'customer' => [
            'placed' => ':name, we have received your order.',
            'confirmed' => ':name, your order is confirmed.',
            'processing' => ':name, we are preparing your order.',
            'shipped' => ':name, your order is on the way.',
            'delivered' => ':name, your order has been delivered.',
            'cancelled' => ':name, your order has been cancelled.',
            'returned' => ':name, your return has been updated.',
            'refunded' => ':name, your refund has been updated.',
        ],
        'admin' => [
            'placed' => ':name placed a new order.',
            'confirmed' => 'Order for :name is confirmed.',
            'processing' => 'Order for :name is being prepared.',
            'shipped' => 'Order for :name has been shipped.',
            'delivered' => 'Order for :name has been delivered.',
            'cancelled' => 'Order for :name has been cancelled.',
            'returned' => 'Order for :name has a return update.',
            'refunded' => 'Order for :name has a refund update.',
        ],
    ],
];
