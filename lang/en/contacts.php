<?php

return [
    'entity' => [
        'singular' => 'Contact',
        'plural' => 'Contacts',
    ],
    'fields' => [
        'contact_person' => 'Contact person',
    ],
    'hints' => [
        'contact_person' => 'The person you deal with; does not replace the name of the company invoiced.',
    ],
    'actions' => [
        'create' => 'Add contact',
        'back_to_list' => 'Back to list',
    ],
    'messages' => [
        'created' => 'The contact has been created.',
        'updated' => 'The contact has been updated.',
        'archived' => 'The contact has been archived.',
        'restored' => 'The contact has been restored.',
        'limit_exceeded' => 'You have reached the limit of active contacts. Archive a contact or upgrade your plan.',
        'confirm_archive' => 'Do you really want to archive the contact? It will not be offered on new documents.',
    ],
    'titles' => [
        'index' => 'Contacts',
        'create' => 'New contact',
        'edit' => 'Edit contact',
        'empty' => 'You have no active contacts yet',
        'empty_archived' => 'No archived contacts',
        'empty_message' => 'Add the companies and people you do business with.',
    ],
];
