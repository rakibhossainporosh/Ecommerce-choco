<?php

return [
    'column_manager' => [
        'actions' => [
            'reorder' => [
                'label' => 'কলাম সাজান',
            ],
        ],
    ],

    'columns' => [
        'actions' => [
            'label' => 'কাজ',
        ],
        'icon' => [
            'boolean' => [
                'true' => 'হ্যাঁ',
                'false' => 'না',
            ],
        ],
    ],

    'actions' => [
        'reorder_record' => [
            'label' => ':key সারি সাজান',
        ],
        'toggle_record_content' => [
            'label' => ':key খুলুন বা গুটিয়ে নিন',
        ],
    ],

    'loading' => 'লোড হচ্ছে...',

    'reorder_indicator' => 'সাজাতে সারি টেনে এনে ছেড়ে দিন।',

    'result_count' => '{0} কিছু পাওয়া যায়নি|{1} :countটি ফলাফল|[2,*] :countটি ফলাফল',
];
