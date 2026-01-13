<?php

namespace Tests\Support\Fakes\Payment;

use GoPay\Payments;

/**
 * Minimal stub of GoPay Payments to simulate API responses for behavior tests.
 * We avoid hitting real GoPay endpoints (no credentials required).
 */
class StubPayments extends Payments
{
    /** @var array<string,array<string,mixed>> */
    private array $store = [];
    public array $config;
    public bool $failCreate = false;
    public bool $failStatus = false;
    public bool $failRefund = false;
    public bool $failCancel = false;

    public function __construct(array $config = [])
    {
        // Do not call parent; Payments has complex internals; we only emulate used subset.
        $this->config = $config;
    }

    private function fakeResponse(bool $success, array $json = []): object
    {
        return new class($success, $json) {
            public function __construct(public bool $success, public array $json) {}
            public function hasSucceed(): bool { return $this->success; }
        };
    }

    public function createPayment(array $payment): object
    {
        if ($this->failCreate) {
            return $this->fakeResponse(false, ['error' => 'create_failed']);
        }
        $id = 'GP-' . (count($this->store) + 1);
        $this->store[$id] = [
            'id' => $id,
            'state' => 'CREATED',
            'gw_url' => 'https://fake.gopay/redirect/' . $id,
            'amount' => $payment['amount'] ?? 0,
        ];
        return $this->fakeResponse(true, $this->store[$id]);
    }

    public function getStatus($id): object
    {
        if ($this->failStatus) {
            return $this->fakeResponse(false, ['error' => 'status_failed']);
        }
        $data = $this->store[$id] ?? ['id' => $id, 'state' => 'CREATED'];
        return $this->fakeResponse(true, $data);
    }

    public function refundPayment($id, $data): object
    {
        if ($this->failRefund) {
            return $this->fakeResponse(false, ['error' => 'refund_failed']);
        }
        if (isset($this->store[$id])) {
            $this->store[$id]['state'] = isset($data['amount']) ? 'PARTIALLY_REFUNDED' : 'REFUNDED';
        }
        return $this->fakeResponse(true, $this->store[$id] ?? ['id' => $id, 'state' => 'REFUNDED']);
    }

    public function voidAuthorization($id): object
    {
        if ($this->failCancel) {
            return $this->fakeResponse(false, ['error' => 'cancel_failed']);
        }
        if (isset($this->store[$id])) {
            $this->store[$id]['state'] = 'CANCELED';
        }
        return $this->fakeResponse(true, $this->store[$id] ?? ['id' => $id, 'state' => 'CANCELED']);
    }
}
