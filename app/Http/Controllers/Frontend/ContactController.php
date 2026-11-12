<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Application\Party\Queries\GetContact;
use App\Application\Party\Queries\GetContactForEdit;
use App\Application\Shared\UseCase\Actor;
use App\Application\Shared\UseCase\Contracts\AuthorizingUseCaseRunnerInterface;
use App\Domain\Party\ValueObjects\ContactId;
use App\Domain\User\ValueObjects\UserId;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Pages of the contact screens; the Livewire components on them run the use cases. The show and edit pages run their
 * Query first, so a missing permission is a 403 and another user's contact a 404 before the page renders. The locale
 * is the first route parameter of every frontend page.
 */
final class ContactController extends Controller
{
    public function __construct(
        private readonly AuthorizingUseCaseRunnerInterface $useCases,
    ) {}

    public function index(): View
    {
        return view('frontend.contacts.index');
    }

    public function create(): View
    {
        return view('frontend.contacts.create');
    }

    public function show(string $locale, int $contact): View
    {
        $owner = $this->owner();
        $found = $this->useCases->run(
            Actor::user($owner),
            new GetContact(
                $owner,
                ContactId::fromInt($contact),
            ),
        );

        return view(
            'frontend.contacts.show',
            ['contactId' => $found->id->value],
        );
    }

    public function edit(string $locale, int $contact): View
    {
        $owner = $this->owner();
        $found = $this->useCases->run(
            Actor::user($owner),
            new GetContactForEdit(
                $owner,
                ContactId::fromInt($contact),
            ),
        );

        return view(
            'frontend.contacts.edit',
            ['contactId' => $found->id->value],
        );
    }

    private function owner(): UserId
    {
        return UserId::fromInt((int) Auth::id());
    }
}
