<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The query Backpack's select2_from_ajax field sends to the owner lookup.
 */
final class UserSearchRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'q' => 'nullable|string|max:255',
            'page' => 'nullable|integer|min:1',
        ];
    }
}
