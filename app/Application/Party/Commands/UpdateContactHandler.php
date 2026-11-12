<?php

declare(strict_types=1);

namespace App\Application\Party\Commands;

use App\Domain\Party\Contracts\ContactReadRepositoryInterface;
use App\Domain\Party\Contracts\ContactWriteRepositoryInterface;
use App\Domain\Party\DTO\ContactDTO;
use App\Domain\Party\Events\ContactUpdated;
use App\Domain\Party\Exceptions\ContactNotFoundException;
use App\Domain\Shared\Authorization\ValueObjects\WriteScope;
use App\Domain\Shared\Events\Contracts\EventPublisherInterface;

final class UpdateContactHandler
{
    public function __construct(
        private readonly ContactReadRepositoryInterface $contacts,
        private readonly ContactWriteRepositoryInterface $contactWriter,
        private readonly EventPublisherInterface $events,
    ) {}

    public function handle(UpdateContact $command, WriteScope $scope): ContactDTO
    {
        if ($this->contacts->findById($command->id, $scope->read) === null) {
            throw new ContactNotFoundException($command->id);
        }

        $contact = $this->contactWriter->update(
            $command->id,
            $scope,
            $command->data,
        );
        $this->events->publish(
            new ContactUpdated($command->owner),
        );

        return $contact;
    }
}
