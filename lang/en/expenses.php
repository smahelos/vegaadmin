<?php

return [
    'sections' => [
        'document' => 'Document details',
        'amounts' => 'Amounts, VAT and currency',
        'status' => 'Status and category',
        'other' => 'Other details and attachments',
    ],
    'entity' => [
        'singular' => 'Expense',
        'plural' => 'Expenses',
    ],
    'fields' => [
        'supplier' => 'Supplier',
        'customer' => 'Customer',
        'expense_date' => 'Date',
        'amount' => 'Amount',
        'currency' => 'Currency',
        'category_id' => 'Category',
        'payment_method_id' => 'Payment Method',
        'reference_number' => 'Reference Number',
        'description' => 'Description',
        'receipt_file' => 'Receipt',
        'attachments' => 'Attachments',
        'tax_rate' => 'Tax Rate',
        'tax_included' => 'Tax is included in the amount',
        'tax_amount' => 'Tax Amount',
        'status_id' => 'Status',
        'user_id' => 'Owner',
    ],
    'hints' => [
        'reference_number' => 'E.g. invoice number, receipt number, etc.',
        'attachments' => 'Upload files related to this expense (images, PDFs, documents).',
        'tax_included' => 'Check if tax is already included in the total amount',
    ],
    'messages' => [
        'created' => 'Expense created.',
        'updated' => 'Expense updated.',
    ],
    'filters' => [
        'date_range' => 'Date Range',
    ],
    'categories' => [
        'entity' => [
            'singular' => 'Expense category',
            'plural' => 'Expense categories',
        ],
        'fields' => [
            'name' => 'Name',
            'slug' => 'Identifier',
            'color' => 'Color',
            'description' => 'Description',
            'is_active' => 'Active',
        ],
        'hints' => [
            'slug' => 'Automatically generated from the name if not filled in',
        ],
    ],
];
