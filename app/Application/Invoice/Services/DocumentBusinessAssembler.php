<?php

declare(strict_types=1);

namespace App\Application\Invoice\Services;

use App\Domain\Party\Contracts\BusinessReadRepositoryInterface;
use App\Domain\Party\DTO\DocumentParties;
use App\Domain\Party\DTO\DocumentPartyDTO;
use App\Domain\Party\Exceptions\ArchivedReferenceSelectedException;
use App\Domain\Party\Exceptions\BusinessNotFoundException;
use App\Domain\Party\Exceptions\IncompleteBusinessAddressException;
use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\User\ValueObjects\UserId;

final class DocumentBusinessAssembler
{
    public function __construct(private readonly BusinessReadRepositoryInterface $businesses) {}

    public function assemble(
        UserId $userId,
        BusinessId $businessId,
        ?DocumentParties $stored,
        bool $reload,
        bool $supplier,
    ): DocumentPartyDTO {
        $business = $this->businesses->findByIdForUser($businessId, $userId) ?? throw new BusinessNotFoundException($businessId);
        $sameBusiness = $stored?->businessId->equals($businessId) === true;
        if ($business->isArchived() && ! $sameBusiness) {
            throw new ArchivedReferenceSelectedException($businessId);
        }
        if ($sameBusiness && ! $reload) {
            return $supplier ? $stored->supplier : $stored->customer;
        }
        $party = DocumentPartyDTO::fromProfile($business->profile);
        if ($supplier && ! $party->hasCompleteAddress()) {
            throw new IncompleteBusinessAddressException($businessId);
        }

        return $party;
    }
}
