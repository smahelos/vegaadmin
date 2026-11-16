<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure\Persistence\Eloquent\Analytics\Repositories;

use App\Application\Analytics\Queries\GetCustomerRevenue;
use App\Application\Analytics\Queries\GetDashboard;
use App\Application\Analytics\Queries\GetMonthlyRevenue;
use App\Domain\Analytics\DTO\CustomerTotalDTO;
use App\Domain\Analytics\DTO\StatisticsFilter;
use App\Domain\Analytics\Enums\StatisticsPeriod;
use App\Domain\Party\ValueObjects\ContactId;
use App\Domain\User\ValueObjects\UserId;
use App\Models\Business;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\User;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\RunsUseCases;
use Tests\TestCase;

final class CustomerRevenueTest extends TestCase
{
    use RunsUseCases;

    #[Test]
    public function groups_archived_contacts_and_one_off_customers_without_losing_or_merging_identities(): void
    {
        $userFactory = User::factory();
        $user = $userFactory->create();
        $user->assignRole('frontend_user');
        $businessFactory = Business::factory();
        $archivedBusinessFactory = $businessFactory->archived();
        $business = $archivedBusinessFactory->create(['user_id' => $user->id]);
        $contactFactory = Contact::factory();
        $archivedContactFactory = $contactFactory->archived();
        $contact = $archivedContactFactory->create(['user_id' => $user->id, 'name' => 'Current contact name']);
        $base = [
            'user_id' => $user->id,
            'business_id' => $business->id,
            'issue_date' => '2026-10-20',
        ];
        $invoiceFactory = Invoice::factory();
        foreach (
            [
                [
                    'contact_id' => $contact->id,
                    'customer_name' => 'Old snapshot',
                    'payment_amount' => '10.01',
                ],
                [
                    'contact_id' => $contact->id,
                    'customer_name' => 'Changed snapshot',
                    'payment_amount' => '20.02',
                ],
                [
                    'customer_company_number' => '123',
                    'customer_registration_country' => 'AT',
                    'customer_name' => 'Registration A',
                    'payment_amount' => '1.11',
                ],
                [
                    'customer_company_number' => '123',
                    'customer_registration_country' => 'AT',
                    'customer_name' => 'Registration renamed',
                    'payment_amount' => '2.22',
                ],
                [
                    'customer_company_number' => '123',
                    'customer_registration_country' => 'DE',
                    'customer_name' => 'Same number elsewhere',
                    'payment_amount' => '4.44',
                ],
                ['customer_name' => 'Name only', 'payment_amount' => '0.01'],
                ['customer_name' => 'Name only', 'payment_amount' => '0.02'],
                ['customer_name' => 'Another name', 'payment_amount' => '0.04'],
                ['customer_name' => '123', 'payment_amount' => '0.08'],
            ] as $attributes
        ) {
            $invoiceFactory->create($attributes + $base);
        }
        $invoiceFactory->create(['issue_date' => '2026-10-20', 'payment_amount' => '9999']);
        $uid = UserId::fromInt($user->id);
        $rows = $this->runAs(
            $uid,
            new GetCustomerRevenue(
                $uid,
                $this->since('2026-10-01'),
            ),
        );

        $this->assertCount(6, $rows);
        $this->assertSame($contact->id, $rows[0]['contact_id']);
        $this->assertSame('Current contact name', $rows[0]['customer_name']);
        $this->assertSame(
            [
                '30.03',
                '4.44',
                '3.33',
                '0.08',
                '0.04',
                '0.03',
            ],
            array_column($rows, 'total'),
        );
        $filtered = $this->runAs(
            $uid,
            new GetCustomerRevenue(
                $uid,
                $this->since(
                    '2026-10-01',
                    [ContactId::fromInt($contact->id)],
                ),
            ),
        );
        $this->assertCount(1, $filtered);
        $this->assertSame('30.03', $filtered[0]['total']);

        $dashboard = $this->runAs(
            $uid,
            new GetDashboard($uid),
        );
        $statistics = $dashboard->statistics;
        $this->assertSame(
            '37.95',
            $statistics->totalAmount->getAmount(),
        );
        $this->assertSame(
            1,
            $statistics->businessCount,
        );
        $this->assertSame(
            1,
            $statistics->contactCount,
        );
        // The contact filter offers only stored contacts, not the one-off customers
        $this->assertSame(
            [$contact->id],
            array_map(
                static fn (CustomerTotalDTO $customer): ?int => $customer->contactId?->value,
                $dashboard->contacts,
            ),
        );
        $months = $this->runAs(
            $uid,
            new GetMonthlyRevenue(
                $uid,
                $this->since('2026-10-01'),
            ),
        );
        $this->assertSame('37.95', $months[0]['total']);
    }

    #[Test]
    public function a_foreign_contact_filter_returns_no_other_users_statistics(): void
    {
        $userFactory = User::factory();
        $owner = $userFactory->create();
        $owner->assignRole('frontend_user');
        $contactFactory = Contact::factory();
        $foreignContact = $contactFactory->create();
        $invoiceFactory = Invoice::factory();
        $invoiceFactory->create(
            [
                'user_id' => $foreignContact->user_id,
                'contact_id' => $foreignContact->id,
                'payment_amount' => '9999',
            ],
        );
        foreach (
            [
                'monthly-revenue',
                'customer-revenue',
                'invoice-status',
                'payment-methods',
            ] as $chart
        ) {
            $this->actingAs($owner);
            $response = $this->getJson(
                route('api.statistics.' . $chart, ['contacts' => [$foreignContact->id]]),
            );
            $response->assertOk();
            $response->assertExactJson(['data' => []]);
        }
    }

    /** @param  list<ContactId>  $contactIds */
    private function since(string $from, array $contactIds = []): StatisticsFilter
    {
        return new StatisticsFilter(
            StatisticsPeriod::YEAR,
            new DateTimeImmutable($from),
            null,
            $contactIds,
        );
    }
}
