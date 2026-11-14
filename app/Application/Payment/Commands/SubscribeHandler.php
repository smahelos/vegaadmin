<?php

declare(strict_types=1);

namespace App\Application\Payment\Commands;

use App\Application\Payment\DTO\SubscriptionPaymentResultDTO;
use App\Domain\Payment\Contracts\SubscriptionDtoReadRepositoryInterface;
use App\Domain\Payment\Contracts\SubscriptionDtoWriteRepositoryInterface;
use App\Domain\Payment\Contracts\SubscriptionPaymentServiceInterface;
use App\Domain\Payment\Contracts\SubscriptionPlanReadRepositoryInterface;
use App\Domain\Payment\DTO\SubscriptionDTO;
use App\Domain\Payment\DTO\SubscriptionWriteData;
use App\Domain\Payment\Enums\SubscriptionStatus;
use App\Domain\Payment\Exceptions\ActiveSubscriptionExistsException;
use App\Domain\Payment\Exceptions\SubscriptionPlanNotFoundException;
use App\Domain\Payment\ValueObjects\SubscriptionId;
use App\Domain\Shared\Authorization\ValueObjects\WriteScope;
use App\Domain\Shared\Database\Contracts\TransactionBoundaryInterface;

final class SubscribeHandler
{
    public function __construct(
        private readonly SubscriptionPlanReadRepositoryInterface $plans,
        private readonly SubscriptionDtoReadRepositoryInterface $subscriptions,
        private readonly SubscriptionDtoWriteRepositoryInterface $subscriptionWriter,
        private readonly SubscriptionPaymentServiceInterface $payments,
        private readonly TransactionBoundaryInterface $transactions,
    ) {}

    /**
     * @throws ActiveSubscriptionExistsException
     * @throws SubscriptionPlanNotFoundException
     */
    public function handle(Subscribe $command, WriteScope $scope): SubscriptionPaymentResultDTO
    {
        $plan = $this->plans->findById($command->planId)
            ?? throw new SubscriptionPlanNotFoundException($command->planId);
        $subscription = $this->transactions->transaction(
            function () use ($scope, $plan): SubscriptionDTO {
                if ($this->subscriptions->findActive($scope->read) !== null) {
                    throw new ActiveSubscriptionExistsException;
                }

                return $this->subscriptionWriter->create(
                    $scope,
                    new SubscriptionWriteData(
                        $plan->id,
                        SubscriptionStatus::PENDING,
                        $plan->amount,
                    ),
                );
            },
        );

        $payment = $this->payments->processSubscriptionPayment(
            $subscription->id,
            $command->gateway,
            [
                'return_url' => $command->returnUrl,
                'notify_url' => $command->notifyUrl,
                'lang' => $command->language,
            ],
        );
        $result = SubscriptionPaymentResultDTO::fromArray($payment);
        if (!$result->success) {
            // shortcut: a stored payment row blocks the delete (ON DELETE RESTRICT), TECH_DEBT step 18 "A refused subscription payment ends with a 500"
            $this->transactions->transaction(
                fn () => $this->subscriptionWriter->delete(
                    SubscriptionId::fromInt($subscription->id),
                    $scope,
                ),
            );
        }

        return $result;
    }
}
