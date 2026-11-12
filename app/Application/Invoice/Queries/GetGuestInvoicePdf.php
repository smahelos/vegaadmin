<?php

declare(strict_types=1);

namespace App\Application\Invoice\Queries;

use App\Application\Invoice\DTO\PdfInvoiceDTO;
use App\Application\Shared\UseCase\Access;
use App\Application\Shared\UseCase\Contracts\QueryInterface;

/**
 * The token is the guest's only access to the invoice they entered (PRD_PERMISSIONS A10).
 *
 * @implements QueryInterface<PdfInvoiceDTO>
 */
final readonly class GetGuestInvoicePdf implements QueryInterface
{
    public function __construct(
        public string $token,
        public ?string $locale,
    ) {}

    public static function access(): Access
    {
        return Access::public();
    }
}
