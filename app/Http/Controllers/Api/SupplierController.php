<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\HandlesBackpackApiAuthentication;
use App\Traits\HandlesFrontendApiAuthentication;
use App\Models\Supplier;
use Illuminate\Support\Facades\Auth;

class SupplierController extends Controller
{
    use HandlesFrontendApiAuthentication, 
        HandlesBackpackApiAuthentication;

    /**
     * Get supplier data by ID (Admin API endpoint)
     * Admins can access any supplier
     *
     * @param int $id Supplier ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSupplierAdmin($id)
    {
        try {
            $user = $this->getBackpackUser();
            
            if (!$user) {
                return response()->json(['error' => __('users.auth.unauthenticated')], 401);
            }

            // Check if user has admin role (this should be handled by middleware, but double-check)
            if (!$user->hasRole('admin')) {
                return response()->json(['error' => __('users.auth.unauthenticated')], 403);
            }

            $supplier = Supplier::findOrFail($id);
            
            return response()->json($supplier);
        } catch (\Exception $e) {
            return response()->json(['error' => __('suppliers.messages.not_found')], 404);
        }
    }

    /**
     * Get supplier data by ID (Frontend API endpoint)
     * Users can only access their own suppliers
     *
     * @param int $id Supplier ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSupplier($id)
    {
        try {
            $user = $this->getFrontendUser();
            
            if (!$user) {
                return response()->json(['error' => __('users.auth.unauthenticated')], 401);
            }

            $supplier = Supplier::findOrFail($id);
            
            // Users can see only their suppliers (no admin bypass in frontend API)
            if ($supplier->user_id !== $user->id) {
                return response()->json(['error' => __('suppliers.messages.not_found')], 403);
            }
            
            return response()->json($supplier);
        } catch (\Exception $e) {
            return response()->json(['error' => __('suppliers.messages.not_found')], 404);
        }
    }
    
    /**
     * Get list of suppliers for authenticated user
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSuppliersAdmin()
    {
        $user = $this->getBackpackUser();

        if (!$user) {
            return response()->json(['message' => __('users.auth.unauthenticated')], 401);
        }
        
        // Admins can see all suppliers
        // if ($user->hasRole('admin')) {
        //     $suppliers = Supplier::all();
        // } else {
        //     $suppliers = Supplier::where('user_id', $user->id)->get();
        // }
        $suppliers = Supplier::all();
        
        return response()->json($suppliers);
    }
    
    /**
     * Get list of suppliers for authenticated user
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSuppliers()
    {
        $user = $this->getFrontendUser();
        
        if (!$user) {
            return response()->json(['message' => __('users.auth.unauthenticated')], 401);
        }
        
        $suppliers = Supplier::where('user_id', $user->id)->get();
        
        return response()->json($suppliers);
    }

    /**
     * Get default supplier for the authenticated user
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDefaultSupplier()
    {
        try {
            // Find default supplier
            $supplier = Supplier::where('user_id', Auth::id())
                ->where('is_default', true)
                ->first();
            
            // If no default supplier found, get the first one
            if (!$supplier) {
                $supplier = Supplier::where('user_id', Auth::id())
                    ->orderBy('created_at', 'desc')
                    ->first();
            }
            
            if (!$supplier) {
                return response()->json([
                    'error' => __('suppliers.messages.no_suppliers')
                ], 404);
            }
            
            return response()->json($supplier);
        } catch (\Exception $e) {
            return response()->json([
                'error' => __('suppliers.messages.error_loading')
            ], 500);
        }
    }
}
