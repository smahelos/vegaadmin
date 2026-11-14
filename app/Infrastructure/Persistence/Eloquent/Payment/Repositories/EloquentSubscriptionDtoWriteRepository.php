<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Payment\Repositories;

use App\Domain\Payment\Contracts\SubscriptionDtoWriteRepositoryInterface;
use App\Domain\Payment\DTO\SubscriptionDTO;
use App\Domain\Payment\DTO\SubscriptionWriteData;
use App\Domain\Payment\Enums\SubscriptionStatus;
use App\Domain\Payment\Exceptions\SubscriptionInUseException;
use App\Domain\Payment\Exceptions\SubscriptionNotFoundException;
use App\Domain\Payment\ValueObjects\SubscriptionId;
use App\Domain\Shared\Authorization\ValueObjects\WriteScope;
use App\Infrastructure\Persistence\Eloquent\Payment\Mappers\EloquentSubscriptionMapper;
use App\Models\Subscription;
use DateTimeImmutable;

final class EloquentSubscriptionDtoWriteRepository implements SubscriptionDtoWriteRepositoryInterface
{
    public function __construct(
        private readonly EloquentSubscriptionWriteRepository $subscriptionWriteRepository,
        private readonly EloquentSubscriptionMapper $mapper,
    ) {}

    public function activateById(
        int $subscriptionId,
        DateTimeImmutable $starts,
        DateTimeImmutable $ends,
    ): bool {
        return $this->subscriptionWriteRepository->activateById(
            $subscriptionId,
            $starts,
            $ends,
        );
    }

    public function create(WriteScope $scope, SubscriptionWriteData $data): SubscriptionDTO
    {
        $subscription = new Subscription;
        $subscription->user_id = $scope->owner->toInt();
        $this->fill($subscription, $data);
        $subscription->save();

        return $this->mapper->toDto($subscription);
    }

    public function update(
        SubscriptionId $id,
        WriteScope $scope,
        SubscriptionWriteData $data,
    ): SubscriptionDTO {
        $subscription = $this->owned($id, $scope);
        $this->fill($subscription, $data);
        $subscription->save();

        return $this->mapper->toDto($subscription);
    }

    public function cancel(SubscriptionId $id, WriteScope $scope): void
    {
        $subscription = $this->owned($id, $scope);
        $subscription->status = SubscriptionStatus::CANCELLED->value;
        $subscription->save();
    }

    public function delete(SubscriptionId $id, WriteScope $scope): void
    {
        $subscription = $this->owned($id, $scope);
        $payments = $subscription->payments();
        if ($payments->exists()) {
            throw new SubscriptionInUseException($id->value);
        }

        $subscription->delete();
    }

    private function owned(SubscriptionId $id, WriteScope $scope): Subscription
    {
        $subscriptionQuery = Subscription::query();
        $subscriptionQuery->where('user_id', $scope->owner->toInt());

        return $subscriptionQuery->find($id->value)
            ?? throw new SubscriptionNotFoundException($id->value);
    }

    private function fill(Subscription $subscription, SubscriptionWriteData $data): void
    {
        $subscription->fill(
            [
                'subscription_plan_id' => $data->planId,
                'status' => $data->status->value,
                'amount' => $data->amount->getAmount(),
                'currency' => $data->amount->getCurrency(),
                'starts_at' => $data->starts,
                'ends_at' => $data->ends,
                'trial_ends_at' => $data->trialEnds,
                'next_billing_at' => $data->nextBilling,
                'metadata' => $data->metadata,
            ],
        );
    }
}
