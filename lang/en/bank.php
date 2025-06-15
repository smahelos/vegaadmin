<?php

return [
    'title' => 'Bank',
    'title_plural' => 'Banks',
    'list' => 'Bank List',
    'created_at' => 'Created at',
    'updated_at' => 'Updated at',

    'actions' => [
        'edit' => 'Edit',
        'delete' => 'Delete',
        'view' => 'View',
    ],

    'fields' => [
        'name' => 'Bank Name',
        'code' => 'Bank Code',
        'swift' => 'SWIFT Code',
        'country' => 'Country',
        'active' => 'Active',
    ],

    'validation' => [
        'name' => 'Bank name is required.',
        'code' => 'Bank code is required.',
        'swift' => 'SWIFT code is required.',
        'success_create' => 'Bank was successfully created.',
        'success_update' => 'Bank was successfully updated.',
        'success_delete' => 'Bank was successfully deleted.',
        'error_create' => 'An error occurred while creating the bank.',
        'error_update' => 'An error occurred while updating the bank.',
        'error_delete' => 'An error occurred while deleting the bank.',
        'code_unique' => 'Bank code already exists.',
        'country' => 'Country is required.',
        'country_size' => 'Country must be 2 characters.',
    ],
];
