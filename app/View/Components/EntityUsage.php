<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Application\User\Services\EntityUsageApplicationService;
use App\Domain\User\Enums\LimitedEntity;
use App\Domain\User\Exceptions\AmbiguousPlanRoleException;
use App\Domain\User\ValueObjects\UserId;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Number;
use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * The signed-in user's usage of one plan limit; renders nothing for a guest (the guest invoice has no limit).
 */
final class EntityUsage extends Component
{
    public function __construct(
        private readonly LimitedEntity $entity,
        private readonly EntityUsageApplicationService $usages,
    ) {}

    public function render(): View|string
    {
        $guard = Auth::guard('web');
        $userId = $guard->id();
        if ($userId === null) {
            return '';
        }

        $label = __('role_limits.usage.' . $this->entity->value);

        try {
            $usage = $this->usages->usage(
                UserId::fromInt((int) $userId),
                $this->entity,
            );
        } catch (AmbiguousPlanRoleException) {
            // Creating stays refused, but the page showing the usage must still open
            return view(
                'components.entity-usage',
                ['usage' => null, 'label' => $label],
            );
        }

        return view(
            'components.entity-usage',
            [
                'usage' => $usage,
                'label' => $label,
                'barId' => 'usage-' . $this->entity->value,
                'percent' => Number::percentage(
                    $usage->percent(),
                    locale: App::getLocale(),
                ),
                'full' => $usage->isFull(),
                'near' => $usage->isNear(),
                'plansUrl' => route(
                    'subscriptions.index',
                    ['locale' => App::getLocale()],
                ),
            ],
        );
    }
}
