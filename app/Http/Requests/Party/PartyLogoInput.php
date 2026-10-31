<?php

declare(strict_types=1);

namespace App\Http\Requests\Party;

final class PartyLogoInput
{
    /** @return array<string, list<string>> */
    public static function rules(): array
    {
        return [
            'logo' => [
                'nullable',
                'image',
                'mimes:png,jpg,jpeg,webp',
                'max:2048',
            ],
            'remove_logo' => ['nullable', 'boolean'],
        ];
    }
}
