<?php

return [
    'fields' => [
        'name' => 'Název',
        'description' => 'Popis',
        'price' => 'Cena',
        'sku' => 'SKU',
        'supplier_id' => 'Dodavatel',
        'category_id' => 'Kategorie',
        'tax_id' => 'Daň',
        'is_default' => 'Výchozí produkt',
        'is_active' => 'Aktivní produkt',
        'image' => 'Obrázek',
        'slug' => 'Slug',
        'currency' => 'Měna',
    ],

    'titles' => [
        'invoices' => 'Faktury',
        'index' => 'Produkty',
        'create' => 'Vytvořit produkt',
        'edit' => 'Upravit produkt',
        'show' => 'Produkt #',
    ],

    'no_input_labels' => [
        'current_image' => 'Aktuální obrázek',
    ],

    'sections' => [
        'basic_info' => 'Základní informace',
        'detail_info' => 'Podrobnosti',
    ],

    'actions' => [
        'actions' => 'Akce',
        'create' => 'Vytvořit produkt',
        'edit' => 'Upravit produkt',
        'delete' => 'Smazat produkt',
        'view' => 'Zobrazit produkt',
        'cancel' => 'Zrušit',
        'save' => 'Uložit',
        'confirm_delete' => 'Opravit smazání produktu?',
        'back_to_list' => 'Zpět na seznam',
    ],

    'hints' => [
        'delete' => 'Smazání produktu odstraní všechny související faktury a položky.',
        'create' => 'Vytvořte nový produkt vyplněním všech potřebných polí.',
        'edit' => 'Upravte informace o produktu podle potřeby.',
        'view' => 'Zobrazte podrobnosti o produktu a jeho fakturách.',
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
        'slug' => 'Slug je unikátní identifikátor produktu, který se používá v URL.',
    ],

    'tags' => [
        'product' => 'Produkt',
        'invoice' => 'Faktura',
        'supplier' => 'Dodavatel',
        'category' => 'Kategorie',
        'tax' => 'Daň',
        'details' => 'Podrobnosti',
    ],

    'messages' => [
        'created' => 'Produkt byl úspěšně vytvořen.',
        'updated' => 'Produkt byl úspěšně aktualizován.',
        'deleted' => 'Produkt byl úspěšně smazán.',
        'error_create' => 'Chyba při vytváření produktu.',
        'error_update' => 'Chyba při aktualizaci produktu.',
        'error_delete' => 'Chyba při mazání produktu.',
        'no_image' => 'Žádný obrázek nebyl vybrán.',
        'no_image_selected' => 'Žádný obrázek nebyl vybrán.',
        'image_uploaded' => 'Obrázek byl úspěšně nahrán.',
        'image_deleted' => 'Obrázek byl úspěšně smazán.',
        'image_error' => 'Chyba při nahrávání obrázku.',
    ],

    'validation' => [
        'not_found' => 'Produkt nebyl nalezen.',
        'invalid_data' => 'Neplatná data.',
        'api_error' => 'Chyba při komunikaci s API.',
    ],

    'placeholders' => [
        'select_category' => 'Vyberte kategorii',
        'select_supplier' => 'Vyberte dodavatele',
        'select_tax' => 'Vyberte daň',
        'select_image' => 'Vyberte obrázek',
        'select_product' => 'Vyberte produkt',
        'select_currency' => 'Vyberte měnu',
    ],
];
