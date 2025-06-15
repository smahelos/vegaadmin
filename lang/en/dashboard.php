<?php

return [
    // Headings and titles
    'title' => 'Dashboard',
    'monthly_invoices' => 'Monthly Invoicing',
    'recent_clients' => 'Recent Clients',
    'recent_invoices' => 'Recent Invoices',
    'recent_suppliers' => 'Recent Suppliers',
    
    // Overview cards
    'cards' => [
        'clients_count' => 'Total Clients',
        'total_amount' => 'Total Amount',
        'suppliers_count' => 'Total Suppliers',
        'invoices_count' => 'Total Invoices',
    ],

    // Page sections
    'sections' => [
        'overview' => 'Overview',
        'recent_invoices' => 'Recent Invoices',
        'statistics' => 'Statistics',
        'quick_actions' => 'Quick Actions',
    ],
    
    // Notification and status texts
    'status' => [
        'no_email' => 'Email not provided',
        'unknown_client' => 'Unknown client',
        'no_clients' => 'You don\'t have any clients yet',
        'no_suppliers' => 'You don\'t have any suppliers yet',
        'no_invoices' => 'You don\'t have any invoices yet',
        'overdue' => 'Overdue',
        'paid' => 'Paid',
        'partially-paid' => 'Partially Paid',
        'pending' => 'Pending',
        'draft' => 'Draft',
        'cancelled' => 'Cancelled',
    ],
    
    // Action buttons and links
    'actions' => [
        'view_all_clients' => 'View All Clients',
        'view_all_invoices' => 'View All Invoices',
        'view_all_suppliers' => 'View All Suppliers',
    ],
    
    // Charts
    'charts' => [
        'billed_currency' => 'Billed ($)',
        'no_data' => 'No data',
        'amount' => 'Amount',
        'paid_currency' => 'Paid ($)',
        'invoice_status' => 'Invoice Status',
        'payment_methods' => 'Payment Methods',
        'revenue_vs_expenses' => 'Revenue vs Expenses',
        'revenue_by_client' => 'Revenue by Client',
        'expenses_by_supplier' => 'Expenses by Supplier',
        'revenue_vs_expenses_monthly' => 'Revenue vs Expenses (Monthly)',
        'revenue' => 'Revenue',
        'expenses' => 'Expenses',
    ],

    // Statistics
    'statistics' => [
        'total_income' => 'Total Income',
        'overdue_amount' => 'Overdue Amount',
        'clients_count' => 'Number of Clients',
        'invoices_count' => 'Number of Invoices',
    ],

    'filters' => [
        'date_range' => 'Date Range',
        'custom_range' => 'Custom Range',
        'last_6_months' => 'Last 6 Months',
        'last_month' => 'Last Month',
        'last_quarter' => 'Last Quarter',
        'last_year' => 'Last Year',
        'date_from' => 'Date From',
        'date_to' => 'Date To',
        'clients' => 'Clients',
        'suppliers' => 'Suppliers',
        'status' => 'Status',
    ],
];
