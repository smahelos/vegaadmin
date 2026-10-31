# Billex AI instructions

This is the canonical repository guidance for Codex, Claude and other coding agents. Codex reads `AGENTS.md`; `.claude/CLAUDE.md` imports this same file for Claude. Keep shared project instructions here and maintain one copy of each detailed rule or workflow. Tool entry files must not introduce a separate project standard.

Read the full set of rule files in `.claude/rules/` once per session before changing files, and reread a rule when it changes. Their `paths` metadata describes applicability; apply the rules governing the current change. References and skill bodies are read when their listed task needs them. Explicit user instructions take precedence over repository guidance; a local handoff does not authorize a commit, push or PR.

## Project

Billex (formerly "vegaadmin" / "Invoice"; old names survive in some files) is an invoicing app: Laravel 12 on PHP 8.3, Backpack 6.8 admin panel (with PermissionManager / spatie/laravel-permission), a public frontend built with Blade + Livewire 3 + Alpine + Tailwind 4 (Vite), GoPay payments, dompdf invoice PDFs and QR payment codes. UI languages: cs, de, en, sk.

## One standard, no legacy

Billex is not in production. There is no backward compatibility to keep: classes, columns, routes and APIs are renamed or deleted whenever the standard calls for it.

The rules in `.claude/rules/` describe the **target standard**. Much of the existing code predates it (see the conversion backlog in `docs/TECH_DEBT.md`), so the surrounding code is not a template: when a rule and the existing code disagree, the rule wins. Every new or changed line follows the rules. Rule violations you notice in the code you touch are listed to the user with a proposed conversion; converting whole files or contexts is its own task, run with the `/convert-to-standard` skill.

The following catalog is shared by both agents. Read the same files regardless of which agent is running; path-scoped automatic loading by one tool is not a substitute for this shared loading rule:

- [`.claude/rules/php.md`](.claude/rules/php.md) — PHP style: strict types, `final`, typing, naming, exceptions, comments (all PHP)
- [`.claude/rules/architecture.md`](.claude/rules/architecture.md) — layers and allowed dependencies, bounded contexts, ports and interfaces, data classes (DTO / WriteData / Result), events, composition roots, architecture guards
- [`.claude/rules/domain.md`](.claude/rules/domain.md) — `app/Domain`: pure PHP, value objects, money, time, invoice invariants
- [`.claude/rules/application.md`](.claude/rules/application.md) — `app/Application`: use cases, ownership checks, transactions
- [`.claude/rules/persistence.md`](.claude/rules/persistence.md) — repositories, mappers, Eloquent models, migrations, SQL
- [`.claude/rules/http.md`](.claude/rules/http.md) — controllers, form requests, routes, middleware, API responses
- [`.claude/rules/admin.md`](.claude/rules/admin.md) — Backpack CRUD controllers, own field/column/filter types, admin assets
- [`.claude/rules/frontend.md`](.claude/rules/frontend.md) — Blade, Livewire, Alpine, JS, Tailwind
- [`.claude/rules/translations.md`](.claude/rules/translations.md) — user-facing text and locale files (always loaded)
- [`.claude/rules/testing.md`](.claude/rules/testing.md) — what to test, test types, test database
- [`.claude/rules/payments.md`](.claude/rules/payments.md) — payment gateways, subscriptions, QR payments
- [`.claude/rules/permissions.md`](.claude/rules/permissions.md) — permission catalog, guards, entity limits (UELS)

Reference material lives in `.claude/references/` and is read when a rule points to it; paths such as `references/layers.md` in a rule refer to `.claude/references/layers.md`. Bare rule filenames refer to the shared `.claude/rules/` catalog. Architecture decisions (the "why") live in `docs/adr/`.

## Documentation and AI working files

Read [the shared documentation policy](docs/DOCUMENTATION.md) and use [the documentation index](docs/README.md) to find relevant accepted requirements and decisions. This policy applies to all agents and human contributors. PRDs live in `docs/prd/`, technical designs in `docs/design/`, ADRs in `docs/adr/`.

Put all agent-created temporary scripts, logs, reports, screenshots, browser profiles and scratch files under `dev_docs/ai/tmp/<task>/`; keep local handoffs in `dev_docs/ai/sessions/`. Do not use application `storage/`, the repository root or a general `/tmp` directory for agent artifacts. Application-owned runtime outputs retain their configured locations, as explained in the shared policy. `dev_docs/` is ignored by Git; maintained documentation must be usable without it. Do not infer permission to commit, push or open a PR from permission to edit files.

## Running commands

Everything PHP runs inside the `BILLEX-app` container (stack in `docker-compose.yaml`: app/php-fpm, nginx, MySQL, Redis, MailHog, phpMyAdmin, cron, queue). The host `php` / `./php` wrappers exec into other containers; never use them.

