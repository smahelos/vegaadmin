<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Application\Invoice\Services\InvoiceContactApplicationService;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\User\ValueObjects\UserId;
use App\Http\Controllers\Controller;
use App\Http\Requests\Invoice\LinkInvoiceContactRequest;
use App\Http\Requests\Invoice\SaveCustomerToContactsRequest;
use Illuminate\Http\RedirectResponse;

/**
 * Links the customer of an invoice to the address book from the invoice detail.
 */
final class InvoiceContactController extends Controller
{
    public function __construct(
        private readonly InvoiceContactApplicationService $invoiceContacts,
    ) {}

    public function saveToContacts(
        SaveCustomerToContactsRequest $request,
        string $locale,
        int $invoice,
    ): RedirectResponse {
        $user = $request->user();
        $this->invoiceContacts->saveCustomerToContacts(
            UserId::fromInt((int) $user?->getAuthIdentifier()),
            InvoiceId::fromInt($invoice),
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
        $user = $request->user();
        $this->invoiceContacts->linkContact(
            UserId::fromInt((int) $user?->getAuthIdentifier()),
            InvoiceId::fromInt($invoice),
            $request->contactId(),
        );

        $redirect = back();

        return $redirect->with(
            'success',
            __('invoices.messages.contact_linked'),
        );
    }
}
