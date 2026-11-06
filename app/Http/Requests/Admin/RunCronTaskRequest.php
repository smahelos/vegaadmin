<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class RunCronTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = backpack_user();

        return $user?->can('can_create_edit_cron_task') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
