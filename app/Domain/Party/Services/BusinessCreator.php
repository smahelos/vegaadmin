<?php

declare(strict_types=1);

namespace App\Domain\Party\Services;

use App\Domain\Party\Contracts\BankAccountWriteRepositoryInterface;
use App\Domain\Party\Contracts\BusinessReadRepositoryInterface;
use App\Domain\Party\Contracts\BusinessWriteRepositoryInterface;
use App\Domain\Party\DTO\BankAccountWriteData;
use App\Domain\Party\DTO\BusinessDTO;
use App\Domain\Party\DTO\BusinessWriteData;
use App\Domain\Party\Events\BusinessCreated;
use App\Domain\Party\Events\DefaultBusinessChanged;
use App\Domain\Party\Exceptions\BusinessNotFoundException;
use App\Domain\Party\ValueObjects\AccountOwner;
use App\Domain\Shared\Events\Contracts\EventPublisherInterface;
use App\Domain\User\Enums\EntityType;
use App\Domain\User\ValueObjects\UserId;

/**
 * Creates a business for the business screens and for registration alike, which call it inside their
 * transaction: the capacity check, the business with its address, its first bank account and the default
 * selection succeed or fail together.
 */
final class BusinessCreator
{
    public function __construct(
        private readonly BusinessReadRepositoryInterface $businesses,
        private readonly BusinessWriteRepositoryInterface $businessWriter,
        private readonly BankAccountWriteRepositoryInterface $accountWriter,
        private readonly CapacityLimit $capacity,
        private readonly EventPublisherInterface $events,
    ) {}

    /**
     * The first active business of the account becomes its default; a given account becomes the business's
     * default account.
     */
    public function create(
        UserId $userId,
        BusinessWriteData $data,
        ?BankAccountWriteData $firstAccount,
    ): BusinessDTO {
        $this->capacity->assertCanActivate($userId, EntityType::BUSINESS);
        $isFirstActive = $this->businesses->countActiveForUserForUpdate($userId) === 0;

        $business = $this->businessWriter->create($userId, $data);

        if ($firstAccount !== null) {
            $owner = AccountOwner::business($business->id);
            $account = $this->accountWriter->create($owner, $firstAccount);
            $this->accountWriter->setDefaultForOwner($owner, $account->id);
        }

        $this->events->publish(new BusinessCreated($business->id, $userId));

        if ($isFirstActive) {
            $this->businessWriter->setDefaultForUser($userId, $business->id);
            $this->events->publish(new DefaultBusinessChanged($business->id, $userId));
        }

        // Read back, so the returned business carries its default account
        return $this->businesses->findByIdForUser($business->id, $userId)
            ?? throw new BusinessNotFoundException($business->id);
    }
}
