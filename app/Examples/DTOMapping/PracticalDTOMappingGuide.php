<?php

namespace App\Examples\DTOMapping;

use App\Domain\Analytics\DTO\UserStatisticsDTO;
use App\Domain\Analytics\DTO\MonthlyStatDTO;
use App\Domain\Party\DTO\ClientDTO;
use App\Domain\Shared\Money\ValueObjects\Money;

/**
 * PRAKTICKÝ PRŮVODCE: DTO Mapping v Laravel 12
 * 
 * Demonstrace nejlepších praktik pro mapování single DTO objektů
 * založeno na reálných patterns z Invoice aplikace
 */
class PracticalDTOMappingGuide
{
    /**
     * === 1. PROBLÉM: Kdy mapovat DTO vs. kdy použít array ===
     */
    public function problemStatement(): void
    {
        // ❓ SITUACE: Máte DTO objekt a potřebujete ho transformovat pro view/API
        $userStats = UserStatisticsDTO::fromArray([
            'invoice_count' => 15,
            'client_count' => 8, 
            'suppliers_count' => 3,
            'total_amount' => 45678.90
        ], 'CZK');

        // ❌ ŠPATNÝ přístup - zbytečná konverze
        $array = $userStats->toArray();
        $result = array_map('strtoupper', $array); // pak pracujeme s array

        // ✅ SPRÁVNÝ přístup - transformujeme přímo DTO
        $result = $this->transformUserStatistics($userStats);
    }

    /**
     * === 2. ŘEŠENÍ: Optimální způsoby mapování single DTO ===
     */

    /**
     * Způsob A: Direct Transformation (nejjednodušší)
     */
    public function directTransformation(UserStatisticsDTO $dto): array
    {
        return [
            'dashboard_summary' => [
                'total_invoices' => $dto->invoiceCount,
                'total_clients' => $dto->clientCount,
                'revenue_display' => $dto->totalAmount->__toString(),
                'average_per_client' => $dto->clientCount > 0 
                    ? $dto->totalAmount->divide($dto->clientCount)->__toString()
                    : '0 CZK',
            ],
            'computed_metrics' => [
                'invoices_per_client' => $dto->clientCount > 0 
                    ? round($dto->invoiceCount / $dto->clientCount, 2) 
                    : 0,
                'business_tier' => $this->calculateBusinessTier($dto->totalAmount->toFloat()),
            ]
        ];
    }

    /**
     * Způsob B: Conditional Mapping (mapuj jen za určitých podmínek)
     */
    public function conditionalMapping(UserStatisticsDTO $dto): ?array
    {
        // Mapuj jen pokud má uživatel dostatečnou aktivitu
        if ($dto->invoiceCount < 5) {
            return null; // nebo basic version
        }

        return [
            'advanced_stats' => [
                'revenue' => $dto->totalAmount->__toString(),
                'avg_invoice_value' => $dto->totalAmount->divide($dto->invoiceCount)->__toString(),
                'client_diversity' => $this->calculateClientDiversity($dto),
            ],
            'recommendations' => $this->generateRecommendations($dto),
        ];
    }

    /**
     * Způsob C: Context-Aware Mapping (různé transformace pro různé kontexty)
     */
    public function contextualMapping(UserStatisticsDTO $dto, string $context): array
    {
        return match ($context) {
            'dashboard' => $this->mapForDashboard($dto),
            'api_v1' => $this->mapForApiV1($dto),
            'export' => $this->mapForExport($dto),
            'mobile' => $this->mapForMobile($dto),
            default => $dto->toArray()
        };
    }

    /**
     * === 3. POKROČILÉ PATTERNS ===
     */

    /**
     * Builder Pattern pro postupné sestavení výstupu
     */
    public function builderPattern(UserStatisticsDTO $dto): array
    {
        $builder = new ViewDataBuilder();
        
        return $builder
            ->withBasicStats($dto)
            ->withComputedMetrics($dto)
            ->withConditionalData($dto, fn($d) => $d->invoiceCount > 10)
            ->withFormatting('dashboard')
            ->build();
    }

    /**
     * Presenter Pattern (nejelegantnější pro UI)
     */
    public function presenterPattern(UserStatisticsDTO $dto): array
    {
        $presenter = new UserStatisticsPresenter();
        return $presenter->present($dto);
    }

    /**
     * === 4. REAL-WORLD EXAMPLES z Invoice App ===
     */

    /**
     * Příklad z DashboardApplicationService - mapování kolekcí DTOs
     */
    public function dashboardServiceExample(): array
    {
        // Simulace dat z domain service
        $userStats = UserStatisticsDTO::fromArray([
            'invoice_count' => 15,
            'client_count' => 8,
            'suppliers_count' => 3,
            'total_amount' => 45678.90
        ], 'CZK');

        $monthlyStats = collect([
            new MonthlyStatDTO('2024-09', Money::fromString('15000', 'CZK')),
            new MonthlyStatDTO('2024-10', Money::fromString('18000', 'CZK')),
        ]);

        // ✅ Pattern z vašeho kódu - kombinace raw DTOs + formatted data
        return [
            'statistics' => $userStats,
            'statistics_formatted' => $this->formatUserStats($userStats),
            'monthly_stats' => $monthlyStats,
            'monthly_stats_formatted' => $monthlyStats->map(fn($dto) => $this->formatMonthlyStats($dto)),
        ];
    }

