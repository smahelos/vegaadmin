<?php

return [
    'entity' => [
        'singular' => 'Steuer',
        'plural' => 'Steuern',
    ],
    'fields' => [
        'name' => 'Name',
        'rate' => 'Satz',
        'slug' => 'Kennung',
        'description' => 'Beschreibung',
    ],
    'hints' => [
        'name' => 'Geben Sie den Namen der Steuer ein.',
        'rate' => 'Geben Sie den Steuersatz als Prozentsatz ein.',
        'description' => 'Geben Sie eine Beschreibung für die Steuer ein.',
        'slug' => 'Feste Kennung, über die die Anwendung den Satz findet (z. B. dph). Nach dem Anlegen nicht ändern.',
    ],
];
