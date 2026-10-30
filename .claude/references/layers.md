# Layers — target shape by example

One slice of the Party context (creating and reading a contact), written the way every layer should look. Rules behind it: `rules/architecture.md`, `rules/php.md`. The names illustrate the Business/Contact model of step 7: `ContactId`, `ContactDTO`, `ContactWriteData`, the repository ports, `EloquentContactRepository`, `EloquentContactMapper`, the `Contact` model, `ContactApplicationService`, `StoreContactRequest`, `EntityType`, `EntityLimitExceededException`, `ContactNotFoundException` (`docs/prd/PRD_BUSINESSES_AND_CONTACTS.md`). The address, the shared party profile and the duplicate check are left out to keep the slice short.

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
    /** Null when the contact does not exist or belongs to another user; archived contacts are found. */
    public function findByIdForUser(ContactId $id, UserId $userId): ?ContactDTO;

    /** @return list<ContactDTO> */
    public function listActiveForUser(UserId $userId): array;
}

interface ContactWriteRepositoryInterface
{
    public function create(UserId $userId, ContactWriteData $data): ContactDTO;
}
```

## Infrastructure — repository and mapper

```php
namespace App\Infrastructure\Persistence\Eloquent\Party\Repositories;

final class EloquentContactRepository implements ContactReadRepositoryInterface, ContactWriteRepositoryInterface
{
    public function findByIdForUser(ContactId $id, UserId $userId): ?ContactDTO
    {
        $contact = Contact::query()
            ->whereKey($id->value)
            ->where('user_id', $userId->value)
            ->first();

        return $contact === null ? null : EloquentContactMapper::toDTO($contact);
    }

    public function create(UserId $userId, ContactWriteData $data): ContactDTO
    {
        $contact = Contact::create(['user_id' => $userId->value] + EloquentContactMapper::toAttributes($data));

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
        return new ContactDTO(
            id: ContactId::fromInt($contact->id),
            userId: UserId::fromInt($contact->user_id),
            name: $contact->name,
            companyNumber: $contact->company_number,
            vatNumber: $contact->vat_number,
            email: $contact->email,
            archived: $contact->archived_at === null ? null : DateTimeImmutable::createFromInterface($contact->archived_at),
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

## Application — use case

```php
namespace App\Application\Party\Services;

final class ContactApplicationService
{
    public function __construct(
        private readonly ContactReadRepositoryInterface $contacts,
        private readonly ContactWriteRepositoryInterface $contactWriter,
        private readonly EntityLimitChecker $limits,
        private readonly TransactionBoundaryInterface $transactions,
        private readonly EventPublisherInterface $events,
    ) {}

    public function create(UserId $userId, ContactWriteData $data): ContactDTO
    {
        $contact = $this->transactions->transaction(function () use ($userId, $data): ContactDTO {
            // capacity limit: locks the owner, counts active contacts (rules/permissions.md)
            $this->limits->assertCanCreate($userId, EntityType::Contact);

            return $this->contactWriter->create($userId, $data);
        });

        $this->events->publish(new ContactCreated($contact->id, $userId));

        return $contact;
    }

    public function get(UserId $userId, ContactId $id): ContactDTO
    {
        return $this->contacts->findByIdForUser($id, $userId)
            ?? throw new ContactNotFoundException($id);
    }
}
```

No interface, no binding: the class is autowired.

## Delivery — form request and controller action

```php
final class ContactController extends Controller
{
    public function __construct(
        private readonly ContactApplicationService $contacts,
    ) {}

    public function store(StoreContactRequest $request): RedirectResponse
    {
        $this->contacts->create(UserId::fromInt($request->user()->id), $request->toWriteData());

        return redirect()
            ->route('frontend.contacts.index', ['locale' => app()->getLocale()])
            ->with('success', __('contacts.messages.created'));
    }
}
```

`StoreContactRequest::authorize()` checks `frontend.can_create_contact`, `rules()` validates, `toWriteData()` builds `ContactWriteData` from the validated input. `ContactNotFoundException` and `EntityLimitExceededException` are mapped to responses once in `app/Exceptions/ExceptionHandling.php`.

## Test — real services, fake ports

Application tests run `ContactApplicationService` for real against the MySQL test database; only outward ports (gateways, clock, exchange rates) use fakes from `tests/Support/Fakes/`. The HTTP test of `store` covers guest → login redirect, missing permission → 403, limit reached → error message, success → stored row.
