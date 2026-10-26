<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Application\Party\Services\BusinessApplicationService;
use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\User\ValueObjects\UserId;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pages of the business screens; the Livewire components on them check the permissions and do the work through the
 * application services. The locale is the first route parameter of every frontend page.
 */
final class BusinessController extends Controller
{
    public function __construct(
        private readonly BusinessApplicationService $businesses,
    ) {}

    public function index(): View
    {
        return view('frontend.businesses.index');
    }

    public function create(): View
    {
        return view('frontend.businesses.create');
    }

    public function show(
        Request $request,
        string $locale,
        int $business,
    ): View {
        return view('frontend.businesses.show', ['businessId' => $this->owned($request, $business)]);
    }

    public function edit(
        Request $request,
        string $locale,
        int $business,
    ): View {
        return view('frontend.businesses.edit', ['businessId' => $this->owned($request, $business)]);
    }

    /**
     * Another user's business is a 404 before the page renders.
     */
    private function owned(Request $request, int $id): int
    {
        return $this->businesses->get(UserId::fromInt((int) $request->user()?->getAuthIdentifier()), BusinessId::fromInt($id))->id->value;
    }
}
