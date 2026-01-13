<?php

namespace App\Application\Invoice\Services;

use App\Application\Invoice\Contracts\InvoiceStatusApplicationServiceInterface;
use App\Application\Invoice\Contracts\InvoiceLimitApplicationServiceInterface;
use App\Application\Invoice\Contracts\InvoiceListingApplicationServiceInterface;
use App\Application\Invoice\Contracts\InvoiceReadRepositoryInterface;
use App\Domain\Invoice\Contracts\InvoiceServiceInterface;
use App\Application\Invoice\Contracts\InvoiceFrontendActionsApplicationServiceInterface;
use App\Application\Invoice\DTO\InvoiceActionResult;
use App\Application\Invoice\DTO\OrchestratedInvoiceActions;
use App\Domain\User\ValueObjects\UserId;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Shared\Status\ValueObjects\StatusId;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class InvoiceFrontendActionsApplicationService implements InvoiceFrontendActionsApplicationServiceInterface
{
    public function __construct(
        private InvoiceStatusApplicationServiceInterface $invoiceStatus,
        private InvoiceLimitApplicationServiceInterface $invoiceLimit,
        private InvoiceListingApplicationServiceInterface $invoiceListing,
        private InvoiceReadRepositoryInterface $invoiceReadRepository,
        private InvoiceServiceInterface $invoiceService
    ) {}

    public function markAsPaid(int $userId, int $invoiceId): InvoiceActionResult
    {
        // Delegate to generic status change to reduce duplication.
        return $this->changeStatus($userId, $invoiceId, 'paid');
    }

    public function setTemplate(int $userId, int $invoiceId, string $template): InvoiceActionResult
    {
        try {
            // Find invoice and verify ownership
            $invoice = $this->invoiceReadRepository->findForUser($userId, $invoiceId);
            if (!$invoice) {
                return InvoiceActionResult::failure('invoices.messages.not_found');
            }
            
            // Delegate to domain service
            $ok = $this->invoiceService->setInvoiceTemplate(InvoiceId::fromInt($invoice->id), $template);
            return $ok
                ? InvoiceActionResult::success('invoices.messages.template_set')
                : InvoiceActionResult::failure('invoices.messages.update_error');
        } catch (\Throwable $e) {
            Log::error('InvoiceFrontendActions setTemplate failed', [
                'invoice_id' => $invoiceId,
                'user_id' => $userId,
                'template' => $template,
                'error' => $e->getMessage(),
            ]);
            return InvoiceActionResult::failure('invoices.messages.update_error');
        }
    }

    public function changeStatus(int $userId, int $invoiceId, string $statusSlug): InvoiceActionResult
    {
        try {
            // Convert string status to StatusId - first find status by slug
            // For now, use a simple mapping - this should be improved to use repository
            $statusMapping = [
                'paid' => 2, // assuming paid status has ID 2
                'unpaid' => 1, // assuming unpaid status has ID 1
                'draft' => 3, // assuming draft status has ID 3
            ];
            
            if (!isset($statusMapping[$statusSlug])) {
                return InvoiceActionResult::failure('invoices.messages.status_not_found');
            }
            
            $statusId = new StatusId($statusMapping[$statusSlug]);
            $this->invoiceStatus->changeStatus(
                new InvoiceId($invoiceId),
                $statusId,
                new UserId($userId)
            );
            
            $messageKey = $statusSlug === 'paid' ? 'invoices.messages.marked_as_paid' : 'invoices.messages.status_changed';
            return InvoiceActionResult::success(message: $messageKey);
        } catch (\Throwable $e) {
            Log::error('InvoiceFrontendActions changeStatus failed', [
                'invoice_id' => $invoiceId,
                'user_id' => $userId,
                'status' => $statusSlug,
                'error' => $e->getMessage(),
            ]);
            return InvoiceActionResult::failure('invoices.messages.update_error');
        }
    }

    public function getLimitData(int $userId): InvoiceActionResult
    {
        try {
            $data = $this->invoiceLimit->getLimitInfo(new UserId($userId));
            return InvoiceActionResult::success(data: $data);
        } catch (\Throwable $e) {
            Log::warning('InvoiceFrontendActions getLimitData failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            return InvoiceActionResult::failure('invoices.messages.limits_error', data: [ 'limit' => 0, 'current_usage' => 0, 'allowed' => false ]);
        }
    }

    /**
     * Execute multiple lightweight frontend actions atomically from caller perspective using immutable command.
     * Non-fatal failures accumulate; any sub-action failure returns FAILURE with aggregated errors.
     * If includeInvoice is true and any action succeeded, the updated invoice is fetched and returned.
     * To use it, you need to call this method via AJAX or standard form POST.
     * Now, there is prepared batchActions method in InvoiceController to use.
     */
    public function orchestrate(int $userId, int $invoiceId, OrchestratedInvoiceActions $actions): InvoiceActionResult
    {
        $errors = [];
        $messages = [];
        // Template change
        if ($actions->template) {
            $r = $this->setTemplate($userId, $invoiceId, $actions->template);
            if ($r->status === \App\Application\Invoice\DTO\InvoiceActionStatus::FAILURE) {
                $errors['template'] = $r->message;
            } else { $messages[] = $r->message; }
        }
        // Status (already normalized in command: mark_paid => 'paid')
        if ($actions->status) {
            $r = $this->changeStatus($userId, $invoiceId, $actions->status);
            if ($r->status === \App\Application\Invoice\DTO\InvoiceActionStatus::FAILURE) {
                $errors['status'] = $r->message;
            } else { $messages[] = $r->message; }
        }
        if ($errors) {
            // Use first granular error key instead of generic update_error for better UX / i18n specificity.
            $primaryErrorKey = reset($errors) ?: 'invoices.messages.update_error';
            return InvoiceActionResult::failure($primaryErrorKey, data: [ 'errors' => $errors, 'messages' => $messages ]);
        }
        $invoice = null;
        if ($actions->includeInvoice && !empty($messages)) {
            try {
                // // Reuse existing read path: prepareShowData returns DTO; fetch invoice directly via API fetch for minimal overhead.
                // $invoiceDTO = $this->invoiceListing->getById(
                //     new InvoiceId($invoiceId),
                //     new UserId($userId)
                // );
                // // InvoiceActionResult očekáva Model, ne DTO - zatím neposíláme invoice
                // // TODO: Upravit InvoiceActionResult aby podporoval DTO nebo načítat Model
                // $invoice = null;

                // Fetch the Eloquent Invoice model for the user to attach to the result
                $found = $this->invoiceReadRepository->findForUser($userId, $invoiceId);
                $invoice = $found ?: null;
            } catch (\Throwable $e) {
                Log::warning('InvoiceFrontendActions orchestrate includeInvoice fetch failed', [
                    'invoice_id' => $invoiceId,
                    'user_id' => $userId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        return InvoiceActionResult::success(message: $messages ? $messages[0] : null, data: [ 'messages' => $messages ], invoice: $invoice);
    }
}
