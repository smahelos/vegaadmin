<?php

namespace App\Http\Controllers\Api;

use App\Application\Party\Contracts\PartyApplicationServiceInterface;
use App\Application\User\Contracts\UserAuthorizationAdapterInterface;
// Use Application facade for party operations
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class SupplierController extends Controller
{
    public function __construct(
        private PartyApplicationServiceInterface $partyService,
        private ?UserAuthorizationAdapterInterface $auth = null,
    ) {
        $this->auth = $this->auth ?? app(UserAuthorizationAdapterInterface::class);
    }

    /**
     * Get supplier data by ID (Admin API endpoint)
     * Admins can access any supplier
     *
     * @param  int  $id  Supplier ID
     */
    public function getSupplierAdmin(int $id): JsonResponse
    {
        try {
            $user = $this->getBackpackUser();

            if (! $user) {
                return response()->json(['error' => __('users.auth.unauthenticated')], 401);
            }

            // Check if user has permission to view suppliers (backpack guard)
            if (! $this->auth->hasBackpackPermission((int) $user->id, 'can_view_supplier')) {
                return response()->json(['error' => __('backpack::crud.unauthorized_access')], 403);
            }

            // Admin endpoint: global lookup without ownership restriction (handled in service)
            $supplier = $this->partyService->findSupplier($user->id, (int) $id);

            return response()->json($supplier);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => __('suppliers.messages.not_found')], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => __('suppliers.messages.not_found')], 404);
        }
    }

    /**
     * Get supplier data by ID (Frontend API endpoint)
     * Users can only access their own suppliers
     *
     * @param  int  $id  Supplier ID
     */
    public function getSupplier(int $id): JsonResponse
    {
        try {
            $user = $this->getFrontendUser();

            if (! $user) {
                return response()->json(['error' => __('users.auth.unauthenticated')], 401);
            }

            $supplier = $this->partyService->findSupplier($user->id, (int) $id);

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
     */
    public function getSuppliersAdmin(): JsonResponse
    {
        $user = $this->getBackpackUser();

        if (! $user) {
            return response()->json(['message' => __('users.auth.unauthenticated')], 401);
        }

        $suppliers = $this->partyService->listSuppliers($user);

        return response()->json($suppliers);
    }

    /**
     * Get list of suppliers for authenticated user
     */
    public function getSuppliers(): JsonResponse
    {
        $user = $this->getFrontendUser();

        if (! $user) {
            return response()->json(['message' => __('users.auth.unauthenticated')], 401);
        }

        $suppliers = $this->partyService->listSuppliers($user);

        return response()->json($suppliers);
    }

    /**
     * Get default supplier for the authenticated user
     */
    public function getDefaultSupplier(): JsonResponse
    {
        try {
            // Find default supplier
            $supplier = $this->partyService->defaultSupplierOrFirst((int) Auth::id());

            if (! $supplier) {
                return response()->json([
                    'error' => __('suppliers.messages.no_suppliers'),
                ], 404);
            }

            return response()->json($supplier);
        } catch (\Exception $e) {
            return response()->json([
                'error' => __('suppliers.messages.error_loading'),
            ], 500);
        }
    }

    /**
     * Get currently authenticated backpack user (for tests, override in subclass)
     */
    protected function getBackpackUser(): ?User
    {
        return function_exists('backpack_auth') && backpack_auth()->check() ? backpack_auth()->user() : null;
    }

    /**
     * Get currently authenticated frontend user (for tests, override in subclass)
     */
    protected function getFrontendUser(): ?User
    {
        return Auth::guard('web')->check() ? Auth::guard('web')->user() : null;
    }
}
