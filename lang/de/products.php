<?php

return [
    'fields' => [
        'name' => 'Name',
        'description' => 'Beschreibung',
        'price' => 'Preis',
        'sku' => 'SKU',
        'supplier_id' => 'Lieferant',
        'category_id' => 'Kategorie',
        'tax_id' => 'Steuer',
        'is_default' => 'Standardprodukt',
        'is_active' => 'Aktives Produkt',
        'image' => 'Bild',
        'slug' => 'Slug',
        'currency' => 'Währung',
    ],

    'titles' => [
        'invoices' => 'Rechnungen',
        'index' => 'Produkte',
        'create' => 'Produkt erstellen',
        'edit' => 'Produkt bearbeiten',
        'show' => 'Produkt #',
        'select_product' => 'Produkt auswählen',
    ],

    'no_input_labels' => [
        'current_image' => 'Aktuelles Bild',
    ],

    'sections' => [
        'basic_info' => 'Basisinformationen',
        'detail_info' => 'Details',
    ],

    'actions' => [
        'actions' => 'Aktionen',
        'create' => 'Produkt erstellen',
        'edit' => 'Produkt bearbeiten',
        'delete' => 'Produkt löschen',
        'view' => 'Produkt anzeigen',
        'cancel' => 'Abbrechen',
        'save' => 'Speichern',
        'confirm_delete' => 'Produktlöschung bestätigen?',
        'back_to_list' => 'Zurück zur Liste',
        'search' => 'Produkt suchen...',
    ],

    'hints' => [
        'delete' => 'Das Löschen eines Produkts entfernt alle zugehörigen Rechnungen und Artikel.',
        'create' => 'Erstellen Sie ein neues Produkt, indem Sie alle erforderlichen Felder ausfüllen.',
        'edit' => 'Produktinformationen nach Bedarf bearbeiten.',
        'view' => 'Produktdetails und zugehörige Rechnungen anzeigen.',
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
        'slug' => 'Slug ist ein eindeutiger Produktbezeichner, der in der URL verwendet wird.',
    ],

    'tags' => [
        'product' => 'Produkt',
        'invoice' => 'Rechnung',
        'supplier' => 'Lieferant',
        'category' => 'Kategorie',
        'tax' => 'Steuer',
        'details' => 'Details',
    ],

    'messages' => [
        'created' => 'Produkt wurde erfolgreich erstellt.',
        'updated' => 'Produkt wurde erfolgreich aktualisiert.',
        'deleted' => 'Produkt wurde erfolgreich gelöscht.',
        'error_create' => 'Fehler beim Erstellen des Produkts.',
        'error_update' => 'Fehler beim Aktualisieren des Produkts.',
        'error_delete' => 'Fehler beim Löschen des Produkts.',
        'no_image' => 'Kein Bild verfügbar.',
        'no_image_selected' => 'Kein Bild ausgewählt.',
        'image_uploaded' => 'Bild wurde erfolgreich hochgeladen.',
        'image_deleted' => 'Bild wurde erfolgreich gelöscht.',
        'image_error' => 'Fehler beim Hochladen des Bildes.',
        'no_products_found' => 'Es wurden keine Produkte gefunden.',
    ],

    'validation' => [
        'not_found' => 'Produkt wurde nicht gefunden.',
        'invalid_data' => 'Ungültige Daten.',
        'api_error' => 'Fehler bei der Kommunikation mit der API.',
    ],

    'placeholders' => [
        'select_category' => 'Kategorie auswählen',
        'select_supplier' => 'Lieferant auswählen',
        'select_tax' => 'Steuer auswählen',
        'select_image' => 'Bild auswählen',
        'select_product' => 'Produkt auswählen',
        'select_currency' => 'Währung auswählen',
    ],
];
