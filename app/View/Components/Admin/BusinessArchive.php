<?php

declare(strict_types=1);

namespace App\View\Components\Admin;

use App\Application\Party\Services\BusinessApplicationService;
use App\Domain\Party\DTO\BusinessDTO;
use App\Domain\User\ValueObjects\UserId;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

final class BusinessArchive extends Component
{
    /** @var list<BusinessDTO> */
    public readonly array $replacements;

    public function __construct(
        BusinessApplicationService $businesses,
        public readonly int $businessId,
        int $userId,
        bool $isDefault,
    ) {
        $this->replacements = $isDefault ? array_values(array_filter(
            $businesses->listActive(UserId::fromInt($userId)),
            fn (BusinessDTO $business): bool => $business->id->value !== $businessId,
        )) : [];
    }

    public function render(): View
    {
        return view('components.admin.business-archive');
    }
}
