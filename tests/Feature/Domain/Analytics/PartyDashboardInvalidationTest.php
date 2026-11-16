<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Analytics;

use App\Application\Party\Commands\ArchiveBusiness;
use App\Application\Party\Commands\ArchiveContact;
use App\Application\Party\Commands\CreateBusiness;
use App\Application\Party\Commands\CreateContact;
use App\Application\Party\Commands\RestoreBusiness;
use App\Application\Party\Commands\RestoreContact;
use App\Application\Party\Commands\UpdateContact;
use App\Domain\Party\DTO\BusinessWriteData;
use App\Domain\Party\DTO\ContactWriteData;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\Support\BuildsParties;
use Tests\Support\ReadsDashboard;
use Tests\Support\RunsUseCases;
use Tests\TestCase;

final class PartyDashboardInvalidationTest extends TestCase
{
    use BuildsParties;
    use ReadsDashboard;
    use RunsUseCases;

    #[Test]
    public function party_lifecycle_refreshes_the_owners_dashboard_and_keeps_other_users_cached_data(): void
    {
        $owner = $this->frontendUser();
        $other = $this->frontendUser();
        $uid = $this->idOf($owner);
        $this->dashboardOf($uid);
        $this->dashboardOf(
            $this->idOf($other),
        );
        $invoiceFactory = Invoice::factory();
        Invoice::withoutEvents(
            fn () => $invoiceFactory->create(['user_id' => $other->id]),
        );
        $contact = $this->runAs(
            $uid,
            new CreateContact(
                $uid,
                new ContactWriteData(
                    $this->profile('First name'),
                    null,
                ),
            ),
        );
        $business = $this->runAs(
            $uid,
            new CreateBusiness(
                $uid,
                new BusinessWriteData(
                    $this->profile('Own business'),
                    null,
                ),
                null,
            ),
        );
        $this->assertSame(
            1,
            $this->dashboardOf($uid)->statistics->contactCount,
        );
        $this->assertSame(
            1,
            $this->dashboardOf($uid)->statistics->businessCount,
        );
        $this->assertSame(
            'First name',
            $this->dashboardOf($uid)->contacts[0]->customerName,
        );
        $this->runAs(
            $uid,
            new UpdateContact(
                $uid,
                $contact->id,
                new ContactWriteData(
                    $this->profile('Renamed contact'),
                    null,
                ),
            ),
        );
        $this->assertSame(
            'Renamed contact',
            $this->dashboardOf($uid)->contacts[0]->customerName,
        );
        $this->runAs(
            $uid,
            new ArchiveContact($uid, $contact->id),
        );
        $this->runAs(
            $uid,
            new ArchiveBusiness(
                $uid,
                $business->id,
                null,
            ),
        );
        $this->assertSame(
            1,
            $this->dashboardOf($uid)->statistics->contactCount,
        );
        $this->assertSame(
            1,
            $this->dashboardOf($uid)->statistics->businessCount,
        );
        $this->runAs(
            $uid,
            new RestoreContact($uid, $contact->id),
        );
        $this->runAs(
            $uid,
            new RestoreBusiness($uid, $business->id),
        );
        $this->assertSame(
            1,
            $this->dashboardOf($uid)->statistics->contactCount,
        );
        $this->assertSame(
            0,
            $this->dashboardOf(
                $this->idOf($other),
            )->statistics->invoiceCount,
        );
    }

    #[Test]
    public function a_rolled_back_contact_does_not_invalidate_cached_statistics(): void
    {
        $user = $this->frontendUser();
        $uid = $this->idOf($user);
        $this->dashboardOf($uid);
        $invoiceFactory = Invoice::factory();
        Invoice::withoutEvents(
            fn () => $invoiceFactory->create(['user_id' => $user->id]),
        );
        try {
            DB::transaction(
                function () use ($uid): void {
                    $this->runAs(
                        $uid,
                        new CreateContact(
                            $uid,
                            new ContactWriteData(
                                $this->profile('Rolled back'),
                                null,
                            ),
                        ),
                    );
                    throw new RuntimeException('Roll back the use case');
                },
            );
        } catch (RuntimeException $exception) {
            $this->assertSame('Roll back the use case', $exception->getMessage());
        }
        $this->assertDatabaseMissing('contacts', ['user_id' => $user->id, 'name' => 'Rolled back']);
        $this->assertSame(
            0,
            $this->dashboardOf($uid)->statistics->invoiceCount,
        );
    }
}
