<?php

return [
    'cron_tasks' => [
        'cron_task' => 'Scheduled Task',
        'cron_tasks' => 'Scheduled Tasks',
        
        'frequency' => [
            'daily' => 'Daily',
            'weekly' => 'Weekly',
            'monthly' => 'Monthly',
            'custom' => 'Custom',
        ],

        'days' => [
            'sunday' => 'Sunday',
            'monday' => 'Monday',
            'tuesday' => 'Tuesday',
            'wednesday' => 'Wednesday',
            'thursday' => 'Thursday',
            'friday' => 'Friday',
            'saturday' => 'Saturday',
        ],

        'fields' => [
            'name' => 'Name',
            'command' => 'Command',
            'base_command' => 'Base Command',
            'command_params' => 'Command Parameters',
            'frequency' => 'Frequency',
            'custom_expression' => 'Custom Cron Expression',
            'run_at' => 'Run At',
            'day_of_week' => 'Day of Week',
            'day_of_month' => 'Day of Month',
            'is_active' => 'Active',
            'last_run' => 'Last Run',
            'last_output' => 'Last Output',
            'description' => 'Description',
            'next_run' => 'Next Run',
        ],

        'tabs' => [
            'basic' => 'Basic Settings',
            'schedule' => 'Scheduling',
            'advanced' => 'Advanced',
            'history' => 'History',
        ],

        'messages' => [
            'task_executed' => 'Task was successfully executed',
            'execution_failed' => 'Task execution failed',
        ],

        'hints' => [
            'custom_expression' => 'If you choose custom expression, enter a valid cron expression.',
            'custom_expression_examples' => 'Examples: "*/5 * * * *" (every 5 minutes), "0 0 * * *" (every day at midnight), "0 12 * * 1-5" (every weekday at noon).',
            'command_params' => 'Enter parameters for the command, e.g. "--days=7 --force", or "--days-before=0 --days-after=14"',
        ],
        
        'predefined' => [
            'upcoming_invoices' => 'Upcoming Invoice Reminders',
            'overdue_invoices' => 'Overdue Invoice Reminders',
            'long_overdue_invoices' => 'Long Overdue Invoice Reminders',
        ],
        
        'descriptions' => [
            'upcoming_invoices' => 'Sends reminders for invoices that will be due soon (3 days before due date)',
            'overdue_invoices' => 'Sends reminders for invoices that are overdue (up to 7 days)',
            'long_overdue_invoices' => 'Sends reminders for invoices that are significantly overdue (14 days or more)',
        ],

        'validation' => [
            'invalid_cron_expression' => 'Invalid CRON expression. Use format: minute hour day month day_of_week.',
        ],

        'buttons' => [
            'run_now' => 'Run Now',
        ],
    ],

    'artisan_commands' => [
        'command' => 'Artisan Command',
        'commands' => 'Artisan Commands',
        'category' => 'Command Category',
        'categories' => 'Command Categories',
        'uncategorized' => 'Uncategorized Commands',
        'uncategorized_description' => 'Auto-detected commands without a category',
        
        'fields' => [
            'name' => 'Name',
            'command' => 'Command',
            'slug' => 'Identifier (slug)',
            'description' => 'Description',
            'parameters_description' => 'Parameters Description',
            'category' => 'Category',
            'commands_count' => 'Number of Commands',
            'is_active' => 'Active',
            'sort_order' => 'Sort Order',
        ],

        'hints' => [
            'slug' => 'Unique category identifier, used in code (e.g. "cron", "system", "admin")',
            'command' => 'Artisan command, e.g. "app:update-exchange-rates"',
            'parameters_description' => 'Description of command parameters, e.g. "--date=YYYY-MM-DD: Sets the exchange rate date"',
            'sort_order' => 'Command order in the list (smaller number = higher position)',
        ],
    ],

    'statuses' => [
        'status_types' => 'Status Types',
    ],
];
