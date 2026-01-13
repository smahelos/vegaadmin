<?php

namespace App\Examples\DTOMapping;

use App\Domain\Analytics\DTO\UserStatisticsDTO;
use App\Domain\Analytics\DTO\MonthlyStatDTO;
use App\Domain\Party\DTO\ClientDTO;
use App\Domain\Shared\Money\ValueObjects\Money;

/**
 * Praktické ukázky mapování single DTO objektů v projektu
 * Založeno na real code patterns z Dashboard a Analytics
 */
class DTOMappingExamples
{
    /**
     * 1. SINGLE DTO OBJECT MAPPING
     * Ukázka jak mapovat jeden DTO objekt (ne kolekci)
     */
    public function singleDtoMapping(): void
    {
        // ✅ Způsob 1: Použití factory metody fromArray (nejběžnější)
        $rawData = [
            'invoice_count' => 15,
            'client_count' => 8,
            'suppliers_count' => 3,
            'total_amount' => 45678.90
        ];
        
        $statsDto = UserStatisticsDTO::fromArray($rawData, 'CZK');
        
        // ✅ Způsob 2: Direct transformation pomocí toArray()
        $arrayForJson = $statsDto->toArray();
        
        // ✅ Způsob 3: Conditional mapping - mapuj jen za určitých podmínek
        $formattedStats = $this->mapDtoWhen($statsDto, fn($dto) => $dto->invoiceCount > 10);
        
        // ✅ Způsob 4: Transform with additional data
        $enrichedData = $this->enrichSingleDto($statsDto, ['user_tier' => 'premium']);
    }

    /**
     * 2. DTO vs ARRAY - kdy použít co
     */
    public function dtoVsArrayComparison(): void
    {
        // ❌ ŠPATNĚ - konverze na array jen kvůli mapování
        $dto = UserStatisticsDTO::fromArray(['invoice_count' => 5, 'client_count' => 2, 'suppliers_count' => 1, 'total_amount' => 1000]);
        $array = $dto->toArray(); // zbytečná konverze
        $result = array_map('strtoupper', $array); // pak map na array
        
        // ✅ LÉPE - pracuj přímo s DTO objektem
        $transformedDto = $this->transformSingleDto($dto);
    }

    /**
     * 3. FUNCTIONAL APPROACH pro single DTO
     * Používání vyšších funkcí i na single objekty
     */
    public function functionalMapping(): void
    {
        $clientDto = ClientDTO::fromArray([
            'id' => 1,
            'name' => 'ACME Corp',
            'email' => 'contact@acme.com',
            'phone' => '+420123456789',
            'street' => 'Main St 123',
            'city' => 'Prague',
            'zip' => '10000',
            'country' => 'CZ',
            'ico' => '12345678',
            'dic' => 'CZ12345678',
            'shortcut' => null,
            'description' => null,
            'created_at' => '2024-01-15 10:30:00',
            'user_id' => 1,
            'is_default' => true,
        ]);

        // ✅ Pipe pattern - řetězení transformací
        $result = $this->pipe(
            $clientDto,
            fn($dto) => $this->addComputedFields($dto),
            fn($data) => $this->formatForApi($data),
            fn($data) => $this->addMetadata($data)
        );

        // ✅ Optional mapping - mapuj jen když existuje
        $optionalResult = $this->optional($clientDto, fn($dto) => $this->transformForInvoice($dto));
    }

    /**
     * 4. BUILDER PATTERN pro komplexní single DTO
     */
    public function builderPatternExample(): void
    {
        // Postupné sestavení komplexního DTO objektu
        $complexDto = DTOBuilder::create()
            ->withUserStats(15, 8, 3, Money::fromString('45678.90', 'CZK'))
            ->withTimeframe(new \DateTimeImmutable('2024-01-01'), new \DateTimeImmutable())
            ->withMetadata(['source' => 'dashboard', 'version' => '2.0'])
            ->build();
            
        // Pak můžeme mapovat tento complex DTO
        $mapped = $this->mapComplexDto($complexDto);
    }

    /**
     * 5. PRESENTER PATTERN (z vašeho kódu)
     * Nejelegantnější způsob pro UI transformace
     */
    public function presenterPatternExample(): void
    {
        $monthlyDto = new MonthlyStatDTO('2024-10', Money::fromString('12345.67', 'CZK'));
        
        // ✅ Presenter pattern pro UI formatting
        $presenter = new class {
            public function present(MonthlyStatDTO $dto): array {
                return [
                    'month_formatted' => $this->formatMonth($dto->month),
                    'total_formatted' => $dto->total->__toString(),
                    'total_raw' => $dto->total->toFloat(),
                    'period' => $this->getPeriodInfo($dto->month),
                ];
            }
            
            private function formatMonth(string $month): string {
                return \DateTime::createFromFormat('Y-m', $month)->format('F Y');
            }
            
            private function getPeriodInfo(string $month): array {
                $date = \DateTime::createFromFormat('Y-m', $month);
                return [
                    'year' => $date->format('Y'),
                    'month_num' => $date->format('n'),
                    'month_name' => $date->format('F'),
                    'quarter' => ceil($date->format('n') / 3),
                ];
            }
        };
        
        $presentedData = $presenter->present($monthlyDto);
    }

    // ================== HELPER METHODS ==================

    /**
     * Conditional mapping - mapuj DTO jen když splňuje podmínku
     */
    private function mapDtoWhen(UserStatisticsDTO $dto, callable $condition): ?array
    {
        if ($condition($dto)) {
            return [
                'invoice_count' => $dto->invoiceCount,
                'client_count' => $dto->clientCount,
                'total_formatted' => $dto->totalAmount->__toString(),
                'average_per_invoice' => $dto->invoiceCount > 0 
                    ? $dto->totalAmount->divide($dto->invoiceCount)->__toString()
                    : '0',
            ];
        }
        return null;
    }

