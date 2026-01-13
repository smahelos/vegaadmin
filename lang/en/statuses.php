<?php

return [
    // Existing status labels
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
    'unpaid' => 'Unpaid',

    // Fields & validation used in StatusRequest tests
    'fields' => [
        'name' => 'Name',
        'slug' => 'Slug',
        'color' => 'Color',
        'description' => 'Description',
        'is_active' => 'Active',
        'category' => 'Category',
    ],

    'validation' => [
        'name_required' => 'The name field is required.',
        'slug_required' => 'The slug field is required.',
        'slug_unique' => 'The slug has already been taken.',
    ],
];
