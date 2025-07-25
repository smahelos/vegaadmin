<?php

return [
    'fields' => [
        'name' => 'Název',
        'email' => 'Email',
        'phone' => 'Telefon',
        'notes' => 'Poznámky',
        'street' => 'Ulice a číslo',
        'city' => 'Město',
        'zip' => 'PSČ',
        'country' => 'Země',
        'ico' => 'IČO',
        'dic' => 'DIČ',
        'shortcut' => 'Zkratka',
        'is_active' => 'Aktivní',
        'created_at' => 'Vytvořeno',
        'updated_at' => 'Aktualizováno',
        'invoices' => 'Faktury',
        'actions' => 'Akce',
        'is_default' => 'Výchozí dodavatel',
        'description' => 'Popis dodavatele',
        'account_number' => 'Číslo účtu',
        'bank_code' => 'Kód banky',
        'bank_name' => 'Název banky',
        'iban' => 'IBAN',
        'swift' => 'SWIFT',
        'select_bank' => 'Vyberte banku',
    ],
    'actions' => [
        'create' => 'Vytvořit dodavatele',
        'edit' => 'Upravit dodavatele',
        'edit_short' => 'Upravit',
        'delete' => 'Smazat dodavatele',
        'save' => 'Uložit dodavatele',
        'save_changes' => 'Uložit změny',
        'cancel' => 'Zrušit',
        'back_to_list' => 'Zpět na seznam',
        'new' => 'Nový dodavatel',
        'show' => 'Zobrazit dodavatele',
        'invoice' => 'Fakturovat',
        'back' => 'Zpět na seznam',
        'view' => 'Zobrazit detail',
    ],
    'titles' => [
        'index' => 'Dodavatelé',
        'create' => 'Přidat nového dodavatele',
        'edit' => 'Upravit dodavatele',
        'show' => 'Detail dodavatele',
        'supplier' => 'dodavatel',
        'empty' => 'Zatím nemáte žádné dodavatele',
        'empty_message' => 'Začněte vytvořením vašeho prvního dodavatele.',
    ],
    'sections' => [
        'basic_info' => 'Základní informace',
        'billing_info' => 'Fakturační údaje',
        'supplier_invoices' => 'Faktury dodavatele',
    ],
    'messages' => [
        'created' => 'Dodavatel byl úspěšně vytvořen.',
        'updated' => 'Dodavatel byl úspěšně upraven.',
        'deleted' => 'Dodavatel byl úspěšně smazán.',
        'set_default' => 'Dodavatel byl úspěšně nastaven jako výchozí.',
        'error_create' => 'Při vytváření dodavatele došlo k chybě.',
        'error_update' => 'Při aktualizaci dodavatele došlo k chybě.',
        'error_delete' => 'Při mazání dodavatele došlo k chybě.',
        'error_edit' => 'Při úpravě dodavatele došlo k chybě.',
        'error_show' => 'Při zobrazování dodavatele došlo k chybě.',
        'error_set_default' => 'Při nastavování dodavatele jako výchozího došlo k chybě.',
        'error_delete_invoices' => 'Dodavatele nelze smazat, protože má přiřazené faktury.',
        'error_loading' => 'Nastala chyba při načítání dodavatelů. Prosím, zkuste to znovu později.',
        'no_suppliers' => 'Nebyli nalezeni žádní dodavatelé.',
        'confirm_delete' => 'Opravdu chcete smazat tohoto dodavatele?',
        'is_default_explanation' => 'Pokud je tento dodavatel nastaven jako výchozí, bude automaticky vybrán při vytváření nových faktur.',
        'not_found' => 'Dodavatel nebyl nalezen.',
        'invalid_id' => 'Neplatné ID dodavatele.',
    ],
    'validation' => [
        'name_required' => 'Název firmy/jméno je povinné',
        'email_required' => 'E-mail je povinný',
        'email_valid' => 'Zadejte platnou e-mailovou adresu',
        'phone_required' => 'Telefon je povinný',
        'street_required' => 'Ulice je povinná',
        'city_required' => 'Město je povinné',
        'zip_required' => 'PSČ je povinné',
        'country_required' => 'Země je povinná',
        'ico_format' => 'IČO musí obsahovat maximálně 20 znaků',
        'account_number_format' => 'Číslo účtu nesmí překročit 50 znaků',
        'bank_code_required' => 'Kód banky je povinný, pokud je zadáno číslo účtu',
        'iban_format' => 'IBAN nesmí překročit 50 znaků',
        'swift_required' => 'SWIFT kód je povinný, pokud je zadán IBAN',
    ],
    'hints' => [
        'supplier' => '',
        'email' => '',
        'phone' => '',
        'name' => '',
        'shortcut' => '',
        'street' => '',
        'city' => '',
        'zip' => '',
        'country' => '',
        'ico' => '',
        'dic' => '',
        'description' => '',
        'account_number' => '',
        'bank_code' => '',
        'iban' => '',
        'swift' => '',
        'bank_name' => '',
    ],
    'placeholders' => [
        'account_number' => '123456789',
        'bank_code' => 'Vyberte banku',
        'bank_name' => '',
        'iban' => 'CZ0000000000000000000000',
        'swift' => 'AAAACZPP',
    ],
    'tags' => [
        'supplier' => 'Dodavatel',
        'billing' => 'Fakturace',
    ],
];
