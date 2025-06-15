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

    'accepted' => ':attribute musí byť akceptované.',
    'accepted_if' => ':attribute musí byť akceptované, keď :other je :value.',
    'active_url' => ':attribute musí byť platná URL adresa.',
    'after' => ':attribute musí byť dátum po :date.',
    'after_or_equal' => ':attribute musí byť dátum najskôr :date.',
    'alpha' => ':attribute môže obsahovať iba písmená.',
    'alpha_dash' => ':attribute môže obsahovať iba písmená, čísla, pomlčky a podčiarkovníky.',
    'alpha_num' => ':attribute môže obsahovať iba písmená a čísla.',
    'array' => ':attribute musí byť pole.',
    'ascii' => ':attribute môže obsahovať iba jednobajtové alfanumerické znaky a symboly.',
    'before' => ':attribute musí byť dátum pred :date.',
    'before_or_equal' => ':attribute musí byť dátum najneskôr :date.',
    'between' => [
        'array' => ':attribute musí obsahovať medzi :min a :max položkami.',
        'file' => ':attribute musí mať medzi :min a :max kilobajtov.',
        'numeric' => ':attribute musí byť medzi :min a :max.',
        'string' => ':attribute musí mať medzi :min a :max znakov.',
    ],
    'boolean' => ':attribute musí byť true alebo false.',
    'can' => ':attribute obsahuje nepovolenú hodnotu.',
    'confirmed' => 'Potvrdenie :attribute sa nezhoduje.',
    'contains' => ':attribute neobsahuje požadovanú hodnotu.',
    'current_password' => 'Heslo je nesprávne.',
    'date' => ':attribute musí byť platný dátum.',
    'date_equals' => ':attribute musí byť dátum rovný :date.',
    'date_format' => ':attribute nezodpovedá formátu :format.',
    'decimal' => ':attribute musí mať :decimal desatinných miest.',
    'declined' => ':attribute musí byť odmietnuté.',
    'declined_if' => ':attribute musí byť odmietnuté, keď :other je :value.',
    'different' => ':attribute a :other musia byť odlišné.',
    'digits' => ':attribute musí mať :digits číslic.',
    'digits_between' => ':attribute musí mať medzi :min a :max číslicami.',
    'dimensions' => ':attribute má neplatné rozmery obrázka.',
    'distinct' => ':attribute obsahuje duplicitnú hodnotu.',
    'doesnt_end_with' => ':attribute nesmie končiť jedným z: :values.',
    'doesnt_start_with' => ':attribute nesmie začínať jedným z: :values.',
    'email' => ':attribute musí byť platná e-mailová adresa.',
    'ends_with' => ':attribute musí končiť jedným z: :values.',
    'enum' => 'Vybraný :attribute je neplatný.',
    'exists' => 'Vybraný :attribute je neplatný.',
    'extensions' => ':attribute musí mať jednu z nasledujúcich prípon: :values.',
    'file' => ':attribute musí byť súbor.',
    'filled' => ':attribute musí mať hodnotu.',
    'gt' => [
        'array' => ':attribute musí mať viac ako :value položiek.',
        'file' => ':attribute musí byť väčší ako :value kilobajtov.',
        'numeric' => ':attribute musí byť väčší ako :value.',
        'string' => ':attribute musí byť dlhší ako :value znakov.',
    ],
    'gte' => [
        'array' => ':attribute musí mať :value položiek alebo viac.',
        'file' => ':attribute musí byť väčší alebo rovný :value kilobajtom.',
        'numeric' => ':attribute musí byť väčší alebo rovný :value.',
        'string' => ':attribute musí byť dlhší alebo rovný :value znakom.',
    ],
    'hex_color' => ':attribute musí byť platná hexadecimálna farba.',
    'image' => ':attribute musí byť obrázok.',
    'in' => 'Vybraný :attribute je neplatný.',
    'in_array' => ':attribute musí existovať v :other.',
    'integer' => ':attribute musí byť celé číslo.',
    'ip' => ':attribute musí byť platná IP adresa.',
    'ipv4' => ':attribute musí byť platná IPv4 adresa.',
    'ipv6' => ':attribute musí byť platná IPv6 adresa.',
    'json' => ':attribute musí byť platný JSON reťazec.',
    'list' => ':attribute musí byť zoznam.',
    'lowercase' => ':attribute musí byť malými písmenami.',
    'lt' => [
        'array' => ':attribute musí mať menej ako :value položiek.',
        'file' => ':attribute musí byť menší ako :value kilobajtov.',
        'numeric' => ':attribute musí byť menší ako :value.',
        'string' => ':attribute musí byť kratší ako :value znakov.',
    ],
    'lte' => [
        'array' => ':attribute nesmie mať viac ako :value položiek.',
        'file' => ':attribute musí byť menší alebo rovný :value kilobajtom.',
        'numeric' => ':attribute musí byť menší alebo rovný :value.',
        'string' => ':attribute musí byť kratší alebo rovný :value znakom.',
    ],
    'mac_address' => ':attribute musí byť platná MAC adresa.',
    'max' => [
        'array' => ':attribute nesmie mať viac ako :max položiek.',
        'file' => ':attribute nesmie byť väčší ako :max kilobajtov.',
        'numeric' => ':attribute nesmie byť väčší ako :max.',
        'string' => ':attribute nesmie byť dlhší ako :max znakov.',
    ],
    'max_digits' => ':attribute nesmie mať viac ako :max číslic.',
    'mimes' => ':attribute musí byť súbor typu: :values.',
    'mimetypes' => ':attribute musí byť súbor typu: :values.',
    'min' => [
        'array' => ':attribute musí mať aspoň :min položiek.',
        'file' => ':attribute musí mať aspoň :min kilobajtov.',
        'numeric' => ':attribute musí byť aspoň :min.',
        'string' => ':attribute musí mať aspoň :min znakov.',
    ],
    'min_digits' => ':attribute musí mať aspoň :min číslic.',
    'missing' => ':attribute musí chýbať.',
    'missing_if' => ':attribute musí chýbať, keď :other je :value.',
    'missing_unless' => ':attribute musí chýbať, pokiaľ :other nie je :value.',
    'missing_with' => ':attribute musí chýbať, keď je prítomné :values.',
    'missing_with_all' => ':attribute musí chýbať, keď sú prítomné :values.',
    'multiple_of' => ':attribute musí byť násobkom :value.',
    'not_in' => 'Vybraný :attribute je neplatný.',
    'not_regex' => 'Formát :attribute je neplatný.',
    'numeric' => ':attribute musí byť číslo.',
    'password' => [
        'letters' => ':attribute musí obsahovať aspoň jedno písmeno.',
        'mixed' => ':attribute musí obsahovať aspoň jedno veľké a jedno malé písmeno.',
        'numbers' => ':attribute musí obsahovať aspoň jedno číslo.',
        'symbols' => ':attribute musí obsahovať aspoň jeden symbol.',
        'uncompromised' => 'Zadané :attribute sa objavilo v úniku dát. Zvoľte prosím iné :attribute.',
    ],
    'present' => ':attribute musí byť prítomný.',
    'present_if' => ':attribute musí byť prítomný, keď :other je :value.',
    'present_unless' => ':attribute musí byť prítomný, pokiaľ :other nie je :value.',
    'present_with' => ':attribute musí byť prítomný, keď je prítomné :values.',
    'present_with_all' => ':attribute musí byť prítomný, keď sú prítomné :values.',
    'prohibited' => ':attribute je zakázaný.',
    'prohibited_if' => ':attribute je zakázaný, keď :other je :value.',
    'prohibited_if_accepted' => ':attribute je zakázaný, keď je :other akceptované.',
    'prohibited_if_declined' => ':attribute je zakázaný, keď je :other odmietnuté.',
    'prohibited_unless' => ':attribute je zakázaný, pokiaľ :other nie je v :values.',
    'prohibits' => ':attribute zakazuje prítomnosť :other.',
    'regex' => 'Formát :attribute je neplatný.',
    'required' => ':attribute je povinné pole.',
    'required_array_keys' => ':attribute musí obsahovať položky: :values.',
    'required_if' => ':attribute je povinné, keď :other je :value.',
    'required_if_accepted' => ':attribute je povinné, keď je :other akceptované.',
    'required_if_declined' => ':attribute je povinné, keď je :other odmietnuté.',
    'required_unless' => ':attribute je povinné, pokiaľ :other nie je v :values.',
    'required_with' => ':attribute je povinné, keď je prítomné :values.',
    'required_with_all' => ':attribute je povinné, keď sú prítomné :values.',
    'required_without' => ':attribute je povinné, keď nie je prítomné :values.',
    'required_without_all' => ':attribute je povinné, keď nie je prítomné žiadne z :values.',
    'same' => ':attribute musí zodpovedať :other.',
    'size' => [
        'array' => ':attribute musí obsahovať :size položiek.',
        'file' => ':attribute musí mať :size kilobajtov.',
        'numeric' => ':attribute musí byť :size.',
        'string' => ':attribute musí mať :size znakov.',
    ],
    'starts_with' => ':attribute musí začínať jedným z: :values.',
    'string' => ':attribute musí byť reťazec.',
    'timezone' => ':attribute musí byť platná časová zóna.',
    'unique' => ':attribute už existuje.',
    'uploaded' => 'Nahrávanie :attribute sa nepodarilo.',
    'uppercase' => ':attribute musí byť veľkými písmenami.',
    'url' => ':attribute musí byť platná URL adresa.',
    'ulid' => ':attribute musí byť platný ULID.',
    'uuid' => ':attribute musí byť platný UUID.',

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

    'attributes' => [],

];
