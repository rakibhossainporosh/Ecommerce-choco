<?php

return [
    'cod' => [
        'label' => 'Cash on delivery',
        'description' => 'Pay with cash when the parcel arrives.',
    ],
    'bkash' => [
        'label' => 'bKash',
        'description' => 'Send the total to the bKash number. We pack the order after the payment is confirmed.',
    ],
    'nagad' => [
        'label' => 'Nagad',
        'description' => 'Send the total to the Nagad number. We pack the order after the payment is confirmed.',
    ],
    'rocket' => [
        'label' => 'Rocket',
        'description' => 'Send the total to the Rocket number. We pack the order after the payment is confirmed.',
    ],
    'bank_transfer' => [
        'label' => 'Bank transfer',
        'description' => 'Transfer the total to the bank account. We pack the order after the payment is confirmed.',
    ],
    'sslcommerz' => [
        'label' => 'Card / mobile banking',
        'description' => 'Pay online with card, bKash, Nagad, or internet banking. You come back here after payment.',
    ],
    'bkash_checkout' => [
        'label' => 'bKash checkout',
        'description' => 'Pay on the official bKash page. The order is confirmed after bKash accepts the payment.',
    ],
    'nagad_checkout' => [
        'label' => 'Nagad checkout',
        'description' => 'Pay on the official Nagad page. The order is confirmed after Nagad accepts the payment.',
    ],
];
