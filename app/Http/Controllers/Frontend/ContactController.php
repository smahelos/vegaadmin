<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Application\Party\Services\ContactApplicationService;
use App\Domain\Party\ValueObjects\ContactId;
use App\Domain\User\ValueObjects\UserId;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pages of the contact screens; the Livewire components on them check the permissions and do the work through the
 * application services. The locale is the first route parameter of every frontend page.
 */
final class ContactController extends Controller
{
    public function __construct(
        private readonly ContactApplicationService $contacts,
    ) {}

    public function index(): View
    {
        return view('frontend.contacts.index');
    }

    public function create(): View
    {
        return view('frontend.contacts.create');
    }

    public function show(
        Request $request,
        string $locale,
        int $contact,
    ): View {
        return view('frontend.contacts.show', ['contactId' => $this->owned($request, $contact)]);
    }

    public function edit(
        Request $request,
        string $locale,
        int $contact,
    ): View {
        return view('frontend.contacts.edit', ['contactId' => $this->owned($request, $contact)]);
    }

    /**
     * Another user's contact is a 404 before the page renders.
     */
    private function owned(Request $request, int $id): int
    {
        return $this->contacts->get(UserId::fromInt((int) $request->user()?->getAuthIdentifier()), ContactId::fromInt($id))->id->value;
    }
}
