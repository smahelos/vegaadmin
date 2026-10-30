<?php

namespace App\Application\Invoice\Services;

use App\Application\Invoice\Contracts\GuestInvoiceApplicationServiceInterface;
use App\Application\Invoice\Contracts\InvoiceFormApplicationServiceInterface;
use App\Application\Invoice\Contracts\TemporaryInvoiceStoreInterface;
use App\Application\Invoice\Form\InvoiceCreateFieldSetFactory;
use App\Application\Payment\Mappers\QrPaymentMapper;
use App\Domain\Invoice\ValueObjects\InvoiceTotals;
use App\Domain\Party\DTO\DocumentAccountDTO;
use App\Domain\Party\DTO\DocumentPartyDTO;
use App\Domain\Payment\Contracts\QrPaymentServiceInterface;
use App\Domain\Shared\Money\Enums\Currency;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Domain\User\Contracts\Locale;
use App\Models\PaymentMethod;
use App\Models\Status;
use App\Models\Tax;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class GuestInvoiceApplicationService implements GuestInvoiceApplicationServiceInterface
{
    public function __construct(
        private readonly InvoiceFormApplicationServiceInterface $invoiceFormService,
        private readonly Locale $locale,
        private readonly QrPaymentServiceInterface $qrPaymentService,
        private readonly TemporaryInvoiceStoreInterface $temporaryInvoiceStore,
        private readonly QrPaymentMapper $qrPaymentMapper,
        private readonly InvoiceProductWriteDataAssembler $products,
    ) {}

    /**
     * The parties are stored as entered, in the shape a stored invoice keeps them, so the print reads both alike.
     *
     * @param  array<string, mixed>  $validatedData  validated request input, items under 'invoice-products'
     */
    public function storeGuestInvoice(
        array $validatedData,
        DocumentPartyDTO $supplier,
        DocumentPartyDTO $customer,
        ?DocumentAccountDTO $account,
        ?string $requestedLocale,
    ): array {
        $locale = $this->locale->determineLocale($requestedLocale);
        $validatedData['lang'] = $locale;
        $validatedData['supplier'] = $supplier;
        $validatedData['customer'] = $customer;
        $validatedData['account'] = $account;

        if (isset($validatedData['due_in'])) {
            $validatedData['due_in'] = (int) $validatedData['due_in'];
        }

        $validatedData['payment_amount'] = InvoiceTotals::fromProducts(
            Currency::from((string) $validatedData['payment_currency']),
            $this->products->fromValidated($validatedData['invoice-products']),
        )->total()->getAmount();

        $token = $this->temporaryInvoiceStore->store($validatedData);

        Session::put('last_guest_invoice_token', $token);
        Session::put('last_guest_invoice_number', $validatedData['invoice_vs'] ?? null);
        Session::put('last_guest_invoice_expires', now()->addMinutes(10)->timestamp);

        $qrCodeBase64 = null;
        try {
            if ($account !== null) {
                $qrPayload = $this->qrPaymentMapper->fromSnapshot(
                    $validatedData['invoice_vs'] ?? null,
                    Money::fromString($validatedData['payment_amount'], $validatedData['payment_currency']),
                    $account,
                    $validatedData['invoice_text'] ?? null,
                );
                $qrCodeBase64 = $this->qrPaymentService->generateQrCodeBase64($qrPayload);
            }
        } catch (\Exception $e) {
            Log::error('Guest invoice QR generation failed', ['exception' => $e]);
        }

        return [
            'success' => true,
            'message' => __('invoices.messages.created_guest'),
            'invoice_number' => $validatedData['invoice_vs'] ?? null,
            'download_url' => route('frontend.invoice.download.token', [
                'locale' => $locale,
                'token' => $token,
            ]),
            'token' => $token,
            'qr_code' => $qrCodeBase64,
            'has_qr_code' => ! empty($qrCodeBase64),
        ];
    }

    public function prepareGuestCreateData(): array
    {
        $paymentMethods = PaymentMethod::pluck('slug', 'id')->toArray();
        $statuses = Status::pluck('name', 'id')->toArray();
        $taxRates = Tax::where('slug', 'dph')->pluck('rate', 'id')->toArray();
        // Guest context: no authenticated user ID => use 0 sentinel to start sequence at YYYY0001
        $suggestedNumber = $this->invoiceFormService->getNextInvoiceNumber(0);
        $limitsData = [
            'limit' => 0,
            'current_usage' => 0,
            'allowed' => false,
        ];
        $invoiceProducts = [];
        $fieldSet = app(InvoiceCreateFieldSetFactory::class)->build(
            $paymentMethods,
            $statuses,
        );
        $fields = $fieldSet->toArray();

        return compact(
            'paymentMethods',
            'statuses',
            'taxRates',
            'suggestedNumber',
            'limitsData',
            'invoiceProducts',
            'fields',
        );
    }

    public function deleteTemporary(string $token): bool
    {
        $sessionToken = Session::get('last_guest_invoice_token');
        if ($sessionToken !== $token) {
            return false;
        }
        $this->temporaryInvoiceStore->delete($token);
        Session::forget('last_guest_invoice_token');
        Session::forget('last_guest_invoice_number');
        Session::forget('last_guest_invoice_expires');

        return true;
    }
}
