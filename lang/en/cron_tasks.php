<?php

return [
    'entity' => [
        'singular' => 'Scheduled Task',
        'plural' => 'Scheduled Tasks',
    ],
    'fields' => [
        'name' => 'Name',
        'command' => 'Command',
        'parameters' => 'Command Parameters',
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
        'parameters' => 'Command options separated by spaces, e.g. "--days-before=5 --days-after=2" or "--analyze". A value may contain only letters, digits and the characters _ . , : -',
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
        'parameters' => 'The command parameters must be options of the form --name or --name=value separated by spaces; a value may contain only letters, digits and the characters _ . , : -',
    ],
    'predefined' => [
        'payment_reminders' => [
            'name' => 'Invoice Reminders',
            'description' => 'Sends reminders for invoices 3 days before the due date, on the due date and one day after it',
        ],
    ],
];
