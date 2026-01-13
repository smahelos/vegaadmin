<?php

namespace Tests\Feature\Domain\Payment\Services;

use App\Domain\Payment\Contracts\GatewayRegistryInterface;
use App\Domain\Payment\Contracts\SubscriptionPaymentServiceInterface;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\Fakes\Payment\FakePaymentGateway;
use App\Domain\Payment\Contracts\PaymentDtoWriteRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Payment\Repositories\EloquentPaymentWriteRepository;
use App\Infrastructure\Persistence\Eloquent\Payment\Mappers\EloquentPaymentMapper;
use App\Domain\Payment\DTO\PaymentDTO;
use App\Domain\Payment\DTO\PaymentWriteData;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithData;

/**
 * Focused mutation tests covering refundSubscriptionPayment branches.
 */
class SubscriptionPaymentRefundMutationTest extends TestCase
{
    use RefreshDatabaseWithData;

    private SubscriptionPaymentServiceInterface $service;
    private FakePaymentGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();
        // Override fake gateway to return numeric gateway_payment_id compatible with DTO
        $this->gateway = new class extends FakePaymentGateway {
            public function createPayment(array $paymentData): array
            {
                if ($this->shouldFailCreate) {
                    return [
                        'success' => false,
                        'error' => 'create-failed',
                    ];
                }
                $id = count($this->createdPayments) + 1;
                $gatewayPaymentId = $id; // numeric
                $this->createdPayments[$id] = [
                    'subscription_id' => $paymentData['subscription_id'] ?? null,
                    'amount' => $paymentData['amount'] ?? null,
                    'currency' => $paymentData['currency'] ?? null,
                    'gateway_payment_id' => $gatewayPaymentId,
                ];
                $this->statuses[$gatewayPaymentId] = 'pending';
                return [
                    'success' => true,
                    'payment_id' => $gatewayPaymentId,
                    'status' => 'pending',
                    'redirect_url' => 'https://fake.test/redirect/' . $gatewayPaymentId,
                ];
            }
        };
        // Override registry using instance() to ensure replacement of any prior resolved singleton
        $registryStub = new class($this->gateway) implements GatewayRegistryInterface {
            private array $store = [];
            private array $meta = [];
            public function __construct(private $fake) {
                $this->store['fake'] = $this->fake;
                $this->meta['fake'] = [
                    'display_name' => 'Fake Gateway',
                    'currencies' => ['EUR','USD'],
                    'recurring' => null,
                ];
            }
            public function register(string $name, \App\Domain\Payment\Contracts\PaymentGatewayInterface $gateway): void { $this->store[$name] = $gateway; }
            public function get(string $name): ?\App\Domain\Payment\Contracts\PaymentGatewayInterface { return $this->store[$name] ?? null; }
            public function names(): array { return array_keys($this->store); }
            public function remove(string $name): void { unset($this->store[$name], $this->meta[$name]); }
            public function metadata(string $name): ?array { return $this->meta[$name] ?? null; }
            public function allMetadata(): array { return $this->meta; }
        };
        app()->instance(GatewayRegistryInterface::class, $registryStub);
        // Bind safe PaymentDtoWriteRepositoryInterface for tests
        app()->bind(PaymentDtoWriteRepositoryInterface::class, function ($app) {
            $eloquent = $app->make(EloquentPaymentWriteRepository::class);
            $mapper = $app->make(EloquentPaymentMapper::class);
            return new class($eloquent, $mapper) implements PaymentDtoWriteRepositoryInterface {
                public function __construct(
                    private EloquentPaymentWriteRepository $eloquent,
                    private EloquentPaymentMapper $mapper
                ) {}
                public function create(PaymentWriteData $attributes): PaymentDTO
                {
                    $m = $this->eloquent->create($attributes->toModelAttributes());
                    return $this->mapper->toDto($m);
                }
                public function updateById(int $paymentId, PaymentWriteData $attributes): PaymentDTO
                {
                    $m = \App\Models\Payment::query()->find($paymentId);
                    if ($m && ($arr = $attributes->toModelAttributes())) {
                        $m->fill($arr);
                        $m->save();
                    }
                    $m = \App\Models\Payment::query()->findOrFail($paymentId);
                    return $this->mapper->toDto($m);
                }
                public function applyRefund(int $paymentId, float $amount): bool
                {
                    $m = \App\Models\Payment::query()->find($paymentId);
                    return $m ? $m->applyRefund($amount) : false;
                }
            };
        });
        // Stub SubscriptionDtoReadRepositoryInterface to avoid mapper pitfalls
        app()->bind(\App\Domain\Payment\Contracts\SubscriptionDtoReadRepositoryInterface::class, function () {
            return new class implements \App\Domain\Payment\Contracts\SubscriptionDtoReadRepositoryInterface {
                public function findById(int $id): ?\App\Domain\Payment\DTO\SubscriptionDTO
                {
                    $m = \App\Models\Subscription::find($id);
                    if (!$m) { return null; }
                    return \App\Domain\Payment\DTO\SubscriptionDTO::fromArray([
                        'id' => (int) $m->id,
                        'user_id' => (int) $m->user_id,
                        'subscription_plan_id' => $m->subscription_plan_id ? (int) $m->subscription_plan_id : null,
                        'subscriptionPlan' => null,
                        'status' => $m->status,
                        'starts_at' => $m->starts_at?->toDateTimeString(),
                        'ends_at' => $m->ends_at?->toDateTimeString(),
                        'trial_ends_at' => $m->trial_ends_at?->toDateTimeString(),
                        'next_billing_at' => $m->next_billing_at?->toDateTimeString(),
                        'amount' => $m->amount !== null ? number_format((float)$m->amount, 2, '.', '') : null,
                        'currency' => $m->currency,
                        'metadata' => null,
                        'created_at' => $m->created_at?->toDateTimeString(),
                    ]);
                }
            };
        });
        $this->service = app(SubscriptionPaymentServiceInterface::class);
    }

    private function newSubscription(float $amount = 25.00, string $currency = 'EUR'): Subscription
    {
        $u = User::factory()->create(['email' => 'ref_' . uniqid() . '@ex.test']);
        $plan = SubscriptionPlan::create([
            'name' => 'Plan ' . uniqid(),
            'price' => $amount,
            'currency' => $currency,
            'billing_period' => 'monthly',
            'billing_interval' => 1,
            'is_active' => true,
            'trial_days' => 0,
        ]);
        return Subscription::create([
            'user_id' => $u->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'pending',
            'amount' => $amount,
            'currency' => $currency,
        ]);
    }

    private function createCompletedPayment(float $amount = 25.00): Payment
    {
        $subId = $this->newSubscription($amount)->id;
        $process = $this->service->processSubscriptionPayment($subId, 'fake', [
            'amount' => number_format($amount, 2, '.', ''),
            'currency' => 'EUR',
        ]);
        $payment = Payment::findOrFail($process['payment_id']);
        // Complete via callback
        $this->service->handlePaymentCallback('fake', [
            'payment_id' => $process['gateway_payment_id'],
            'order_id' => $payment->id,
            'status' => 'completed',
        ]);
        $payment->refresh();
        return $payment;
    }

    #[Test]
    public function partial_then_full_with_null_amount(): void
    {
        $payment = $this->createCompletedPayment(20.00);
        $paymentId = $payment->id;
        $r1 = $this->service->refundSubscriptionPayment($paymentId, 5.00);
        $this->assertTrue($r1['success']);
        $payment->refresh();
        $this->assertEquals('partially_refunded', $payment->status);
        // null amount should refund remaining 15.00
        $r2 = $this->service->refundSubscriptionPayment($paymentId, null);
        $this->assertTrue($r2['success']);
        $payment->refresh();
        $this->assertEquals('refunded', $payment->status);
        $this->assertEquals(20.00, (float)$payment->refunded_amount);
    }

    #[Test]
    public function over_refund_is_blocked(): void
    {
        $payment = $this->createCompletedPayment(30.00);
        $paymentId = $payment->id;
        $ok = $this->service->refundSubscriptionPayment($paymentId, 10.00);
        $this->assertTrue($ok['success']);
        $payment->refresh();
        $fail = $this->service->refundSubscriptionPayment($paymentId, 25.00); // 10 + 25 > 30
        $this->assertFalse($fail['success']);
        $this->assertStringContainsString('exceeds', $fail['error']);
    }

    #[Test]
    public function zero_amount_is_blocked(): void
    {
        $payment = $this->createCompletedPayment(12.00);
        $paymentId = $payment->id;
        $res = $this->service->refundSubscriptionPayment($paymentId, 0.0);
        $this->assertFalse($res['success']);
        $this->assertStringContainsString('positive', $res['error']);
    }

    #[Test]
    public function missing_gateway_payment_id_is_blocked(): void
    {
        $payment = $this->createCompletedPayment(18.00);
        $paymentId = $payment->id;
        $payment->gateway_payment_id = null; // simulate data corruption
        $payment->save();
        $res = $this->service->refundSubscriptionPayment($paymentId, 5.00);
        $this->assertFalse($res['success']);
        $this->assertStringContainsString('Missing gateway payment id', $res['error']);
    }

    #[Test]
    public function unavailable_gateway_is_blocked(): void
    {
        $payment = $this->createCompletedPayment(22.00);
        $paymentId = $payment->id;
        $payment->gateway = 'ghost';
        $payment->save();
        $res = $this->service->refundSubscriptionPayment($paymentId, 5.00);
        $this->assertFalse($res['success']);
        $this->assertStringContainsString('Gateway not available', $res['error']);
    }

    #[Test]
    public function invalid_status_pending_is_blocked(): void
    {
        $sub = $this->newSubscription(15.00);
        $subId = $sub->id;
        $process = $this->service->processSubscriptionPayment($subId, 'fake', [
            'amount' => number_format(15.00, 2, '.', ''),
            'currency' => 'EUR',
        ]);
        $payment = Payment::findOrFail($process['payment_id']);
        $this->assertEquals('pending', $payment->status);
        $res = $this->service->refundSubscriptionPayment($payment->id, 5.00);
        $this->assertFalse($res['success']);
        $this->assertStringContainsString('Only completed', $res['error']);
    }

    #[Test]
    public function multiple_partials_accumulate_until_full(): void
    {
        $payment = $this->createCompletedPayment(10.00);
        $paymentId = $payment->id;
        $r1 = $this->service->refundSubscriptionPayment($paymentId, 3.00);
        $this->assertTrue($r1['success']);
        $payment->refresh();
        $this->assertEquals('partially_refunded', $payment->status);
        $r2 = $this->service->refundSubscriptionPayment($paymentId, 2.00);
        $this->assertTrue($r2['success']);
        $payment->refresh();
        $this->assertEquals('partially_refunded', $payment->status);
        $r3 = $this->service->refundSubscriptionPayment($paymentId, 5.00); // completes
        $this->assertTrue($r3['success']);
        $payment->refresh();
        $this->assertEquals('refunded', $payment->status);
        $this->assertEquals(10.00, (float)$payment->refunded_amount);
    }
}
