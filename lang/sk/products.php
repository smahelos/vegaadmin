<?php

return [
    'fields' => [
        'name' => 'Názov',
        'description' => 'Popis',
        'price' => 'Cena',
        'sku' => 'SKU',
        'supplier_id' => 'Dodávateľ',
        'category_id' => 'Kategória',
        'tax_id' => 'Daň',
        'is_default' => 'Predvolený produkt',
        'is_active' => 'Aktívny produkt',
        'image' => 'Obrázok',
        'slug' => 'Slug',
        'currency' => 'Mena',
    ],

    'titles' => [
        'invoices' => 'Faktúry',
        'index' => 'Produkty',
        'create' => 'Vytvoriť produkt',
        'edit' => 'Upraviť produkt',
        'show' => 'Produkt #',
    ],

    'no_input_labels' => [
        'current_image' => 'Aktuálny obrázok',
    ],

    'sections' => [
        'basic_info' => 'Základné informácie',
        'detail_info' => 'Podrobnosti',
    ],

    'actions' => [
        'actions' => 'Akcie',
        'create' => 'Vytvoriť produkt',
        'edit' => 'Upraviť produkt',
        'delete' => 'Zmazať produkt',
        'view' => 'Zobraziť produkt',
        'cancel' => 'Zrušiť',
        'save' => 'Uložiť',
        'confirm_delete' => 'Opraviť smazání produktu?',
        'back_to_list' => 'Späť na zoznam',
    ],

    'hints' => [
        'delete' => 'Smazanie produktu odstráni všetky súvisiace faktúry a položky.',
        'create' => 'Vytvorenie nového produktu vyplnením všetkých potrebných polí.',
        'edit' => 'Upravte informácie o produkte podľa potreby.',
        'view' => 'Zobrazte podrobnosti o produkte a jeho fakturách.',
        'name' => '',
        'description' => '',
        'price' => '',
        'sku' => '',
        'supplier_id' => '',
        'category_id' => '',
        'tax_id' => '',
        'is_default' => '',
        'is_active' => '',
        'image' => '',
        'currency' => '',
        'slug' => 'Slug je unikátny identifikátor produktu, ktorý sa používa v URL.',
    ],

    'messages' => [
        'created' => 'Produkt bol úspešne vytvorený.',
        'updated' => 'Produkt bol úspešne aktualizovaný.',
        'deleted' => 'Produkt bol úspešne zmazaný.',
        'error_create' => 'Chyba pri vytváraní produktu.',
        'error_update' => 'Chyba pri aktualizácii produktu.',
        'error_delete' => 'Chyba pri mazaní produktu.',
        'no_image' => 'Žiadny obrázok',
        'no_image_selected' => 'Žiadny obrázok nebol vybraný.',
        'image_uploaded' => 'Obrázok bol úspešne nahraný.',
        'image_deleted' => 'Obrázok bol úspešne zmazaný.',
        'image_error' => 'Chyba pri nahrávaní obrázku.',
    ],

    'validation' => [
        'not_found' => 'Produkt nebol nájdený.',
        'invalid_data' => 'Neplatné dáta.',
        'api_error' => 'Chyba pri komunikácii s API.',
    ],

    'tags' => [
        'product' => 'Produkt',
        'invoice' => 'Faktúra',
        'supplier' => 'Dodávateľ',
        'category' => 'Kategória',
        'tax' => 'Daň',
        'details' => 'Podrobnosti',
    ],

    'placeholders' => [
        'select_category' => 'Vyberte kategóriu',
        'select_supplier' => 'Vyberte dodávateľa',
        'select_tax' => 'Vyberte daň',
        'select_image' => 'Vyberte obrázok',
        'select_product' => 'Vyberte produkt',
        'select_currency' => 'Vyberte menu',
    ],
];
