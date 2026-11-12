<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Application\Invoice\Commands\LinkInvoiceContact;
use App\Application\Invoice\Commands\SaveInvoiceCustomerToContacts;
use App\Application\Shared\UseCase\Actor;
use App\Application\Shared\UseCase\Contracts\AuthorizingUseCaseRunnerInterface;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\User\ValueObjects\UserId;
use App\Http\Controllers\Controller;
use App\Http\Requests\Invoice\LinkInvoiceContactRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Links the customer of an invoice to the address book from the invoice detail.
 */
final class InvoiceContactController extends Controller
{
    public function __construct(
        private readonly AuthorizingUseCaseRunnerInterface $useCases,
    ) {}

    public function saveToContacts(string $locale, int $invoice): RedirectResponse
    {
        $owner = $this->owner();
        $this->useCases->run(
            Actor::user($owner),
            new SaveInvoiceCustomerToContacts(
                $owner,
                InvoiceId::fromInt($invoice),
            ),
        );

        $redirect = back();

        return $redirect->with(
            'success',
            __('invoices.messages.saved_to_contacts'),
        );
    }

    public function linkContact(
        LinkInvoiceContactRequest $request,
        string $locale,
        int $invoice,
    ): RedirectResponse {
        $owner = $this->owner();
        // The route names the same use case in its `allowed` middleware: change them together
        $this->useCases->run(
            Actor::user($owner),
            new LinkInvoiceContact(
                $owner,
                InvoiceId::fromInt($invoice),
                $request->contactId(),
            ),
        );

        $redirect = back();

        return $redirect->with(
            'success',
            __('invoices.messages.contact_linked'),
        );
    }

    private function owner(): UserId
    {
        return UserId::fromInt((int) Auth::id());
    }
}
