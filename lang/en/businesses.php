<?php

return [
    'entity' => [
        'singular' => 'Business',
        'plural' => 'My businesses',
    ],
    'fields' => [
        'logo' => 'Logo',
        'default' => 'Default',
        'new_default' => 'New default business',
    ],
    'hints' => [
        'logo' => 'PNG, JPG or WebP up to 2 MB, printed on invoices.',
        'first_bank_account' => 'Optional. An account entered here becomes the business’s default account; add further accounts on the business detail.',
    ],
    'actions' => [
        'create' => 'Add business',
        'back_to_list' => 'Back to list',
        'make_default' => 'Make default',
        'remove_logo' => 'Remove logo',
    ],
    'messages' => [
        'created' => 'The business has been created.',
        'updated' => 'The business has been updated.',
        'archived' => 'The business has been archived.',
        'restored' => 'The business has been restored.',
        'default_changed' => 'The default business has been changed.',
        'default_archived' => 'An archived business cannot be the default.',
        'default_required' => 'Choose another default business before archiving the default one.',
        'new_default_archived' => 'The chosen new default business is archived.',
        'limit_exceeded' => 'You have reached the limit of active businesses. Archive a business or upgrade your plan.',
        'confirm_archive' => 'Do you really want to archive the business? It will not be offered on new documents.',
        'choose_new_default' => 'This business is the default. Choose which of the other active businesses becomes the default.',
        'no_logo' => 'The business has no logo.',
    ],
    'sections' => [
        'details' => 'Business details',
        'first_bank_account' => 'Bank account',
    ],
    'titles' => [
        'index' => 'My businesses',
        'create' => 'New business',
        'edit' => 'Edit business',
        'empty' => 'You have no active business yet',
        'empty_archived' => 'No archived businesses',
        'empty_message' => 'Add the business on whose behalf you issue documents.',
        'choose_new_default' => 'New default business',
    ],
];
