<?php

declare(strict_types=1);

namespace App\Application\Invoice\Commands;

use App\Application\Invoice\DTO\CreateGuestInvoiceResult;
use App\Application\Shared\UseCase\Access;
use App\Application\Shared\UseCase\Contracts\CallsOutsideDatabaseInterface;
use App\Application\Shared\UseCase\Contracts\CommandInterface;
use App\Domain\Party\DTO\DocumentAccountDTO;
use App\Domain\Party\DTO\DocumentPartyDTO;

/**
 * A guest invoice is never stored in the database and has no limit, only the route's rate limit (rules/permissions.md);
 * it lives in the cache under its token.
 *
 * @implements CommandInterface<CreateGuestInvoiceResult>
 */
final readonly class CreateGuestInvoice implements CallsOutsideDatabaseInterface, CommandInterface
{
    /**
     * @param  array<string, mixed>  $validated  validated request input, items under 'invoice-products'
     */
    public function __construct(
        public array $validated,
        public DocumentPartyDTO $supplier,
        public DocumentPartyDTO $customer,
        public ?DocumentAccountDTO $account,
        public ?string $locale,
    ) {}

    public static function access(): Access
    {
        return Access::public();
    }
}
