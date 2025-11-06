<?php

return [

    'orders' => [
        'label' => 'Import Orders',
        'permission_required' => 'import-orders',

        'files' => [
            'orders_file' => [
                'label' => 'Orders File',

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

            'customers_file' => [
                'label' => 'Customer Details File',

                'headers_to_db' => [
                    'customer_id' => [
                        'label' => 'Customer ID',
                        'type' => 'string',
                        'validation' => ['required'],
                    ],
                    'customer_name' => [
                        'label' => 'Customer Name',
                        'type' => 'string',
                        'validation' => ['required'],
                    ],
                    'email' => [
                        'label' => 'Email Address',
                        'type' => 'email',
                        'validation' => ['required', 'email'],
                    ],
                    'phone' => [
                        'label' => 'Phone Number',
                        'type' => 'string',
                        'validation' => ['nullable'],
                    ],
                    'address' => [
                        'label' => 'Shipping Address',
                        'type' => 'string',
                        'validation' => ['required'],
                    ],
                    'city' => [
                        'label' => 'City',
                        'type' => 'string',
                        'validation' => ['required'],
                    ],
                    'country' => [
                        'label' => 'Country',
                        'type' => 'string',
                        'validation' => ['required'],
                    ],
                ],

                'update_or_create' => ['customer_id'],
            ],

            'tracking_file' => [
                'label' => 'Tracking Information File',

                'headers_to_db' => [
                    'so_num' => [
                        'label' => 'SO Number',
                        'type' => 'string',
                        'validation' => ['required'],
                    ],
                    'tracking_number' => [
                        'label' => 'Tracking Number',
                        'type' => 'string',
                        'validation' => ['required'],
                    ],
                    'carrier' => [
                        'label' => 'Shipping Carrier',
                        'type' => 'string',
                        'validation' => ['required', 'in' => ['UPS', 'FedEx', 'DHL', 'USPS']],
                    ],
                    'shipped_date' => [
                        'label' => 'Shipped Date',
                        'type' => 'date',
                        'validation' => ['required'],
                    ],
                    'estimated_delivery' => [
                        'label' => 'Estimated Delivery Date',
                        'type' => 'date',
                        'validation' => ['nullable'],
                    ],
                ],

                'update_or_create' => ['so_num', 'tracking_number'],
            ],
        ],
    ],

    'inventory' => [
        'label' => 'Import Inventory Data',
        'permission_required' => 'import-inventory',

        'files' => [
            'products_file' => [
                'label' => 'Products Master File',

                'headers_to_db' => [
                    'sku' => [
                        'label' => 'Product SKU',
                        'type' => 'string',
                        'validation' => ['required'],
                    ],
                    'product_name' => [
                        'label' => 'Product Name',
                        'type' => 'string',
                        'validation' => ['required'],
                    ],
                    'category' => [
                        'label' => 'Category',
                        'type' => 'string',
                        'validation' => ['required'],
                    ],
                    'weight' => [
                        'label' => 'Weight (kg)',
                        'type' => 'double',
                        'validation' => ['required'],
                    ],
                    'dimensions' => [
                        'label' => 'Dimensions (LxWxH)',
                        'type' => 'string',
                        'validation' => ['nullable'],
                    ],
                ],

                'update_or_create' => ['sku'],
            ],

            'stock_levels_file' => [
                'label' => 'Stock Levels File',

                'headers_to_db' => [
                    'sku' => [
                        'label' => 'Product SKU',
                        'type' => 'string',
                        'validation' => ['required'],
                    ],
                    'warehouse_location' => [
                        'label' => 'Warehouse Location',
                        'type' => 'string',
                        'validation' => ['required'],
                    ],
                    'quantity_on_hand' => [
                        'label' => 'Quantity on Hand',
                        'type' => 'integer',
                        'validation' => ['required', 'min:0'],
                    ],
                    'reserved_quantity' => [
                        'label' => 'Reserved Quantity',
                        'type' => 'integer',
                        'validation' => ['required', 'min:0'],
                    ],
                    'reorder_point' => [
                        'label' => 'Reorder Point',
                        'type' => 'integer',
                        'validation' => ['required', 'min:0'],
                    ],
                    'last_counted_date' => [
                        'label' => 'Last Counted Date',
                        'type' => 'date',
                        'validation' => ['nullable'],
                    ],
                ],

                'update_or_create' => ['sku', 'warehouse_location'],
            ],
        ],
    ],

    'suppliers' => [
        'label' => 'Import Suppliers',
        'permission_required' => 'import-suppliers',

        'files' => [
            'suppliers_file' => [
                'label' => 'Suppliers File',

                'headers_to_db' => [
                    'supplier_id' => [
                        'label' => 'Supplier ID',
                        'type' => 'string',
                        'validation' => ['required', 'unique:suppliers,supplier_id'],
                    ],
                    'name' => [
                        'label' => 'Supplier Name',
                        'type' => 'string',
                        'validation' => ['required'],
                    ],
                    'contact_person' => [
                        'label' => 'Contact Person',
                        'type' => 'string',
                        'validation' => ['nullable'],
                    ],
                    'email' => [
                        'label' => 'Email Address',
                        'type' => 'string',
                        'validation' => ['nullable', 'email'],
                    ],
                    'phone' => [
                        'label' => 'Phone Number',
                        'type' => 'string',
                        'validation' => ['nullable'],
                    ],
                    'country' => [
                        'label' => 'Country',
                        'type' => 'string',
                        'validation' => ['required'],
                    ],
                    'city' => [
                        'label' => 'City',
                        'type' => 'string',
                        'validation' => ['nullable'],
                    ],
                    'address' => [
                        'label' => 'Address',
                        'type' => 'string',
                        'validation' => ['nullable'],
                    ],
                ],

                'update_or_create' => ['supplier_id'],
            ],
        ],
    ],

];