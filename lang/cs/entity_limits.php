<?php

return [
    'entity' => [
        'singular' => 'Limit entity',
        'plural' => 'Limity entit',
    ],
    'fields' => [
        'permission_name' => 'Oprávnění',
        'entity_type' => 'Typ entity',
        'metric_type' => 'Typ limitu',
        'period_type' => 'Období',
        'limit_value' => 'Hodnota limitu',
        'description' => 'Popis',
        'is_active' => 'Aktivní',
    ],
    'hints' => [
        'permission_name' => 'Oprávnění, na které se limit vztahuje, nebo Anonymní uživatelé pro nepřihlášené návštěvníky.',
        'limit_value' => 'Nejvyšší povolená hodnota za období: u počtu počet záznamů, u částky peněžní hodnota, u velikosti bajty.',
        'description' => 'Volitelný popis limitu.',
        'is_active' => 'Zda se limit právě uplatňuje.',
    ],
    'labels' => [
        'anonymous_users' => 'Anonymní uživatelé',
    ],
    'entity_types' => [
        'invoice' => [
            'singular' => 'Faktura',
            'plural' => 'Faktury',
        ],
        'client' => [
            'singular' => 'Klient',
            'plural' => 'Klienti',
        ],
        'supplier' => [
            'singular' => 'Dodavatel',
            'plural' => 'Dodavatelé',
        ],
        'product' => [
            'singular' => 'Produkt',
            'plural' => 'Produkty',
        ],
        'expense' => [
            'singular' => 'Výdaj',
            'plural' => 'Výdaje',
        ],
        'user' => [
            'singular' => 'Uživatel',
            'plural' => 'Uživatelé',
        ],
        'page' => [
            'singular' => 'Stránka',
            'plural' => 'Stránky',
        ],
    ],
    'metric_types' => [
        'count' => 'Počet',
        'value' => 'Částka',
        'size' => 'Velikost souborů',
    ],
    'periods' => [
        'daily' => 'Denně',
        'weekly' => 'Týdně',
        'monthly' => 'Měsíčně',
        'yearly' => 'Ročně',
        'lifetime' => 'Celkově',
    ],
    'statuses' => [
        'success' => 'V pořádku',
        'warning' => 'Blízko limitu',
        'danger' => 'Limit překročen',
        'not_allowed' => 'Vytváření není povoleno',
    ],
    'widget' => [
        'title' => 'Limity entit',
        'usage' => 'Využití',
        'unlimited' => 'Bez limitu',
        'show_details' => 'Zobrazit detaily',
        'limit_exceeded' => 'Limit byl překročen!',
        'approaching_limit' => 'Blížíte se k limitu.',
        'loading' => 'Načítání...',
    ],
    'modal' => [
        'title' => 'Detail limitů entit',
        'loading' => 'Načítání...',
        'overview' => 'Přehled',
        'current_period' => 'Aktuální období',
        'recent_activity' => 'Nedávná aktivita',
        'error' => 'Chyba při načítání dat',
        'retry' => 'Zkusit znovu',
        'refresh' => 'Obnovit',
        'close' => 'Zavřít',
        'current_usage' => 'Aktuální využití',
        'limit_info' => 'Informace o limitech',
        'usage_percentage' => 'Procento využití',
        'today' => 'Dnes',
        'this_week' => 'Tento týden',
        'this_month' => 'Tento měsíc',
        'total' => 'Celkem',
        'no_activity' => 'Žádná nedávná aktivita',
        'entity_created' => 'Entita vytvořena',
    ],
    'usages' => [
        'entity' => [
            'singular' => 'Využití limitu',
            'plural' => 'Využití limitů',
        ],
        'fields' => [
            'user_id' => 'Uživatel',
            'entity_type' => 'Typ entity',
            'metric_type' => 'Typ limitu',
            'period_type' => 'Období',
            'period_start' => 'Začátek období',
            'period_end' => 'Konec období',
            'current_value' => 'Aktuální hodnota',
            'last_reset_at' => 'Poslední vynulování',
            'created_at' => 'Vytvořeno',
            'updated_at' => 'Aktualizováno',
        ],
        'filters' => [
            'period_range' => 'Rozsah období',
        ],
        'labels' => [
            'anonymous' => 'Anonymní',
            'unknown_user' => 'Neznámý uživatel (ID: :id)',
        ],
    ],
];
