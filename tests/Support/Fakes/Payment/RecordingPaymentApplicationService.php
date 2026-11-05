<?php

declare(strict_types=1);

namespace Tests\Support\Fakes\Payment;

use App\Application\Payment\Contracts\PaymentApplicationServiceInterface;
use App\Application\Payment\DTO\SubscriptionPaymentResultDTO;
use App\Models\Subscription;

final class RecordingPaymentApplicationService implements PaymentApplicationServiceInterface
{
    public const REDIRECT_URL = 'https://gate.test/1';

    /** @var list<array<string, mixed>> */
    public array $initiated = [];

    public function initiateSubscription(
        Subscription $subscription,
        string $gateway,
        array $data = [],
    ): SubscriptionPaymentResultDTO {
        $this->initiated[] = $data;

        return SubscriptionPaymentResultDTO::fromArray(
            [
                'success' => true,
                'redirect_url' => self::REDIRECT_URL,
            ],
        );
    }

    public function handleCallback(string $gateway, array $payload): SubscriptionPaymentResultDTO
    {
        return SubscriptionPaymentResultDTO::fromArray(['success' => false]);
    }

    public function getAvailableGateways(): array
    {
        return [];
    }

    public function cancelSubscription(Subscription $subscription): bool
    {
        return false;
    }
}
