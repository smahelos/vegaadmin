<?php

namespace Tests\Support\Stubs\Payment;

use GoPay\Payments;
use GoPay\Http\Response;

/**
 * Clean stub extending GoPay\Payments (no HTTP). Only implements methods used by GoPayGateway.
 */
class StubGoPayPayments extends Payments
{
    /** @var array<string,array<string,mixed>> */
    public array $payments = [];
    public string $nextVerifyState = 'PAID';
    public bool $failCreate = false;
    public bool $failVerify = false;
    public bool $failCancel = false;
    public bool $failRefund = false;
    private int $counter = 1;

    public function __construct() {}

    public function createPayment(array $rawPayment)
    {
        $r = new Response();
        if ($this->failCreate) {
            $r->statusCode = 400;
            $r->json = ['error' => 'create_failed'];
            return $r;
        }
        $id = 'STUB-' . $this->counter++;
        $payment = $rawPayment + [
            'id' => $id,
            'state' => 'CREATED',
            'gw_url' => 'https://stub-gateway.test/pay/' . $id,
        ];
        $this->payments[$id] = $payment;
        $r->statusCode = 200;
        $r->json = $payment;
        return $r;
    }

    public function getStatus($id)
    {
        $r = new Response();
        if ($this->failVerify) {
            $r->statusCode = 400;
            $r->json = ['error' => 'verify_failed'];
            return $r;
        }
        if (!isset($this->payments[$id])) {
            $r->statusCode = 404;
            $r->json = ['error' => 'not_found'];
            return $r;
        }
        $payment = $this->payments[$id];
        $payment['state'] = $this->nextVerifyState;
        $this->payments[$id] = $payment;
        $r->statusCode = 200;
        $r->json = $payment;
        return $r;
    }

    public function voidAuthorization($id)
    {
        $r = new Response();
        if ($this->failCancel || !isset($this->payments[$id])) {
            $r->statusCode = 400;
            $r->json = ['error' => 'cancel_failed'];
            return $r;
        }
        $r->statusCode = 200;
        $r->json = ['id' => $id, 'state' => 'CANCELED'];
        return $r;
    }

    public function refundPayment($id, $data)
    {
        $r = new Response();
        if ($this->failRefund || !isset($this->payments[$id])) {
            $r->statusCode = 400;
            $r->json = ['error' => 'refund_failed'];
            return $r;
        }
        $r->statusCode = 200;
        $r->json = ['id' => $id, 'state' => 'REFUNDED'];
        return $r;
    }
}
