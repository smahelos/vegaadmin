<?php

declare(strict_types=1);

namespace App\Application\Invoice\Services\Specialized;

use App\Application\Invoice\Contracts\InvoiceCrudApplicationServiceInterface;
use App\Application\Invoice\DTO\InvoicePartySelection;
use App\Application\Invoice\Services\DocumentLogoApplicationService;
use App\Application\Invoice\Services\InvoicePartiesAssembler;
use App\Application\Invoice\Services\InvoiceProductOwnership;
use App\Domain\Invoice\Contracts\InvoiceDtoReadRepositoryInterface;
use App\Domain\Invoice\Contracts\InvoiceServiceInterface;
use App\Domain\Invoice\DTO\InvoiceDTO;
use App\Domain\Invoice\DTO\InvoiceProductWriteData;
use App\Domain\Invoice\Exceptions\InvoiceNotFoundException;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Party\Contracts\BusinessReadRepositoryInterface;
use App\Domain\Party\Exceptions\BusinessNotFoundException;
use App\Domain\Shared\Database\Contracts\TransactionBoundaryInterface;
use App\Domain\User\Contracts\UniversalLimitServiceInterface;
use App\Domain\User\Contracts\UserPermissionServiceInterface;
use App\Domain\User\Contracts\UserWriteRepositoryInterface;
use App\Domain\User\Exceptions\EntityLimitExceededException;
use App\Domain\User\ValueObjects\UserId;

final class InvoiceCrudApplicationService implements InvoiceCrudApplicationServiceInterface
{
    public function __construct(
        private readonly InvoiceServiceInterface $invoiceService,
        private readonly DocumentLogoApplicationService $logos,
        private readonly InvoiceProductOwnership $productOwnership,
        private readonly BusinessReadRepositoryInterface $businesses,
        private readonly InvoiceDtoReadRepositoryInterface $invoices,
        private readonly InvoicePartiesAssembler $parties,
        private readonly TransactionBoundaryInterface $transaction,
        private readonly UniversalLimitServiceInterface $limits,
        private readonly UserPermissionServiceInterface $permissions,
        private readonly UserWriteRepositoryInterface $users,
    ) {}

    /**
     * A customer saved to the contacts is created with the invoice or not at all.
     *
     * @param  array<string, mixed>  $data
     * @param  list<InvoiceProductWriteData>  $products
     */
    public function create(
        UserId $userId,
        array $data,
        InvoicePartySelection $parties,
        array $products,
        ?UserId $actorId = null,
    ): InvoiceDTO {
        $this->productOwnership->assertOwned($userId, $products);
        $business = $this->businesses->findByIdForUser($parties->businessId, $userId)
            ?? throw new BusinessNotFoundException($parties->businessId);

        return $this->logos->saveWithLogo(
            null,
            false,
            $business->logo,
            null,
            fn (?string $logo): InvoiceDTO => $this->transaction->transaction(
                function () use ($userId, $data, $parties, $products, $actorId, $logo): InvoiceDTO {
                    $this->users->lockForUpdate($userId);
                    $unlimited = $actorId !== null && in_array(
                        'can_create_invoice_unlimited',
                        $this->permissions->getUserPermissions($actorId->toInt(), 'backpack'),
                        true,
                    );
                    if (! $unlimited) {
                        $check = $this->limits->checkLimit(
                            $userId->toInt(),
                            'invoice',
                            'count',
                            $this->limits->getBestPeriodType($userId->toInt(), 'invoice'),
                        );
                        if (! $check['allowed']) {
                            throw new EntityLimitExceededException($check);
                        }
                    }

                    return $this->invoiceService->createInvoice(
                        $userId,
                        $data,
                        $this->parties->assemble($userId, $parties, null)->withSupplierLogo($logo),
                        $products,
                    );
                },
            ),
        );
    }

    /**
     * The invoice and its lines are replaced together; a failure leaves the stored invoice untouched.
     *
     * @param  array<string, mixed>  $data
     * @param  list<InvoiceProductWriteData>  $products
     */
    public function update(
        UserId $userId,
        InvoiceId $invoiceId,
        array $data,
        InvoicePartySelection $parties,
        ?array $products,
    ): InvoiceDTO {
        $stored = $this->invoices->findByIdForUser($invoiceId, $userId)
            ?? throw new InvoiceNotFoundException('Invoice not accessible for user');
        $this->productOwnership->assertOwned($userId, $products ?? []);
        $business = $this->businesses->findByIdForUser($parties->businessId, $userId)
            ?? throw new BusinessNotFoundException($parties->businessId);
        $keep = ! $parties->reloadBusiness && $stored->parties->businessId->equals($parties->businessId);

        return $this->logos->saveWithLogo(
            $stored->parties->supplierLogo,
            $keep,
            $business->logo,
            null,
            fn (?string $logo): InvoiceDTO => $this->transaction->transaction(
                fn (): InvoiceDTO => $this->invoiceService->updateInvoice(
                    $userId,
                    $invoiceId,
                    $data,
                    $this->parties->assemble($userId, $parties, $stored->parties)->withSupplierLogo($logo),
                    $products,
                ),
            ),
        );
    }
}
