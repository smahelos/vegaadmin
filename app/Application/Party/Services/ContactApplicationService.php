<?php

declare(strict_types=1);

namespace App\Application\Party\Services;

use App\Domain\Party\Contracts\ContactReadRepositoryInterface;
use App\Domain\Party\Contracts\ContactWriteRepositoryInterface;
use App\Domain\Party\DTO\ContactDTO;
use App\Domain\Party\DTO\ContactWriteData;
use App\Domain\Party\DTO\DuplicatePartyDTO;
use App\Domain\Party\DTO\PartyProfile;
use App\Domain\Party\Events\ContactArchived;
use App\Domain\Party\Events\ContactCreated;
use App\Domain\Party\Events\ContactRestored;
use App\Domain\Party\Events\ContactUpdated;
use App\Domain\Party\Exceptions\ContactNotFoundException;
use App\Domain\Party\Services\CapacityLimit;
use App\Domain\Party\Services\DuplicateFinder;
use App\Domain\Party\ValueObjects\ContactId;
use App\Domain\Shared\Database\Contracts\TransactionBoundaryInterface;
use App\Domain\Shared\Events\Contracts\EventPublisherInterface;
use App\Domain\Shared\Time\Contracts\ClockInterface;
use App\Domain\User\Enums\EntityType;
use App\Domain\User\ValueObjects\UserId;

/**
 * The user's address book: one contact per company or person, whatever role it has on documents.
 */
final class ContactApplicationService
{
    public function __construct(
        private readonly ContactReadRepositoryInterface $contacts,
        private readonly ContactWriteRepositoryInterface $contactWriter,
        private readonly CapacityLimit $capacity,
        private readonly DuplicateFinder $duplicates,
        private readonly TransactionBoundaryInterface $transactions,
        private readonly EventPublisherInterface $events,
        private readonly ClockInterface $clock,
    ) {}

    public function create(UserId $userId, ContactWriteData $data): ContactDTO
    {
        return $this->transactions->transaction(function () use ($userId, $data): ContactDTO {
            $this->capacity->assertCanActivate($userId, EntityType::CONTACT);
            $contact = $this->contactWriter->create($userId, $data);
            $this->events->publish(new ContactCreated($contact->id, $userId));

            return $contact;
        });
    }

    public function update(
        UserId $userId,
        ContactId $id,
        ContactWriteData $data,
    ): ContactDTO {
        return $this->transactions->transaction(function () use ($userId, $id, $data): ContactDTO {
            $this->get($userId, $id);
            $contact = $this->contactWriter->update($id, $data);
            $this->events->publish(new ContactUpdated($id, $userId));

            return $contact;
        });
    }

    public function archive(UserId $userId, ContactId $id): void
    {
        $this->transactions->transaction(function () use ($userId, $id): void {
            if ($this->get($userId, $id)->isArchived()) {
                return;
            }

            $this->contactWriter->archive($id, $this->clock->now());
            $this->events->publish(new ContactArchived($id, $userId));
        });
    }

    /**
     * Restoring takes a place within the capacity limit.
     */
    public function restore(UserId $userId, ContactId $id): void
    {
        $this->transactions->transaction(function () use ($userId, $id): void {
            if (! $this->get($userId, $id)->isArchived()) {
                return;
            }

            $this->capacity->assertCanActivate($userId, EntityType::CONTACT);
            $this->contactWriter->restore($id);
            $this->events->publish(new ContactRestored($id, $userId));
        });
    }

    /**
     * @throws ContactNotFoundException
     */
    public function get(UserId $userId, ContactId $id): ContactDTO
    {
        return $this->contacts->findByIdForUser($id, $userId)
            ?? throw new ContactNotFoundException($id);
    }

    /**
     * @return list<ContactDTO>
     */
    public function listActive(UserId $userId): array
    {
        return $this->contacts->listActiveForUser($userId);
    }

    /**
     * @return list<ContactDTO>
     */
    public function listArchived(UserId $userId): array
    {
        return $this->contacts->listArchivedForUser($userId);
    }

    /**
     * Contacts the profile may duplicate; a blocking match is refused on save as well.
     *
     * @return list<DuplicatePartyDTO>
     */
    public function findDuplicates(
        UserId $userId,
        PartyProfile $profile,
        ?ContactId $except,
    ): array {
        return $this->duplicates->find($profile, $this->contacts->listPossibleDuplicates(
            $userId,
            $profile,
            $except,
        ));
    }
}
