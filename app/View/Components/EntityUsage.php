<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Application\User\Services\EntityUsageApplicationService;
use App\Domain\User\Enums\LimitedEntity;
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

        $usage = $this->usages->usage(
            UserId::fromInt((int) $userId),
            $this->entity,
        );

        // Warned from 80 %, full once the limit is reached; unlimited and not allowed have no bar
        $limit = (int) $usage->limit;
        $full = $limit > 0 && $usage->used >= $limit;
        $percent = 0;
        if ($limit > 0) {
            $percent = intdiv($usage->used * 100, $limit);
            $percent = min(100, $percent);
        }

        return view(
            'components.entity-usage',
            [
                'usage' => $usage,
                'label' => __('role_limits.usage.' . $this->entity->value),
                'barId' => 'usage-' . $this->entity->value,
                'percent' => Number::percentage(
                    $percent,
                    locale: App::getLocale(),
                ),
                'full' => $full,
                'near' => !$full && $percent >= 80,
                'plansUrl' => route(
                    'subscriptions.index',
                    ['locale' => App::getLocale()],
                ),
            ],
        );
    }
}
