<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Application\Party\Queries\GetBusiness;
use App\Application\Party\Queries\GetBusinessForEdit;
use App\Application\Shared\UseCase\Actor;
use App\Application\Shared\UseCase\Contracts\AuthorizingUseCaseRunnerInterface;
use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\User\ValueObjects\UserId;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Pages of the business screens; the Livewire components on them run the use cases. The show and edit pages run their
 * Query first, so a missing permission is a 403 and another user's business a 404 before the page renders. The locale
 * is the first route parameter of every frontend page.
 */
final class BusinessController extends Controller
{
    public function __construct(
        private readonly AuthorizingUseCaseRunnerInterface $useCases,
    ) {}

    public function index(): View
    {
        return view('frontend.businesses.index');
    }

    public function create(): View
    {
        return view('frontend.businesses.create');
    }

    public function show(string $locale, int $business): View
    {
        $owner = $this->owner();
        $found = $this->useCases->run(
            Actor::user($owner),
            new GetBusiness(
                $owner,
                BusinessId::fromInt($business),
            ),
        );

        return view(
            'frontend.businesses.show',
            ['businessId' => $found->id->value],
        );
    }

    public function edit(string $locale, int $business): View
    {
        $owner = $this->owner();
        $found = $this->useCases->run(
            Actor::user($owner),
            new GetBusinessForEdit(
                $owner,
                BusinessId::fromInt($business),
            ),
        );

        return view(
            'frontend.businesses.edit',
            ['businessId' => $found->id->value],
        );
    }

    private function owner(): UserId
    {
        return UserId::fromInt((int) Auth::id());
    }
}
