<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Domain\User\Enums\LimitedEntity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class EntityUsageRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'entity' => ['required', Rule::enum(LimitedEntity::class)],
            'user_id' => 'required|integer|exists:users,id',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'entity' => __('role_limits.usage.title'),
            'user_id' => __('parties.fields.owner'),
        ];
    }
}
