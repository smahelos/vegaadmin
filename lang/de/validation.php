<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Every message names the field as „das Feld :attribute“, so articles and
    | adjectives agree with the neuter noun "Feld" whatever the field name is.
    |
    */

    'accepted' => 'Das Feld „:attribute“ muss akzeptiert werden.',
    'accepted_if' => 'Das Feld „:attribute“ muss akzeptiert werden, wenn das Feld „:other“ den Wert :value hat.',
    'active_url' => 'Das Feld „:attribute“ muss eine gültige URL enthalten.',
    'after' => 'Das Feld „:attribute“ muss ein Datum nach dem :date enthalten.',
    'after_or_equal' => 'Das Feld „:attribute“ muss ein Datum am oder nach dem :date enthalten.',
    'alpha' => 'Das Feld „:attribute“ darf nur Buchstaben enthalten.',
    'alpha_dash' => 'Das Feld „:attribute“ darf nur Buchstaben, Ziffern, Binde- und Unterstriche enthalten.',
    'alpha_num' => 'Das Feld „:attribute“ darf nur Buchstaben und Ziffern enthalten.',
    'array' => 'Das Feld „:attribute“ muss eine Liste von Werten enthalten.',
    'ascii' => 'Das Feld „:attribute“ darf nur Buchstaben ohne Akzente, Ziffern und Symbole enthalten.',
    'before' => 'Das Feld „:attribute“ muss ein Datum vor dem :date enthalten.',
    'before_or_equal' => 'Das Feld „:attribute“ muss ein Datum am oder vor dem :date enthalten.',
    'between' => [
        'array' => 'Das Feld „:attribute“ muss zwischen :min und :max Elemente enthalten.',
        'file' => 'Die Datei im Feld „:attribute“ muss zwischen :min und :max KB groß sein.',
        'numeric' => 'Das Feld „:attribute“ muss einen Wert zwischen :min und :max enthalten.',
        'string' => 'Das Feld „:attribute“ muss zwischen :min und :max Zeichen lang sein.',
    ],
    'boolean' => 'Das Feld „:attribute“ muss ja oder nein sein.',
    'can' => 'Das Feld „:attribute“ enthält einen nicht erlaubten Wert.',
    'confirmed' => 'Die Bestätigung des Feldes „:attribute“ stimmt nicht überein.',
    'contains' => 'Das Feld „:attribute“ enthält den erforderlichen Wert nicht.',
    'current_password' => 'Das Passwort ist falsch.',
    'date' => 'Das Feld „:attribute“ muss ein gültiges Datum enthalten.',
    'date_equals' => 'Das Feld „:attribute“ muss das Datum :date enthalten.',
    'date_format' => 'Das Feld „:attribute“ muss dem Format :format entsprechen.',
    'decimal' => 'Das Feld „:attribute“ muss :decimal Nachkommastellen haben.',
    'declined' => 'Das Feld „:attribute“ muss abgelehnt werden.',
    'declined_if' => 'Das Feld „:attribute“ muss abgelehnt werden, wenn das Feld „:other“ den Wert :value hat.',
    'different' => 'Die Felder „:attribute“ und „:other“ müssen sich unterscheiden.',
    'digits' => 'Das Feld „:attribute“ muss :digits Ziffern haben.',
    'digits_between' => 'Das Feld „:attribute“ muss zwischen :min und :max Ziffern haben.',
    'dimensions' => 'Das Bild im Feld „:attribute“ hat ungültige Abmessungen.',
    'distinct' => 'Das Feld „:attribute“ enthält einen doppelten Wert.',
    'doesnt_end_with' => 'Das Feld „:attribute“ darf mit keinem der folgenden Werte enden: :values.',
    'doesnt_start_with' => 'Das Feld „:attribute“ darf mit keinem der folgenden Werte beginnen: :values.',
    'email' => 'Das Feld „:attribute“ muss eine gültige E-Mail-Adresse enthalten.',
    'ends_with' => 'Das Feld „:attribute“ muss mit einem der folgenden Werte enden: :values.',
    'enum' => 'Das Feld „:attribute“ hat einen ungültigen Wert.',
    'exists' => 'Das Feld „:attribute“ hat einen ungültigen Wert.',
    'extensions' => 'Die Datei im Feld „:attribute“ muss eine dieser Endungen haben: :values.',
    'file' => 'Das Feld „:attribute“ muss eine Datei enthalten.',
    'filled' => 'Das Feld „:attribute“ muss einen Wert haben.',
    'gt' => [
        'array' => 'Das Feld „:attribute“ muss mehr als :value Elemente enthalten.',
        'file' => 'Die Datei im Feld „:attribute“ muss größer als :value KB sein.',
        'numeric' => 'Das Feld „:attribute“ muss einen Wert größer als :value enthalten.',
        'string' => 'Das Feld „:attribute“ muss mehr als :value Zeichen lang sein.',
    ],
    'gte' => [
        'array' => 'Das Feld „:attribute“ muss mindestens :value Elemente enthalten.',
        'file' => 'Die Datei im Feld „:attribute“ muss mindestens :value KB groß sein.',
        'numeric' => 'Das Feld „:attribute“ muss einen Wert von mindestens :value enthalten.',
        'string' => 'Das Feld „:attribute“ muss mindestens :value Zeichen lang sein.',
    ],
    'hex_color' => 'Das Feld „:attribute“ muss eine gültige hexadezimale Farbe enthalten.',
    'image' => 'Das Feld „:attribute“ muss ein Bild enthalten.',
    'in' => 'Das Feld „:attribute“ hat einen ungültigen Wert.',
    'in_array' => 'Der Wert des Feldes „:attribute“ muss im Feld „:other“ enthalten sein.',
    'integer' => 'Das Feld „:attribute“ muss eine ganze Zahl enthalten.',
    'ip' => 'Das Feld „:attribute“ muss eine gültige IP-Adresse enthalten.',
    'ipv4' => 'Das Feld „:attribute“ muss eine gültige IPv4-Adresse enthalten.',
    'ipv6' => 'Das Feld „:attribute“ muss eine gültige IPv6-Adresse enthalten.',
    'json' => 'Das Feld „:attribute“ muss gültiges JSON enthalten.',
    'list' => 'Das Feld „:attribute“ muss eine Liste von Werten enthalten.',
    'lowercase' => 'Das Feld „:attribute“ muss in Kleinbuchstaben geschrieben sein.',
    'lt' => [
        'array' => 'Das Feld „:attribute“ muss weniger als :value Elemente enthalten.',
        'file' => 'Die Datei im Feld „:attribute“ muss kleiner als :value KB sein.',
        'numeric' => 'Das Feld „:attribute“ muss einen Wert kleiner als :value enthalten.',
        'string' => 'Das Feld „:attribute“ muss weniger als :value Zeichen lang sein.',
    ],
    'lte' => [
        'array' => 'Das Feld „:attribute“ darf höchstens :value Elemente enthalten.',
        'file' => 'Die Datei im Feld „:attribute“ darf höchstens :value KB groß sein.',
        'numeric' => 'Das Feld „:attribute“ darf höchstens den Wert :value enthalten.',
        'string' => 'Das Feld „:attribute“ darf höchstens :value Zeichen lang sein.',
    ],
    'mac_address' => 'Das Feld „:attribute“ muss eine gültige MAC-Adresse enthalten.',
    'max' => [
        'array' => 'Das Feld „:attribute“ darf höchstens :max Elemente enthalten.',
        'file' => 'Die Datei im Feld „:attribute“ darf höchstens :max KB groß sein.',
        'numeric' => 'Das Feld „:attribute“ darf höchstens den Wert :max enthalten.',
        'string' => 'Das Feld „:attribute“ darf höchstens :max Zeichen lang sein.',
    ],
    'max_digits' => 'Das Feld „:attribute“ darf höchstens :max Ziffern haben.',
    'mimes' => 'Die Datei im Feld „:attribute“ muss vom Typ :values sein.',
    'mimetypes' => 'Die Datei im Feld „:attribute“ muss vom Typ :values sein.',
    'min' => [
        'array' => 'Das Feld „:attribute“ muss mindestens :min Elemente enthalten.',
        'file' => 'Die Datei im Feld „:attribute“ muss mindestens :min KB groß sein.',
        'numeric' => 'Das Feld „:attribute“ muss einen Wert von mindestens :min enthalten.',
        'string' => 'Das Feld „:attribute“ muss mindestens :min Zeichen lang sein.',
    ],
    'min_digits' => 'Das Feld „:attribute“ muss mindestens :min Ziffern haben.',
    'missing' => 'Das Feld „:attribute“ darf nicht ausgefüllt sein.',
    'missing_if' => 'Das Feld „:attribute“ darf nicht ausgefüllt sein, wenn das Feld „:other“ den Wert :value hat.',
    'missing_unless' => 'Das Feld „:attribute“ darf nicht ausgefüllt sein, wenn das Feld „:other“ nicht den Wert :value hat.',
    'missing_with' => 'Das Feld „:attribute“ darf nicht ausgefüllt sein, wenn :values ausgefüllt ist.',
    'missing_with_all' => 'Das Feld „:attribute“ darf nicht ausgefüllt sein, wenn alle Felder :values ausgefüllt sind.',
    'multiple_of' => 'Das Feld „:attribute“ muss ein Vielfaches von :value enthalten.',
    'not_in' => 'Das Feld „:attribute“ hat einen ungültigen Wert.',
    'not_regex' => 'Das Feld „:attribute“ hat ein ungültiges Format.',
    'numeric' => 'Das Feld „:attribute“ muss eine Zahl enthalten.',
    'password' => [
        'letters' => 'Das Feld „:attribute“ muss mindestens einen Buchstaben enthalten.',
        'mixed' => 'Das Feld „:attribute“ muss mindestens einen Groß- und einen Kleinbuchstaben enthalten.',
        'numbers' => 'Das Feld „:attribute“ muss mindestens eine Ziffer enthalten.',
        'symbols' => 'Das Feld „:attribute“ muss mindestens ein Sonderzeichen enthalten.',
        'uncompromised' => 'Der Wert des Feldes „:attribute“ ist in einem Datenleck aufgetaucht. Bitte wählen Sie einen anderen.',
    ],
    'present' => 'Das Feld „:attribute“ muss vorhanden sein.',
    'present_if' => 'Das Feld „:attribute“ muss vorhanden sein, wenn das Feld „:other“ den Wert :value hat.',
    'present_unless' => 'Das Feld „:attribute“ muss vorhanden sein, wenn das Feld „:other“ nicht den Wert :value hat.',
    'present_with' => 'Das Feld „:attribute“ muss vorhanden sein, wenn :values vorhanden ist.',
    'present_with_all' => 'Das Feld „:attribute“ muss vorhanden sein, wenn alle Felder :values vorhanden sind.',
    'prohibited' => 'Das Feld „:attribute“ darf nicht ausgefüllt sein.',
    'prohibited_if' => 'Das Feld „:attribute“ darf nicht ausgefüllt sein, wenn das Feld „:other“ den Wert :value hat.',
    'prohibited_if_accepted' => 'Das Feld „:attribute“ darf nicht ausgefüllt sein, wenn das Feld „:other“ akzeptiert ist.',
    'prohibited_if_declined' => 'Das Feld „:attribute“ darf nicht ausgefüllt sein, wenn das Feld „:other“ abgelehnt ist.',
    'prohibited_unless' => 'Das Feld „:attribute“ darf nicht ausgefüllt sein, wenn das Feld „:other“ keinen der Werte :values hat.',
    'prohibits' => 'Das Feld „:attribute“ verbietet, das Feld „:other“ auszufüllen.',
    'regex' => 'Das Feld „:attribute“ hat ein ungültiges Format.',
    'required' => 'Das Feld „:attribute“ ist erforderlich.',
    'required_array_keys' => 'Das Feld „:attribute“ muss folgende Einträge enthalten: :values.',
    'required_if' => 'Das Feld „:attribute“ ist erforderlich, wenn das Feld „:other“ den Wert :value hat.',
    'required_if_accepted' => 'Das Feld „:attribute“ ist erforderlich, wenn das Feld „:other“ akzeptiert ist.',
    'required_if_declined' => 'Das Feld „:attribute“ ist erforderlich, wenn das Feld „:other“ abgelehnt ist.',
    'required_unless' => 'Das Feld „:attribute“ ist erforderlich, wenn das Feld „:other“ keinen der Werte :values hat.',
    'required_with' => 'Das Feld „:attribute“ ist erforderlich, wenn :values ausgefüllt ist.',
    'required_with_all' => 'Das Feld „:attribute“ ist erforderlich, wenn alle Felder :values ausgefüllt sind.',
    'required_without' => 'Das Feld „:attribute“ ist erforderlich, wenn :values nicht ausgefüllt ist.',
    'required_without_all' => 'Das Feld „:attribute“ ist erforderlich, wenn keines der Felder :values ausgefüllt ist.',
    'same' => 'Das Feld „:attribute“ muss mit dem Feld „:other“ übereinstimmen.',
    'size' => [
        'array' => 'Das Feld „:attribute“ muss :size Elemente enthalten.',
        'file' => 'Die Datei im Feld „:attribute“ muss :size KB groß sein.',
        'numeric' => 'Das Feld „:attribute“ muss den Wert :size enthalten.',
        'string' => 'Das Feld „:attribute“ muss :size Zeichen lang sein.',
    ],
    'starts_with' => 'Das Feld „:attribute“ muss mit einem der folgenden Werte beginnen: :values.',
    'string' => 'Das Feld „:attribute“ muss einen Text enthalten.',
    'timezone' => 'Das Feld „:attribute“ muss eine gültige Zeitzone enthalten.',
    'unique' => 'Der Wert des Feldes „:attribute“ ist bereits vergeben.',
    'uploaded' => 'Die Datei im Feld „:attribute“ konnte nicht hochgeladen werden.',
    'uppercase' => 'Das Feld „:attribute“ muss in Großbuchstaben geschrieben sein.',
    'url' => 'Das Feld „:attribute“ muss eine gültige URL enthalten.',
    'ulid' => 'Das Feld „:attribute“ muss eine gültige ULID enthalten.',
    'uuid' => 'Das Feld „:attribute“ muss eine gültige UUID enthalten.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'benutzerdefinierte-nachricht',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [
        'supplier_logo' => 'Lieferantenlogo',
        'invoice_logo' => 'Rechnungslogo',
        'image' => 'Produktbild',
    ],

    'file_upload' => [
        'type_not_allowed' => 'Dieser Dateityp ist im Feld „:attribute“ nicht erlaubt.',
        'size_exceeded' => 'Die Datei im Feld „:attribute“ ist größer als erlaubt.',
    ],

];
