<?php

namespace App\Http\Controllers\Api;

// Use ApiBackpackController to inherit getAuthenticatedUser + logging context
use App\Application\Invoice\Contracts\InvoiceListingApplicationServiceInterface;
use App\Domain\Invoice\ValueObjects\InvoiceId as InvoiceIdVO;
use App\Domain\User\ValueObjects\UserId;
use App\Http\Controllers\Api\ApiBackpackController as Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InvoiceController extends Controller
{
    public function __construct(private InvoiceListingApplicationServiceInterface $invoiceListing) {}

    /**
     * Get invoice data by ID using query parameter
     * Supports either /invoice?q=12 or /invoice/12 formats
     *
     * @param  int|null  $id  Optional ID from route parameter
     */
    public function getInvoice(Request $request, ?int $id = null): JsonResponse
    {
        // Get ID either from query parameter or route parameter
        $invoiceId = $id ?? $request->query('q');

        if (! $invoiceId) {
            return response()->json([
                'error' => __('invoices.messages.id_required'),
            ], 400);
        }

        $logContext = $this->getLogContext(['invoice_id' => $invoiceId]);

        try {
            $user = $this->getAuthenticatedUser();

            if (! $user) {
                return response()->json(['error' => __('users.auth.unauthenticated')], 401);
            }

            $invoice = $this->invoiceListing->getById(
                new InvoiceIdVO((int) $invoiceId),
                new UserId($user->id),
            );

            // Admins can see any invoice
            if (method_exists($user, 'hasRole') && ($user->hasRole('admin', 'backpack') || $user->hasRole('admin'))) {
                // Admin access
            }
            // Regular users can see only their invoices
            elseif ($invoice->user_id !== $user->id) {
                return response()->json(['error' => __('users.auth.unauthorized')], 403);
            }

            // Map DTO to primitive JSON structure
            $id = ($invoice->id instanceof InvoiceIdVO) ? $invoice->id->getValue() : (int) $invoice->id;

            return response()->json([
                'id' => (int) $id,
                'user_id' => (int) $invoice->user_id,
            ]);
        } catch (\Exception $e) {
            Log::error('API error: Invoice not found', array_merge($logContext, [
                'error' => $e->getMessage(),
            ]));

            return response()->json(['error' => __('invoices.messages.not_found')], 404);
        }
    }

    /**
     * Get list of invoices for authenticated user
     */
    public function getInvoices(): JsonResponse
    {
        $user = $this->getAuthenticatedUser();

        if (! $user) {
            return response()->json(['error' => __('users.auth.unauthenticated')], 401);
        }

        $invoices = $this->invoiceListing->getAll(new UserId($user->id));

        return response()->json($invoices);
    }
}
