<?php

namespace App\Traits;

trait ClientFormFields
{
    /**
     * Get client form fields definitions
     */
    protected function getClientFields()
    {
        return [
            [
                'name' => 'name',
                'label' => __('clients.fields.name'),
                'type' => 'text',
                'hint' => __('clients.hints.name'),
                'required' => true,
            ],
            [
                'name' => 'shortcut',
                'label' => __('clients.fields.shortcut'),
                'type' => 'text',
                'hint' => __('clients.hints.shortcut'),
            ],
            [
                'name' => 'email',
                'label' => __('clients.fields.email'),
                'type' => 'email',
                'hint' => __('clients.hints.email'),
            ],
            [
                'name' => 'phone',
                'label' => __('clients.fields.phone'),
                'type' => 'text',
                'hint' => __('clients.hints.phone'),
            ],
            [
                'name' => 'street',
                'label' => __('clients.fields.street'),
                'type' => 'text',
                'hint' => __('clients.hints.street'),
                'required' => true,
            ],
            [
                'name' => 'city',
                'label' => __('clients.fields.city'),
                'type' => 'text',
                'hint' => __('clients.hints.city'),
                'required' => true,
            ],
            [
                'name' => 'zip',
                'label' => __('clients.fields.zip'),
                'type' => 'text',
                'hint' => __('clients.hints.zip'),
                'required' => true,
            ],
            [
                'name' => 'country',
                'label' => __('clients.fields.country'),
                'type' => 'text',
                'hint' => __('clients.hints.country'),
                'required' => true,
            ],
            [
                'name' => 'ico',
                'label' => __('clients.fields.ico'),
                'type' => 'text',
                'hint' => __('clients.validation.ico_format'),
            ],
            [
                'name' => 'dic',
                'label' => __('clients.fields.dic'),
                'type' => 'text',
                'hint' => __('clients.fields.dic') . ' ' . __('clients.placeholders.not_specified'),
            ],
            [
                'name' => 'description',
                'label' => __('clients.fields.description'),
                'type' => 'textarea',
                'hint' => __('clients.hints.description'),
            ],
            [
                'name' => 'is_default',
                'label' => __('clients.fields.is_default'),
                'type' => 'checkbox',
                'hint' => __('clients.messages.is_default_explanation'),
            ],
        ];
    }
}