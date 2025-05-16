<?php

return [
    'system' => [
        'settings' => 'Nastavení',
    ],
    'invoices' => [
        'invoice' => 'Faktura',
        'invoices' => 'Faktury',
        'due_date' => 'Datum splatnosti',
        'issue_date' => 'Datum vystavení',
        'invoice_vs' => 'Číslo faktury/VS',
        'invoice_ks' => 'Konstantní symbol',
        'invoice_ss' => 'Specifický symbol',
        'client' => 'Klient',
        'client_id' => 'ID klienta',
        'status' => 'Stav',
        'payment_status' => 'Stav platby',
        'payment_method' => 'Způsob platby',
        'payment_date' => 'Datum platby',
        'amount' => 'Částka',
        'currency' => 'Měna',
        'description' => 'Popis',
        'tax' => 'DPH',
        'tax_rate' => 'Sazba DPH',
        'tax_amount' => 'Částka DPH',
        'total' => 'Celková částka',
    ],

    'clients' => [
        'client' => 'Klient',
        'clients' => 'Klienti',
        'ico' => 'IČO',
        'dic' => 'DIČ',
        'company_name' => 'Název firmy',
        'street' => 'Ulice',
        'city' => 'Město',
        'zip' => 'PSČ',
        'country' => 'Země',
        'email' => 'E-mail',
        'phone' => 'Telefon',
        'description' => 'Popis',
        'status' => [
            'active' => 'Aktivní',
            'inactive' => 'Neaktivní',
            'archived' => 'Archivovaný',
            'deleted' => 'Smazaný',
        ],
        'is_default' => 'Výchozí',
        'user' => 'Uživatel',
    ],

    'suppliers' => [
        'supplier' => 'Dodavatel',
        'suppliers' => 'Dodavatelé',
        'ico' => 'IČO',
        'dic' => 'DIČ',
        'company_name' => 'Název firmy',
        'street' => 'Ulice',
        'city' => 'Město',
        'zip' => 'PSČ',
        'country' => 'Země',
        'email' => 'E-mail',
        'phone' => 'Telefon',
        'description' => 'Popis',

        'status' => [
            'active' => 'Aktivní',
            'inactive' => 'Neaktivní',
            'archived' => 'Archivovaný',
            'deleted' => 'Smazaný',
        ],

        'is_default' => 'Výchozí',
        'user' => 'Uživatel',
        'account_number' => 'Číslo účtu',
        'bank_code' => 'Kód banky',
        'iban' => 'IBAN',
        'swift' => 'SWIFT',
        'bank_name' => 'Název banky',
    ],

    'cron_tasks' => [
        'cron_task' => 'Plánovač úloh',
        'cron_tasks' => 'Plánovače úloh',
        
        'frequency' => [
            'daily' => 'Denně',
            'weekly' => 'Týdně',
            'monthly' => 'Měsíčně',
            'custom' => 'Vlastní',
        ],

        'days' => [
            'sunday' => 'Neděle',
            'monday' => 'Pondělí',
            'tuesday' => 'Úterý',
            'wednesday' => 'Středa',
            'thursday' => 'Čtvrtek',
            'friday' => 'Pátek',
            'saturday' => 'Sobota',
        ],

        'fields' => [
            'name' => 'Název',
            'command' => 'Příkaz',
            'base_command' => 'Základní příkaz',
            'command_params' => 'Parametry příkazu',
            'frequency' => 'Frekvence',
            'custom_expression' => 'Vlastní cron výraz',
            'run_at' => 'Čas spuštění',
            'day_of_week' => 'Den v týdnu',
            'day_of_month' => 'Den v měsíci',
            'is_active' => 'Aktivní',
            'last_run' => 'Poslední spuštění',
            'last_output' => 'Poslední výstup',
            'description' => 'Popis',
            'next_run' => 'Příští spuštění',
        ],

        'tabs' => [
            'basic' => 'Základní nastavení',
            'schedule' => 'Plánování',
            'advanced' => 'Pokročilé',
            'history' => 'Historie',
        ],

        'messages' => [
            'task_executed' => 'Úloha byla úspěšně spuštěna',
            'execution_failed' => 'Spuštění úlohy selhalo',
        ],

        'hints' => [
            'custom_expression' => 'Pokud zvolíte vlastní výraz, zadejte platný cron výraz.',
            'custom_expression_examples' => 'Příklady: "*/5 * * * *" (každých 5 minut), "0 0 * * *" (každý den o půlnoci), "0 12 * * 1-5" (každý pracovní den v poledne).',
            'command_params' => 'Zadejte parametry pro příkaz, např. "--days=7 --force", nebo "--days-before=0 --days-after=14"',
        ],
        
        'predefined' => [
            'upcoming_invoices' => 'Upomínky před splatností faktury',
            'overdue_invoices' => 'Upomínky faktur po splatnosti',
            'long_overdue_invoices' => 'Upomínky faktury dlouho po splatnosti',
        ],
        
        'descriptions' => [
            'upcoming_invoices' => 'Odesílá upomínky na faktury, které budou brzy splatné (3 dny před splatností)',
            'overdue_invoices' => 'Odesílá upomínky na faktury, které jsou po splatnosti (až 7 dní)',
            'long_overdue_invoices' => 'Odesílá upomínky na faktury, které jsou výrazně po splatnosti (14 a více dní)',
        ],

        'validation' => [
            'invalid_cron_expression' => 'Neplatný CRON výraz. Použijte formát: minuta hodina den měsíc den_v_týdnu.',
        ],

        'buttons' => [
            'run_now' => 'Spustit nyní',
        ],
    ],

    'artisan_commands' => [
        'command' => 'Artisan příkaz',
        'commands' => 'Artisan příkazy',
        'category' => 'Kategorie příkazů',
        'categories' => 'Kategorie příkazů',
        'uncategorized' => 'Nezařazené příkazy',
        'uncategorized_description' => 'Automaticky detekované příkazy bez kategorie',
        
        'fields' => [
            'name' => 'Název',
            'command' => 'Příkaz',
            'slug' => 'Identifikátor (slug)',
            'description' => 'Popis',
            'parameters_description' => 'Popis parametrů',
            'category' => 'Kategorie',
            'commands_count' => 'Počet příkazů',
            'is_active' => 'Aktivní',
            'sort_order' => 'Pořadí',
        ],

        'hints' => [
            'slug' => 'Jednoznačný identifikátor kategorie, používá se v kódu (např. "cron", "system", "admin")',
            'command' => 'Artisan příkaz, např. "app:update-exchange-rates"',
            'parameters_description' => 'Popis parametrů příkazu, např. "--date=YYYY-MM-DD: Nastaví datum směnného kurzu"',
            'sort_order' => 'Pořadí příkazu v seznamu (menší číslo = vyšší pozice)',
        ],
    ],

    'statuses' => [
        'status_types' => 'Typy stavů',
    ],

    'taxes' => [
        'tax' => 'DPH',
        'taxes' => 'DPH',
        'name' => 'Název',
        'rate' => 'Sazba',
        'description' => 'Popis',
        'slug' => 'Slug',
    ],

    'banks' => [
        'bank' => 'Banka',
        'banks' => 'Banky',
        'name' => 'Název',
        'code' => 'Kód banky',
        'swift' => 'SWIFT',
        'country' => 'Země',
        'description' => 'Popis',
        'is_active' => 'Aktivní',
        'sort_order' => 'Pořadí',
    ],

    'payment_methods' => [
        'payment_method' => 'Způsob platby',
        'payment_methods' => 'Způsoby platby',
        'name' => 'Název',
        'description' => 'Popis',
        'slug' => 'Slug',
        'country' => 'Země',
        'currency' => 'Měna',
        'icon' => 'Ikona',
        'is_active' => 'Aktivní',
        'sort_order' => 'Pořadí',
    ],

    'products' => [
        'product' => 'Produkt',
        'products' => 'Produkty',
        'supplier' => 'Dodavatel/Prodejce',
        'product_category' => 'Kategorie produktů',
        'product_categories' => 'Kategorie produktů',
        'name' => 'Název',
        'slug' => 'URL klíč',
        'description' => 'Popis',
        'price' => 'Cena',
        'tax' => 'DPH',
        'category' => 'Kategorie',
        'is_default' => 'Výchozí',
        'user' => 'Uživatel',
        'image' => 'Obrázek',
        'leave_empty_for_autogeneration' => 'Ponechte prázdné pro automatické generování',
        'remove_image' => 'Odstranit obrázek',
        'image_help' => 'Podporované formáty: JPG, PNG, GIF (max. 2MB)',
    ],
];
