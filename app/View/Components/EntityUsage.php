<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Application\User\Services\EntityUsageApplicationService;
use App\Domain\User\Enums\LimitedEntity;
use App\Domain\User\ValueObjects\UserId;
use Illuminate\Support\Facades\Auth;
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

        // The meter turns yellow from 80 % and red once the limit is reached (high sits just below the limit)
        return view(
            'components.entity-usage',
            [
                'usage' => $usage,
                'label' => __('role_limits.usage.' . $this->entity->value),
                'meterId' => 'usage-' . $this->entity->value,
                'low' => (int) $usage->limit * 0.8,
                'high' => (int) $usage->limit - 0.5,
            ],
        );
    }
}
