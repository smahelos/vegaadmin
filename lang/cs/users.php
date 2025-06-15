<?php

return [
    'auth' => [
        'unauthenticated' => 'Nejste přihlášeni.',
        'forbidden' => 'Přístup odepřen.',
        'unauthorized' => 'Přístup odepřen. Nemáte oprávnění pro přístup k tomuto zdroji.',
        'no_frontend_access' => 'Přístup odepřen. Nemáte oprávnění pro přístup k frontend API.',
        'no_admin_access' => 'Přístup odepřen. Nemáte oprávnění pro přístup do administrace.',
        'registration_success' => 'Registrace byla úspěšná.',
        'registration_failed' => 'Registrace se nezdařila.',
        'failed' => 'Přihlášení se nezdařilo.',
    ],

    'fields' => [
        'name' => 'Jméno',
        'email' => 'E-mail',
        'phone' => 'Telefon',
        'czech_republic' => 'Česká republika',
        'current_password' => 'Současné heslo',
        'current_password_hint' => 'Nutné pro potvrzení',
        'password_hint' => 'Alespoň 8 znaků',
        'password' => 'Heslo',
        'new_password' => 'Nové heslo',
        'password_confirmation' => 'Potvrzení hesla',
        'password_confirmation_hint' => 'Zadejte heslo znovu pro potvrzení',
        'remember_me' => 'Zapamatovat si mě',
        
        // Nápovědy a placeholdery
        'email_placeholder' => 'E-mail',
        'password_placeholder' => 'Heslo',
    ],
    
    'titles' => [
        'edit_profile' => 'Úprava profilu',
        'login' => 'Přihlášení',
        'register' => 'Registrace',
        'system_name' => 'Fakturační systém',
    ],
    
    'errors' => [
        'login_errors' => 'Vyskytly se chyby při přihlášení:',
    ],
    
    'sections' => [
        'basic_info' => 'Základní údaje',
        'address' => 'Adresa',
        'change_password' => 'Změna hesla',
        'security' => 'Zabezpečení',
    ],
    
    'actions' => [
        'create' => 'Vytvořit dodavatele',
        'edit' => 'Upravit dodavatele',
        'delete' => 'Smazat dodavatele',
        'save' => 'Uložit změny',
        'cancel' => 'Zrušit',
        'logout' => 'Odhlásit se',
        'back_to_dashboard' => 'Zpět na přehled',
        'update_password' => 'Aktualizoat heslo',
        'login' => 'Přihlásit se',
        'register' => 'Registrovat',
        'forgot_password' => 'Zapomenuté heslo?',
        'back_to_login' => 'Zpět na přihlášení',
    ],
    'messages' => [
        'profile_updated' => 'Profil byl úspěšně aktualizován.',
        'password_updated' => 'Profil a jeho heslo bylo úspěšně změněno.',
        'profile_error_update' => 'Chyba při aktualizaci profilu: ',
        'profile_error' => 'Chyba při načítání profilu: ',
        'profile_error_update_password' => 'Chyba při změně profilu a hesla: ',
        'profile_error_update_password_current' => 'Současné heslo je nesprávné.',
        'profile_error_update_password_empty' => 'Heslo je prázdné.',
        'error_edit_client' => 'Chyba při úpravě klienta: ',
        'error_create_client' => 'Chyba při vytváření klienta: ',
        'error_delete_client' => 'Chyba při mazání klienta: ',
        'error_update_client' => 'Chyba při aktualizaci klienta: ',
        'no_account' => 'Nemáte účet?',
        'register_prompt' => 'Registrujte se',
        'have_account' => 'Již máte účet?',
        'login_prompt' => 'Přihlaste se',
    ],

    'validation' => [
        'name_required' => 'Jméno je povinné',
        'email_required' => 'E-mail je povinný',
        'email_email' => 'Zadejte platnou e-mailovou adresu',
        'email_unique' => 'Tento e-mail je již používán',
        'street_required' => 'Ulice je povinná',
        'city_required' => 'Město je povinné',
        'zip_required' => 'PSČ je povinné',
        'country_required' => 'Země je povinná',
        'password_min' => 'Heslo musí mít alespoň :min znaků',
        'password_required' => 'Heslo je povinné pole',
        'password_confirmed' => 'Hesla se neshodují',
        'required_field' => 'Pole je povinné',
    ],

    'placeholders' => [
        'name' => 'Zadejte jméno',
        'email' => 'Zadejte e-mail',
        'phone' => 'Zadejte telefonní číslo',
        'ico' => 'Zadejte IČO',
        'dic' => 'Zadejte DIČ',
        'shortcut' => 'Zadejte zkratku',
        'street' => 'Zadejte ulici',
        'city' => 'Zadejte město',
        'zip' => 'Zadejte PSČ',
        'country' => 'Vyberte zemi',
        'new_password' => 'Zadejte nové heslo',
        'password_confirmation' => 'Stejné heslo pro potvrzení',
    ],

    'status' => [
        'user_statuses' => 'Stavy uživatele',
    ],
];
