<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Analytics\Services;

use App\Domain\Analytics\Services\DashboardService as AnalyticsDashboardService;
use App\Domain\Shared\Money\ValueObjects\Money; // updated namespace
use App\Domain\User\ValueObjects\UserId;
use App\Models\Business;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesFrontendTestEnvironment;

final class DashboardServiceFeatureTest extends TestCase
{
    use CreatesFrontendTestEnvironment, WithFaker;

    private AnalyticsDashboardService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpFrontendTestEnvironment();
        $this->service = app(AnalyticsDashboardService::class);
    }

    #[Test]
    public function statistics_structure_for_new_user_is_zeroed(): void
    {
        $user = User::factory()->create();
        $stats = $this->service->getUserStatistics(UserId::fromInt($user->id));
        $this->assertSame(0, $stats->invoiceCount);
        $this->assertSame(0, $stats->contactCount);
        $this->assertSame(0, $stats->businessCount);
        $this->assertInstanceOf(Money::class, $stats->totalAmount);
        $this->assertSame('0', $stats->totalAmount->getAmount());
        $this->assertSame(0.0, $stats->totalAmount->toFloat());
    }

    #[Test]
    public function statistics_reflect_created_entities(): void
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create(['user_id' => $user->id]);
        $supplier = Business::factory()->create(['user_id' => $user->id]);
        Invoice::factory()->create([
            'user_id' => $user->id,
            'contact_id' => $contact->id,
            'business_id' => $supplier->id,
            'payment_amount' => '123.45',
        ]);

        $stats = $this->service->getUserStatistics(UserId::fromInt($user->id));
        $this->assertSame(1, $stats->invoiceCount);
        $this->assertSame(1, $stats->contactCount);
        $this->assertSame(1, $stats->businessCount);
        $this->assertInstanceOf(Money::class, $stats->totalAmount);
        $this->assertSame(123.45, $stats->totalAmount->toFloat());
    }

    #[Test]
    public function customers_with_totals_returns_expected_shape(): void
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create(['user_id' => $user->id]);
        Invoice::factory()->create([
            'user_id' => $user->id,
            'contact_id' => $contact->id,
            'payment_amount' => 55,
        ]);

        $customers = collect($this->service->getCustomersWithInvoiceTotals(UserId::fromInt($user->id)));
        $this->assertCount(1, $customers);
        $first = $customers->first();
        $this->assertSame($contact->id, $first->contactId->value);
        $this->assertInstanceOf(Money::class, $first->totalAmount);
        $this->assertSame(55.0, $first->totalAmount->toFloat());
    }

    #[Test]
    public function statistics_are_isolated_per_user(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $contactA = Contact::factory()->create(['user_id' => $userA->id]);
        Invoice::factory()->create([
            'user_id' => $userA->id,
            'contact_id' => $contactA->id,
            'payment_amount' => 11,
        ]);

        $statsA = $this->service->getUserStatistics(UserId::fromInt($userA->id));
        $statsB = $this->service->getUserStatistics(UserId::fromInt($userB->id));
        $this->assertSame(1, $statsA->invoiceCount);
        $this->assertSame(0, $statsB->invoiceCount);
        $this->assertInstanceOf(Money::class, $statsA->totalAmount);
        $this->assertInstanceOf(Money::class, $statsB->totalAmount);
    }
}
