<?php

namespace Tests\Unit\Domain\Analytics\DTO;

use App\Domain\Analytics\DTO\ClientTotalDTO;
use App\Models\Client;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Domain\Shared\Money\Contracts\MoneyFormatterInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ClientTotalDTOTest extends TestCase
{
    #[Test]
    public function from_client_uses_loaded_invoice_aggregate(): void
    {
        // Create a lightweight client model instance (no DB needed for structure mapping)
        $client = new Client();
        $client->client_id = 10;
        $client->client_name = 'Test Client';
        // Simulate loaded invoices relation with aggregate total
        $aggregate = new Client(); // reuse model to attach dynamic total attribute
        $aggregate->total = 99.999; // will normalize to 100.00
        $client->setRelation('invoices', new EloquentCollection([$aggregate]));
        $client->total = $client->invoices->sum('total');

        $dto = ClientTotalDTO::fromArray($client->toArray(), 'CZK');
        $this->assertSame(10, $dto->clientId);
        $this->assertSame('Test Client', $dto->clientName);
        $this->assertInstanceOf(Money::class, $dto->totalAmount);
        $this->assertSame(100.00, $dto->totalAmount->toFloat());
    }

    #[Test]
    public function to_array_exports_expected_shape(): void
    {
        $client = new Client();
        $client->client_id = 1;
        $client->client_name = 'X';
        $client->setRelation('invoices', new EloquentCollection());
        $client->total = $client->invoices->sum('total');
        $dto = ClientTotalDTO::fromArray($client->toArray(), 'CZK');
        $arr = $dto->toArray();
        $this->assertSame(['client_id','client_name','total_amount'], array_keys($arr));
        $this->assertSame(1, $arr['client_id']);
        $this->assertSame(0.0, $arr['total_amount']);
    }

    #[Test]
    public function to_formatted_array_uses_formatter(): void
    {
        $client = new Client();
        $client->client_id = 2;
        $client->client_name = 'Y';
        $client->setRelation('invoices', new EloquentCollection());
        $client->total = $client->invoices->sum('total');
        $dto = ClientTotalDTO::fromArray($client->toArray(), 'CZK');
        $fakeFormatter = new class implements MoneyFormatterInterface {
            public function format(\App\Domain\Shared\Money\ValueObjects\Money $money, ?string $locale = null, ?int $minFraction = null, ?int $maxFraction = null): string { return 'X'; }
            public function formatWithCode(\App\Domain\Shared\Money\ValueObjects\Money $money, ?string $locale = null, ?int $minFraction = null, ?int $maxFraction = null): string { return '0.00 CZK'; }
        };
        $formatted = $dto->toFormattedArray($fakeFormatter);
        $this->assertSame('Y', $formatted['client_name']);
        $this->assertSame(0.0, $formatted['total_amount']);
        $this->assertSame('0.00 CZK', $formatted['total_amount_formatted']);
    }
}
