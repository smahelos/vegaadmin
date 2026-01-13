# Odpověď na otázku: Mapování single DTO objektů

## TL;DR - Rychlá odpověď

**Ano, můžete efektivně "mapovat" single DTO objekt** i když to není kolekce. Existuje několik elegantních způsobů:

### 1. **Je lepší mapovat přímo DTO nebo konvertovat na array?**

**✅ LÉPE: Mapovat přímo DTO objekt**
```php
// Přímo transformace DTO
public function transformUserStats(UserStatisticsDTO $dto): array 
{
    return [
        'revenue_display' => $dto->totalAmount->__toString(),
        'avg_per_client' => $dto->clientCount > 0 
            ? $dto->totalAmount->divide($dto->clientCount)->__toString()
            : '0',
    ];
}
```

**❌ HŮŘE: Zbytečná konverze na array**
```php
// Zbytečná konverze
$array = $dto->toArray(); // extra krok
$result = array_map('transform', $array); // pak map na array
```

### 2. **Nejlepší praktické způsoby z vašeho projektu:**

#### A) **Direct Transformation** (nejjednodušší)
```php
public function mapForDashboard(UserStatisticsDTO $dto): array
{
    return [
        'cards' => [
            'invoices' => ['count' => $dto->invoiceCount, 'icon' => 'invoice'],
            'clients' => ['count' => $dto->clientCount, 'icon' => 'users'],
            'revenue' => ['amount' => $dto->totalAmount->__toString(), 'icon' => 'money'],
        ],
        'summary' => "Celkem {$dto->invoiceCount} faktur pro {$dto->clientCount} klientů"
    ];
}
```

#### B) **Presenter Pattern** (z vašeho Dashboard kódu - nejelegantnější)
```php
// Váš aktuální pattern - kombinace raw + formatted
public function getDashboardData(User $user): array
{
    $stats = $this->dashboardService->getUserStatistics($user->id);
    return [
        'statistics' => $stats, // raw DTO
        'statistics_formatted' => $this->statsPresenter->present($stats), // formatted
    ];
}
```

#### C) **Conditional Mapping**
```php
public function mapIf(UserStatisticsDTO $dto, callable $condition): ?array
{
    if ($condition($dto)) {
        return $this->transform($dto);
    }
    return null;
}
```

#### D) **Context-Aware Mapping**
```php
public function mapForContext(UserStatisticsDTO $dto, string $context): array
{
    return match ($context) {
        'dashboard' => $this->mapForDashboard($dto),
        'api' => $this->mapForApi($dto),
        'export' => $this->mapForExport($dto),
        default => $dto->toArray()
    };
}
```

## Kdy použít jaký přístup?

### **Direct Transformation** - použijte když:
- Jednoduchá transformace pro specifický use case
- Potřebujete compute fields z DTO properties
- Máte clear mapping logic

### **Presenter Pattern** - použijte když:
- Složitější UI formatting
- Různé display contexts (dashboard, mobile, export)
- Chcete separovat presentation logic

### **Array konverze** - použijte pouze když:
- Potřebujete backward compatibility
- JSON serialization bez logic
- Jednoduché pass-through scenarios

## Praktické příklady z vašeho kódu

Váš současný kód už elegantně používá tyto patterns:

```php
// Z DashboardApplicationService.php - map collection of DTOs
'monthly_stats_formatted' => $monthly->map(fn ($dto) => $this->monthlyPresenter->present($dto)),
'clients_formatted' => $clients->map(fn ($dto) => $this->clientPresenter->present($dto)),
```

```php
// Z UserStatisticsDTO.php - multiple output formats
public function toArray(): array { /* basic array */ }
public function toFormattedArray(MoneyFormatterInterface $formatter): array { /* formatted version */ }
```

## 🎯 **Doporučení pro váš projekt:**

1. **Pokračujte v Presenter pattern** - je nejčistější a nejflexibilnější
2. **Používejte DTO factories** (`fromArray`) pro input mapping  
3. **Kombinujte raw + formatted data** jak už děláte v DashboardService
4. **Mapujte přímo DTO objekty** - nekonvertujte zbytečně na array
5. **Využívejte context-aware mapping** pro různé API/UI contexts

Váš současný přístup je již velmi dobrý a odpovídá best practices! 🚀
