<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Application\Shared\UseCase\Contracts\AuthorizingUseCaseRunnerInterface;
use App\Application\User\Queries\GetEntityUsage;
use App\Domain\User\Enums\LimitedEntity;
use App\Domain\User\Exceptions\AmbiguousPlanRoleException;
use App\Domain\User\ValueObjects\UserId;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EntityUsageRequest;
use Illuminate\View\View;

final class EntityUsageController extends Controller
{
    use ActsAsAdmin;

    public function __construct(
        private readonly AuthorizingUseCaseRunnerInterface $useCases,
    ) {}

    /**
     * The usage of one plan limit for the owner selected in an admin form, as the user detail shows it.
     */
    public function show(EntityUsageRequest $request): View
    {
        try {
            // The route's `allowed` middleware names GetEntityUsage: change them together
            $usages = [
                $this->useCases->run(
                    $this->admin(),
                    new GetEntityUsage(
                        UserId::fromInt(
                            $request->integer('user_id'),
                        ),
                        LimitedEntity::from(
                            $request->validated('entity'),
                        ),
                    ),
                ),
            ];
        } catch (AmbiguousPlanRoleException) {
            $usages = null;
        }

        return view(
            'admin.columns.entity_usage',
            ['column' => ['value' => $usages]],
        );
    }
}
