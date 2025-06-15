<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => ':attribute musí být potvrzen.',
    'accepted_if' => ':attribute musí být potvrzen, pokud :other je :value.',
    'active_url' => ':attribute musí být platná URL adresa.',
    'after' => ':attribute musí být datum po :date.',
    'after_or_equal' => ':attribute musí být datum nejpozději :date.',
    'alpha' => ':attribute může obsahovat pouze písmena.',
    'alpha_dash' => ':attribute může obsahovat pouze písmena, čísla, pomlčky a podtržítka.',
    'alpha_num' => ':attribute může obsahovat pouze písmena a čísla.',
    'array' => ':attribute musí být pole.',
    'ascii' => ':attribute může obsahovat pouze jednovidové alfanumerické znaky a symboly.',
    'before' => ':attribute musí být datum před :date.',
    'before_or_equal' => ':attribute musí být datum nejdříve :date.',
    'between' => [
        'array' => ':attribute musí obsahovat mezi :min a :max položkami.',
        'file' => ':attribute musí mít mezi :min a :max kilobajty.',
        'numeric' => ':attribute musí být mezi :min a :max.',
        'string' => ':attribute musí mít mezi :min a :max znaky.',
    ],
    'boolean' => ':attribute musí být true nebo false.',
    'can' => ':attribute obsahuje nepovolenou hodnotu.',
    'confirmed' => 'Potvrzení :attribute nesouhlasí.',
    'contains' => ':attribute neobsahuje požadovanou hodnotu.',
    'current_password' => 'Heslo je nesprávné.',
    'date' => ':attribute musí být platné datum.',
    'date_equals' => ':attribute musí být datum rovné :date.',
    'date_format' => ':attribute neodpovídá formátu :format.',
    'decimal' => ':attribute musí mít :decimal desetinných míst.',
    'declined' => ':attribute musí být odmítnut.',
    'declined_if' => ':attribute musí být odmítnut, pokud :other je :value.',
    'different' => ':attribute a :other musí být odlišné.',
    'digits' => ':attribute musí mít :digits číslic.',
    'digits_between' => ':attribute musí mít mezi :min a :max číslicemi.',
    'dimensions' => ':attribute má neplatné rozměry obrázku.',
    'distinct' => ':attribute obsahuje duplicitní hodnotu.',
    'doesnt_end_with' => ':attribute nesmí končit jedním z: :values.',
    'doesnt_start_with' => ':attribute nesmí začínat jedním z: :values.',
    'email' => ':attribute musí být platná e-mailová adresa.',
    'ends_with' => ':attribute musí končit jedním z: :values.',
    'enum' => 'Vybraný :attribute je neplatný.',
    'exists' => 'Vybraný :attribute je neplatný.',
    'extensions' => ':attribute musí mít jednu z následujících přípon: :values.',
    'file' => ':attribute musí být soubor.',
    'filled' => ':attribute musí mít hodnotu.',
    'gt' => [
        'array' => ':attribute musí mít více než :value položek.',
        'file' => ':attribute musí být větší než :value kilobajtů.',
        'numeric' => ':attribute musí být větší než :value.',
        'string' => ':attribute musí být delší než :value znaků.',
    ],
    'gte' => [
        'array' => ':attribute musí mít :value položek nebo více.',
        'file' => ':attribute musí být větší nebo rovno :value kilobajtům.',
        'numeric' => ':attribute musí být větší nebo rovno :value.',
        'string' => ':attribute musí být delší nebo rovno :value znakům.',
    ],
    'hex_color' => ':attribute musí být platná hexadecimální barva.',
    'image' => ':attribute musí být obrázek.',
    'in' => 'Vybraný :attribute je neplatný.',
    'in_array' => ':attribute musí existovat v :other.',
    'integer' => ':attribute musí být celé číslo.',
    'ip' => ':attribute musí být platná IP adresa.',
    'ipv4' => ':attribute musí být platná IPv4 adresa.',
    'ipv6' => ':attribute musí být platná IPv6 adresa.',
    'json' => ':attribute musí být platný JSON řetězec.',
    'list' => ':attribute musí být seznam.',
    'lowercase' => ':attribute musí být malými písmeny.',
    'lt' => [
        'array' => ':attribute musí mít méně než :value položek.',
        'file' => ':attribute musí být menší než :value kilobajtů.',
        'numeric' => ':attribute musí být menší než :value.',
        'string' => ':attribute musí být kratší než :value znaků.',
    ],
    'lte' => [
        'array' => ':attribute nesmí mít více než :value položek.',
        'file' => ':attribute musí být menší nebo rovno :value kilobajtům.',
        'numeric' => ':attribute musí být menší nebo rovno :value.',
        'string' => ':attribute musí být kratší nebo rovno :value znakům.',
    ],
    'mac_address' => ':attribute musí být platná MAC adresa.',
    'max' => [
        'array' => ':attribute nesmí mít více než :max položek.',
        'file' => ':attribute nesmí být větší než :max kilobajtů.',
        'numeric' => ':attribute nesmí být větší než :max.',
        'string' => ':attribute nesmí být delší než :max znaků.',
    ],
    'max_digits' => ':attribute nesmí mít více než :max číslic.',
    'mimes' => ':attribute musí být soubor typu: :values.',
    'mimetypes' => ':attribute musí být soubor typu: :values.',
    'min' => [
        'array' => ':attribute musí mít alespoň :min položek.',
        'file' => ':attribute musí mít alespoň :min kilobajtů.',
        'numeric' => ':attribute musí být alespoň :min.',
        'string' => ':attribute musí mít alespoň :min znaků.',
    ],
    'min_digits' => ':attribute musí mít alespoň :min číslic.',
    'missing' => ':attribute musí chybět.',
    'missing_if' => ':attribute musí chybět, pokud :other je :value.',
    'missing_unless' => ':attribute musí chybět, pokud :other není :value.',
    'missing_with' => ':attribute musí chybět, pokud je přítomno :values.',
    'missing_with_all' => ':attribute musí chybět, pokud jsou přítomny :values.',
    'multiple_of' => ':attribute musí být násobkem :value.',
    'not_in' => 'Vybraný :attribute je neplatný.',
    'not_regex' => 'Formát :attribute je neplatný.',
    'numeric' => ':attribute musí být číslo.',
    'password' => [
        'letters' => ':attribute musí obsahovat alespoň jedno písmeno.',
        'mixed' => ':attribute musí obsahovat alespoň jedno velké a jedno malé písmeno.',
        'numbers' => ':attribute musí obsahovat alespoň jedno číslo.',
        'symbols' => ':attribute musí obsahovat alespoň jeden symbol.',
        'uncompromised' => 'Zadaný :attribute se objevil v úniku dat. Zvolte prosím jiné :attribute.',
    ],
    'present' => ':attribute musí být přítomen.',
    'present_if' => ':attribute musí být přítomen, pokud :other je :value.',
    'present_unless' => ':attribute musí být přítomen, pokud :other není :value.',
    'present_with' => ':attribute musí být přítomen, pokud je přítomno :values.',
    'present_with_all' => ':attribute musí být přítomen, pokud jsou přítomny :values.',
    'prohibited' => ':attribute je zakázán.',
    'prohibited_if' => ':attribute je zakázán, pokud :other je :value.',
    'prohibited_if_accepted' => ':attribute je zakázán, pokud je :other potvrzen.',
    'prohibited_if_declined' => ':attribute je zakázán, pokud je :other odmítnut.',
    'prohibited_unless' => ':attribute je zakázán, pokud :other není v :values.',
    'prohibits' => ':attribute zakazuje přítomnost :other.',
    'regex' => 'Formát :attribute je neplatný.',
    'required' => ':attribute je povinné pole.',
    'required_array_keys' => ':attribute musí obsahovat položky: :values.',
    'required_if' => ':attribute je povinné, pokud :other je :value.',
    'required_if_accepted' => ':attribute je povinné, pokud je :other potvrzen.',
    'required_if_declined' => ':attribute je povinné, pokud je :other odmítnut.',
    'required_unless' => ':attribute je povinné, pokud :other není v :values.',
    'required_with' => ':attribute je povinné, pokud je přítomno :values.',
    'required_with_all' => ':attribute je povinné, pokud jsou přítomny :values.',
    'required_without' => ':attribute je povinné, pokud není přítomno :values.',
    'required_without_all' => ':attribute je povinné, pokud není přítomno žádné z :values.',
    'same' => ':attribute musí odpovídat :other.',
    'size' => [
        'array' => ':attribute musí obsahovat :size položek.',
        'file' => ':attribute musí mít :size kilobajtů.',
        'numeric' => ':attribute musí být :size.',
        'string' => ':attribute musí mít :size znaků.',
    ],
    'starts_with' => ':attribute musí začínat jedním z: :values.',
    'string' => ':attribute musí být řetězec.',
    'timezone' => ':attribute musí být platná časová zóna.',
    'unique' => ':attribute již existuje.',
    'uploaded' => 'Nahrání :attribute se nezdařilo.',
    'uppercase' => ':attribute musí být velkými písmeny.',
    'url' => ':attribute musí být platná URL adresa.',
    'ulid' => ':attribute musí být platný ULID.',
    'uuid' => ':attribute musí být platný UUID.',

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

    'attributes' => [],

];
