<?php

return [
    'entity' => [
        'singular' => 'Role limit',
        'plural' => 'Role limits',
    ],
    'fields' => [
        'role' => 'Role',
        'invoices' => 'Invoices per month',
        'expenses' => 'Expenses per month',
        'businesses' => 'Businesses',
        'contacts' => 'Contacts',
        'products' => 'Products',
    ],
    'hints' => [
        'role' => 'Only web roles that have no limits yet.',
        'quota' => 'Number created per calendar month, counted from the role assignment at the earliest.',
        'capacity' => 'Number of active records at a time.',
    ],
    'statuses' => [
        'limited' => 'Number',
        'unlimited' => 'Unlimited',
        'not_allowed' => 'Not allowed',
    ],
    'usage' => [
        'title' => 'Plan usage',
        'invoices' => 'Invoices this month',
        'expenses' => 'Expenses this month',
        'businesses' => 'Active businesses',
        'contacts' => 'Active contacts',
        'products' => 'Products',
        'approaching' => 'You are approaching the limit.',
        'reached' => 'The limit has been reached.',
    ],
];
