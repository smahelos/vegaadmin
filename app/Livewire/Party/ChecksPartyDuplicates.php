<?php

declare(strict_types=1);

namespace App\Livewire\Party;

use App\Domain\Party\DTO\DuplicatePartyDTO;
use App\Domain\Party\DTO\PartyProfile;
use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\Party\ValueObjects\ContactId;

/**
 * The duplicate check before saving a business or contact. A Czech IČO match blocks the save and points to the
 * existing record, so the user can open or restore it; any other match is a warning the user confirms once.
 *
 * The component declares the action `save()`.
 */
trait ChecksPartyDuplicates
{
    /** @var list<array{name: string, archived: bool, reason: string, url: string}> */
    public array $duplicateWarnings = [];

    /** @var array{name: string, archived: bool, url: string}|null */
    public ?array $blockingDuplicate = null;

    public bool $duplicatesConfirmed = false;

    /**
     * @return list<DuplicatePartyDTO>
     */
    abstract protected function findDuplicates(PartyProfile $profile): array;

    abstract protected function duplicateUrl(BusinessId|ContactId $id): string;

    /**
     * A changed field may match other records, so earlier warnings no longer count as confirmed.
     */
    public function updatedParty(): void
    {
        $this->duplicatesConfirmed = false;
    }

    /**
     * Saves only after the user has seen the warnings once.
     */
    public function confirmDuplicates(): void
    {
        $this->duplicatesConfirmed = true;
        app()->call([$this, 'save']);
    }

    /**
     * Call after validation. False when a Czech IČO match blocks the save or warnings wait for confirmation.
     */
    protected function passesDuplicateCheck(PartyProfile $profile): bool
    {
        $this->blockingDuplicate = null;
        $warnings = [];

        foreach ($this->findDuplicates($profile) as $duplicate) {
            if ($duplicate->reason->isBlocking()) {
                $this->blockingDuplicate = [
                    'name' => $duplicate->name,
                    'archived' => $duplicate->archived,
                    'url' => $this->duplicateUrl($duplicate->id),
                ];

                return false;
            }

            $warnings[] = [
                'name' => $duplicate->name,
                'archived' => $duplicate->archived,
                'reason' => $duplicate->reason->value,
                'url' => $this->duplicateUrl($duplicate->id),
            ];
        }

        $this->duplicateWarnings = $warnings;

        return $warnings === [] || $this->duplicatesConfirmed;
    }

    /**
     * A concurrent save took the Czech IČO between the check and the insert.
     */
    protected function showBlockingDuplicate(BusinessId|ContactId $existing): void
    {
        $this->blockingDuplicate = [
            'name' => '',
            'archived' => false,
            'url' => $this->duplicateUrl($existing),
        ];
    }
}
