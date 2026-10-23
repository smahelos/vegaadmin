<?php

return [
    'entity' => [
        'singular' => 'Entity limit',
        'plural' => 'Entity limits',
    ],
    'fields' => [
        'permission_name' => 'Permission',
        'entity_type' => 'Entity type',
        'metric_type' => 'Limit type',
        'period_type' => 'Period',
        'limit_value' => 'Limit value',
        'description' => 'Description',
        'is_active' => 'Active',
    ],
    'hints' => [
        'permission_name' => 'The permission the limit applies to, or Anonymous users for visitors who are not logged in.',
        'limit_value' => 'The highest value allowed per period: a number of records for count, a monetary amount for value, bytes for size.',
        'description' => 'An optional description of the limit.',
        'is_active' => 'Whether the limit is currently enforced.',
    ],
    'labels' => [
        'anonymous_users' => 'Anonymous users',
    ],
    'entity_types' => [
        'invoice' => [
            'singular' => 'Invoice',
            'plural' => 'Invoices',
        ],
        'client' => [
            'singular' => 'Client',
            'plural' => 'Clients',
        ],
        'supplier' => [
            'singular' => 'Supplier',
            'plural' => 'Suppliers',
        ],
        'product' => [
            'singular' => 'Product',
            'plural' => 'Products',
        ],
        'expense' => [
            'singular' => 'Expense',
            'plural' => 'Expenses',
        ],
        'user' => [
            'singular' => 'User',
            'plural' => 'Users',
        ],
        'page' => [
            'singular' => 'Page',
            'plural' => 'Pages',
        ],
    ],
    'metric_types' => [
        'count' => 'Count',
        'value' => 'Monetary value',
        'size' => 'File size',
    ],
    'periods' => [
        'daily' => 'Daily',
        'weekly' => 'Weekly',
        'monthly' => 'Monthly',
        'yearly' => 'Yearly',
        'lifetime' => 'Lifetime',
    ],
    'statuses' => [
        'success' => 'Good',
        'warning' => 'Near limit',
        'danger' => 'Limit exceeded',
        'not_allowed' => 'Creating is not allowed',
    ],
    'widget' => [
        'title' => 'Entity Limits',
        'usage' => 'Usage',
        'unlimited' => 'Unlimited',
        'show_details' => 'Show details',
        'limit_exceeded' => 'Limit exceeded',
        'approaching_limit' => 'Approaching limit',
        'loading' => 'Loading...',
    ],
    'modal' => [
        'title' => 'Entity Limits Details',
        'loading' => 'Loading...',
        'overview' => 'Overview',
        'current_period' => 'Current Period',
        'recent_activity' => 'Recent Activity',
        'error' => 'Error loading data',
        'retry' => 'Try again',
        'refresh' => 'Refresh',
        'close' => 'Close',
        'current_usage' => 'Current Usage',
        'limit_info' => 'Limit Information',
        'usage_percentage' => 'Usage Percentage',
        'today' => 'Today',
        'this_week' => 'This Week',
        'this_month' => 'This Month',
        'total' => 'Total',
        'no_activity' => 'No recent activity',
        'entity_created' => 'Entity created',
    ],
    'usages' => [
        'entity' => [
            'singular' => 'Limit usage',
            'plural' => 'Limit usages',
        ],
        'fields' => [
            'user_id' => 'User',
            'entity_type' => 'Entity type',
            'metric_type' => 'Limit type',
            'period_type' => 'Period',
            'period_start' => 'Period start',
            'period_end' => 'Period end',
            'current_value' => 'Current value',
            'last_reset_at' => 'Last reset',
            'created_at' => 'Created',
            'updated_at' => 'Updated',
        ],
        'filters' => [
            'period_range' => 'Period range',
        ],
        'labels' => [
            'anonymous' => 'Anonymous',
            'unknown_user' => 'Unknown user (ID: :id)',
        ],
    ],
];