```bash
docker exec BILLEX-app php artisan test                                  # full suite
docker exec BILLEX-app php artisan test --parallel --drop-databases      # paratest, drops invoice_testing_test_N afterwards
docker exec BILLEX-app vendor/bin/phpunit --testsuite Unit              # unit tests only: no Laravel, no database
docker exec BILLEX-app php artisan test tests/Feature/Http/              # directory
docker exec BILLEX-app php artisan test --filter=InvoiceRequestTest      # single test/class
docker exec BILLEX-app php artisan ddd:guard                             # architecture guard
docker exec BILLEX-app php artisan migrate:fresh --seed                  # rebuild the dev DB: reference data + DevelopmentDataSeeder accounts (*@billex.test / backpack)
docker exec BILLEX-app vendor/bin/phpcbf                                 # multi-line layout (run before Pint)
docker exec BILLEX-app vendor/bin/pint --dirty                           # format changed files
docker exec BILLEX-app vendor/bin/phpstan analyse --memory-limit=4G      # Larastan at the level in phpstan.neon
```

- `app`, `cron` and `queue` bind-mount the project (`./:/var/www`): code changes apply immediately, `vendor/` comes from the host (`docker exec BILLEX-app composer install` after `composer.lock` changes). Rebuild (`docker compose up -d --build app cron queue`) only after changing `app.dockerfile` or the `WWW_UID`/`WWW_GID` build args in `.env`. `cron` runs `php artisan schedule:work` and `queue` runs `php artisan queue:work` from the same image; the worker keeps the code in memory, so after a change to code a job or listener runs, `docker exec BILLEX-app php artisan queue:restart`. `public/storage` stays a relative symlink (`../storage/app/public`).
- PHPUnit 11: `-v` / `--verbose` fail with `Unknown option`.
- Frontend assets: `npm run dev` / `npm run build` (Vite, `vite.config.js`).

## Architecture at a glance

Layered DDD on Laravel, organised by bounded context (Analytics, Content, Invoice, Party = businesses/contacts, Payment, Product, User, Shared):

- `app/Domain/<Context>/` — business rules in plain PHP
- `app/Application/<Context>/` — use cases called by controllers, Livewire and commands
- `app/Infrastructure/` — everything framework- or vendor-bound: repositories, mappers, gateways, policies, Backpack form fields
- `app/Http/Controllers/{Frontend,Admin,Api}`, `app/Livewire/<Context>/` — delivery
- `app/Models/` — Eloquent models, used only by Infrastructure
- `app/Providers/<Context>ServiceProvider` — one composition root per context (ADR `docs/adr/D-042-context-service-providers.md`)

Routes: `routes/web.php` (frontend, localized), `routes/backpack/custom.php` (admin, `admin` prefix, `backpack` guard), `routes/api.php`. Two auth guards: `backpack` (admin) and `web` (frontend users).

## High-risk areas

Mistakes here cost money or data — flag every non-obvious decision:

- **Money and VAT** — amounts are `Money`, never floats ([`.claude/rules/domain.md`](.claude/rules/domain.md))
- **Invoice numbering and variable symbols** — numbers feed QR payments and must stay unique per user and year
- **Payments and subscriptions** — GoPay callbacks, plan activation ([`.claude/rules/payments.md`](.claude/rules/payments.md))
- **Permissions and entity limits** — a wrong check exposes another user's data or blocks paid features ([`.claude/rules/permissions.md`](.claude/rules/permissions.md))
- **Invoice PDFs** — legal documents; output is pinned by snapshot tests


## Shared workflows

Both agents use the same workflow sources below. Read the relevant `SKILL.md` before the corresponding operation and follow its scope and authorization boundaries. These repository workflows are usable by reading their files even when a tool does not list them in its native skill picker; do not create agent-specific copies.

| Workflow | When to read it |
|---|---|
| [pre-pr-check](.claude/skills/pre-pr-check/SKILL.md) | Before proposing or making a commit, opening/updating a PR, or reporting branch readiness; choose checks appropriate to the change |
| [add-permission](.claude/skills/add-permission/SKILL.md) | When adding, renaming or removing checked permissions or changing a role's default permissions |
| [add-translation](.claude/skills/add-translation/SKILL.md) | When adding or rewording user-facing text in any of the four locales |
| [convert-to-standard](.claude/skills/convert-to-standard/SKILL.md) | Only when the user explicitly requests that conversion and identifies its scope |

## Code Review Rules

Review the diff against this guidance, the shared rules and the relevant accepted requirements, ADRs and technical designs from [the documentation index](docs/README.md). Existing code that predates the standard is not a template: assess the changed lines and behaviour, without turning the review into a conversion of unrelated code. Proposed decisions are not accepted requirements.

Report concrete findings, most severe first:

1. Bugs and regressions, with the input or state that triggers them.
2. Security: authorization and ownership checks, validation, escaping and secrets.
3. The high-risk areas above: Money/VAT, numbering, payments, permissions/limits and PDF output.
4. Violations of shared rules in changed lines, citing the rule file.
5. Missing tests for changed behaviour.
6. Conflicts with accepted requirements or documentation updates missing because of this diff, citing the relevant document. Small changes do not require a new PRD or ADR by default.

Skip formatting handled by Pint and speculative findings that cannot be tied to the diff. Ignored `dev_docs/` files must not be necessary to understand or review a change. GitHub review posts findings on the relevant changed lines and finishes with a summary; follow the requested review language and response format.
