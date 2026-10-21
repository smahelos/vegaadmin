---
paths:
  - "app/Infrastructure/Persistence/**"
  - "app/Models/**"
  - "database/**"
---

# Persistence

Only Infrastructure touches Eloquent (`architecture.md`). Shape of a repository and mapper: `references/layers.md`.

## Repositories

- Every entity the application reads has a Domain port `<Entity>ReadRepositoryInterface`; an entity the application writes through the Domain also has `<Entity>WriteRepositoryInterface`. Reference data managed only in the Backpack admin (statuses, taxes) has the read port only. Port names carry no `Dto` infix: a repository always returns DTOs.
- One implementation per entity, `Eloquent<Entity>Repository` in `app/Infrastructure/Persistence/Eloquent/<Context>/Repositories`, implementing both ports.
- Reads return `<Entity>DTO`, `list<<Entity>DTO>` or `PaginatedResult` built by the mapper; writes accept `<Entity>WriteData` and return the stored `<Entity>DTO` or its id.
- Queries over user data are scoped in the query (`where('user_id', $userId->value)`), not filtered afterwards. Admin-wide reads have their own explicitly named methods (`findByIdAny`, `listAll`).
- Relations a DTO needs are eager-loaded in the same method — no lazy loading inside a mapper.
- A repository holds no business rules: no defaults, no status decisions, no totals.

## Mappers

- `Eloquent<Entity>Mapper` in `…/<Context>/Mappers` is the only place that converts between a model and Domain types: `toDTO(Model): <Entity>DTO` and `toAttributes(<Entity>WriteData): array`. Column names (snake_case) appear here and nowhere above Infrastructure.
- Casting happens here: amounts to `Money`, dates to `DateTimeImmutable`, status columns to enums, ids to id value objects.

## Eloquent models

- A model is a persistence mapping: explicit `$fillable` (never `$guarded`), casts in `casts()`, relations with return types and `::class` targets (`belongsTo(Client::class)`).
- No business logic, logging, totals, or formatting in models — no accessors that compute domain values. Only traits the model actually needs (`CrudTrait` for admin-managed models, `HasRoles` only on `User`).

## SQL

- MySQL is the only database, tests included (`testing.md`). MySQL functions are fine inside a repository; branches on the database driver are not.
- Build queries with the query builder; raw expressions (`selectRaw`, `whereRaw`) pass values as bindings, never by concatenation.
- Column or direction names that come from user input (sorting) are matched against an allowlist before they reach `orderBy()`.

## Migrations and schema

- Every schema change is a new migration, reversible through `down()`. The schema will be squashed into one clean MySQL schema before production (`docs/TECH_DEBT.md`).
- Follow the existing schema naming: plural snake_case tables, `<entity>_id` foreign keys with a real foreign key constraint and an explicit `onDelete`, `is_<flag>` booleans, Laravel `created_at`/`updated_at`.
- Amounts are `decimal`, dates `date`/`datetime`, enum-backed values `string`. A value is stored once: no JSON copy of rows that live in their own table.
- Reference data the app needs to run (statuses, permissions, plans) is created by a migration or by `DatabaseSeeder`, never by hand.