    /**
     * Enrichment - přidání dodatečných dat k DTO
     */
    private function enrichSingleDto(UserStatisticsDTO $dto, array $additional): array
    {
        return array_merge($dto->toArray(), $additional, [
            'computed_average' => $dto->invoiceCount > 0 
                ? $dto->totalAmount->toFloat() / $dto->invoiceCount 
                : 0,
            'performance_tier' => $this->calculateTier($dto->totalAmount->toFloat()),
        ]);
    }

    /**
     * Direct DTO transformation
     */
    private function transformSingleDto(UserStatisticsDTO $dto): array
    {
        return [
            'summary' => [
                'invoices' => $dto->invoiceCount,
                'clients' => $dto->clientCount,
                'revenue' => $dto->totalAmount->__toString(),
            ],
            'metrics' => [
                'avg_invoice_value' => $dto->invoiceCount > 0 
                    ? $dto->totalAmount->divide($dto->invoiceCount)->__toString()
                    : Money::fromString('0', $dto->totalAmount->getCurrency())->__toString(),
                'client_engagement' => $dto->clientCount > 0 
                    ? round($dto->invoiceCount / $dto->clientCount, 2)
                    : 0,
            ],
        ];
    }

    /**
     * Pipe pattern pro řetězení transformací
     */
    private function pipe(ClientDTO $dto, callable ...$transformers): array
    {
        // Začneme s basic array reprezentací DTO
        $result = [
            'id' => $dto->id,
            'name' => $dto->name,
            'email' => $dto->email,
            'phone' => $dto->phone,
            'street' => $dto->street,
            'city' => $dto->city,
            'zip' => $dto->zip,
            'country' => $dto->country,
            'ico' => $dto->ico,
            'dic' => $dto->dic,
            'shortcut' => $dto->shortcut,
            'description' => $dto->description,
            'created_at' => $dto->created_at,
            'user_id' => $dto->user_id,
            'is_default' => $dto->is_default,
        ];
        foreach ($transformers as $transformer) {
            $result = $transformer($result);
        }
        return $result;
    }

    /**
     * Optional mapping - vrátí null pokud DTO neexistuje
     */
    private function optional(?ClientDTO $dto, callable $transformer): ?array
    {
        return $dto ? $transformer($dto) : null;
    }

    // Pomocné transformace pro pipe pattern
    private function addComputedFields(ClientDTO $dto): array
    {
        $base = [
            'id' => $dto->id,
            'name' => $dto->name,
            'email' => $dto->email,
            'phone' => $dto->phone,
            'street' => $dto->street,
            'city' => $dto->city,
            'zip' => $dto->zip,
            'country' => $dto->country,
            'ico' => $dto->ico,
            'dic' => $dto->dic,
            'shortcut' => $dto->shortcut,
            'description' => $dto->description,
            'created_at' => $dto->created_at,
            'user_id' => $dto->user_id,
            'is_default' => $dto->is_default,
        ];
        return array_merge($base, [
            'full_address' => trim("{$dto->street}, {$dto->city} {$dto->zip}"),
            'country_name' => $this->getCountryName($dto->country),
            'has_tax_info' => !empty($dto->ico) && !empty($dto->dic),
        ]);
    }

    private function formatForApi(array $data): array
    {
        return [
            'client_id' => $data['id'],
            'company_name' => $data['name'],
            'contact_email' => $data['email'],
            'full_address' => $data['full_address'] ?? '',
            'tax_numbers' => [
                'ico' => $data['ico'],
                'dic' => $data['dic'],
            ],
            'is_primary' => $data['is_default'],
        ];
    }

    private function addMetadata(array $data): array
    {
        return [
            'data' => $data,
            'meta' => [
                'generated_at' => now()->toISOString(),
                'version' => '1.0',
                'type' => 'client_profile',
            ],
        ];
    }

    private function transformForInvoice(ClientDTO $dto): array
    {
        return [
            'billing_name' => $dto->name,
            'billing_address' => [
                'street' => $dto->street,
                'city' => $dto->city,
                'zip' => $dto->zip,
                'country' => $dto->country,
            ],
            'tax_info' => [
                'ico' => $dto->ico,
                'dic' => $dto->dic,
            ],
        ];
    }

    private function mapComplexDto($dto): array
    {
        // Implementace pro complex DTO mapping
        return ['complex' => 'mapping'];
    }

    private function calculateTier(float $amount): string
    {
        return match (true) {
            $amount >= 100000 => 'premium',
            $amount >= 50000 => 'standard',
            default => 'basic'
        };
    }

    private function getCountryName(?string $code): string
    {
        $countries = ['CZ' => 'Czech Republic', 'SK' => 'Slovakia'];
        return $countries[$code] ?? $code ?? '';
    }
}

/**
 * Ukázkový Builder pro complex DTO
 */
class DTOBuilder
{
    private array $data = [];

    public static function create(): self
    {
        return new self();
    }

    public function withUserStats(int $invoices, int $clients, int $suppliers, Money $total): self
    {
        $this->data['stats'] = compact('invoices', 'clients', 'suppliers', 'total');
        return $this;
    }

    public function withTimeframe(\DateTimeImmutable $from, \DateTimeImmutable $to): self
    {
        $this->data['timeframe'] = compact('from', 'to');
        return $this;
    }

    public function withMetadata(array $metadata): self
    {
        $this->data['metadata'] = $metadata;
        return $this;
    }

    public function build(): array
    {
        return $this->data;
    }
}
