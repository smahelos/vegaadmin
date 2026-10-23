<?php

return [
    'entity' => [
        'singular' => 'Scheduled Task',
        'plural' => 'Scheduled Tasks',
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
    'hints' => [
        'command_params' => 'Enter parameters for the command, e.g. "--days=7 --force", or "--days-before=0 --days-after=14"',
        'custom_expression' => 'If you choose custom expression, enter a valid cron expression. Examples: "*/5 * * * *" (every 5 minutes), "0 0 * * *" (every day at midnight), "0 12 * * 1-5" (every weekday at noon).',
    ],
    'tabs' => [
        'basic' => 'Basic Settings',
        'schedule' => 'Scheduling',
        'history' => 'History',
    ],
    'frequencies' => [
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
    'actions' => [
        'run_now' => 'Run Now',
    ],
    'messages' => [
        'task_executed' => 'Task was successfully executed',
        'execution_failed' => 'Task execution failed',
    ],
    'validation' => [
        'cron_expression' => 'Invalid CRON expression. Use format: minute hour day month day_of_week.',
    ],
    'predefined' => [
        'upcoming_invoices' => [
            'name' => 'Upcoming Invoice Reminders',
            'description' => 'Sends reminders for invoices that will be due soon (3 days before due date)',
        ],
        'overdue_invoices' => [
            'name' => 'Overdue Invoice Reminders',
            'description' => 'Sends reminders for invoices that are overdue (up to 7 days)',
        ],
        'long_overdue_invoices' => [
            'name' => 'Long Overdue Invoice Reminders',
            'description' => 'Sends reminders for invoices that are significantly overdue (14 days or more)',
        ],
    ],
];
