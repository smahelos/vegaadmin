# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

Billex (formerly "vegaadmin" / "Invoice"; old names survive in some files) is an invoicing app: Laravel 12 on PHP 8.3, Backpack 6.8 admin panel (with PermissionManager / spatie/laravel-permission), a public frontend built with Blade + Livewire 3 + Alpine + Tailwind 4 (Vite), GoPay payments, dompdf invoice PDFs and QR payment codes. UI languages: cs, de, en, sk.

## One standard, no legacy

Billex is not in production. There is no backward compatibility to keep: classes, columns, routes and APIs are renamed or deleted whenever the standard calls for it.

The rules in `.claude/rules/` describe the **target standard**. Much of the existing code predates it (see the conversion backlog in `docs/TECH_DEBT.md`), so the surrounding code is not a template: when a rule and the existing code disagree, the rule wins. Every new or changed line follows the rules. Rule violations you notice in the code you touch are listed to the user with a proposed conversion; converting whole files or contexts is its own task, run with the `/convert-to-standard` skill.

Rules load by path; each one owns its topic:

- `rules/php.md` — PHP style: strict types, `final`, typing, naming, exceptions, comments (all PHP)
- `rules/architecture.md` — layers and allowed dependencies, bounded contexts, ports and interfaces, data classes (DTO / WriteData / Result), events, composition roots, architecture guards
- `rules/domain.md` — `app/Domain`: pure PHP, value objects, money, time, invoice invariants
- `rules/application.md` — `app/Application`: use cases, ownership checks, transactions
- `rules/persistence.md` — repositories, mappers, Eloquent models, migrations, SQL
- `rules/http.md` — controllers, form requests, routes, middleware, API responses
- `rules/admin.md` — Backpack CRUD controllers, own field/column/filter types, admin assets
- `rules/frontend.md` — Blade, Livewire, Alpine, JS, Tailwind
- `rules/translations.md` — user-facing text and locale files (always loaded)
- `rules/testing.md` — what to test, test types, test database
- `rules/payments.md` — payment gateways, subscriptions, QR payments
- `rules/permissions.md` — permission catalog, guards, entity limits (UELS)

Reference material lives in `.claude/references/` and is read when a rule points to it. Architecture decisions (the "why") live in `docs/adr/`.

## Running commands

Everything PHP runs inside the `BILLEX-app` container (stack in `docker-compose.yaml`: app/php-fpm, nginx, MySQL, Redis, MailHog, phpMyAdmin, cron). The host `php` / `./php` wrappers exec into other containers; never use them.

```bash
docker exec BILLEX-app php artisan test                                  # full suite
docker exec BILLEX-app php artisan test --parallel                       # paratest
docker exec BILLEX-app php artisan test tests/Unit/Domain/Payment/       # directory
docker exec BILLEX-app php artisan test --filter=InvoiceRequestTest      # single test/class
docker exec BILLEX-app php artisan ddd:guard                             # architecture guard
docker exec BILLEX-app vendor/bin/pint --test app/Models/Bank.php       # check formatting of given files
```

- `app` and `cron` bind-mount the project (`./:/var/www`): code changes apply immediately, `vendor/` comes from the host (`docker exec BILLEX-app composer install` after `composer.lock` changes). Rebuild (`docker compose up -d --build app cron`) only after changing `app.dockerfile` or the `WWW_UID`/`WWW_GID` build args in `.env`. `cron` runs `php artisan schedule:work` from the same image. `public/storage` stays a relative symlink (`../storage/app/public`).
- PHPUnit 11: `-v` / `--verbose` fail with `Unknown option`.
- Frontend assets: `npm run dev` / `npm run build` (Vite, `vite.config.js`).

## Architecture at a glance

Layered DDD on Laravel, organised by bounded context (Analytics, Content, Invoice, Party = clients/suppliers, Payment, Product, User, Shared):

- `app/Domain/<Context>/` — business rules in plain PHP
- `app/Application/<Context>/` — use cases called by controllers, Livewire and commands
- `app/Infrastructure/` — everything framework- or vendor-bound: repositories, mappers, gateways, policies, Backpack form fields
- `app/Http/Controllers/{Frontend,Admin,Api}`, `app/Livewire/<Context>/` — delivery
- `app/Models/` — Eloquent models, used only by Infrastructure
- `app/Providers/<Context>ServiceProvider` — one composition root per context (ADR `docs/adr/D-042-context-service-providers.md`)

Routes: `routes/web.php` (frontend, localized), `routes/backpack/custom.php` (admin, `admin` prefix, `backpack` guard), `routes/api.php`. Two auth guards: `backpack` (admin) and `web` (frontend users).

## High-risk areas

Mistakes here cost money or data — flag every non-obvious decision:

- **Money and VAT** — amounts are `Money`, never floats (`rules/domain.md`)
- **Invoice numbering and variable symbols** — numbers feed QR payments and must stay unique per user and year
- **Payments and subscriptions** — GoPay callbacks, plan activation (`rules/payments.md`)
- **Permissions and entity limits** — a wrong check exposes another user's data or blocks paid features (`rules/permissions.md`)
- **Invoice PDFs** — legal documents; output is pinned by snapshot tests
