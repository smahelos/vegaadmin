<?php

namespace App\Application\Invoice\Services;

use App\Application\Invoice\Contracts\InvoiceCrudApplicationServiceInterface;
use App\Application\Invoice\Contracts\InvoiceMutationApplicationServiceInterface;
use App\Application\Invoice\DTO\InvoiceActionResult;
use App\Domain\Invoice\DTO\InvoiceDTO;
use App\Domain\Invoice\DTO\InvoiceProductWriteData;
use App\Domain\Invoice\ValueObjects\InvoiceId;
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
        array $products,
    ): InvoiceActionResult {
        try {
            $invoice = $this->invoiceCrud->create(
                new UserId($userId),
                $data,
                $products,
            );

            // Convert DTO back to Eloquent model for compatibility
            $model = $this->resolvePublishedModelByDto($invoice);

            return InvoiceActionResult::success(message: 'invoices.messages.created', invoice: $model);
        } catch (EntityLimitExceededException) {
            return InvoiceActionResult::failure('invoices.messages.limit_exceeded');
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
        array $products,
    ): InvoiceActionResult {
        try {
            $invoice = $this->invoiceCrud->update(
                new UserId($userId),
                new InvoiceId($invoiceId),
                $data,
                $products,
            );

            // Convert DTO back to Eloquent model for compatibility
            $model = $this->resolvePublishedModelByDto($invoice);

            return InvoiceActionResult::success(message: 'invoices.messages.updated', invoice: $model);
        } catch (EntityLimitExceededException) {
            return InvoiceActionResult::failure('invoices.messages.limit_exceeded');
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
