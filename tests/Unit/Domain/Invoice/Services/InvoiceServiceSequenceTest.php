<?php

namespace Tests\Unit\Domain\Invoice\Services;

use App\Domain\Invoice\Services\InvoiceService;
use App\Domain\Invoice\DTO\InvoiceDTO;
use App\Domain\Invoice\DTO\InvoiceWriteData;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Product\Contracts\InvoiceProductDtoWriteRepositoryInterface;
use App\Domain\Invoice\Contracts\InvoiceDtoWriteRepositoryInterface;
use App\Domain\Invoice\Contracts\InvoiceDtoReadRepositoryInterface;
use App\Domain\Shared\Events\Contracts\EventPublisherInterface;
use App\Domain\Shared\Events\Contracts\DomainEvent;
use App\Domain\Shared\Status\DTO\StatusDTO;
use App\Domain\Party\DTO\ClientDTO;
use App\Domain\Party\DTO\SupplierDTO;
use App\Domain\User\ValueObjects\UserId;
use App\Domain\Shared\Status\ValueObjects\StatusId;
use App\Domain\Shared\Money\ValueObjects\Money;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoiceServiceSequenceTest extends TestCase
{
    private InvoiceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        // No-op event publisher
        $publisher = new class implements EventPublisherInterface {
            public array $published = [];
            public function publish(DomainEvent $event): void { $this->published[] = $event; }
        };
        // DTO-based repositories stubs
        $invoiceWriteRepo = new class implements InvoiceDtoWriteRepositoryInterface {
            public function create(InvoiceWriteData $writeData): InvoiceDTO {
                return InvoiceDTO::fromArray([
                    'id' => 1,
                    'user_id' => $writeData->user_id ?? 1,
                    'client_id' => $writeData->client_id,
                    'supplier_id' => $writeData->supplier_id,
                    'number' => $writeData->number,
                    'payment_reference' => $writeData->payment_reference,
                    'constant_code' => $writeData->constant_code,
                    'issue_date' => $writeData->issue_date,
                    'tax_point_date' => $writeData->tax_point_date,
                    'due_in' => $writeData->due_in,
                    'subtotal' => $writeData->subtotal ?? Money::fromString('0.00', 'CZK'),
                    'total_tax' => $writeData->total_tax ?? Money::fromString('0.00', 'CZK'),
                    'total_amount' => $writeData->total_amount ?? Money::fromString('0.00', 'CZK'),
                    'payment_method_id' => $writeData->payment_method_id,
                    'invoice_text' => $writeData->invoice_text ?? null,
                    'currency' => $writeData->currency ?? 'CZK',
                    'template' => $writeData->template,
                    'items' => $writeData->items,
                ]);
            }
            public function update(InvoiceId $id, InvoiceWriteData $writeData): InvoiceDTO {
                return InvoiceDTO::fromArray([
                    'id' => $id,
                    'user_id' => $writeData->user_id ?? 1,
                    'client_id' => $writeData->client_id,
                    'supplier_id' => $writeData->supplier_id,
                    'number' => $writeData->number,
                    'payment_reference' => $writeData->payment_reference,
                    'constant_code' => $writeData->constant_code,
                    'issue_date' => $writeData->issue_date,
                    'tax_point_date' => $writeData->tax_point_date,
                    'due_in' => $writeData->due_in,
                    'subtotal' => $writeData->subtotal ?? Money::fromString('0.00', 'CZK'),
                    'total_tax' => $writeData->total_tax ?? Money::fromString('0.00', 'CZK'),
                    'total_amount' => $writeData->total_amount ?? Money::fromString('0.00', 'CZK'),
                    'payment_method_id' => $writeData->payment_method_id,
                    'invoice_text' => $writeData->invoice_text ?? null,
                    'currency' => $writeData->currency ?? 'CZK',
                    'template' => $writeData->template,
                    'items' => $writeData->items,
                ]);
            }
            public function delete(InvoiceId $id): void {}
            public function markAsPaid(InvoiceId $id, int $paidStatusId): bool { return true; }
            public function setTemplate(InvoiceId $id, string $template): bool { return true; }
            public function changeStatus(InvoiceId $id, int $statusId): bool { return true; }
        };
        $invoiceReadRepo = new class implements InvoiceDtoReadRepositoryInterface {
            public function findByIdAny(InvoiceId $id): ?InvoiceDTO { return null; }
            public function findByIdForUser(InvoiceId $id, UserId $userId): ?InvoiceDTO { return null; }
            public function allForAdmin(): array { return []; }
            public function allForUser(UserId $userId): array { return []; }
            public function getInvoicesForDropdown(UserId $userId): array { return []; }
            public function listWithInvoiceText(): array { return []; }
            public function findUnpaidForUser(UserId $userId): array { return []; }
            public function findOverdueForUser(UserId $userId): array { return []; }
            public function getUserInvoiceCount(UserId $userId): int { return 0; }
            public function findLastInvoiceNumber(UserId $userId, int $year): ?string { return null; }
            public function existsWithNumber(UserId $userId, string $number): bool { return false; }
        };
        $productRepo = new class implements InvoiceProductDtoWriteRepositoryInterface {
            public function create(array $data): \App\Domain\Invoice\DTO\InvoiceProductDTO { throw new \RuntimeException('not needed'); }
            public function allForInvoice(InvoiceId $invoiceId): array { return []; }
            public function bulkCreate(InvoiceId $invoiceId, array $products): void {}
            public function deleteByInvoiceId(InvoiceId $invoiceId): void {}
            public function listForInvoiceWithProduct(InvoiceId $invoiceId): array { return []; }
        };
        $statusRepo = new class implements \App\Domain\Shared\Status\Contracts\StatusDtoRepositoryInterface {
            public function findIdBySlug(string $slug): ?int { return $slug === 'paid' ? 99 : null; }
            public function findById(StatusId $id): ?StatusDTO { return null; }
            public function getAllForDropdown(): array { return []; }
            public function findBySlug(string $slug): ?StatusDTO { return null; }
            public function getStatusIdSlugMap(): array { return []; }
            public function getStatusSlugIdMap(): array { return []; }
        };
        $partyService = new class implements \App\Domain\Party\Contracts\InvoicePartyServiceInterface {
            public bool $throwModelNotFound = false;
            public bool $throwGenericException = false;
            public int $returnClientUserId = 1;
            public int $returnSupplierUserId = 1;
            public function clientOptions(int $userId): array { return []; }
            public function supplierOptions(int $userId): array { return []; }
            public function defaultClient(int $userId): ?ClientDTO { return null; }
            public function defaultSupplier(int $userId): ?SupplierDTO { return null; }
            public function resolveOrCreateClient(UserId $userId, array $data): ClientDTO {
                return new ClientDTO(
                    id: 1,
                    name: $data['name'] ?? 'Default Client',
                    email: $data['email'] ?? 'client@example.com',
                    phone: $data['phone'] ?? null,
                    street: $data['street'] ?? null,
                    city: $data['city'] ?? null,
                    zip: $data['zip'] ?? null,
                    country: $data['country'] ?? null,
                    ico: $data['ico'] ?? null,
                    dic: $data['dic'] ?? null,
                    shortcut: $data['shortcut'] ?? null,
                    description: $data['description'] ?? null,
                    created_at: $data['created_at'] ?? null,
                    user_id: $userId->toInt(),
                    is_default: false,
                    invoices: [],
                );
            }
            public function resolveOrCreateSupplier(UserId $userId, array $data): SupplierDTO {
                return new SupplierDTO(
                    id: 1,
                    name: $data['name'] ?? 'Default Supplier',
                    email: $data['email'] ?? 'supplier@example.com',
                    phone: $data['phone'] ?? null,
                    street: $data['street'] ?? null,
                    city: $data['city'] ?? null,
                    zip: $data['zip'] ?? null,
                    country: $data['country'] ?? null,
                    ico: $data['ico'] ?? null,
                    dic: $data['dic'] ?? null,
                    shortcut: $data['shortcut'] ?? null,
                    description: $data['description'] ?? null,
                    created_at: $data['created_at'] ?? null,
                    supplier_logo: $data['supplier_logo'] ?? null,
                    account_number: $data['account_number'] ?? null,
                    bank_code: $data['bank_code'] ?? null,
                    bank_name: $data['bank_name'] ?? null,
                    iban: $data['iban'] ?? null,
                    swift: $data['swift'] ?? null,
                    has_payment_info: $data['has_payment_info'] ?? null,
                    user_id: $userId->toInt(),
                    is_default: false,
                    invoices: [],
                );
            }
            public function resolveOrCreateSupplierWithFlag(UserId $userId, array $data): array {
                return [
                    'supplier' => new SupplierDTO(
                        id: 1,
                        name: $data['name'] ?? 'Default Supplier',
                        email: $data['email'] ?? 'supplier@example.com',
                        phone: $data['phone'] ?? null,
                        street: $data['street'] ?? null,
                        city: $data['city'] ?? null,
                        zip: $data['zip'] ?? null,
                        country: $data['country'] ?? null,
                        ico: $data['ico'] ?? null,
                        dic: $data['dic'] ?? null,
                        shortcut: $data['shortcut'] ?? null,
                        description: $data['description'] ?? null,
                        created_at: $data['created_at'] ?? null,
                        supplier_logo: $data['supplier_logo'] ?? null,
                        account_number: $data['account_number'] ?? null,
                        bank_code: $data['bank_code'] ?? null,
                        bank_name: $data['bank_name'] ?? null,
                        iban: $data['iban'] ?? null,
                        swift: $data['swift'] ?? null,
                        has_payment_info: $data['has_payment_info'] ?? null,
                        user_id: $userId->toInt(),
                        is_default: false,
                        invoices: [],
                    ), 
                    'created' => true
                ];
            }
            public function resolveOrCreateClientWithFlag(UserId $userId, array $data): array {
                return [
                    'client' => new ClientDTO(
                        id: 1,
                        name: $data['name'] ?? 'Default Client',
                        email: $data['email'] ?? 'client@example.com',
                        phone: $data['phone'] ?? null,
                        street: $data['street'] ?? null,
                        city: $data['city'] ?? null,
                        zip: $data['zip'] ?? null,
                        country: $data['country'] ?? null,
                        ico: $data['ico'] ?? null,
                        dic: $data['dic'] ?? null,
                        shortcut: $data['shortcut'] ?? null,
                        description: $data['description'] ?? null,
                        created_at: $data['created_at'] ?? null,
                        user_id: $userId->toInt(),
                        is_default: false,
                        invoices: [],
                    ), 
                    'created' => true
                ];
            }
            public function findClient(int $userId, int $id): ClientDTO {
                if ($this->throwModelNotFound) {
                    throw new ModelNotFoundException();
                }
                if ($this->throwGenericException) {
                    throw new \Exception('Failure');
                }
                $client = new ClientDTO(
                    id: $id,
                    name: $data['name'] ?? 'Client To Find',
                    email: $data['email'] ?? 'clienttofind@example.com',
                    phone: $data['phone'] ?? null,
                    street: $data['street'] ?? null,
                    city: $data['city'] ?? null,
                    zip: $data['zip'] ?? null,
                    country: $data['country'] ?? null,
                    ico: $data['ico'] ?? null,
                    dic: $data['dic'] ?? null,
                    shortcut: $data['shortcut'] ?? null,
                    description: $data['description'] ?? null,
                    created_at: $data['created_at'] ?? null,
                    user_id: $userId,
                    is_default: false,
                    invoices: [],
                );
                return $client;
            }
            public function findSupplier(int $userId, int $id): SupplierDTO {
                return new SupplierDTO(
                    id: $id,
                    name: $data['name'] ?? 'Default Supplier',
                    email: $data['email'] ?? 'supplier@example.com',
                    phone: $data['phone'] ?? null,
                    street: $data['street'] ?? null,
                    city: $data['city'] ?? null,
                    zip: $data['zip'] ?? null,
                    country: $data['country'] ?? null,
                    ico: $data['ico'] ?? null,
                    dic: $data['dic'] ?? null,
                    shortcut: $data['shortcut'] ?? null,
                    description: $data['description'] ?? null,
                    created_at: $data['created_at'] ?? null,
                    supplier_logo: $data['supplier_logo'] ?? null,
                    account_number: $data['account_number'] ?? null,
                    bank_code: $data['bank_code'] ?? null,
                    bank_name: $data['bank_name'] ?? null,
                    iban: $data['iban'] ?? null,
                    swift: $data['swift'] ?? null,
                    has_payment_info: $data['has_payment_info'] ?? null,
                    user_id: $userId,
                    is_default: false,
                    invoices: [],
                );
            }
            public function updateClient(int $clientId, array $data): ClientDTO {
                $data = [
                    'id' => $clientId,
                    'name' => $data['name'] ?? 'Default Client',
                    'email' => $data['email'] ?? 'client@example.com',
                    'phone' => $data['phone'] ?? null,
                    'street' => $data['street'] ?? null,
                    'city' => $data['city'] ?? null,
                    'zip' => $data['zip'] ?? null,
                    'country' => $data['country'] ?? null,
                    'ico' => $data['ico'] ?? null,
                    'dic' => $data['dic'] ?? null,
                    'shortcut' => $data['shortcut'] ?? null,
                    'description' => $data['description'] ?? null,
                    'created_at' => $data['created_at'] ?? null,
                    'user_id' => $this->returnClientUserId,
                    'is_default' => false,
                    'invoices' => [],
                ];
                $dataDTO = ClientDTO::fromArray($data);

                return $dataDTO;
            }
            public function updateSupplier(int $supplierId, array $data): SupplierDTO {
                $data = [
                    'id' => $supplierId,
                    'name' => $data['name'] ?? 'Default Supplier',
                    'email' => $data['email'] ?? 'supplier@example.com',
                    'phone' => $data['phone'] ?? null,
                    'street' => $data['street'] ?? null,
                    'city' => $data['city'] ?? null,
                    'zip' => $data['zip'] ?? null,
                    'country' => $data['country'] ?? null,
                    'ico' => $data['ico'] ?? null,
                    'dic' => $data['dic'] ?? null,
                    'shortcut' => $data['shortcut'] ?? null,
                    'description' => $data['description'] ?? null,
                    'created_at' => $data['created_at'] ?? null,
                    'supplier_logo' => $data['supplier_logo'] ?? null,
                    'account_number' => $data['account_number'] ?? null,
                    'bank_code' => $data['bank_code'] ?? null,
                    'bank_name' => $data['bank_name'] ?? null,
                    'iban' => $data['iban'] ?? null,
                    'swift' => $data['swift'] ?? null,
                    'has_payment_info' => $data['has_payment_info'] ?? null,
                    'user_id' => $this->returnSupplierUserId,
                    'is_default' => false,
                    'invoices' => [],
                ];
                $dataDTO = SupplierDTO::fromArray($data);

                return $dataDTO;
            }
            public function setSupplierDefault(int $userId, int $supplierId): void {}
            public function setClientDefault(int $userId, int $clientId): void {}
            public function deleteSupplier(int $userId, int $supplierId): bool { return true; }
            public function deleteClient(int $userId, int $clientId): bool { return true; }
            public function listSuppliers(int $userId): array { return []; }
            public function listClients(int $userId): array { return []; }
            public function defaultSupplierOrFirst(int $userId): ?SupplierDTO { return null; }
            public function defaultClientOrFirst(int $userId): ?ClientDTO { return null; }
        };
        $this->service = new InvoiceService($publisher, $invoiceWriteRepo, $invoiceReadRepo, $productRepo, $statusRepo, $partyService);
    }

    #[Test]
    public function service_can_be_instantiated(): void
    {
        $this->assertInstanceOf(InvoiceService::class, $this->service);
    }
}
