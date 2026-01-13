<?php

namespace Tests\Support\Fakes\GoPay;

use GoPay\Payments;

/**
 * Adapter that satisfies GoPay\Payments type-hint while delegating to StubPayments store.
 * Only minimal methods used in GoPayGateway are implemented.
 */
class StubPaymentsAdapter extends Payments
{
    private StubPayments $stub;

    public function __construct(StubPayments $stub)
    {
        // Do not call parent constructor; stub does not need SDK config.
        $this->stub = $stub;
    }

    public function createPayment(array $payload)
    {
        $result = $this->stub->create(($payload['amount'] ?? 0) / 100, $payload['currency'] ?? 'EUR');
        $json = [];
        if ($result['success']) {
            $json = [
                'id' => $result['payment_id'],
                'gw_url' => $result['payment_url'],
                'state' => 'CREATED',
            ];
        } else {
            $json = ['error' => $result['error'] ?? 'unknown'];
        }
        return new class($result, $json) {
            public array $json; private array $r; public function __construct(array $r, array $json){$this->r=$r;$this->json=$json;}
            public function hasSucceed(){return $this->r['success'] ?? false;}
        };
    }

    public function getStatus($paymentId)
    {
        $result = $this->stub->status($paymentId);
        if(!($result['success'] ?? false)){
            return new class($result){public array $json;public function __construct(array $r){$this->json=$r;}public function hasSucceed(){return false;}};
        }
        // Pending internal state represented as CREATED for mapping in gateway
        $state = match ($result['status']) {
            'pending' => 'CREATED',
            'cancelled' => 'CANCELED',
            'refunded' => 'REFUNDED',
            default => strtoupper($result['status'])
        };
        $json = ['id'=>$paymentId,'state'=> $state];
        return new class($json){public array $json;public function __construct(array $j){$this->json=$j;}public function hasSucceed(){return true;}};
    }

    public function voidAuthorization($paymentId)
    {
        $ok = $this->stub->cancel($paymentId);
        return new class($ok){public array $json;private bool $ok;public function __construct(bool $ok){$this->ok=$ok;$this->json=['success'=>$ok];}public function hasSucceed(){return $this->ok;}};
    }

    public function refundPayment($paymentId, $data)
    {
        $amount = isset($data['amount']) ? $data['amount']/100 : null;
        $result = $this->stub->refund($paymentId, $amount);
        return new class($result){public array $json;private array $r;public function __construct(array $r){$this->r=$r;$this->json=$r;}public function hasSucceed(){return $this->r['success'] ?? false;}};
    }
}
