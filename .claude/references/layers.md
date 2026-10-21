# Layers — target shape by example

One slice of the Party context (creating and reading a client), written the way every layer should look. Rules behind it: `rules/architecture.md`, `rules/php.md`. Several names show the target and do not exist yet: the repository ports without `Dto`, `EloquentClientRepository`, `ClientApplicationService`, `StoreClientRequest`, `EntityType`, `EntityLimitExceededException`, `ClientNotFoundException`, and the camelCase properties of the data classes and the `company_number` / `vat_number` columns for IČO / DIČ (`docs/TECH_DEBT.md`).

## Domain — value object, data classes, port

```php
<?php

declare(strict_types=1);

namespace App\Domain\Party\DTO;

use App\Domain\Party\ValueObjects\PartyId;
use App\Domain\User\ValueObjects\UserId;
use DateTimeImmutable;

final readonly class ClientDTO
{
    public function __construct(
        public PartyId $id,
        public UserId $userId,
        public string $name,
        public ?string $companyNumber,
        public ?string $vatNumber,
        public string $email,
        public bool $isDefault,
        public DateTimeImmutable $created,
    ) {}
}
```

```php
final readonly class ClientWriteData
{
    public function __construct(
        public string $name,
        public ?string $companyNumber,
        public ?string $vatNumber,
        public string $email,
        public bool $isDefault,
    ) {}
}
```

```php
namespace App\Domain\Party\Contracts;

interface ClientReadRepositoryInterface
{
    /** Null when the client does not exist or belongs to another user. */
    public function findByIdForUser(PartyId $id, UserId $userId): ?ClientDTO;

    /** @return list<ClientDTO> */
    public function listForUser(UserId $userId): array;
}

interface ClientWriteRepositoryInterface
{
    public function create(UserId $userId, ClientWriteData $data): ClientDTO;
}
```

## Infrastructure — repository and mapper

```php
namespace App\Infrastructure\Persistence\Eloquent\Party\Repositories;

final class EloquentClientRepository implements ClientReadRepositoryInterface, ClientWriteRepositoryInterface
{
    public function findByIdForUser(PartyId $id, UserId $userId): ?ClientDTO
    {
        $client = Client::query()
            ->whereKey($id->value)
            ->where('user_id', $userId->value)
            ->first();

        return $client === null ? null : EloquentClientMapper::toDTO($client);
    }

    public function create(UserId $userId, ClientWriteData $data): ClientDTO
    {
        $client = Client::create(['user_id' => $userId->value] + EloquentClientMapper::toAttributes($data));

        return EloquentClientMapper::toDTO($client);
    }
}
```

```php
namespace App\Infrastructure\Persistence\Eloquent\Party\Mappers;

final class EloquentClientMapper
{
    public static function toDTO(Client $client): ClientDTO
    {
        return new ClientDTO(
            id: PartyId::fromInt($client->id),
            userId: UserId::fromInt($client->user_id),
            name: $client->name,
            companyNumber: $client->company_number,
            vatNumber: $client->vat_number,
            email: $client->email,
            isDefault: $client->is_default,
            created: DateTimeImmutable::createFromInterface($client->created_at),
        );
    }

    /** @return array<string, mixed> */
    public static function toAttributes(ClientWriteData $data): array
    {
        return [
            'name' => $data->name,
            'company_number' => $data->companyNumber,
            'vat_number' => $data->vatNumber,
            'email' => $data->email,
            'is_default' => $data->isDefault,
        ];
    }
}
```

Bound once in `PartyServiceProvider::registerInfrastructure()` to both ports.

## Application — use case

```php
namespace App\Application\Party\Services;

final class ClientApplicationService
{
    public function __construct(
        private readonly ClientReadRepositoryInterface $clients,
        private readonly ClientWriteRepositoryInterface $clientWriter,
        private readonly EntityLimitChecker $limits,
        private readonly TransactionBoundaryInterface $transactions,
        private readonly EventPublisherInterface $events,
    ) {}

    public function create(UserId $userId, ClientWriteData $data): ClientDTO
    {
        $client = $this->transactions->transaction(function () use ($userId, $data): ClientDTO {
            $this->limits->assertCanCreate($userId, EntityType::Client);

            return $this->clientWriter->create($userId, $data);
        });

        $this->events->publish(new ClientCreated($client->id, $userId));

        return $client;
    }

    public function get(UserId $userId, PartyId $id): ClientDTO
    {
        return $this->clients->findByIdForUser($id, $userId)
            ?? throw new ClientNotFoundException($id);
    }
}
```

No interface, no binding: the class is autowired.

## Delivery — form request and controller action

```php
final class ClientController extends Controller
{
    public function __construct(
        private readonly ClientApplicationService $clients,
    ) {}

    public function store(StoreClientRequest $request): RedirectResponse
    {
        $this->clients->create(UserId::fromInt($request->user()->id), $request->toWriteData());

        return redirect()
            ->route('frontend.clients.index', ['locale' => app()->getLocale()])
            ->with('success', __('clients.messages.created'));
    }
}
```

`StoreClientRequest::authorize()` checks `frontend.can_create_edit_client`, `rules()` validates, `toWriteData()` builds `ClientWriteData` from the validated input. `ClientNotFoundException` and `EntityLimitExceededException` are mapped to responses once in `app/Exceptions/ExceptionHandling.php`.

## Test — real services, fake ports

Application tests run `ClientApplicationService` for real against the MySQL test database; only outward ports (gateways, clock, exchange rates) use fakes from `tests/Support/Fakes/`. The HTTP test of `store` covers guest → login redirect, missing permission → 403, limit reached → error message, success → stored row.
