<?php

declare(strict_types=1);

namespace App\Application\Payment\Commands;

use App\Domain\Payment\Contracts\SubscriptionDtoWriteRepositoryInterface;
use App\Domain\Payment\Contracts\SubscriptionPaymentServiceInterface;
use App\Domain\Payment\Exceptions\SubscriptionNotFoundException;
use App\Domain\Shared\Authorization\ValueObjects\WriteScope;
use App\Domain\Shared\Database\Contracts\TransactionBoundaryInterface;

final class CancelSubscriptionHandler
{
    public function __construct(
        private readonly SubscriptionDtoWriteRepositoryInterface $subscriptionWriter,
        private readonly SubscriptionPaymentServiceInterface $payments,
        private readonly TransactionBoundaryInterface $transactions,
    ) {}

    /**
     * @throws SubscriptionNotFoundException
     */
    public function handle(CancelSubscription $command, WriteScope $scope): void
    {
        $this->transactions->transaction(
            fn () => $this->subscriptionWriter->cancel($command->id, $scope),
        );
        $this->payments->cancelSubscriptionPayment($command->id->value);
    }
}
