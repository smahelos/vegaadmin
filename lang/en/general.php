<?php

return [
    // General translations for the application
    'logo_alt' => 'Vega Invoices',
    'currency' => '$',
    'yes' => 'Yes',
    'no' => 'No',

    // Months for date formatting
    'months' => [
        1 => 'January',
        2 => 'February',
        3 => 'March',
        4 => 'April',
        5 => 'May',
        6 => 'June',
        7 => 'July',
        8 => 'August',
        9 => 'September',
        10 => 'October',
        11 => 'November',
        12 => 'December',
    ],

    'navigation' => [
        'dashboard' => 'Dashboard',
        'invoices' => 'Invoices',
        'clients' => 'Clients',
        'suppliers' => 'Suppliers',
        'profile' => 'Profile',
        'products' => 'Products',
        'create_invoice' => 'Create Invoice',
        'pages' => 'Pages',
    ],

    'placeholders' => [
        'select_client' => 'Select a client or create a new one...',
        'select_status' => 'Select status...',
        'select_supplier' => 'Select a supplier or create a new one...',
        'select_country' => 'Select country...',
        'payment_method_select' => 'Select payment method',
        'due_in_select' => 'Select due date',
        'suggested_number_desc' => 'You can change the suggested invoice number according to your needs',
        'not_available' => '—',
        'not_specified' => 'Not specified',
        'yes' => 'Yes',
        'no' => 'No',
    ],

    'joins' => [
        'and' => 'and',
    ],

    'empty' => [
        'not_specified' => 'Not specified.',
        'no_items' => 'No items',
        'no_results' => 'No results',
    ],

    'pagination' => [
        'navigation' => 'Navigation',
        'shown' => 'Showing',
        'of' => 'of',
        'up_to' => 'to',
        'items' => 'items',
        'previous' => 'Previous',
        'next' => 'Next',
    ],

    'actions' => [
        'create' => 'Create',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'view' => 'View',
        'save' => 'Save',
        'cancel' => 'Cancel',
        'confirm' => 'Confirm',
        'back' => 'Back',
        'download' => 'Download',
        'print' => 'Print',
        'send' => 'Send',
        'actions' => 'Actions',
    ],

    'common' => [
        'created_at' => 'Created At',
        'updated_at' => 'Updated At',
    ],

    // Entity Limits System
    'entity_limits' => [
        'permission_name' => 'Permission Name',
        'limit_value' => 'Limit Value',
        'description' => 'Description',
        'is_active' => 'Active Status',
    'user_id' => 'User',
    'entity_type' => 'Entity Type',
    'metric_type' => 'Metric Type',
    'period_type' => 'Period Type',
    'period_start' => 'Period Start',
    'period_end' => 'Period End',
    'current_value' => 'Current Value',
        'exceeded' => [
            'title' => 'Limit Exceeded',
            'message' => 'You have reached your limit for :entity_type. Maximum allowed: :max_count per :period_type.',
            'current_usage' => 'Current usage: :current_count',
            'period_info' => 'Period: :period_start to :period_end',
            'contact_admin' => 'Please contact your administrator to increase your limit.',
        ],
        'entities' => [
            'invoice' => 'invoices',
            'client' => 'clients',
            'supplier' => 'suppliers',
            'product' => 'products',
            'expense' => 'expenses',
        ],
        'periods' => [
            'daily' => 'day',
            'weekly' => 'week',
            'monthly' => 'month',
            'yearly' => 'year',
            'lifetime' => 'lifetime',
        ],
        'limit_types' => [
            'count' => 'count',
            'value' => 'value',
            'size' => 'size',
        ],
    ],
];
