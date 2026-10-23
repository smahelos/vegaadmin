<?php

declare(strict_types=1);

namespace App\Domain\Invoice\Services;

use App\Domain\Invoice\Contracts\InvoiceServiceInterface;
use App\Domain\Invoice\DTO\InvoiceDTO;
use App\Domain\Invoice\DTO\InvoiceProductWriteData;
use App\Domain\Invoice\DTO\InvoiceWriteData;
use App\Domain\Invoice\Events\InvoiceCreated;
use App\Domain\Invoice\Exceptions\InvoiceNotFoundException;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Invoice\ValueObjects\InvoiceNumber;
use App\Domain\Invoice\ValueObjects\InvoiceTotals;
use App\Domain\Party\Contracts\InvoicePartyServiceInterface;
use App\Domain\Shared\Events\Contracts\EventPublisherInterface;
use App\Domain\Shared\Money\Enums\Currency;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Domain\Shared\Status\Contracts\StatusDtoRepositoryInterface;
use App\Domain\User\ValueObjects\UserId;

final class InvoiceService implements InvoiceServiceInterface
{
    public function __construct(
    private readonly EventPublisherInterface $events,
        private readonly \App\Domain\Invoice\Contracts\InvoiceDtoWriteRepositoryInterface $invoiceWriteRepository,
        private readonly \App\Domain\Invoice\Contracts\InvoiceDtoReadRepositoryInterface $invoiceReadRepository,
        private readonly \App\Domain\Product\Contracts\InvoiceProductDtoWriteRepositoryInterface $invoiceProductRepository,
        private readonly StatusDtoRepositoryInterface $statusRepository,
        // Domain service responsible for resolving/creating parties
        private readonly InvoicePartyServiceInterface $partyService,
    ) {}

    /**
     * @param list<InvoiceProductWriteData> $products
     */
    public function saveInvoiceProducts(InvoiceId $invoiceId, array $products): void
    {
        $this->invoiceProductRepository->bulkCreate($invoiceId, $products);
    }

    /**
     * Mark invoice as paid
     *
     * @param InvoiceId $id Invoice ID
     * @return bool
     */
    public function markInvoiceAsPaid(InvoiceId $id): bool
    {
        // Security (ownership) enforced at application layer; domain performs pure update.
        $paidStatusId = $this->statusRepository->findIdBySlug('paid');
        if (!$paidStatusId) { return false; }
        return $this->invoiceWriteRepository->markAsPaid($id, $paidStatusId);
    }

    /**
     * Set invoice pdf template
     *
     * @param InvoiceId $id Invoice ID
     * @param string $template Template name
     * @return bool
     */
    public function setInvoiceTemplate(InvoiceId $id, string $template): bool
    {
        // Security (ownership) enforced before calling this domain service.
        return $this->invoiceWriteRepository->setTemplate($id, $template);
    }

    /**
     * Ensure object has all required properties (with default values)
     *
     * @param \stdClass $object
     * @param array $properties
     * @return void
     */
    public function ensureObjectProperties(\stdClass $object, array $properties): void
    {
        foreach ($properties as $property) {
            if (!property_exists($object, $property)) {
                if ($property === 'due_in') {
                    $object->$property = 14;
                } elseif ($property === 'payment_method_id' || $property === 'payment_status_id') {
                    $object->$property = 1;
                } elseif ($property === 'payment_amount') {
                    $object->$property = 0;
                } else {
                    $object->$property = '';
                }
            } else if ($property === 'due_in' || $property === 'payment_method_id' || $property === 'payment_status_id') {
                $object->$property = (int)$object->$property;
            } else if ($property === 'payment_amount') {
                $object->$property = (float)$object->$property;
            }
        }
    }

    /**
     * Create invoice including on-the-fly client/supplier resolution and product persistence.
     * Security (ownership) is enforced by passing explicit $userId and scoping persistence there.
     */
    public function createInvoice(UserId $userId, array $data, array $invoiceProducts): InvoiceDTO
    {
        // Resolve or create client if needed
        if (empty($data['client_id']) && !empty($data['client_name']) && strlen((string)$data['client_name']) >= 3) {
            $client = $this->partyService->resolveOrCreateClient($userId, [
                'name' => $data['client_name'],
                'street' => $data['client_street'] ?? '',
                'city' => $data['client_city'] ?? '',
                'zip' => $data['client_zip'] ?? '',
                'country' => $data['client_country'] ?? 'CZ',
                'ico' => $data['client_ico'] ?? '',
                'dic' => $data['client_dic'] ?? '',
                'email' => $data['client_email'] ?? '',
                'phone' => $data['client_phone'] ?? '',
            ]);
            $data['client_id'] = $client->id;
        }

        // Resolve or create supplier if needed
        if (empty($data['supplier_id']) && !empty($data['name']) && strlen((string)$data['name']) >= 3) {
            $supplier = $this->partyService->resolveOrCreateSupplier($userId, [
                'name' => $data['name'],
                'street' => $data['street'] ?? '',
                'city' => $data['city'] ?? '',
                'zip' => $data['zip'] ?? '',
                'country' => $data['country'] ?? 'CZ',
                'ico' => $data['ico'] ?? '',
                'dic' => $data['dic'] ?? '',
                'email' => $data['email'] ?? '',
                'phone' => $data['phone'] ?? '',
                'account_number' => $data['account_number'] ?? '',
                'bank_code' => $data['bank_code'] ?? '',
                'bank_name' => $data['bank_name'] ?? '',
                'iban' => $data['iban'] ?? '',
                'swift' => $data['swift'] ?? '',
                'supplier_logo' => $data['supplier_logo'] ?? '',
            ]);
            $data['supplier_id'] = $supplier->id;
        }

        $data['user_id'] = $userId->toInt();
        $data['total_amount'] = $this->totalOf($data, $invoiceProducts);
        $invoice = $this->invoiceWriteRepository->create(InvoiceWriteData::fromArray($data));

        $this->saveInvoiceProducts($invoice->id, $invoiceProducts);

        // Publish Domain Event for new invoice creation via unified publisher
        $this->events->publish(new InvoiceCreated($invoice, $userId->toInt()));

        return $invoice;
    }

