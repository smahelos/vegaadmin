<?php

declare(strict_types=1);

namespace App\Application\Party\Commands;

use App\Domain\Party\Contracts\ContactWriteRepositoryInterface;
use App\Domain\Party\DTO\ContactDTO;
use App\Domain\Party\Events\ContactCreated;
use App\Domain\Shared\Authorization\ValueObjects\WriteScope;
use App\Domain\Shared\Events\Contracts\EventPublisherInterface;
use App\Domain\User\Enums\LimitedEntity;
use App\Domain\User\Services\EntityLimit;

final class CreateContactHandler
{
    public function __construct(
        private readonly ContactWriteRepositoryInterface $contacts,
        private readonly EntityLimit $limits,
        private readonly EventPublisherInterface $events,
    ) {}

    public function handle(CreateContact $command, WriteScope $scope): ContactDTO
    {
        $this->limits->assertCanCreate($command->owner, LimitedEntity::CONTACT);
        $contact = $this->contacts->create($scope, $command->data);
        $this->events->publish(
            new ContactCreated($command->owner),
        );

        return $contact;
    }
}
