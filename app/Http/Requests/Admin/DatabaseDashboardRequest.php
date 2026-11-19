<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Application\Shared\UseCase\Actor;
use App\Application\Shared\UseCase\Contracts\AuthorizingUseCaseRunnerInterface;
use App\Domain\User\Enums\Permission;
use App\Domain\User\ValueObjects\UserId;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Guards the database dashboard until its actions become use cases (docs/TECH_DEBT.md, step 20).
 */
final class DatabaseDashboardRequest extends FormRequest
{
    public function authorize(AuthorizingUseCaseRunnerInterface $useCases): bool
    {
        $guard = backpack_auth();
        if ($guard->id() === null) {
            return false;
        }

        return $useCases->holds(
            Actor::admin(
                UserId::fromInt((int) $guard->id()),
            ),
            Permission::SYSTEM_CONFIGURE,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