    /**
     * Mapování komplexního DTO s nested objekty
     */
    public function complexDtoMapping(ClientDTO $clientDto): array
    {
        return [
            'client_profile' => [
                'basic_info' => $this->extractBasicInfo($clientDto),
                'contact_details' => $this->extractContactDetails($clientDto),
                'business_info' => $this->extractBusinessInfo($clientDto),
                'computed_fields' => $this->addComputedFields($clientDto),
            ],
            'display_options' => [
                'can_edit' => $this->canEdit($clientDto),
                'is_primary' => $clientDto->is_default,
                'status_badge' => $this->getStatusBadge($clientDto),
            ]
        ];
    }

    /**
     * === 5. PERFORMANCE OPTIMIZATIONS ===
     */

    /**
     * Lazy mapping - mapuj jen když je potřeba
     */
    public function lazyMapping(UserStatisticsDTO $dto, bool $includeHeavyCalculations = false): array
    {
        $basic = [
            'invoice_count' => $dto->invoiceCount,
            'client_count' => $dto->clientCount,
            'total_amount' => $dto->totalAmount->toFloat(),
        ];

        if ($includeHeavyCalculations) {
            $basic['heavy_metrics'] = $this->calculateExpensiveMetrics($dto);
        }

        return $basic;
    }

    /**
     * Caching mapped results
     */
    public function cachedMapping(UserStatisticsDTO $dto): array
    {
        $cacheKey = "user_stats_formatted_{$dto->invoiceCount}_{$dto->totalAmount->getAmount()}";
        
        return cache()->remember($cacheKey, 3600, function() use ($dto) {
            return $this->directTransformation($dto);
        });
    }

    // ================== HELPER METHODS ==================

    private function transformUserStatistics(UserStatisticsDTO $dto): array
    {
        return [
            'summary' => "Celkem {$dto->invoiceCount} faktur pro {$dto->clientCount} klientů",
            'total_revenue' => $dto->totalAmount->__toString(),
            'metrics' => [
                'avg_per_invoice' => $dto->invoiceCount > 0 
                    ? $dto->totalAmount->divide($dto->invoiceCount)->__toString()
                    : '0',
                'avg_per_client' => $dto->clientCount > 0 
                    ? $dto->totalAmount->divide($dto->clientCount)->__toString()
                    : '0',
            ]
        ];
    }

    private function mapForDashboard(UserStatisticsDTO $dto): array
    {
        return [
            'cards' => [
                'invoices' => ['count' => $dto->invoiceCount, 'icon' => 'invoice'],
                'clients' => ['count' => $dto->clientCount, 'icon' => 'users'],
                'revenue' => ['amount' => $dto->totalAmount->__toString(), 'icon' => 'money'],
            ],
            'charts_data' => $this->prepareChartData($dto),
        ];
    }

    private function mapForApiV1(UserStatisticsDTO $dto): array
    {
        return [
            'user_statistics' => [
                'invoices_total' => $dto->invoiceCount,
                'clients_total' => $dto->clientCount,
                'suppliers_total' => $dto->suppliersCount,
                'revenue_total' => $dto->totalAmount->toFloat(),
                'revenue_currency' => $dto->totalAmount->getCurrency(),
            ]
        ];
    }

    private function mapForExport(UserStatisticsDTO $dto): array
    {
        return [
            'Počet faktur' => $dto->invoiceCount,
            'Počet klientů' => $dto->clientCount,
            'Počet dodavatelů' => $dto->suppliersCount,
            'Celkový obrat' => $dto->totalAmount->__toString(),
            'Průměr na fakturu' => $dto->invoiceCount > 0 
                ? $dto->totalAmount->divide($dto->invoiceCount)->__toString()
                : '0',
            'Průměr na klienta' => $dto->clientCount > 0 
                ? $dto->totalAmount->divide($dto->clientCount)->__toString()
                : '0',
        ];
    }

    private function mapForMobile(UserStatisticsDTO $dto): array
    {
        return [
            'stats' => [
                'invoices' => $dto->invoiceCount,
                'revenue' => $dto->totalAmount->__toString(),
            ],
            'simplified' => true
        ];
    }

    private function formatUserStats(UserStatisticsDTO $dto): array
    {
        return [
            'invoice_count_formatted' => number_format($dto->invoiceCount) . ' faktur',
            'client_count_formatted' => number_format($dto->clientCount) . ' klientů',
            'total_amount_formatted' => $dto->totalAmount->__toString(),
            'avg_per_invoice' => $dto->invoiceCount > 0 
                ? $dto->totalAmount->divide($dto->invoiceCount)->__toString()
                : '0 ' . $dto->totalAmount->getCurrency(),
        ];
    }

