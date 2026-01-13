# DTO Mapping Patterns a Best Practices

Tento dokument ukazuje optimální způsoby práce s DTO objekty v Laravel 12 projektu, včetně různých způsobů mapování, transformace a použití.

## 1. Základní DTO Patterns

### 1.1 Simple DTO (Read-only Data)
```php
<?php

namespace App\Domain\Example\DTO;

/**
 * Jednoduchý immutable DTO pro přenos dat
 */
class UserProfileDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $avatar = null,
    ) {}

    /**
     * Factory metoda pro vytvoření z pole
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            name: $data['name'],
            email: $data['email'],
            avatar: $data['avatar'] ?? null,
        );
    }

    /**
     * Konverze zpět na pole
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar' => $this->avatar,
        ];
    }
}
```

### 1.2 Complex DTO s Value Objects
```php
<?php

namespace App\Domain\Example\DTO;

use App\Domain\Shared\Money\ValueObjects\Money;
use App\Domain\User\ValueObjects\UserId;

/**
 * Komplexní DTO s value objekty a business logikou
 */
class InvoiceSummaryDTO
{
    public function __construct(
        public readonly UserId $userId,
        public readonly Money $totalAmount,
        public readonly Money $paidAmount,
        public readonly int $invoiceCount,
        public readonly \DateTimeImmutable $lastInvoiceDate,
    ) {}

    public static function fromArray(array $data, string $currency = 'CZK'): self
    {
        return new self(
            userId: UserId::fromInt($data['user_id']),
            totalAmount: Money::fromString((string) $data['total_amount'], $currency),
            paidAmount: Money::fromString((string) $data['paid_amount'], $currency),
            invoiceCount: $data['invoice_count'],
            lastInvoiceDate: new \DateTimeImmutable($data['last_invoice_date']),
        );
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId->toInt(),
            'total_amount' => $this->totalAmount->toFloat(),
            'paid_amount' => $this->paidAmount->toFloat(),
            'invoice_count' => $this->invoiceCount,
            'last_invoice_date' => $this->lastInvoiceDate->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Business metoda - zůstatek k úhradě
     */
    public function remainingAmount(): Money
    {
        return $this->totalAmount->subtract($this->paidAmount);
    }

    /**
     * Formátované výstupy pro view
     */
    public function toFormattedArray(): array
    {
        return [
            'total_amount_formatted' => $this->totalAmount->formatWithCurrency(),
            'paid_amount_formatted' => $this->paidAmount->formatWithCurrency(),
            'remaining_amount_formatted' => $this->remainingAmount()->formatWithCurrency(),
            'invoice_count' => $this->invoiceCount,
            'last_invoice_relative' => $this->lastInvoiceDate->diffForHumans(),
        ];
    }
}
```

## 2. Mapování Single DTO Objects

### 2.1 Mapper Class Pattern (Doporučený způsob)
```php
<?php

namespace App\Infrastructure\Mappers;

use App\Domain\Example\DTO\UserProfileDTO;
use App\Models\User;

/**
 * Dedikovaný mapper pro transformace mezi modely a DTOs
 */
class UserProfileMapper
{
    /**
     * Mapuje Eloquent model na DTO
     */
    public function toDto(User $user): UserProfileDTO
    {
        return new UserProfileDTO(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            avatar: $user->avatar_url, // možnost transformace názvu atributu
        );
    }

    /**
     * Mapuje DTO na array pro vytvoření/aktualizaci modelu
     */
    public function toModelArray(UserProfileDTO $dto): array
    {
        return [
            'name' => $dto->name,
            'email' => $dto->email,
            'avatar_url' => $dto->avatar,
        ];
    }

    /**
     * Mapuje raw database result na DTO
     */
    public function fromDatabaseRow(array $row): UserProfileDTO
    {
        return UserProfileDTO::fromArray([
            'id' => $row['id'],
            'name' => $row['name'],
            'email' => $row['email'],
            'avatar' => $row['avatar_url'],
        ]);
    }
}
```

