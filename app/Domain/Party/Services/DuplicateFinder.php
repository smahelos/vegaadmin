<?php

declare(strict_types=1);

namespace App\Domain\Party\Services;

use App\Domain\Party\DTO\BusinessDTO;
use App\Domain\Party\DTO\ContactDTO;
use App\Domain\Party\DTO\DuplicatePartyDTO;
use App\Domain\Party\DTO\PartyProfile;
use App\Domain\Party\Enums\DuplicateReason;

/**
 * Decides which existing records of one entity may be the party being saved. Businesses and contacts are checked
 * separately: the same company may be both without being a duplicate.
 */
final class DuplicateFinder
{
    /**
     * @param  list<BusinessDTO|ContactDTO>  $candidates
     * @return list<DuplicatePartyDTO> blocking matches first
     */
    public function find(PartyProfile $profile, array $candidates): array
    {
        $duplicates = [];
        foreach ($candidates as $candidate) {
            $reason = $this->reason($profile, $candidate->profile);
            if ($reason === null) {
                continue;
            }

            $duplicates[] = new DuplicatePartyDTO(
                $candidate->id,
                $candidate->profile->name,
                $candidate->isArchived(),
                $reason,
            );
        }

        usort(
            $duplicates,
            fn (DuplicatePartyDTO $a, DuplicatePartyDTO $b): int => $b->reason->isBlocking() <=> $a->reason->isBlocking(),
        );

        return $duplicates;
    }

    /**
     * The strongest thing the two profiles share: the registration identity, then the name, then the email.
     */
    private function reason(PartyProfile $profile, PartyProfile $existing): ?DuplicateReason
    {
        $number = $profile->registrationNumber;
        $existingNumber = $existing->registrationNumber;
        if (
            $number !== null &&
            $existingNumber !== null &&
            $number->country === $existingNumber->country &&
            $number->number === $existingNumber->number
        ) {
            return $number->isCzech() ? DuplicateReason::CZECH_COMPANY_NUMBER : DuplicateReason::COMPANY_NUMBER;
        }

        if ($this->sameText($profile->name, $existing->name)) {
            return DuplicateReason::NAME;
        }

        if ($this->sameText($profile->email, $existing->email)) {
            return DuplicateReason::EMAIL;
        }

        return null;
    }

    private function sameText(?string $a, ?string $b): bool
    {
        if ($a === null || $b === null) {
            return false;
        }

        return mb_strtolower(trim($a)) === mb_strtolower(trim($b));
    }
}
