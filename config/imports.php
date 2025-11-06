<?php

return [

    'orders' => [
        'label' => 'Import Orders',
        'permission_required' => 'import-orders',

        'files' => [
            'file1' => [
                'label' => 'File 1',

                'headers_to_db' => [
                    'order_date' => [
                        'label' => 'Order Date',
                        'type' => 'date',
                        'validation' => ['required'],
                    ],
                    'channel' => [
                        'label' => 'Channel',
                        'type' => 'string',
                        'validation' => ['required', 'in' => ['PT', 'Amazon']],
                    ],
                    'sku' => [
                        'label' => 'SKU',
                        'type' => 'string',
                        'validation' => ['required', 'exists' => [
                            'table' => 'products',
                            'column' => 'sku'
                        ]],
                    ],
                    'item_description' => [
                        'label' => 'Item Description',
                        'type' => 'string',
                        'validation' => ['nullable'],
                    ],
                    'origin' => [
                        'label' => 'Origin',
                        'type' => 'string',
                        'validation' => ['required'],
                    ],
                    'so_num' => [
                        'label' => 'SO#',
                        'type' => 'string',
                        'validation' => ['required'],
                    ],
                    'cost' => [
                        'label' => 'Cost',
                        'type' => 'double',
                        'validation' => ['required'],
                    ],
                    'shipping_cost' => [
                        'label' => 'Shipping Cost',
                        'type' => 'double',
                        'validation' => ['required'],
                    ],
                    'total_price' => [
                        'label' => 'Total Price',
                        'type' => 'double',
                        'validation' => ['required'],
                    ],
                ],

                'update_or_create' => ['so_num', 'sku'],
            ],
        ],
    ],
];