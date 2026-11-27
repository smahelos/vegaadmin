<?php

declare(strict_types=1);

namespace App\Application\Invoice\Commands;

use App\Domain\Invoice\DTO\InvoiceDTO;
use App\Domain\Invoice\Services\InvoiceService;
use App\Domain\Party\Contracts\BusinessReadRepositoryInterface;
use App\Domain\Party\Exceptions\BusinessNotFoundException;
use App\Domain\Shared\Authorization\ValueObjects\WriteScope;
use App\Domain\Shared\Time\Contracts\ClockInterface;
use App\Domain\User\Enums\LimitedEntity;
use App\Domain\User\Services\EntityLimit;

/**
 * The monthly invoice quota counts issued invoices, so it is checked here (D-047, D-050). The supplier's
 * invoicing settings of this moment are copied to the invoice.
 */
final class IssueInvoiceHandler
{
    public function __construct(
        private readonly InvoiceService $invoiceService,
        private readonly BusinessReadRepositoryInterface $businesses,
        private readonly EntityLimit $limits,
        private readonly ClockInterface $clock,
    ) {}

    public function handle(IssueInvoice $command, WriteScope $scope): InvoiceDTO
    {
        $this->limits->assertCanCreate($command->owner, LimitedEntity::INVOICE);
        $draft = $this->invoiceService->draftForUpdate($scope, $command->id);
        $businessId = $draft->parties->businessId;
        $business = $this->businesses->findById($businessId, $scope->read)
            ?? throw new BusinessNotFoundException($businessId);

        return $this->invoiceService->issueInvoice(
            $scope,
            $command->id,
            $command->number,
            $command->issueDate,
            $command->taxPointDate,
            $business->invoicing,
            $this->clock->now(),
        );
    }
}