    /**
     * Update invoice including on-the-fly party resolution and product replacement.
     */
    public function updateInvoice(UserId $userId, InvoiceId $invoiceId, array $data, array $invoiceProducts): InvoiceDTO
    {
        $invoice = $this->invoiceReadRepository->findByIdForUser($invoiceId, $userId);
        if (!$invoice) {
            throw new InvoiceNotFoundException('Invoice not accessible for user');
        }

        if (empty($data['client_id']) && !empty($data['client_name']) && strlen((string)$data['client_name']) >= 3) {
            $client = $this->partyService->resolveOrCreateClient($userId, [
                'name' => $data['client_name'],
                'street' => $data['client_street'] ?? '',
                'city' => $data['client_city'] ?? '',
                'zip' => $data['client_zip'] ?? '',
                'country' => $data['client_country'] ?? 'CZ',
                'ico' => $data['client_ico'] ?? '',
                'dic' => $data['client_dic'] ?? '',
                'email' => $data['client_email'] ?? '',
                'phone' => $data['client_phone'] ?? '',
                'description' => $data['client_description'] ?? '',
            ]);
            $data['client_id'] = $client->id;
        }

        if (empty($data['supplier_id']) && !empty($data['name']) && strlen((string)$data['name']) >= 3) {
            $supplier = $this->partyService->resolveOrCreateSupplier($userId, [
                'name' => $data['name'],
                'street' => $data['street'] ?? '',
                'city' => $data['city'] ?? '',
                'zip' => $data['zip'] ?? '',
                'country' => $data['country'] ?? 'CZ',
                'ico' => $data['ico'] ?? '',
                'dic' => $data['dic'] ?? '',
                'email' => $data['email'] ?? '',
                'phone' => $data['phone'] ?? '',
                'description' => $data['description'] ?? '',
                'account_number' => $data['account_number'] ?? '',
                'bank_code' => $data['bank_code'] ?? '',
                'bank_name' => $data['bank_name'] ?? '',
                'iban' => $data['iban'] ?? '',
                'swift' => $data['swift'] ?? '',
                'supplier_logo' => $data['supplier_logo'] ?? '',
            ]);
            $data['supplier_id'] = $supplier->id;
        }
        $data['user_id'] = $userId->toInt();
        $data['total_amount'] = $this->totalOf($data, $invoiceProducts);
        $invoice = $this->invoiceWriteRepository->update($invoice->id, InvoiceWriteData::fromArray($data));

        $this->invoiceProductRepository->deleteByInvoiceId($invoiceId);
        $this->saveInvoiceProducts($invoiceId, $invoiceProducts);

        return $invoice;
    }

    /**
     * Delete an invoice owned by a user
     *
     * @param UserId $userId Owner user id
     * @param InvoiceId $invoiceId Invoice id
     */
    public function deleteInvoice(UserId $userId, InvoiceId $invoiceId): void
    {
        $invoice = $this->invoiceReadRepository->findByIdForUser($invoiceId, $userId);
        if (!$invoice) {
            throw new InvoiceNotFoundException('Invoice not accessible for user');
        }
        $this->invoiceWriteRepository->delete($invoiceId);
    }

    /**
     * Generate next available invoice number for a given user id.
     *
     * @param UserId $userId
     * @param int $year Current year for the invoice number
     * @return string Generated invoice number (see InvoiceNumber)
     */
    public function generateInvoiceNumber(UserId $userId, int $year): string
    {
        $lastInvoiceNumber = $this->invoiceReadRepository->findLastInvoiceNumber($userId, (int) date('Y'));
        return InvoiceNumber::generateNext($lastInvoiceNumber)->getNumber();
    }

    /**
     * Change invoice status
     * @param UserId $userId Owner user id
     * @param InvoiceId $invoiceId Invoice id
     * @param int $statusId New status id
     */
    public function changeInvoiceStatus(UserId $userId, InvoiceId $invoiceId, int $statusId): bool
    {
        $invoice = $this->invoiceReadRepository->findByIdForUser($invoiceId, $userId);
        if (!$invoice) {
            throw new InvoiceNotFoundException('Invoice not accessible for user');
        }
        return $this->invoiceWriteRepository->changeStatus($invoiceId, $statusId);
    }

    /**
     * The invoice amount is always the sum of its lines; an amount typed into the form is ignored.
     *
     * @param array<string, mixed> $data
     * @param list<InvoiceProductWriteData> $products
     */
    private function totalOf(array $data, array $products): Money
    {
        return InvoiceTotals::fromProducts(Currency::from((string) $data['payment_currency']), $products)->total();
    }
}
