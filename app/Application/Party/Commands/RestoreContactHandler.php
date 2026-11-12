<?php

declare(strict_types=1);

namespace App\Application\Party\Commands;

use App\Domain\Party\Contracts\ContactReadRepositoryInterface;
use App\Domain\Party\Contracts\ContactWriteRepositoryInterface;
use App\Domain\Party\Events\ContactRestored;
use App\Domain\Party\Exceptions\ContactNotFoundException;
use App\Domain\Shared\Authorization\ValueObjects\WriteScope;
use App\Domain\Shared\Events\Contracts\EventPublisherInterface;
use App\Domain\User\Enums\LimitedEntity;
use App\Domain\User\Services\EntityLimit;

/**
 * Restoring takes a place within the capacity limit.
 */
final class RestoreContactHandler
{
    public function __construct(
        private readonly ContactReadRepositoryInterface $contacts,
        private readonly ContactWriteRepositoryInterface $contactWriter,
        private readonly EntityLimit $limits,
        private readonly EventPublisherInterface $events,
    ) {}

    public function handle(RestoreContact $command, WriteScope $scope): void
    {
        $contact = $this->contacts->findById($command->id, $scope->read)
            ?? throw new ContactNotFoundException($command->id);
        if (!$contact->isArchived()) {
            return;
        }

        $this->limits->assertCanCreate($command->owner, LimitedEntity::CONTACT);
        $this->contactWriter->restore($command->id, $scope);
        $this->events->publish(
            new ContactRestored($command->owner),
        );
    }
}
