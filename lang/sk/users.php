<?php

return [
    'fields' => [
        'name' => 'Meno',
        'email' => 'E-mail',
        'phone' => 'Telefón',
        'czech_republic' => 'Česká republika',
        'current_password' => 'Súčasné heslo',
        'current_password_hint' => 'Potrebné na potvrdenie',
        'password_hint' => 'Aspoň 8 znakov',
        'password' => 'Heslo',
        'new_password' => 'Nové heslo',
        'password_confirmation' => 'Potvrdenie hesla',
        'password_confirmation_hint' => 'Zadajte heslo znova pre potvrdenie',
        'remember_me' => 'Zapamätať si ma',
        
        // Nápovedy a placeholdery
        'email_placeholder' => 'E-mail',
        'password_placeholder' => 'Heslo',
    ],
    
    'titles' => [
        'edit_profile' => 'Úprava profilu',
        'login' => 'Prihlásenie',
        'register' => 'Registrácia',
        'system_name' => 'Fakturačný systém',
    ],
    
    'errors' => [
        'login_errors' => 'Vyskytli sa chyby pri prihlásení:',
    ],
    
    'sections' => [
        'basic_info' => 'Základné údaje',
        'address' => 'Adresa',
        'change_password' => 'Zmena hesla',
        'security' => 'Zabezpečenie',
    ],
    
    'actions' => [
        'create' => 'Vytvoriť dodávateľa',
        'edit' => 'Upraviť dodávateľa',
        'delete' => 'Vymazať dodávateľa',
        'save' => 'Uložiť zmeny',
        'cancel' => 'Zrušiť',
        'logout' => 'Odhlásiť sa',
        'back_to_dashboard' => 'Späť na prehľad',
        'update_password' => 'Aktualizovať heslo',
        'login' => 'Prihlásiť sa',
        'register' => 'Registrovať',
        'forgot_password' => 'Zabudnuté heslo?',
        'back_to_login' => 'Späť na prihlásenie',
    ],
    'messages' => [
        'profile_updated' => 'Profil bol úspešne aktualizovaný.',
        'password_updated' => 'Profil a jeho heslo bolo úspešne zmenené.',
        'profile_error_update' => 'Chyba pri aktualizácii profilu: ',
        'profile_error' => 'Chyba pri načítaní profilu: ',
        'profile_error_update_password' => 'Chyba pri zmene profilu a hesla: ',
        'profile_error_update_password_current' => 'Súčasné heslo je nesprávne.',
        'profile_error_update_password_empty' => 'Heslo je prázdne.',
        'error_edit_client' => 'Chyba pri úprave klienta: ',
        'error_create_client' => 'Chyba pri vytváraní klienta: ',
        'error_delete_client' => 'Chyba pri vymazávaní klienta: ',
        'error_update_client' => 'Chyba pri aktualizácii klienta: ',
        'no_account' => 'Nemáte účet?',
        'register_prompt' => 'Registrujte sa',
        'have_account' => 'Už máte účet?',
        'login_prompt' => 'Prihláste sa',
    ],

    'validation' => [
        'name_required' => 'Meno je povinné',
        'email_required' => 'E-mail je povinný',
        'email_email' => 'Zadajte platnú e-mailovú adresu',
        'email_unique' => 'Tento e-mail sa už používa',
        'street_required' => 'Ulica je povinná',
        'city_required' => 'Mesto je povinné',
        'zip_required' => 'PSČ je povinné',
        'country_required' => 'Krajina je povinná',
        'password_min' => 'Heslo musí mať aspoň :min znakov',
        'password_required' => 'Heslo je povinné pole',
        'password_confirmed' => 'Heslá sa nezhodujú',
        'required_field' => 'Pole je povinné',
    ],

    'placeholders' => [
        'name' => 'Zadajte meno',
        'email' => 'Zadajte e-mail',
        'phone' => 'Zadajte telefónne číslo',
        'ico' => 'Zadajte IČO',
        'dic' => 'Zadajte DIČ',
        'shortcut' => 'Zadajte skratku',
        'street' => 'Zadajte ulicu',
        'city' => 'Zadajte mesto',
        'zip' => 'Zadajte PSČ',
        'country' => 'Vyberte krajinu',
    ],
];