# Layers — target shape by example

One slice of the Party context (creating and reading a contact), written the way every layer should look. Rules behind it: `rules/architecture.md`, `rules/application.md`, `rules/permissions.md`, `rules/php.md`. The names illustrate the Business/Contact model of step 7: `ContactId`, `ContactDTO`, `ContactWriteData`, the repository ports, `EloquentContactRepository`, `EloquentContactMapper`, the `Contact` model, `CreateContact`, `CreateContactHandler`, `GetContact`, `GetContactHandler`, `StoreContactRequest`, `EntityType`, `EntityLimitExceededException`, `ContactNotFoundException` (`docs/prd/PRD_BUSINESSES_AND_CONTACTS.md`). The address, the shared party profile and the duplicate check are left out to keep the slice short.

## Domain — value object, data classes, port

```php
<?php

declare(strict_types=1);

namespace App\Domain\Party\DTO;

use App\Domain\Party\ValueObjects\ContactId;
use App\Domain\User\ValueObjects\UserId;
use DateTimeImmutable;

final readonly class ContactDTO
{
    public function __construct(
        public ContactId $id,
        public UserId $userId,
        public string $name,
        public ?string $companyNumber,
        public ?string $vatNumber,
        public ?string $email,
        public ?DateTimeImmutable $archived,
        public DateTimeImmutable $created,
    ) {}
}
```

```php
final readonly class ContactWriteData
{
    public function __construct(
        public string $name,
        public ?string $companyNumber,
        public ?string $vatNumber,
        public ?string $email,
    ) {}
}
```

```php
namespace App\Domain\Party\Contracts;

interface ContactReadRepositoryInterface
{
    /** Null when the contact does not exist or is outside the scope; archived contacts are found. */
    public function findById(ContactId $id, ReadScope $scope): ?ContactDTO;

    /** @return list<ContactDTO> */
    public function listActive(ReadScope $scope): array;
}

interface ContactWriteRepositoryInterface
{
    public function create(WriteScope $scope, ContactWriteData $data): ContactDTO;
}
```

Ports are Domain, so `ReadScope` and `WriteScope` are too (`App\Domain\Shared\Authorization\ValueObjects`). A Command reads with the `ReadScope` its `WriteScope` holds: `$this->contacts->findById($command->id, $scope->read)`.

## Infrastructure — repository and mapper

```php
namespace App\Infrastructure\Persistence\Eloquent\Party\Repositories;

final class EloquentContactRepository implements ContactReadRepositoryInterface, ContactWriteRepositoryInterface
{
    public function findById(ContactId $id, ReadScope $scope): ?ContactDTO
    {
        $query = Contact::query();
        $query->whereKey($id->value);
        // ReadScope::allOwners() has no owner
        if ($scope->owner !== null) {
            $query->where('user_id', $scope->owner->value);
        }
        $contact = $query->first();

        return $contact === null ? null : EloquentContactMapper::toDTO($contact);
    }

    public function create(WriteScope $scope, ContactWriteData $data): ContactDTO
    {
        $attributes = ['user_id' => $scope->owner->value] + EloquentContactMapper::toAttributes($data);
        $contact = Contact::create($attributes);

        return EloquentContactMapper::toDTO($contact);
    }
}
```

```php
namespace App\Infrastructure\Persistence\Eloquent\Party\Mappers;

final class EloquentContactMapper
{
    public static function toDTO(Contact $contact): ContactDTO
    {
        $archived = null;
        if ($contact->archived_at !== null) {
            $archived = DateTimeImmutable::createFromInterface($contact->archived_at);
        }

        return new ContactDTO(
            id: ContactId::fromInt($contact->id),
            userId: UserId::fromInt($contact->user_id),
            name: $contact->name,
            companyNumber: $contact->company_number,
            vatNumber: $contact->vat_number,
            email: $contact->email,
            archived: $archived,
            created: DateTimeImmutable::createFromInterface($contact->created_at),
        );
    }

    /** @return array<string, mixed> */
    public static function toAttributes(ContactWriteData $data): array
    {
        return [
            'name' => $data->name,
            'company_number' => $data->companyNumber,
            'vat_number' => $data->vatNumber,
            'email' => $data->email,
        ];
    }
}
```

Bound once in `PartyServiceProvider::registerInfrastructure()` to both ports.

## Application — the gate and a use case

