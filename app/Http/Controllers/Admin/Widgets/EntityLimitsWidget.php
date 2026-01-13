<?php

namespace App\Http\Controllers\Admin\Widgets;

use App\Models\EntityLimit;
use App\Models\User;
use App\Domain\User\Contracts\UniversalLimitServiceInterface as UniversalLimitService;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Illuminate\Http\Request;

class EntityLimitsWidget
{
    /**
     * Show the entity limits overview widget
     */
    public function show(Request $request)
    {
        $user = backpack_auth()->user();
        
        if (!$user || !$user->can('can_configure_system')) {
            return '';
        }

        $limitService = app(UniversalLimitService::class);
        
        // Get overview statistics
        $stats = [
            'total_limits' => EntityLimit::count(),
            'active_limits' => EntityLimit::where('is_active', true)->count(),
            'entity_types' => EntityLimit::distinct('entity_type')->count('entity_type'),
        ];
        
        // Get recent limits for display
        $recentLimits = EntityLimit::where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('admin.widgets.entity_limits_overview', compact('stats', 'recentLimits'));
    }

    /**
     * Show user-specific usage statistics  
     */
    public function userUsage(Request $request)
    {
        $user = backpack_auth()->user();
        $limitService = app(UniversalLimitService::class);
        
        // Get user's current usage for different entity types
        $entityTypes = ['invoice', 'client', 'supplier', 'product'];
        $userUsage = [];
        
        foreach ($entityTypes as $entityType) {
            $stats = $limitService->getUsageStatistics($user, $entityType);
            if ($stats) {
                $userUsage[$entityType] = $stats;
            }
        }
        
        return view('admin.widgets.user_entity_usage', compact('userUsage'));
    }
}
