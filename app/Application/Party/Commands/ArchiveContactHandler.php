<?php

declare(strict_types=1);

namespace App\Application\Party\Commands;

use App\Domain\Party\Contracts\ContactReadRepositoryInterface;
use App\Domain\Party\Contracts\ContactWriteRepositoryInterface;
use App\Domain\Party\Events\ContactArchived;
use App\Domain\Party\Exceptions\ContactNotFoundException;
use App\Domain\Shared\Authorization\ValueObjects\WriteScope;
use App\Domain\Shared\Events\Contracts\EventPublisherInterface;
use App\Domain\Shared\Time\Contracts\ClockInterface;

final class ArchiveContactHandler
{
    public function __construct(
        private readonly ContactReadRepositoryInterface $contacts,
        private readonly ContactWriteRepositoryInterface $contactWriter,
        private readonly EventPublisherInterface $events,
        private readonly ClockInterface $clock,
    ) {}

    public function handle(ArchiveContact $command, WriteScope $scope): void
    {
        $contact = $this->contacts->findById($command->id, $scope->read)
            ?? throw new ContactNotFoundException($command->id);
        if ($contact->isArchived()) {
            return;
        }

        $this->contactWriter->archive(
            $command->id,
            $scope,
            $this->clock->now(),
        );
        $this->events->publish(
            new ContactArchived($command->owner),
        );
    }
}
