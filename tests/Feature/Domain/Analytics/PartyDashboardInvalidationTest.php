<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Analytics;

use App\Application\Party\Services\BusinessApplicationService;
use App\Application\Party\Services\ContactApplicationService;
use App\Domain\Analytics\Services\DashboardService;
use App\Domain\Party\DTO\BusinessWriteData;
use App\Domain\Party\DTO\ContactWriteData;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\Support\BuildsParties;
use Tests\TestCase;

final class PartyDashboardInvalidationTest extends TestCase
{
    use BuildsParties;

    #[Test]
    public function party_lifecycle_refreshes_the_owners_dashboard_and_keeps_other_users_cached_data(): void
    {
        $owner = $this->frontendUser();
        $other = $this->frontendUser();
        $dashboard = app(DashboardService::class);
        $uid = $this->idOf($owner);
        $dashboard->getUserStatistics($uid);
        $dashboard->getCustomersWithInvoiceTotals($uid);
        $dashboard->getUserStatistics($this->idOf($other));
        Invoice::withoutEvents(fn () => Invoice::factory()->create(['user_id' => $other->id]));
        $contacts = app(ContactApplicationService::class);
        $businesses = app(BusinessApplicationService::class);

        $contact = $contacts->create($uid, new ContactWriteData($this->profile('First name'), null));
        $business = $businesses->create(
            $uid,
            new BusinessWriteData($this->profile('Own business'), null),
            null,
        );
        $this->assertSame(1, $dashboard->getUserStatistics($uid)->contactCount);
        $this->assertSame(1, $dashboard->getUserStatistics($uid)->businessCount);
        $this->assertSame('First name', $dashboard->getCustomersWithInvoiceTotals($uid)[0]->customerName);
        $contacts->update(
            $uid,
            $contact->id,
            new ContactWriteData($this->profile('Renamed contact'), null),
        );
        $this->assertSame('Renamed contact', $dashboard->getCustomersWithInvoiceTotals($uid)[0]->customerName);
        $contacts->archive($uid, $contact->id);
        $businesses->archive(
            $uid,
            $business->id,
            null,
        );
        $this->assertSame(1, $dashboard->getUserStatistics($uid)->contactCount);
        $this->assertSame(1, $dashboard->getUserStatistics($uid)->businessCount);
        $contacts->restore($uid, $contact->id);
        $businesses->restore($uid, $business->id);
        $this->assertSame(1, $dashboard->getUserStatistics($uid)->contactCount);
        $this->assertSame(0, $dashboard->getUserStatistics($this->idOf($other))->invoiceCount);
    }

    #[Test]
    public function a_rolled_back_contact_does_not_invalidate_cached_statistics(): void
    {
        $user = $this->frontendUser();
        $uid = $this->idOf($user);
        $dashboard = app(DashboardService::class);
        $dashboard->getUserStatistics($uid);
        Invoice::withoutEvents(fn () => Invoice::factory()->create(['user_id' => $user->id]));
        try {
            DB::transaction(function () use ($uid): void {
                app(ContactApplicationService::class)->create($uid, new ContactWriteData($this->profile('Rolled back'), null));
                throw new RuntimeException('Roll back the use case');
            });
        } catch (RuntimeException $exception) {
            $this->assertSame('Roll back the use case', $exception->getMessage());
        }
        $this->assertDatabaseMissing('contacts', ['user_id' => $user->id, 'name' => 'Rolled back']);
        $this->assertSame(0, $dashboard->getUserStatistics($uid)->invoiceCount);
    }
}
