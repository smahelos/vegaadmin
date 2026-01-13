<?php

return [
    // Obecné překlady pro aplikaci
    'logo_alt' => 'Vega Faktury',
    'currency' => 'Kč',
    'yes' => 'Ano',
    'no' => 'Ne',

    // Měsíce pro formátování dat
    'months' => [
        1 => 'leden',
        2 => 'únor',
        3 => 'březen',
        4 => 'duben',
        5 => 'květen',
        6 => 'červen',
        7 => 'červenec',
        8 => 'srpen',
        9 => 'září',
        10 => 'říjen',
        11 => 'listopad',
        12 => 'prosinec',
    ],

    'navigation' => [
        'dashboard' => 'Přehled',
        'invoices' => 'Faktury',
        'clients' => 'Klienti',
        'suppliers' => 'Dodavatelé',
        'profile' => 'Profil',
        'products' => 'Produkty',
        'create_invoice' => 'Vytvořit fakturu',
        'pages' => 'Stránky',
    ],

    'placeholders' => [
        'select_client' => 'Vyberte klienta nebo vytvořte nového...',
        'select_status' => 'Vyberte stav...',
        'select_supplier' => 'Vyberte dodavatele nebo vytvořte nového...',
        'select_country' => 'Vyberte zemi...',
        'payment_method_select' => 'Vyberte způsob platby',
        'due_in_select' => 'Vyberte splatnost',
        'suggested_number_desc' => 'Navrhované číslo faktury můžete změnit podle vašich potřeb',
        'not_available' => '—',
        'not_specified' => 'Neuvedeno',
        'yes' => 'Ano',
        'no' => 'Ne',
    ],

    'joins' => [
        'and' => 'a',
    ],

    'empty' => [
        'not_specified' => 'Nespecifikováno.',
        'no_items' => 'Žádné položky',
        'no_results' => 'Žádné výsledky',
    ],

    'pagination' => [
        'navigation' => 'Navigace',
        'shown' => 'Zobrazeno',
        'of' => 'z',
        'up_to' => 'až',
        'items' => 'položek',
        'previous' => 'Předchozí',
        'next' => 'Další',
    ],

    'actions' => [
        'create' => 'Vytvořit',
        'edit' => 'Upravit',
        'delete' => 'Smazat',
        'view' => 'Zobrazit',
        'save' => 'Uložit',
        'cancel' => 'Zrušit',
        'confirm' => 'Potvrdit',
        'back' => 'Zpět',
        'download' => 'Stáhnout',
        'print' => 'Tisknout',
        'send' => 'Odeslat',
        'actions' => 'Akce',
    ],

    'common' => [
        'created_at' => 'Vytvořeno',
        'updated_at' => 'Aktualizováno',
    ],

    // Systém limitů entit
    'entity_limits' => [
        'exceeded' => [
            'title' => 'Limit překročen',
            'message' => 'Dosáhli jste svého limitu pro :entity_type. Maximální povolené: :max_count za :period_type.',
            'current_usage' => 'Aktuální využití: :current_count',
            'period_info' => 'Období: :period_start do :period_end',
            'contact_admin' => 'Pro zvýšení limitu kontaktujte administrátora.',
        ],
        'entities' => [
            'invoice' => 'faktury',
            'client' => 'klienti',
            'supplier' => 'dodavatelé',
            'product' => 'produkty',
            'expense' => 'výdaje',
        ],
        'periods' => [
            'daily' => 'den',
            'weekly' => 'týden',
            'monthly' => 'měsíc',
            'yearly' => 'rok',
            'lifetime' => 'celková doba',
        ],
        'limit_types' => [
            'count' => 'počet',
            'value' => 'hodnota',
            'size' => 'velikost',
        ],
    ],
];
