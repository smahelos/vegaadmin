<?php

declare(strict_types=1);

namespace App\Application\Party\Commands;

use App\Domain\Party\Contracts\BankAccountWriteRepositoryInterface;
use App\Domain\Party\Contracts\BusinessReadRepositoryInterface;
use App\Domain\Party\Contracts\BusinessWriteRepositoryInterface;
use App\Domain\Party\DTO\BusinessDTO;
use App\Domain\Party\Events\BusinessCreated;
use App\Domain\Party\Events\DefaultBusinessChanged;
use App\Domain\Party\Exceptions\BusinessNotFoundException;
use App\Domain\Party\ValueObjects\AccountOwner;
use App\Domain\Shared\Authorization\ValueObjects\WriteScope;
use App\Domain\Shared\Events\Contracts\EventPublisherInterface;
use App\Domain\User\Enums\LimitedEntity;
use App\Domain\User\Services\EntityLimit;

/**
 * The business with its address, its first bank account and the default selection succeed or fail together. The
 * first active business of the account becomes its default; a given account becomes the business's default account.
 */
final class CreateBusinessHandler
{
    public function __construct(
        private readonly BusinessReadRepositoryInterface $businesses,
        private readonly BusinessWriteRepositoryInterface $businessWriter,
        private readonly BankAccountWriteRepositoryInterface $accountWriter,
        private readonly EntityLimit $limits,
        private readonly EventPublisherInterface $events,
    ) {}

    public function handle(CreateBusiness $command, WriteScope $scope): BusinessDTO
    {
        $this->limits->assertCanCreate($command->owner, LimitedEntity::BUSINESS);
        $isFirstActive = $this->businesses->countActiveForUpdate($scope->read) === 0;

        $business = $this->businessWriter->create($scope, $command->data);

        if ($command->firstAccount !== null) {
            $owner = AccountOwner::business($business->id);
            $account = $this->accountWriter->create(
                $scope,
                $owner,
                $command->firstAccount,
            );
            $this->accountWriter->setDefaultForOwner(
                $scope,
                $owner,
                $account->id,
            );
        }

        $this->events->publish(
            new BusinessCreated($command->owner),
        );

        if ($isFirstActive) {
            $this->businessWriter->setDefault($scope, $business->id);
            $this->events->publish(
                new DefaultBusinessChanged($command->owner),
            );
        }

        // Read back, so the returned business carries its default account
        return $this->businesses->findById($business->id, $scope->read)
            ?? throw new BusinessNotFoundException($business->id);
    }
}