The gate is a port, implemented by `App\Infrastructure\Authorization\Services\AuthorizingUseCaseRunner`:

```php
namespace App\Application\Shared\UseCase\Contracts;

interface AuthorizingUseCaseRunnerInterface
{
    /**
     * @template TResult
     *
     * @param  CommandInterface<TResult>|QueryInterface<TResult>  $useCase
     * @return TResult
     */
    public function run(Actor $actor, CommandInterface|QueryInterface $useCase): mixed;

    /** @param  class-string<CommandInterface<mixed>|QueryInterface<mixed>>  $useCase */
    public function allowed(Actor $actor, string $useCase): bool;
}
```

A Command, its declaration and its handler side by side:

```php
namespace App\Application\Party\Commands;

/** @implements CommandInterface<ContactDTO> */
final readonly class CreateContact extends OwnedUseCase implements CommandInterface
{
    public function __construct(
        UserId $owner,
        public ContactWriteData $data,
    ) {
        parent::__construct($owner);
    }

    public static function access(): Access
    {
        return Access::permissionWithOwner(Permission::CONTACT_CREATE);
    }
}

final class CreateContactHandler
{
    public function __construct(
        private readonly ContactWriteRepositoryInterface $contacts,
        private readonly EntityLimit $limits,
        private readonly EventPublisherInterface $events,
    ) {}

    public function handle(CreateContact $command, WriteScope $scope): ContactDTO
    {
        // capacity limit: locks the owner, counts active contacts (rules/permissions.md)
        $this->limits->assertCanCreate($command->owner, LimitedEntity::CONTACT);

        $contact = $this->contacts->create($scope, $command->data);

        $this->events->publish(
            new ContactCreated($contact->id, $command->owner),
        );

        return $contact;
    }
}
```

A Query has the same shape, gets a `ReadScope` and runs without a transaction:

```php
namespace App\Application\Party\Queries;

/** @implements QueryInterface<ContactDTO> */
final readonly class GetContact extends OwnedUseCase implements QueryInterface
{
    public function __construct(
        UserId $owner,
        public ContactId $id,
    ) {
        parent::__construct($owner);
    }

    public static function access(): Access
    {
        return Access::permission(Permission::CONTACT_VIEW);
    }
}

final class GetContactHandler
{
    public function __construct(
        private readonly ContactReadRepositoryInterface $contacts,
    ) {}

    public function handle(GetContact $query, ReadScope $scope): ContactDTO
    {
        return $this->contacts->findById($query->id, $scope)
            ?? throw new ContactNotFoundException($query->id);
    }
}
```

`OwnedUseCase` is an `abstract readonly class` holding `public UserId $owner`; the gate reads the owner through it. Handlers have no interface and no binding: they are autowired. A Command with no result is `@implements CommandInterface<null>` and its handler returns `void`.

## Delivery — route, form request and controller action

```php
// Answers a 403 before validation: names the use case the controller runs
$resource = Route::resource('contacts', ContactController::class);
$resource->middlewareFor('store', 'allowed:' . CreateContact::class);
```

```php
use Illuminate\Support\Facades\App;

final class ContactController extends Controller
{
    public function __construct(
        private readonly AuthorizingUseCaseRunnerInterface $useCases,
    ) {}

    public function store(StoreContactRequest $request): RedirectResponse
    {
        $owner = UserId::fromInt($request->user()->id);
        // The route names the same use case in its `allowed` middleware: change them together
        $this->useCases->run(
            Actor::user($owner),
            new CreateContact($owner, $request->toWriteData()),
        );

        $url = route('frontend.contacts.index', ['locale' => App::currentLocale()]);
        $response = redirect($url);

        return $response->with(
            'success',
            __('contacts.messages.created'),
        );
    }
}
```

`StoreContactRequest` has no `authorize()`: `rules()` validates and `toWriteData()` builds `ContactWriteData` from the validated input. `PermissionDeniedException`, `ContactNotFoundException` and `EntityLimitExceededException` are mapped to responses once in `app/Exceptions/ExceptionHandling.php`.

## Test — real gate and services, fake ports

Application tests run a use case through the real gate against the MySQL test database; only outward ports (gateways, clock, exchange rates) use fakes from `tests/Support/Fakes/`. The HTTP test of `store` covers guest → login redirect, missing permission → 403 before validation, limit reached → error message, success → stored row.
