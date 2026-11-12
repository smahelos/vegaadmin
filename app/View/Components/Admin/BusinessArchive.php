<?php

declare(strict_types=1);

namespace App\View\Components\Admin;

use App\Application\Party\Queries\ListActiveBusinesses;
use App\Application\Shared\UseCase\Contracts\AuthorizingUseCaseRunnerInterface;
use App\Domain\Party\DTO\BusinessDTO;
use App\Domain\User\ValueObjects\UserId;
use App\Http\Support\CurrentActor;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

final class BusinessArchive extends Component
{
    /** @var list<BusinessDTO> */
    public readonly array $replacements;

    public function __construct(
        AuthorizingUseCaseRunnerInterface $useCases,
        CurrentActor $currentActor,
        public readonly int $businessId,
        int $userId,
        bool $isDefault,
    ) {
        $actor = $currentActor->find();
        if ($isDefault && $actor !== null) {
            $active = $useCases->run(
                $actor,
                new ListActiveBusinesses(
                    UserId::fromInt($userId),
                ),
            );
            $this->replacements = array_values(
                array_filter(
                    $active,
                    fn (BusinessDTO $business): bool => $business->id->value !== $businessId,
                ),
            );
        } else {
            $this->replacements = [];
        }
    }

    public function render(): View
    {
        return view('components.admin.business-archive');
    }
}
