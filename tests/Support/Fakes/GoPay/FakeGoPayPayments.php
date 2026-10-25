<?php

declare(strict_types=1);

namespace Tests\Support\Fakes\GoPay;

use GoPay\Http\Response;
use GoPay\Payments;

/**
 * In-memory GoPay payments API for GoPayGateway tests: no credentials, no HTTP.
 *
 * The parameters of the overridden methods stay untyped because GoPay\Payments declares them
 * untyped and PHP refuses a narrower parameter type in a subclass (phpcs.xml excludes this file).
 */
final class FakeGoPayPayments extends Payments
{
    /** @var array<string, array{id: string, state: string, amount: int, refunded: int, gw_url: string}> */
    private array $payments = [];

    /** @var list<array<string, mixed>> */
    public array $created = [];

    public bool $failCreate = false;

    public bool $failStatus = false;

    public bool $failCancel = false;

    public bool $failRefund = false;

    public function __construct() {}

    /**
     * Changes the state on the gateway side, as a paying customer or a timeout would.
     */
    public function setState(string $id, string $state): void
    {
        $this->payments[$id]['state'] = $state;
    }

    /**
     * @param  array<string, mixed>  $rawPayment
     */
    public function createPayment(array $rawPayment): Response
    {
        if ($this->failCreate) {
            return $this->response(400, ['error' => 'create_failed']);
        }
        $this->created[] = $rawPayment;
        $id = 'GP-' . count($this->created);
        $this->payments[$id] = [
            'id' => $id,
            'state' => 'CREATED',
            'amount' => (int) $rawPayment['amount'],
            'refunded' => 0,
            'gw_url' => 'https://gate.gopay.test/' . $id,
        ];

        return $this->response(200, $this->payments[$id]);
    }

    public function getStatus($id): Response
    {
        if ($this->failStatus) {
            return $this->response(500, ['error' => 'status_failed']);
        }
        if (! isset($this->payments[$id])) {
            return $this->response(404, ['error' => 'not_found']);
        }

        return $this->response(200, $this->payments[$id]);
    }

    public function voidAuthorization($id): Response
    {
        if ($this->failCancel || ! isset($this->payments[$id])) {
            return $this->response(400, ['error' => 'cancel_failed']);
        }
        $this->payments[$id]['state'] = 'CANCELED';

        return $this->response(200, ['id' => $id, 'result' => 'FINISHED']);
    }

    /**
     * Refunds the given amount in cents, or the rest of the payment without an amount.
     */
    public function refundPayment($id, $data): Response
    {
        if ($this->failRefund || ! isset($this->payments[$id])) {
            return $this->response(400, ['error' => 'refund_failed']);
        }
        $payment = &$this->payments[$id];
        $payment['refunded'] += $data['amount'] ?? $payment['amount'] - $payment['refunded'];
        $payment['state'] = $payment['refunded'] < $payment['amount'] ? 'PARTIALLY_REFUNDED' : 'REFUNDED';

        return $this->response(200, ['id' => $id, 'result' => 'FINISHED']);
    }

    /**
     * @param  array<string, mixed>  $json
     */
    private function response(int $statusCode, array $json): Response
    {
        $response = new Response(json_encode($json, JSON_THROW_ON_ERROR));
        $response->statusCode = $statusCode;
        $response->json = $json;

        return $response;
    }
}
