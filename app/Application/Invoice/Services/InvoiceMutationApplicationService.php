<?php

namespace App\Application\Invoice\Services;

use App\Application\Invoice\Contracts\InvoiceCrudApplicationServiceInterface;
use App\Application\Invoice\Contracts\InvoiceMutationApplicationServiceInterface;
use App\Application\Invoice\DTO\InvoiceActionResult;
use App\Application\Invoice\DTO\InvoicePartySelection;
use App\Domain\Invoice\DTO\InvoiceDTO;
use App\Domain\Invoice\DTO\InvoiceProductWriteData;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Party\Exceptions\ArchivedReferenceSelectedException;
use App\Domain\Party\Exceptions\BankAccountNotFoundException;
use App\Domain\Party\Exceptions\BusinessNotFoundException;
use App\Domain\Party\Exceptions\ContactIdentityChangedException;
use App\Domain\Party\Exceptions\ContactNotFoundException;
use App\Domain\Party\Exceptions\DuplicateCompanyNumberException;
use App\Domain\Party\Exceptions\IncompleteBusinessAddressException;
use App\Domain\User\Exceptions\EntityLimitExceededException;
use App\Domain\User\ValueObjects\UserId;
use App\Models\Invoice;
use Illuminate\Support\Facades\Log;

class InvoiceMutationApplicationService implements InvoiceMutationApplicationServiceInterface
{
    public function __construct(private readonly InvoiceCrudApplicationServiceInterface $invoiceCrud) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  list<InvoiceProductWriteData>  $products
     */
    public function create(
        int $userId,
        array $data,
        InvoicePartySelection $parties,
        array $products,
    ): InvoiceActionResult {
        try {
            $invoice = $this->invoiceCrud->create(
                new UserId($userId),
                $data,
                $parties,
                $products,
            );

            // Convert DTO back to Eloquent model for compatibility
            $model = $this->resolvePublishedModelByDto($invoice);

            return InvoiceActionResult::success(message: 'invoices.messages.created', invoice: $model);
        } catch (
            ArchivedReferenceSelectedException|
            BankAccountNotFoundException|
            BusinessNotFoundException|
            ContactIdentityChangedException|
            ContactNotFoundException|
            DuplicateCompanyNumberException|
            IncompleteBusinessAddressException $e
        ) {
            // The exception handler answers these as validation errors or a 404
            throw $e;
        } catch (EntityLimitExceededException $e) {
            return InvoiceActionResult::failure($e->getMessageKey());
        } catch (\Throwable $e) {
            Log::error('Invoice create failed: ' . $e->getMessage() . json_encode($e->getTrace()));

            return InvoiceActionResult::failure('invoices.messages.create_failed');
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<InvoiceProductWriteData>  $products
     */
    public function update(
        int $userId,
        int $invoiceId,
        array $data,
        InvoicePartySelection $parties,
        array $products,
    ): InvoiceActionResult {
        try {
            $invoice = $this->invoiceCrud->update(
                new UserId($userId),
                new InvoiceId($invoiceId),
                $data,
                $parties,
                $products,
            );

            // Convert DTO back to Eloquent model for compatibility
            $model = $this->resolvePublishedModelByDto($invoice);

            return InvoiceActionResult::success(message: 'invoices.messages.updated', invoice: $model);
        } catch (
            ArchivedReferenceSelectedException|
            BankAccountNotFoundException|
            BusinessNotFoundException|
            ContactIdentityChangedException|
            ContactNotFoundException|
            DuplicateCompanyNumberException|
            IncompleteBusinessAddressException $e
        ) {
            // The exception handler answers these as validation errors or a 404
            throw $e;
        } catch (EntityLimitExceededException $e) {
            return InvoiceActionResult::failure($e->getMessageKey());
        } catch (\Throwable $e) {
            Log::error('Invoice update failed: ' . $e->getMessage());

            return InvoiceActionResult::failure('invoices.messages.update_failed');
        }
    }

    /**
     * Convert DTO back to Eloquent model for backward compatibility
     */
    private function resolvePublishedModelByDto(InvoiceDTO $dto): ?Invoice
    {
        // Simple implementation - just find the model by ID
        return Invoice::find($dto->id);
    }
}
