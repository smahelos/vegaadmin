<?php

return [
    'entity' => [
        'singular' => 'Status',
        'plural' => 'Statuses',
    ],
    'fields' => [
        'name' => 'Name',
        'slug' => 'Identifier',
        'color' => 'Color',
        'description' => 'Description',
        'is_active' => 'Active',
        'category_id' => 'Category',
    ],
    'statuses' => [
        'approved' => 'Approved',
        'cancelled' => 'Cancelled',
        'cancelled-expense' => 'Cancelled Expense',
        'cancelled-status' => 'Cancelled Status',
        'draft' => 'Draft',
        'in-review' => 'In Review',
        'overdue' => 'Overdue',
        'paid' => 'Paid',
        'paid-expense' => 'Paid Expense',
        'paid-status' => 'Paid Status',
        'partially-paid' => 'Partially Paid',
        'pending' => 'Pending',
        'pending-payment' => 'Pending Payment',
        'rejected' => 'Rejected',
    ],
    'filters' => [
        'category' => 'Filter by Category',
    ],
    'categories' => [
        'entity' => [
            'singular' => 'Status category',
            'plural' => 'Status categories',
        ],
        'fields' => [
            'name' => 'Name',
            'slug' => 'Identifier',
            'description' => 'Description',
            'statuses_count' => 'Number of Statuses',
            'statuses' => 'List of Statuses',
        ],
        'hints' => [
            'slug' => 'Will be automatically generated from the name if not provided',
        ],
    ],
];
