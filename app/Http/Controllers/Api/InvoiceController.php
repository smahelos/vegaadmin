<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class InvoiceController extends ApiBackpackController
{
    /**
     * Get invoice data by ID using query parameter
     * Supports either /invoice?q=12 or /invoice/12 formats
     *
     * @param Request $request
     * @param int|null $id Optional ID from route parameter
     * @return \Illuminate\Http\JsonResponse
     */
    public function getInvoice(Request $request, $id = null)
    {
        // Get ID either from query parameter or route parameter
        $invoiceId = $id ?? $request->query('q');
        
        if (!$invoiceId) {
            return response()->json([
                'error' => __('invoices.messages.id_required')
            ], 400);
        }
        
        $logContext = $this->getLogContext(['invoice_id' => $invoiceId]);
        
        try {
            $user = $this->getAuthenticatedUser();
            
            if (!$user) {
                return response()->json(['message' => __('auth.unauthenticated')], 401);
            }

            $invoice = Invoice::findOrFail($invoiceId);
            
            // Admins can see any invoice
            if ($user->hasRole('admin')) {
                // Admin access
            }
            // Regular users can see only their invoices
            else if ($invoice->user_id !== $user->id) {
                return response()->json(['error' => __('invoices.messages.not_found')], 403);
            }
            
            return response()->json($invoice);
        } catch (\Exception $e) {
            Log::error('API error: Invoice not found', array_merge($logContext, [
                'error' => $e->getMessage(),
            ]));
            
            return response()->json(['error' => __('invoices.messages.not_found')], 404);
        }
    }
    
    /**
     * Get list of invoices for authenticated user
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getInvoices()
    {
        $user = $this->getAuthenticatedUser();
        
        if (!$user) {
            return response()->json(['message' => __('auth.unauthenticated')], 401);
        }
        
        // Admins can see all invoices
        if ($user->hasRole('admin')) {
            $invoices = Invoice::all();
        } else {
            $invoices = Invoice::where('user_id', $user->id)->get();
        }
        
        return response()->json($invoices);
    }
}