### 2.2 Transform Pattern pro Single DTO
```php
<?php

namespace App\Services;

use App\Domain\Example\DTO\UserProfileDTO;

class UserProfileTransformer
{
    /**
     * Transformuje single DTO objekt
     * Není to collection, ale stále můžeme použít transform pattern
     */
    public function transform(UserProfileDTO $dto): array
    {
        return [
            'id' => $dto->id,
            'display_name' => $this->formatDisplayName($dto),
            'contact_info' => $this->formatContactInfo($dto),
            'has_avatar' => !empty($dto->avatar),
            'avatar_url' => $dto->avatar ? asset($dto->avatar) : $this->getDefaultAvatar(),
        ];
    }

    /**
     * Conditional transform - mapuje pouze pokud splňuje podmínku
     */
    public function transformIf(UserProfileDTO $dto, callable $condition): ?array
    {
        if ($condition($dto)) {
            return $this->transform($dto);
        }
        return null;
    }

    /**
     * Mapuje DTO pro specifický kontext (API vs. View)
     */
    public function transformForApi(UserProfileDTO $dto): array
    {
        return [
            'user_id' => $dto->id,
            'full_name' => $dto->name,
            'email_address' => $dto->email,
            'profile_image' => $dto->avatar,
        ];
    }

    public function transformForView(UserProfileDTO $dto): array
    {
        return [
            'id' => $dto->id,
            'name' => $dto->name,
            'email' => $dto->email,
            'avatar' => $dto->avatar ? asset($dto->avatar) : null,
            'initials' => $this->getInitials($dto->name),
        ];
    }

    private function formatDisplayName(UserProfileDTO $dto): string
    {
        return $dto->name;
    }

    private function formatContactInfo(UserProfileDTO $dto): string
    {
        return $dto->email;
    }

    private function getDefaultAvatar(): string
    {
        return asset('images/default-avatar.png');
    }

    private function getInitials(string $name): string
    {
        $names = explode(' ', $name);
        return strtoupper(substr($names[0], 0, 1) . (isset($names[1]) ? substr($names[1], 0, 1) : ''));
    }
}
```

## 3. Advanced Mapping Patterns

### 3.1 Builder Pattern pro komplexní DTO
```php
<?php

namespace App\Domain\Example\Builders;

use App\Domain\Example\DTO\InvoiceSummaryDTO;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Domain\User\ValueObjects\UserId;

/**
 * Builder pattern pro postupné sestavení komplexního DTO
 */
class InvoiceSummaryDTOBuilder
{
    private ?UserId $userId = null;
    private ?Money $totalAmount = null;
    private ?Money $paidAmount = null;
    private int $invoiceCount = 0;
    private ?\DateTimeImmutable $lastInvoiceDate = null;

    public static function create(): self
    {
        return new self();
    }

    public function withUserId(UserId $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function withTotalAmount(Money $amount): self
    {
        $this->totalAmount = $amount;
        return $this;
    }

    public function withPaidAmount(Money $amount): self
    {
        $this->paidAmount = $amount;
        return $this;
    }

    public function withInvoiceCount(int $count): self
    {
        $this->invoiceCount = $count;
        return $this;
    }

    public function withLastInvoiceDate(\DateTimeImmutable $date): self
    {
        $this->lastInvoiceDate = $date;
        return $this;
    }

    /**
     * Sestaví DTO z databázového řádku
     */
    public function fromDatabaseRow(array $row, string $currency = 'CZK'): self
    {
        return $this
            ->withUserId(UserId::fromInt($row['user_id']))
            ->withTotalAmount(Money::fromString((string) $row['total_amount'], $currency))
            ->withPaidAmount(Money::fromString((string) $row['paid_amount'], $currency))
            ->withInvoiceCount($row['invoice_count'])
            ->withLastInvoiceDate(new \DateTimeImmutable($row['last_invoice_date']));
    }

    public function build(): InvoiceSummaryDTO
    {
        if (!$this->userId || !$this->totalAmount || !$this->paidAmount || !$this->lastInvoiceDate) {
            throw new \InvalidArgumentException('Missing required fields for InvoiceSummaryDTO');
        }

        return new InvoiceSummaryDTO(
            userId: $this->userId,
            totalAmount: $this->totalAmount,
            paidAmount: $this->paidAmount,
            invoiceCount: $this->invoiceCount,
            lastInvoiceDate: $this->lastInvoiceDate,
        );
    }
}
```

### 3.2 Functional Mapping
```php
<?php

namespace App\Services;

use App\Domain\Example\DTO\UserProfileDTO;

/**
 * Funkcionální přístup k mapování DTO
 */
class FunctionalDTOMapper
{
    /**
     * Pipe pattern - řetězení transformací
     */
    public function pipe(UserProfileDTO $dto, callable ...$transformers): array
    {
        return array_reduce($transformers, function ($carry, $transformer) {
            return $transformer($carry);
        }, $dto->toArray());
    }

    /**
     * Map with callback - aplikuje callback na single DTO
     */
    public function mapWith(UserProfileDTO $dto, callable $callback): mixed
    {
        return $callback($dto);
    }

    /**
     * Conditional mapping
     */
    public function when(UserProfileDTO $dto, bool $condition, callable $transformer): array
    {
        $base = $dto->toArray();
        return $condition ? $transformer($base) : $base;
    }

    /**
     * Merge additional data to DTO
     */
    public function merge(UserProfileDTO $dto, array $additional): array
    {
        return array_merge($dto->toArray(), $additional);
    }

    // Ukázka použití:
    public function example(UserProfileDTO $dto): array
    {
        return $this->pipe(
            $dto,
            fn($data) => array_merge($data, ['computed_field' => 'value']),
            fn($data) => array_map('strtoupper', array_filter($data, 'is_string')),
            fn($data) => ['transformed' => $data]
        );
    }
}
```

