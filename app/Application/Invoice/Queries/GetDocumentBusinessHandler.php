<?php

declare(strict_types=1);

namespace App\Application\Invoice\Queries;

use App\Domain\Party\Contracts\BusinessReadRepositoryInterface;
use App\Domain\Party\DTO\BusinessDTO;
use App\Domain\Party\Exceptions\BusinessNotFoundException;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;

final class GetDocumentBusinessHandler
{
    public function __construct(
        private readonly BusinessReadRepositoryInterface $businesses,
    ) {}

    public function handle(GetDocumentBusiness $query, ReadScope $scope): BusinessDTO
    {
        return $this->businesses->findById($query->id, $scope)
            ?? throw new BusinessNotFoundException($query->id);
    }
}
