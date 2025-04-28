<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SupplierController extends ApiBackpackController
{
    /**
     * Get supplier data by ID
     *
     * @param int $id Supplier ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSupplier($id)
    {
        $logContext = $this->getLogContext(['supplier_id' => $id]);
        
        try {
            $user = $this->getAuthenticatedUser();
            
            if (!$user) {
                return response()->json(['message' => __('auth.unauthenticated')], 401);
            }

            $supplier = Supplier::findOrFail($id);
            
            // Admins can see any supplier
            if ($user->hasRole('admin')) {
                // Admin access
            }
            // Regular users can see only their suppliers
            else if ($supplier->user_id !== $user->id) {
                return response()->json(['error' => __('suppliers.messages.not_found')], 403);
            }
            
            return response()->json($supplier);
        } catch (\Exception $e) {
            Log::error('API error: Supplier not found', array_merge($logContext, [
                'error' => $e->getMessage(),
            ]));
            
            return response()->json(['error' => __('suppliers.messages.not_found')], 404);
        }
    }
    
    /**
     * Get list of suppliers for authenticated user
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSuppliers()
    {
        $user = $this->getAuthenticatedUser();
        
        if (!$user) {
            return response()->json(['message' => __('auth.unauthenticated')], 401);
        }
        
        // Admins can see all suppliers
        if ($user->hasRole('admin')) {
            $suppliers = Supplier::all();
        } else {
            $suppliers = Supplier::where('user_id', $user->id)->get();
        }
        
        return response()->json($suppliers);
    }
}