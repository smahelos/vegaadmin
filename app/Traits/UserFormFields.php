<?php

namespace App\Traits;

trait UserFormFields
{
    /**
     * Get user form fields definitions
     *
     * @return array
     */
    public function getUserFields(): array
    {
        return [
            [
                'name' => 'name',
                'type' => 'text',
                'label' => __('users.fields.name'),
                'placeholder' => __('users.placeholders.name'),
                'required' => true,
            ],
            [
                'name' => 'email',
                'type' => 'email',
                'label' => __('users.fields.email'),
                'placeholder' => __('users.placeholders.email'),
                'required' => true,
            ],
        ];
    }

    /**
     * Get password field definitions for password change form
     *
     * @param bool $isEdit Whether the form is for editing an existing user
     * @return array
     */
    public function getPasswordFields(bool $isEdit = false): array
    {
        $fields = [
            [
                'name' => 'password',
                'type' => 'password',
                'label' => __('users.fields.new_password'),
                'placeholder' => __('users.placeholders.new_password'),
                'required' => true,
                'wrapper' => [
                    'class' => 'form-group col-md-6',
                ],
                'hint' => __('users.fields.password_hint'),
            ],
            [
                'name' => 'password_confirmation',
                'type' => 'password',
                'label' => __('users.fields.password_confirmation'),
                'placeholder' => __('users.placeholders.password_confirmation'),
                'required' => true,
                'wrapper' => [
                    'class' => 'form-group col-md-6',
                ],
            ],
            [
                'name' => 'current_password',
                'type' => 'password',
                'label' => __('users.fields.current_password'),
                'placeholder' => __('users.placeholders.current_password'),
                'required' => true,
                'wrapper' => [
                    'class' => 'form-group col-md-6',
                ],
                'hint' => __('users.fields.current_password_hint'),
            ],
        ];
        
        return $fields;
    }
}
