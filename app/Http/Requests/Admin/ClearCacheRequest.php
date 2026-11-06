<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class ClearCacheRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = backpack_user();

        return $user?->can('can_configure_system') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