    private function formatMonthlyStats(MonthlyStatDTO $dto): array
    {
        $date = \DateTime::createFromFormat('Y-m', $dto->month);
        return [
            'month_name' => $date->format('F Y'),
            'total_formatted' => $dto->total->__toString(),
            'period' => [
                'year' => $date->format('Y'),
                'month' => $date->format('n'),
                'quarter' => ceil($date->format('n') / 3),
            ]
        ];
    }

    private function extractBasicInfo(ClientDTO $dto): array
    {
        return [
            'id' => $dto->id,
            'name' => $dto->name,
            'shortcut' => $dto->shortcut,
            'is_default' => $dto->is_default,
        ];
    }

    private function extractContactDetails(ClientDTO $dto): array
    {
        return [
            'email' => $dto->email,
            'phone' => $dto->phone,
            'address' => [
                'street' => $dto->street,
                'city' => $dto->city,
                'zip' => $dto->zip,
                'country' => $dto->country,
                'full' => trim("{$dto->street}, {$dto->city} {$dto->zip}"),
            ]
        ];
    }

    private function extractBusinessInfo(ClientDTO $dto): array
    {
        return [
            'ico' => $dto->ico,
            'dic' => $dto->dic,
            'has_tax_info' => !empty($dto->ico) && !empty($dto->dic),
        ];
    }

    private function addComputedFields(ClientDTO $dto): array
    {
        return [
            'display_name' => $dto->shortcut ?: $dto->name,
            'country_name' => $this->getCountryName($dto->country),
            'created_relative' => $dto->created_at ? \Carbon\Carbon::parse($dto->created_at)->diffForHumans() : null,
        ];
    }

    // Placeholder methods for completeness
    private function calculateBusinessTier(float $amount): string { return $amount > 50000 ? 'premium' : 'standard'; }
    private function calculateClientDiversity(UserStatisticsDTO $dto): float { return $dto->clientCount / max($dto->invoiceCount, 1); }
    private function generateRecommendations(UserStatisticsDTO $dto): array { return ['optimize_pricing', 'expand_client_base']; }
    private function prepareChartData(UserStatisticsDTO $dto): array { return ['chart' => 'data']; }
    private function canEdit(ClientDTO $dto): bool { return true; }
    private function getStatusBadge(ClientDTO $dto): string { return $dto->is_default ? 'primary' : 'secondary'; }
    private function calculateExpensiveMetrics(UserStatisticsDTO $dto): array { return ['complex' => 'calculations']; }
    private function getCountryName(?string $code): string { return $code === 'CZ' ? 'Česká republika' : $code; }
}

/**
 * Ukázkový ViewDataBuilder
 */
class ViewDataBuilder
{
    private array $data = [];

    public function withBasicStats(UserStatisticsDTO $dto): self
    {
        $this->data['basic'] = [
            'invoices' => $dto->invoiceCount,
            'clients' => $dto->clientCount,
            'revenue' => $dto->totalAmount->toFloat(),
        ];
        return $this;
    }

    public function withComputedMetrics(UserStatisticsDTO $dto): self
    {
        $this->data['metrics'] = [
            'avg_per_invoice' => $dto->invoiceCount > 0 ? $dto->totalAmount->divide($dto->invoiceCount)->toFloat() : 0,
            'avg_per_client' => $dto->clientCount > 0 ? $dto->totalAmount->divide($dto->clientCount)->toFloat() : 0,
        ];
        return $this;
    }

    public function withConditionalData(UserStatisticsDTO $dto, callable $condition): self
    {
        if ($condition($dto)) {
            $this->data['premium_features'] = ['enabled' => true];
        }
        return $this;
    }

    public function withFormatting(string $context): self
    {
        $this->data['_formatting'] = ['context' => $context];
        return $this;
    }

    public function build(): array
    {
        return $this->data;
    }
}

/**
 * Ukázkový Presenter
 */
class UserStatisticsPresenter
{
    public function present(UserStatisticsDTO $dto): array
    {
        return [
            'display_cards' => [
                'invoices' => [
                    'value' => $dto->invoiceCount,
                    'label' => 'Faktury',
                    'formatted' => number_format($dto->invoiceCount) . ' ks',
                ],
                'revenue' => [
                    'value' => $dto->totalAmount->toFloat(),
                    'label' => 'Obrat',
                    'formatted' => $dto->totalAmount->__toString(),
                ],
            ],
            'summary_text' => $this->generateSummaryText($dto),
            'progress_indicators' => $this->generateProgressIndicators($dto),
        ];
    }

    private function generateSummaryText(UserStatisticsDTO $dto): string
    {
        return "Za poslední období jste vystavili {$dto->invoiceCount} faktur " .
               "celkem {$dto->clientCount} klientům v hodnotě {$dto->totalAmount->__toString()}.";
    }

    private function generateProgressIndicators(UserStatisticsDTO $dto): array
    {
        return [
            'invoice_goal' => ['current' => $dto->invoiceCount, 'target' => 20],
            'revenue_goal' => ['current' => $dto->totalAmount->toFloat(), 'target' => 100000],
        ];
    }
}
