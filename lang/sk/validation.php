<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Every message names the field as „pole :attribute“, so the sentence
    | agrees with the neuter noun "pole" whatever gender the field name has.
    |
    */

    'accepted' => 'Pole „:attribute“ je potrebné potvrdiť.',
    'accepted_if' => 'Pole „:attribute“ je potrebné potvrdiť, ak má pole „:other“ hodnotu :value.',
    'active_url' => 'Pole „:attribute“ musí obsahovať platnú URL adresu.',
    'after' => 'Pole „:attribute“ musí obsahovať dátum po :date.',
    'after_or_equal' => 'Pole „:attribute“ musí obsahovať dátum :date alebo neskorší.',
    'alpha' => 'Pole „:attribute“ môže obsahovať len písmená.',
    'alpha_dash' => 'Pole „:attribute“ môže obsahovať len písmená, číslice, pomlčky a podčiarkovníky.',
    'alpha_num' => 'Pole „:attribute“ môže obsahovať len písmená a číslice.',
    'array' => 'Pole „:attribute“ musí obsahovať zoznam hodnôt.',
    'ascii' => 'Pole „:attribute“ môže obsahovať len písmená bez diakritiky, číslice a symboly.',
    'before' => 'Pole „:attribute“ musí obsahovať dátum pred :date.',
    'before_or_equal' => 'Pole „:attribute“ musí obsahovať dátum :date alebo skorší.',
    'between' => [
        'array' => 'Pole „:attribute“ musí obsahovať :min až :max položiek.',
        'file' => 'Súbor v poli „:attribute“ musí mať :min až :max kB.',
        'numeric' => 'Pole „:attribute“ musí obsahovať hodnotu od :min do :max.',
        'string' => 'Pole „:attribute“ musí mať :min až :max znakov.',
    ],
    'boolean' => 'Pole „:attribute“ musí mať hodnotu áno, alebo nie.',
    'can' => 'Pole „:attribute“ obsahuje nepovolenú hodnotu.',
    'confirmed' => 'Potvrdenie poľa „:attribute“ sa nezhoduje.',
    'contains' => 'Pole „:attribute“ neobsahuje požadovanú hodnotu.',
    'current_password' => 'Heslo je nesprávne.',
    'date' => 'Pole „:attribute“ musí obsahovať platný dátum.',
    'date_equals' => 'Pole „:attribute“ musí obsahovať dátum :date.',
    'date_format' => 'Pole „:attribute“ musí zodpovedať formátu :format.',
    'decimal' => 'Pole „:attribute“ musí mať :decimal desatinných miest.',
    'declined' => 'Pole „:attribute“ je potrebné odmietnuť.',
    'declined_if' => 'Pole „:attribute“ je potrebné odmietnuť, ak má pole „:other“ hodnotu :value.',
    'different' => 'Polia „:attribute“ a „:other“ sa musia líšiť.',
    'digits' => 'Pole „:attribute“ musí mať :digits číslic.',
    'digits_between' => 'Pole „:attribute“ musí mať :min až :max číslic.',
    'dimensions' => 'Obrázok v poli „:attribute“ má nepovolené rozmery.',
    'distinct' => 'Pole „:attribute“ obsahuje duplicitnú hodnotu.',
    'doesnt_end_with' => 'Pole „:attribute“ nesmie končiť žiadnou z hodnôt: :values.',
    'doesnt_start_with' => 'Pole „:attribute“ nesmie začínať žiadnou z hodnôt: :values.',
    'email' => 'Pole „:attribute“ musí obsahovať platnú e-mailovú adresu.',
    'ends_with' => 'Pole „:attribute“ musí končiť jednou z hodnôt: :values.',
    'enum' => 'Pole „:attribute“ má neplatnú hodnotu.',
    'exists' => 'Pole „:attribute“ má neplatnú hodnotu.',
    'extensions' => 'Súbor v poli „:attribute“ musí mať jednu z prípon: :values.',
    'file' => 'Pole „:attribute“ musí obsahovať súbor.',
    'filled' => 'Pole „:attribute“ musí mať hodnotu.',
    'gt' => [
        'array' => 'Pole „:attribute“ musí obsahovať viac ako :value položiek.',
        'file' => 'Súbor v poli „:attribute“ musí mať viac ako :value kB.',
        'numeric' => 'Pole „:attribute“ musí obsahovať hodnotu väčšiu ako :value.',
        'string' => 'Pole „:attribute“ musí mať viac ako :value znakov.',
    ],
    'gte' => [
        'array' => 'Pole „:attribute“ musí obsahovať aspoň :value položiek.',
        'file' => 'Súbor v poli „:attribute“ musí mať aspoň :value kB.',
        'numeric' => 'Pole „:attribute“ musí obsahovať hodnotu aspoň :value.',
        'string' => 'Pole „:attribute“ musí mať aspoň :value znakov.',
    ],
    'hex_color' => 'Pole „:attribute“ musí obsahovať platnú hexadecimálnu farbu.',
    'image' => 'Pole „:attribute“ musí obsahovať obrázok.',
    'in' => 'Pole „:attribute“ má neplatnú hodnotu.',
    'in_array' => 'Hodnota poľa „:attribute“ musí byť obsiahnutá v poli „:other“.',
    'integer' => 'Pole „:attribute“ musí obsahovať celé číslo.',
    'ip' => 'Pole „:attribute“ musí obsahovať platnú IP adresu.',
    'ipv4' => 'Pole „:attribute“ musí obsahovať platnú IPv4 adresu.',
    'ipv6' => 'Pole „:attribute“ musí obsahovať platnú IPv6 adresu.',
    'json' => 'Pole „:attribute“ musí obsahovať platný JSON.',
    'list' => 'Pole „:attribute“ musí obsahovať zoznam hodnôt.',
    'lowercase' => 'Pole „:attribute“ musí byť písané malými písmenami.',
    'lt' => [
        'array' => 'Pole „:attribute“ musí obsahovať menej ako :value položiek.',
        'file' => 'Súbor v poli „:attribute“ musí mať menej ako :value kB.',
        'numeric' => 'Pole „:attribute“ musí obsahovať hodnotu menšiu ako :value.',
        'string' => 'Pole „:attribute“ musí mať menej ako :value znakov.',
    ],
    'lte' => [
        'array' => 'Pole „:attribute“ môže obsahovať najviac :value položiek.',
        'file' => 'Súbor v poli „:attribute“ môže mať najviac :value kB.',
        'numeric' => 'Pole „:attribute“ môže obsahovať hodnotu najviac :value.',
        'string' => 'Pole „:attribute“ môže mať najviac :value znakov.',
    ],
    'mac_address' => 'Pole „:attribute“ musí obsahovať platnú MAC adresu.',
    'max' => [
        'array' => 'Pole „:attribute“ môže obsahovať najviac :max položiek.',
        'file' => 'Súbor v poli „:attribute“ môže mať najviac :max kB.',
        'numeric' => 'Pole „:attribute“ môže obsahovať hodnotu najviac :max.',
        'string' => 'Pole „:attribute“ môže mať najviac :max znakov.',
    ],
    'max_digits' => 'Pole „:attribute“ môže mať najviac :max číslic.',
    'mimes' => 'Súbor v poli „:attribute“ musí byť typu: :values.',
    'mimetypes' => 'Súbor v poli „:attribute“ musí byť typu: :values.',
    'min' => [
        'array' => 'Pole „:attribute“ musí obsahovať aspoň :min položiek.',
        'file' => 'Súbor v poli „:attribute“ musí mať aspoň :min kB.',
        'numeric' => 'Pole „:attribute“ musí obsahovať hodnotu aspoň :min.',
        'string' => 'Pole „:attribute“ musí mať aspoň :min znakov.',
    ],
    'min_digits' => 'Pole „:attribute“ musí mať aspoň :min číslic.',
    'missing' => 'Pole „:attribute“ nesmie byť vyplnené.',
    'missing_if' => 'Pole „:attribute“ nesmie byť vyplnené, ak má pole „:other“ hodnotu :value.',
    'missing_unless' => 'Pole „:attribute“ nesmie byť vyplnené, ak pole „:other“ nemá hodnotu :value.',
    'missing_with' => 'Pole „:attribute“ nesmie byť vyplnené, ak je vyplnené: :values.',
    'missing_with_all' => 'Pole „:attribute“ nesmie byť vyplnené, ak sú vyplnené všetky polia: :values.',
    'multiple_of' => 'Pole „:attribute“ musí obsahovať násobok :value.',
    'not_in' => 'Pole „:attribute“ má neplatnú hodnotu.',
    'not_regex' => 'Pole „:attribute“ má neplatný formát.',
    'numeric' => 'Pole „:attribute“ musí obsahovať číslo.',
    'password' => [
        'letters' => 'Pole „:attribute“ musí obsahovať aspoň jedno písmeno.',
        'mixed' => 'Pole „:attribute“ musí obsahovať aspoň jedno veľké a jedno malé písmeno.',
        'numbers' => 'Pole „:attribute“ musí obsahovať aspoň jednu číslicu.',
        'symbols' => 'Pole „:attribute“ musí obsahovať aspoň jeden symbol.',
        'uncompromised' => 'Hodnota poľa „:attribute“ sa objavila v úniku dát. Zvoľte prosím inú.',
    ],
    'present' => 'Pole „:attribute“ musí byť uvedené.',
    'present_if' => 'Pole „:attribute“ musí byť uvedené, ak má pole „:other“ hodnotu :value.',
    'present_unless' => 'Pole „:attribute“ musí byť uvedené, ak pole „:other“ nemá hodnotu :value.',
    'present_with' => 'Pole „:attribute“ musí byť uvedené, ak je uvedené: :values.',
    'present_with_all' => 'Pole „:attribute“ musí byť uvedené, ak sú uvedené všetky polia: :values.',
    'prohibited' => 'Pole „:attribute“ nesmie byť vyplnené.',
    'prohibited_if' => 'Pole „:attribute“ nesmie byť vyplnené, ak má pole „:other“ hodnotu :value.',
    'prohibited_if_accepted' => 'Pole „:attribute“ nesmie byť vyplnené, ak je pole „:other“ potvrdené.',
    'prohibited_if_declined' => 'Pole „:attribute“ nesmie byť vyplnené, ak je pole „:other“ odmietnuté.',
    'prohibited_unless' => 'Pole „:attribute“ nesmie byť vyplnené, ak pole „:other“ nemá jednu z hodnôt: :values.',
    'prohibits' => 'Pole „:attribute“ nedovoľuje vyplniť pole „:other“.',
    'regex' => 'Pole „:attribute“ má neplatný formát.',
    'required' => 'Pole „:attribute“ je povinné.',
    'required_array_keys' => 'Pole „:attribute“ musí obsahovať položky: :values.',
    'required_if' => 'Pole „:attribute“ je povinné, ak má pole „:other“ hodnotu :value.',
    'required_if_accepted' => 'Pole „:attribute“ je povinné, ak je pole „:other“ potvrdené.',
    'required_if_declined' => 'Pole „:attribute“ je povinné, ak je pole „:other“ odmietnuté.',
    'required_unless' => 'Pole „:attribute“ je povinné, ak pole „:other“ nemá jednu z hodnôt: :values.',
    'required_with' => 'Pole „:attribute“ je povinné, ak je vyplnené: :values.',
    'required_with_all' => 'Pole „:attribute“ je povinné, ak sú vyplnené všetky polia: :values.',
    'required_without' => 'Pole „:attribute“ je povinné, ak nie je vyplnené: :values.',
    'required_without_all' => 'Pole „:attribute“ je povinné, ak nie je vyplnené žiadne z polí: :values.',
    'same' => 'Pole „:attribute“ sa musí zhodovať s poľom „:other“.',
    'size' => [
        'array' => 'Pole „:attribute“ musí obsahovať :size položiek.',
        'file' => 'Súbor v poli „:attribute“ musí mať :size kB.',
        'numeric' => 'Pole „:attribute“ musí obsahovať hodnotu :size.',
        'string' => 'Pole „:attribute“ musí mať :size znakov.',
    ],
    'starts_with' => 'Pole „:attribute“ musí začínať jednou z hodnôt: :values.',
    'string' => 'Pole „:attribute“ musí obsahovať text.',
    'timezone' => 'Pole „:attribute“ musí obsahovať platné časové pásmo.',
    'unique' => 'Hodnota poľa „:attribute“ sa už používa.',
    'uploaded' => 'Súbor v poli „:attribute“ sa nepodarilo nahrať.',
    'uppercase' => 'Pole „:attribute“ musí byť písané veľkými písmenami.',
    'url' => 'Pole „:attribute“ musí obsahovať platnú URL adresu.',
    'ulid' => 'Pole „:attribute“ musí obsahovať platný ULID.',
    'uuid' => 'Pole „:attribute“ musí obsahovať platný UUID.',

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
            'rule-name' => 'vlastna-sprava',
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
        'supplier_logo' => 'Logo dodávateľa',
        'invoice_logo' => 'Logo faktúry',
        'image' => 'Obrázok produktu',
    ],

    'file_upload' => [
        'type_not_allowed' => 'Tento typ súboru nie je v poli „:attribute“ povolený.',
        'size_exceeded' => 'Súbor v poli „:attribute“ je väčší, ako je povolené.',
    ],

];
