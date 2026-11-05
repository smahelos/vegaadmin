<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure\Persistence\Eloquent\Invoice\Repositories;

use App\Domain\Invoice\Contracts\InvoiceListReadRepositoryInterface;
use App\Domain\Invoice\DTO\InvoiceSearch;
use App\Domain\Shared\Pagination\DTO\PageRequest;
use App\Domain\User\ValueObjects\UserId;
use App\Models\Invoice;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class EloquentInvoiceListRepositoryTest extends TestCase
{
    /** @return array<string, array{string}> */
    public static function columnsOutsideTheAllowlist(): array
    {
        return [
            'existing column' => ['user_id'],
            'sql fragment' => ['id; drop table invoices'],
            'empty' => [''],
        ];
    }

    #[Test]
    public function sorts_by_an_allowed_column(): void
    {
        $this->assertSame(
            [
                '2026000003',
                '2026000001',
                '2026000002',
            ],
            $this->numbersSortedBy('customer_name'),
        );
    }

    #[Test]
    #[DataProvider('columnsOutsideTheAllowlist')]
    public function a_column_outside_the_allowlist_sorts_by_creation_time(string $sort): void
    {
        $this->assertSame(
            [
                '2026000002',
                '2026000003',
                '2026000001',
            ],
            $this->numbersSortedBy($sort),
        );
    }

    /** @return list<string|null> */
    private function numbersSortedBy(string $sort): array
    {
        $userFactory = User::factory();
        $user = $userFactory->create();
        $invoiceFactory = Invoice::factory();
        $rows = [
            [
                '2026000001',
                'Beta',
                '2026-03-01 00:00:00',
            ],
            [
                '2026000002',
                'Gamma',
                '2026-01-01 00:00:00',
            ],
            [
                '2026000003',
                'Alpha',
                '2026-02-01 00:00:00',
            ],
        ];
        foreach ($rows as [$number, $customer, $created]) {
            $invoiceFactory->create(
                [
                    'user_id' => $user->id,
                    'invoice_vs' => $number,
                    'customer_name' => $customer,
                    'created_at' => $created,
                ],
            );
        }
        $repository = app(InvoiceListReadRepositoryInterface::class);
        $result = $repository->searchForUser(
            UserId::fromInt($user->id),
            new InvoiceSearch(
                '',
                null,
                null,
                null,
                $sort,
                true,
            ),
            new PageRequest,
        );
        $numbers = [];
        foreach ($result->items() as $invoice) {
            $numbers[] = $invoice->number;
        }

        return $numbers;
    }
}
