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

    'accepted' => 'Pole „:attribute“ je potřeba potvrdit.',
    'accepted_if' => 'Pole „:attribute“ je potřeba potvrdit, pokud má pole „:other“ hodnotu :value.',
    'active_url' => 'Pole „:attribute“ musí obsahovat platnou URL adresu.',
    'after' => 'Pole „:attribute“ musí obsahovat datum po :date.',
    'after_or_equal' => 'Pole „:attribute“ musí obsahovat datum :date nebo pozdější.',
    'alpha' => 'Pole „:attribute“ může obsahovat jen písmena.',
    'alpha_dash' => 'Pole „:attribute“ může obsahovat jen písmena, číslice, pomlčky a podtržítka.',
    'alpha_num' => 'Pole „:attribute“ může obsahovat jen písmena a číslice.',
    'array' => 'Pole „:attribute“ musí obsahovat seznam hodnot.',
    'ascii' => 'Pole „:attribute“ může obsahovat jen písmena bez diakritiky, číslice a symboly.',
    'before' => 'Pole „:attribute“ musí obsahovat datum před :date.',
    'before_or_equal' => 'Pole „:attribute“ musí obsahovat datum :date nebo dřívější.',
    'between' => [
        'array' => 'Pole „:attribute“ musí obsahovat :min až :max položek.',
        'file' => 'Soubor v poli „:attribute“ musí mít :min až :max kB.',
        'numeric' => 'Pole „:attribute“ musí obsahovat hodnotu od :min do :max.',
        'string' => 'Pole „:attribute“ musí mít :min až :max znaků.',
    ],
    'boolean' => 'Pole „:attribute“ musí mít hodnotu ano, nebo ne.',
    'can' => 'Pole „:attribute“ obsahuje nepovolenou hodnotu.',
    'confirmed' => 'Potvrzení pole „:attribute“ nesouhlasí.',
    'contains' => 'Pole „:attribute“ neobsahuje požadovanou hodnotu.',
    'current_password' => 'Heslo je nesprávné.',
    'date' => 'Pole „:attribute“ musí obsahovat platné datum.',
    'date_equals' => 'Pole „:attribute“ musí obsahovat datum :date.',
    'date_format' => 'Pole „:attribute“ musí odpovídat formátu :format.',
    'decimal' => 'Pole „:attribute“ musí mít :decimal desetinných míst.',
    'declined' => 'Pole „:attribute“ je potřeba odmítnout.',
    'declined_if' => 'Pole „:attribute“ je potřeba odmítnout, pokud má pole „:other“ hodnotu :value.',
    'different' => 'Pole „:attribute“ a „:other“ se musí lišit.',
    'digits' => 'Pole „:attribute“ musí mít :digits číslic.',
    'digits_between' => 'Pole „:attribute“ musí mít :min až :max číslic.',
    'dimensions' => 'Obrázek v poli „:attribute“ má nepovolené rozměry.',
    'distinct' => 'Pole „:attribute“ obsahuje duplicitní hodnotu.',
    'doesnt_end_with' => 'Pole „:attribute“ nesmí končit žádnou z hodnot: :values.',
    'doesnt_start_with' => 'Pole „:attribute“ nesmí začínat žádnou z hodnot: :values.',
    'email' => 'Pole „:attribute“ musí obsahovat platnou e-mailovou adresu.',
    'ends_with' => 'Pole „:attribute“ musí končit jednou z hodnot: :values.',
    'enum' => 'Pole „:attribute“ má neplatnou hodnotu.',
    'exists' => 'Pole „:attribute“ má neplatnou hodnotu.',
    'extensions' => 'Soubor v poli „:attribute“ musí mít jednu z přípon: :values.',
    'file' => 'Pole „:attribute“ musí obsahovat soubor.',
    'filled' => 'Pole „:attribute“ musí mít hodnotu.',
    'gt' => [
        'array' => 'Pole „:attribute“ musí obsahovat víc než :value položek.',
        'file' => 'Soubor v poli „:attribute“ musí mít víc než :value kB.',
        'numeric' => 'Pole „:attribute“ musí obsahovat hodnotu větší než :value.',
        'string' => 'Pole „:attribute“ musí mít víc než :value znaků.',
    ],
    'gte' => [
        'array' => 'Pole „:attribute“ musí obsahovat aspoň :value položek.',
        'file' => 'Soubor v poli „:attribute“ musí mít aspoň :value kB.',
        'numeric' => 'Pole „:attribute“ musí obsahovat hodnotu aspoň :value.',
        'string' => 'Pole „:attribute“ musí mít aspoň :value znaků.',
    ],
    'hex_color' => 'Pole „:attribute“ musí obsahovat platnou hexadecimální barvu.',
    'image' => 'Pole „:attribute“ musí obsahovat obrázek.',
    'in' => 'Pole „:attribute“ má neplatnou hodnotu.',
    'in_array' => 'Hodnota pole „:attribute“ musí být obsažena v poli „:other“.',
    'integer' => 'Pole „:attribute“ musí obsahovat celé číslo.',
    'ip' => 'Pole „:attribute“ musí obsahovat platnou IP adresu.',
    'ipv4' => 'Pole „:attribute“ musí obsahovat platnou IPv4 adresu.',
    'ipv6' => 'Pole „:attribute“ musí obsahovat platnou IPv6 adresu.',
    'json' => 'Pole „:attribute“ musí obsahovat platný JSON.',
    'list' => 'Pole „:attribute“ musí obsahovat seznam hodnot.',
    'lowercase' => 'Pole „:attribute“ musí být psané malými písmeny.',
    'lt' => [
        'array' => 'Pole „:attribute“ musí obsahovat méně než :value položek.',
        'file' => 'Soubor v poli „:attribute“ musí mít méně než :value kB.',
        'numeric' => 'Pole „:attribute“ musí obsahovat hodnotu menší než :value.',
        'string' => 'Pole „:attribute“ musí mít méně než :value znaků.',
    ],
    'lte' => [
        'array' => 'Pole „:attribute“ může obsahovat nejvýše :value položek.',
        'file' => 'Soubor v poli „:attribute“ může mít nejvýše :value kB.',
        'numeric' => 'Pole „:attribute“ může obsahovat hodnotu nejvýše :value.',
        'string' => 'Pole „:attribute“ může mít nejvýše :value znaků.',
    ],
    'mac_address' => 'Pole „:attribute“ musí obsahovat platnou MAC adresu.',
    'max' => [
        'array' => 'Pole „:attribute“ může obsahovat nejvýše :max položek.',
        'file' => 'Soubor v poli „:attribute“ může mít nejvýše :max kB.',
        'numeric' => 'Pole „:attribute“ může obsahovat hodnotu nejvýše :max.',
        'string' => 'Pole „:attribute“ může mít nejvýše :max znaků.',
    ],
    'max_digits' => 'Pole „:attribute“ může mít nejvýše :max číslic.',
    'mimes' => 'Soubor v poli „:attribute“ musí být typu: :values.',
    'mimetypes' => 'Soubor v poli „:attribute“ musí být typu: :values.',
    'min' => [
        'array' => 'Pole „:attribute“ musí obsahovat aspoň :min položek.',
        'file' => 'Soubor v poli „:attribute“ musí mít aspoň :min kB.',
        'numeric' => 'Pole „:attribute“ musí obsahovat hodnotu aspoň :min.',
        'string' => 'Pole „:attribute“ musí mít aspoň :min znaků.',
    ],
    'min_digits' => 'Pole „:attribute“ musí mít aspoň :min číslic.',
    'missing' => 'Pole „:attribute“ nesmí být vyplněné.',
    'missing_if' => 'Pole „:attribute“ nesmí být vyplněné, pokud má pole „:other“ hodnotu :value.',
    'missing_unless' => 'Pole „:attribute“ nesmí být vyplněné, pokud pole „:other“ nemá hodnotu :value.',
    'missing_with' => 'Pole „:attribute“ nesmí být vyplněné, pokud je vyplněno: :values.',
    'missing_with_all' => 'Pole „:attribute“ nesmí být vyplněné, pokud jsou vyplněna všechna pole: :values.',
    'multiple_of' => 'Pole „:attribute“ musí obsahovat násobek :value.',
    'not_in' => 'Pole „:attribute“ má neplatnou hodnotu.',
    'not_regex' => 'Pole „:attribute“ má neplatný formát.',
    'numeric' => 'Pole „:attribute“ musí obsahovat číslo.',
    'password' => [
        'letters' => 'Pole „:attribute“ musí obsahovat aspoň jedno písmeno.',
        'mixed' => 'Pole „:attribute“ musí obsahovat aspoň jedno velké a jedno malé písmeno.',
        'numbers' => 'Pole „:attribute“ musí obsahovat aspoň jednu číslici.',
        'symbols' => 'Pole „:attribute“ musí obsahovat aspoň jeden symbol.',
        'uncompromised' => 'Hodnota pole „:attribute“ se objevila v úniku dat. Zvolte prosím jinou.',
    ],
    'present' => 'Pole „:attribute“ musí být uvedené.',
    'present_if' => 'Pole „:attribute“ musí být uvedené, pokud má pole „:other“ hodnotu :value.',
    'present_unless' => 'Pole „:attribute“ musí být uvedené, pokud pole „:other“ nemá hodnotu :value.',
    'present_with' => 'Pole „:attribute“ musí být uvedené, pokud je uvedeno: :values.',
    'present_with_all' => 'Pole „:attribute“ musí být uvedené, pokud jsou uvedena všechna pole: :values.',
    'prohibited' => 'Pole „:attribute“ nesmí být vyplněné.',
    'prohibited_if' => 'Pole „:attribute“ nesmí být vyplněné, pokud má pole „:other“ hodnotu :value.',
    'prohibited_if_accepted' => 'Pole „:attribute“ nesmí být vyplněné, pokud je pole „:other“ potvrzené.',
    'prohibited_if_declined' => 'Pole „:attribute“ nesmí být vyplněné, pokud je pole „:other“ odmítnuté.',
    'prohibited_unless' => 'Pole „:attribute“ nesmí být vyplněné, pokud pole „:other“ nemá jednu z hodnot: :values.',
    'prohibits' => 'Pole „:attribute“ nedovoluje vyplnit pole „:other“.',
    'regex' => 'Pole „:attribute“ má neplatný formát.',
    'required' => 'Pole „:attribute“ je povinné.',
    'required_array_keys' => 'Pole „:attribute“ musí obsahovat položky: :values.',
    'required_if' => 'Pole „:attribute“ je povinné, pokud má pole „:other“ hodnotu :value.',
    'required_if_accepted' => 'Pole „:attribute“ je povinné, pokud je pole „:other“ potvrzené.',
    'required_if_declined' => 'Pole „:attribute“ je povinné, pokud je pole „:other“ odmítnuté.',
    'required_unless' => 'Pole „:attribute“ je povinné, pokud pole „:other“ nemá jednu z hodnot: :values.',
    'required_with' => 'Pole „:attribute“ je povinné, pokud je vyplněno: :values.',
    'required_with_all' => 'Pole „:attribute“ je povinné, pokud jsou vyplněna všechna pole: :values.',
    'required_without' => 'Pole „:attribute“ je povinné, pokud není vyplněno: :values.',
    'required_without_all' => 'Pole „:attribute“ je povinné, pokud není vyplněno žádné z polí: :values.',
    'same' => 'Pole „:attribute“ se musí shodovat s polem „:other“.',
    'size' => [
        'array' => 'Pole „:attribute“ musí obsahovat :size položek.',
        'file' => 'Soubor v poli „:attribute“ musí mít :size kB.',
        'numeric' => 'Pole „:attribute“ musí obsahovat hodnotu :size.',
        'string' => 'Pole „:attribute“ musí mít :size znaků.',
    ],
    'starts_with' => 'Pole „:attribute“ musí začínat jednou z hodnot: :values.',
    'string' => 'Pole „:attribute“ musí obsahovat text.',
    'timezone' => 'Pole „:attribute“ musí obsahovat platné časové pásmo.',
    'unique' => 'Hodnota pole „:attribute“ už je použitá.',
    'uploaded' => 'Soubor v poli „:attribute“ se nepodařilo nahrát.',
    'uppercase' => 'Pole „:attribute“ musí být psané velkými písmeny.',
    'url' => 'Pole „:attribute“ musí obsahovat platnou URL adresu.',
    'ulid' => 'Pole „:attribute“ musí obsahovat platný ULID.',
    'uuid' => 'Pole „:attribute“ musí obsahovat platný UUID.',

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
            'rule-name' => 'vlastni-zprava',
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
        'supplier_logo' => 'Logo dodavatele',
        'invoice_logo' => 'Logo faktury',
        'image' => 'Obrázek produktu',
    ],

    'file_upload' => [
        'type_not_allowed' => 'Tento typ souboru není v poli „:attribute“ povolený.',
        'size_exceeded' => 'Soubor v poli „:attribute“ je větší, než je povoleno.',
    ],

];
