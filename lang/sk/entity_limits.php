<?php

return [
    'entity' => [
        'singular' => 'Limit entity',
        'plural' => 'Limity entít',
    ],
    'fields' => [
        'permission_name' => 'Oprávnenie',
        'entity_type' => 'Typ entity',
        'metric_type' => 'Typ limitu',
        'period_type' => 'Obdobie',
        'limit_value' => 'Hodnota limitu',
        'description' => 'Popis',
        'is_active' => 'Aktívne',
    ],
    'hints' => [
        'permission_name' => 'Oprávnenie, na ktoré sa limit vzťahuje, alebo Anonymní používatelia pre neprihlásených návštevníkov.',
        'limit_value' => 'Najvyššia povolená hodnota za obdobie: pri počte počet záznamov, pri sume peňažná hodnota, pri veľkosti bajty.',
        'description' => 'Voliteľný popis limitu.',
        'is_active' => 'Či sa limit práve uplatňuje.',
    ],
    'labels' => [
        'anonymous_users' => 'Anonymní používatelia',
    ],
    'entity_types' => [
        'invoice' => [
            'singular' => 'Faktúra',
            'plural' => 'Faktúry',
        ],
        'client' => [
            'singular' => 'Klient',
            'plural' => 'Klienti',
        ],
        'supplier' => [
            'singular' => 'Dodávateľ',
            'plural' => 'Dodávatelia',
        ],
        'product' => [
            'singular' => 'Produkt',
            'plural' => 'Produkty',
        ],
        'expense' => [
            'singular' => 'Výdavok',
            'plural' => 'Výdavky',
        ],
        'user' => [
            'singular' => 'Používateľ',
            'plural' => 'Používatelia',
        ],
        'page' => [
            'singular' => 'Stránka',
            'plural' => 'Stránky',
        ],
    ],
    'metric_types' => [
        'count' => 'Počet',
        'value' => 'Suma',
        'size' => 'Veľkosť súborov',
    ],
    'periods' => [
        'daily' => 'Denne',
        'weekly' => 'Týždenne',
        'monthly' => 'Mesačne',
        'yearly' => 'Ročne',
        'lifetime' => 'Celkovo',
    ],
    'statuses' => [
        'success' => 'V poriadku',
        'warning' => 'Blízko limitu',
        'danger' => 'Limit prekročený',
        'not_allowed' => 'Vytváranie nie je povolené',
    ],
    'widget' => [
        'title' => 'Limity entít',
        'usage' => 'Využitie',
        'unlimited' => 'Bez limitu',
        'show_details' => 'Zobraziť detaily',
        'limit_exceeded' => 'Limit prekročený',
        'approaching_limit' => 'Blíži sa k limitu',
        'loading' => 'Načítanie...',
    ],
    'modal' => [
        'title' => 'Detail limitov entít',
        'loading' => 'Načítanie...',
        'overview' => 'Prehľad',
        'current_period' => 'Aktuálne obdobie',
        'recent_activity' => 'Nedávna aktivita',
        'error' => 'Chyba pri načítaní dát',
        'retry' => 'Skúsiť znovu',
        'refresh' => 'Obnoviť',
        'close' => 'Zavrieť',
        'current_usage' => 'Aktuálne využitie',
        'limit_info' => 'Informácie o limitoch',
        'usage_percentage' => 'Percento využitia',
        'today' => 'Dnes',
        'this_week' => 'Tento týždeň',
        'this_month' => 'Tento mesiac',
        'total' => 'Celkom',
        'no_activity' => 'Žiadna nedávna aktivita',
        'entity_created' => 'Entita vytvorená',
    ],
    'usages' => [
        'entity' => [
            'singular' => 'Využitie limitu',
            'plural' => 'Využitie limitov',
        ],
        'fields' => [
            'user_id' => 'Používateľ',
            'entity_type' => 'Typ entity',
            'metric_type' => 'Typ limitu',
            'period_type' => 'Obdobie',
            'period_start' => 'Začiatok obdobia',
            'period_end' => 'Koniec obdobia',
            'current_value' => 'Aktuálna hodnota',
            'last_reset_at' => 'Posledné vynulovanie',
            'created_at' => 'Vytvorené',
            'updated_at' => 'Aktualizované',
        ],
        'filters' => [
            'period_range' => 'Rozsah obdobia',
        ],
        'labels' => [
            'anonymous' => 'Anonymný',
            'unknown_user' => 'Neznámy používateľ (ID: :id)',
        ],
    ],
];
