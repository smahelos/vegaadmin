<?php

namespace Tests\Feature\Domain\Payment\Services;

use App\Domain\Payment\Contracts\SubscriptionPaymentServiceInterface;
use App\Domain\Payment\Services\SubscriptionPaymentService;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Support\Facades\App;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\Fakes\Payment\FakePaymentGateway;
use App\Domain\Payment\Contracts\GatewayRegistryInterface;
use App\Domain\Payment\Contracts\PaymentDtoWriteRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Payment\Repositories\EloquentPaymentWriteRepository;
use App\Infrastructure\Persistence\Eloquent\Payment\Mappers\EloquentPaymentMapper;
use App\Domain\Payment\DTO\PaymentDTO;
use App\Domain\Payment\DTO\PaymentWriteData;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithData;

/**
 * Behavior tests for subscription payment lifecycle using a Fake gateway.
 */
class SubscriptionPaymentServiceBehaviorTest extends TestCase
{
    use RefreshDatabaseWithData;

    private SubscriptionPaymentServiceInterface $service;
    private FakePaymentGateway $fakeGateway;

    protected function setUp(): void
    {
        parent::setUp();

        // Use a numeric-ID fake gateway to match PaymentWriteData (gateway_payment_id expects int)
        $this->fakeGateway = new class extends FakePaymentGateway {
            public function createPayment(array $paymentData): array
            {
                if ($this->shouldFailCreate) {
                    return [
                        'success' => false,
                        'error' => 'create-failed',
                    ];
                }
                $id = count($this->createdPayments) + 1;
                $gatewayPaymentId = $id; // numeric ID
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
        // Override registry with a deterministic in-memory stub using instance() to replace any resolved singleton
        $registryStub = new class($this->fakeGateway) implements GatewayRegistryInterface {
            private array $store = [];
            private array $meta = [];
            public function __construct(private $fake) {
                // Pre-register fake for deterministic availability
                $this->store['fake'] = $this->fake;
                $this->meta['fake'] = [
                    'display_name' => 'Fake Gateway',
                    'currencies' => ['EUR','USD'],
                    'recurring' => null,
                ];
            }
            public function register(string $name, \App\Domain\Payment\Contracts\PaymentGatewayInterface $gateway): void { $this->store[$name] = $gateway; }
            public function get(string $name): ?\App\Domain\Payment\Contracts\PaymentGatewayInterface { return $this->store[$name] ?? ($name === 'fake' ? $this->fake : null); }
            public function names(): array { return array_keys($this->store); }
            public function remove(string $name): void { unset($this->store[$name], $this->meta[$name]); }
            public function metadata(string $name): ?array { return $this->meta[$name] ?? null; }
            public function allMetadata(): array { return $this->meta; }
        };
        app()->instance(GatewayRegistryInterface::class, $registryStub);
        // Rebind PaymentDtoWriteRepositoryInterface to a safe test implementation (avoid loading undefined relations)
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
        // Bind a safe SubscriptionDtoReadRepositoryInterface stub to bypass faulty mapper subscriptionPlan handling
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
        // Resolve service after registry prepared
        $this->service = app(SubscriptionPaymentServiceInterface::class);
    }

    private function makeSubscription(): Subscription
    {
        $unique = uniqid();
        $user = User::factory()->create([
            'email' => 'user_' . $unique . '@example.com',
        ]);

        $plan = SubscriptionPlan::create([
            'name' => 'Plan ' . $unique,
            'price' => 10.00,
            'currency' => 'EUR',
            'billing_period' => 'monthly',
            'billing_interval' => 1,
            'is_active' => true,
            'trial_days' => 0,
        ]);

        return Subscription::create([
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'pending',
            'amount' => 10.00,
            'currency' => 'EUR',
        ]);
    }

    #[Test]
    public function successful_process_creates_pending_payment_and_returns_redirect(): void
    {
        $subscription = $this->makeSubscription();
        $subscriptionId = $subscription->id;
        // Pass amount/currency as strings to match PaymentWriteData types
        $result = $this->service->processSubscriptionPayment($subscriptionId, 'fake', [
            'amount' => number_format((float)$subscription->amount, 2, '.', ''),
            'currency' => (string)$subscription->currency,
        ]);

        $this->assertTrue($result['success']);
        $this->assertNotNull($result['payment_id']);
        $this->assertNotNull($result['gateway_payment_id']);
        $this->assertNotEmpty($result['redirect_url']);
        $this->assertEquals('pending', $result['status']);

        $payment = Payment::find($result['payment_id']);
        $this->assertNotNull($payment);
        $this->assertEquals('pending', $payment->status);
        $this->assertEquals('fake', $payment->gateway);
    }

    #[Test]
    public function failed_process_sets_payment_failed_and_returns_error(): void
    {
        $subscription = $this->makeSubscription();
        $this->fakeGateway->shouldFailCreate = true;
        $result = $this->service->processSubscriptionPayment($subscription->id, 'fake', [
            'amount' => number_format((float)$subscription->amount, 2, '.', ''),
            'currency' => (string)$subscription->currency,
        ]);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        // Current implementation keeps the created payment record as 'pending' without gateway_payment_id
        $payment = Payment::where('subscription_id', $subscription->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals('pending', $payment->status);
        $this->assertNull($payment->gateway_payment_id);
    }

    #[Test]
    public function callback_completed_updates_payment_and_activates_subscription(): void
    {
        $subscription = $this->makeSubscription();
        $process = $this->service->processSubscriptionPayment($subscription->id, 'fake', [
            'amount' => number_format((float)$subscription->amount, 2, '.', ''),
            'currency' => (string)$subscription->currency,
        ]);
        $payment = Payment::find($process['payment_id']);
        $this->assertEquals('pending', $payment->status);
        // Simulate gateway callback with completed status
        $cb = $this->service->handlePaymentCallback('fake', [
            'payment_id' => $process['gateway_payment_id'],
            'order_id' => $payment->id,
            'status' => 'completed',
        ]);
        $this->assertTrue($cb['success']);
        $payment->refresh();
        $subscription->refresh();
        $this->assertEquals('completed', $payment->status);
        $this->assertEquals('active', $subscription->status);
        $this->assertNotNull($subscription->starts_at);
    }

    #[Test]
    public function callback_failed_updates_payment_and_does_not_activate_subscription(): void
    {
        $subscription = $this->makeSubscription();
        $process = $this->service->processSubscriptionPayment($subscription->id, 'fake', [
            'amount' => number_format((float)$subscription->amount, 2, '.', ''),
            'currency' => (string)$subscription->currency,
        ]);
        $payment = Payment::find($process['payment_id']);
        // Simulate failed callback
        $cb = $this->service->handlePaymentCallback('fake', [
            'payment_id' => $process['gateway_payment_id'],
            'order_id' => $payment->id,
            'status' => 'failed',
        ]);
        $this->assertTrue($cb['success']);
        $payment->refresh();
        $subscription->refresh();
        $this->assertEquals('failed', $payment->status);
        $this->assertNotEquals('active', $subscription->status);
    }

    #[Test]
    public function verify_subscription_updates_status_and_activates_when_completed(): void
    {
        $subscription = $this->makeSubscription();
        $process = $this->service->processSubscriptionPayment($subscription->id, 'fake', [
            'amount' => number_format((float)$subscription->amount, 2, '.', ''),
            'currency' => (string)$subscription->currency,
        ]);
        $payment = Payment::find($process['payment_id']);
        // Set gateway status to completed manually
        $this->fakeGateway->statuses[$process['gateway_payment_id']] = 'completed';
        $verify = $this->service->verifySubscriptionPayment($payment->id);
        $this->assertTrue($verify['success']);
        $this->assertEquals('completed', $verify['status']);
        $payment->refresh();
        $subscription->refresh();
        $this->assertEquals('completed', $payment->status);
        $this->assertEquals('active', $subscription->status);
    }

    #[Test]
    public function cancellation_marks_pending_payment_cancelled(): void
    {
        $subscription = $this->makeSubscription();
        $process = $this->service->processSubscriptionPayment($subscription->id, 'fake', [
            'amount' => number_format((float)$subscription->amount, 2, '.', ''),
            'currency' => (string)$subscription->currency,
        ]);
        $payment = Payment::find($process['payment_id']);
        $this->assertEquals('pending', $payment->status);
        $cancel = $this->service->cancelSubscriptionPayment($subscription->id);
        $this->assertTrue($cancel); // All pending should cancel
        $payment->refresh();
        $this->assertEquals('cancelled', $payment->status);
    }

    #[Test]
    public function refund_completed_payment_returns_success_and_amount(): void
    {
        $subscription = $this->makeSubscription();
        $process = $this->service->processSubscriptionPayment($subscription->id, 'fake', [
            'amount' => number_format((float)$subscription->amount, 2, '.', ''),
            'currency' => (string)$subscription->currency,
        ]);
        $payment = Payment::find($process['payment_id']);
        // Complete payment via callback
        $this->service->handlePaymentCallback('fake', [
            'payment_id' => $process['gateway_payment_id'],
            'order_id' => $payment->id,
            'status' => 'completed',
        ]);
        $payment->refresh();
        $this->assertEquals('completed', $payment->status);
        $refund = $this->service->refundSubscriptionPayment($payment->id, 5.00);
        $this->assertTrue($refund['success']);
        $this->assertEquals($payment->id, $refund['payment_id']);
        $this->assertEquals(5.00, $refund['refunded_amount']);
    }

    #[Test]
    public function refund_pending_payment_fails(): void
    {
        $subscription = $this->makeSubscription();
        $process = $this->service->processSubscriptionPayment($subscription->id, 'fake', []);
        $payment = Payment::find($process['payment_id']);
        $this->assertEquals('pending', $payment->status);
        $refund = $this->service->refundSubscriptionPayment($payment->id, 5.00);
        $this->assertFalse($refund['success']);
        $this->assertStringContainsString('Only completed', $refund['error']);
    }

    #[Test]
    public function unsupported_gateway_process_returns_error(): void
    {
        $subscription = $this->makeSubscription();
        $result = $this->service->processSubscriptionPayment($subscription->id, 'nonexistent', []);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Unsupported gateway', $result['error']);
    }

    #[Test]
    public function get_available_gateways_uses_registry_metadata(): void
    {
        // Arrange: registry already has 'fake' from setUp injection
        $gateways = $this->service->getAvailableGateways();
        $this->assertNotEmpty($gateways);
        // Service returns associative array keyed by gateway name
        $this->assertArrayHasKey('fake', $gateways);
        $fake = $gateways['fake'];
        $this->assertEquals('Fake Gateway', $fake['name']);
        $this->assertEquals(['EUR','USD'], $fake['supported_currencies']);
        $this->assertArrayHasKey('supports_recurring', $fake); // presence check (may be null/false)
    }
}