## 4. Repository Pattern s DTO Mapping

### 4.1 Repository s DTOs
```php
<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Example\DTO\UserProfileDTO;
use App\Infrastructure\Mappers\UserProfileMapper;
use App\Models\User;

/**
 * Repository vracející DTOs místo modelů
 */
class UserProfileDtoRepository
{
    public function __construct(
        private UserProfileMapper $mapper
    ) {}

    /**
     * Vrací single DTO
     */
    public function findById(int $id): ?UserProfileDTO
    {
        $user = User::find($id);
        return $user ? $this->mapper->toDto($user) : null;
    }

    /**
     * Vrací collection DTOs
     */
    public function getAllActive(): array
    {
        return User::where('active', true)
            ->get()
            ->map(fn(User $user) => $this->mapper->toDto($user))
            ->toArray();
    }

    /**
     * Mapuje s dodatečnou business logikou
     */
    public function findWithComputedFields(int $id): ?array
    {
        $user = User::with(['invoices', 'payments'])->find($id);
        if (!$user) {
            return null;
        }

        $dto = $this->mapper->toDto($user);
        
        // Přidáme computed fields
        return array_merge($dto->toArray(), [
            'total_invoices' => $user->invoices->count(),
            'total_payments' => $user->payments->sum('amount'),
            'last_activity' => $user->last_login_at?->diffForHumans(),
        ]);
    }
}
```

## 5. Service Layer s DTO Transformations

### 5.1 Application Service s DTOs
```php
<?php

namespace App\Application\Services;

use App\Domain\Example\DTO\UserProfileDTO;
use App\Infrastructure\Repositories\UserProfileDtoRepository;
use App\Services\UserProfileTransformer;

/**
 * Application service kombinující repository a transformery
 */
class UserProfileApplicationService
{
    public function __construct(
        private UserProfileDtoRepository $repository,
        private UserProfileTransformer $transformer,
    ) {}

    /**
     * Vrací transformovaná data pro view
     */
    public function getUserForProfile(int $id): ?array
    {
        $dto = $this->repository->findById($id);
        return $dto ? $this->transformer->transformForView($dto) : null;
    }

    /**
     * Vrací data pro API
     */
    public function getUserForApi(int $id): ?array
    {
        $dto = $this->repository->findById($id);
        return $dto ? $this->transformer->transformForApi($dto) : null;
    }

    /**
     * Batch processing - i když pracujeme s single DTOs
     */
    public function processMultipleUsers(array $userIds): array
    {
        $results = [];
        foreach ($userIds as $id) {
            $dto = $this->repository->findById($id);
            if ($dto) {
                $results[] = $this->transformer->transform($dto);
            }
        }
        return $results;
    }

    /**
     * Conditional transformation
     */
    public function getUserConditionally(int $id, string $context): ?array
    {
        $dto = $this->repository->findById($id);
        if (!$dto) {
            return null;
        }

        return match ($context) {
            'api' => $this->transformer->transformForApi($dto),
            'view' => $this->transformer->transformForView($dto),
            'minimal' => ['id' => $dto->id, 'name' => $dto->name],
            default => $dto->toArray()
        };
    }
}
```

## 6. Best Practices Summary

### Kdy použít array vs. DTO:

**Použijte DTO když:**
- Potřebujete type safety a IDE podporu
- Data prochází mezi vrstvami aplikace
- Chcete enkapsulovat business logiku s daty
- Potřebujete immutable data structures
- Pracujete s komplexními objekty s value objects

**Použijte array když:**
- Jednoduché, krátkodobé data transformations
- JSON serialization bez dodatečné logiky
- Rychlé prototypování
- Kompatibilita s legacy kódem

### Optimální DTO mapping patterns:

1. **Mapper Classes** - nejčistší separace concerns
2. **Factory Methods** (`fromArray`, `fromModel`) - pohodlné pro simple cases
3. **Builder Pattern** - pro komplexní DTOs s validací
4. **Transform Services** - pro context-specific transformations

### Performance considerations:

```php
// ✅ Dobré - lazy loading
public function getUser(int $id): ?UserProfileDTO 
{
    return $this->repository->findById($id); // DTO se vytvoří jen když je potřeba
}

// ❌ Špatné - eager transformation
public function getAllUsers(): array 
{
    return User::all()->map(fn($user) => $this->mapper->toDto($user))->toArray();
    // Lepší: paginate nebo lazy collection
}
```
