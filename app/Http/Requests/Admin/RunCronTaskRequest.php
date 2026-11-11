<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Domain\User\Enums\Permission;
use Illuminate\Foundation\Http\FormRequest;

final class RunCronTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = backpack_user();

        return $user?->can(Permission::CRON_TASK_RUN) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
