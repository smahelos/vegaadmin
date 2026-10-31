<?php

declare(strict_types=1);

namespace Tests\Feature\Application\Invoice\Services;

use App\Application\Invoice\Services\InvoiceListApplicationService;
use App\Domain\Invoice\DTO\InvoiceSearch;
use App\Domain\Invoice\Exceptions\InvoiceAccessDeniedException;
use App\Domain\Shared\Pagination\DTO\PageRequest;
use App\Domain\User\ValueObjects\UserId;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class InvoiceListApplicationServiceTest extends TestCase
{
    /** @return list<array{string}> */
    public static function reads(): array
    {
        return [
            ['invoices'],
            ['businesses'],
            ['contacts'],
        ];
    }

    #[Test]
    #[DataProvider('reads')]
    public function every_list_read_requires_invoice_view_permission(string $read): void
    {
        $user = UserId::fromInt(User::factory()->create()->id);
        $service = app(InvoiceListApplicationService::class);
        $this->expectException(InvoiceAccessDeniedException::class);
        match ($read) {
            'invoices' => $service->search(
                $user,
                new InvoiceSearch(
                    '',
                    null,
                    null,
                    null,
                    'created_at',
                    false,
                ),
                new PageRequest,
            ),
            'businesses' => $service->businessFilters($user),
            'contacts' => $service->contactFilters($user),
        };
    }
}
