<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class RunCronTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return backpack_auth()->user()?->can('can_create_edit_cron_task') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
