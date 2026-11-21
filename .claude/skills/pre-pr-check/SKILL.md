---
name: pre-pr-check
description: >-
  Run the local checks CI runs before a commit or a pull request in Billex: Pint, PHP_CodeSniffer, Larastan, tests (the full
  suite only when the change can reach anything, targeted tests otherwise), the architecture guards and the
  dependency audit. Use before proposing or making a commit, before opening or updating a PR, and when the
  user asks whether the branch is ready.
---

# Pre-PR check

Runs what CI checks, so a PR does not fail on something a local run would have caught. Report results; fixing is a separate decision.

Before the code checks, assess documentation impact using [the shared policy](../../../docs/DOCUMENTATION.md) and [the index](../../../docs/README.md). Read the relevant accepted requirements and ADRs; update affected maintained documents with the change, repair moved-file links, or state briefly why no documentation change is needed. Treat Proposed decisions as proposals. Documentation-only changes need link/content checks and validation of any edited configuration syntax, without application tests or PHP checks solely for Markdown.

Store agent-created check logs, reports and helper scripts in `dev_docs/ai/tmp/<task>/`; use selectable output paths there. Fixed-output application diagnostics keep their configured paths. This convention is shared by Codex and Claude and does not change the application's runtime storage.

1. **Container.** `docker ps --filter name=BILLEX-app --format '{{.Status}}'` shows `Up`. If not, tell the user and stop — never start or rebuild containers yourself.
2. **Formatting.** `docker exec BILLEX-app vendor/bin/phpcs` and `docker exec BILLEX-app vendor/bin/pint --test` — what `.github/workflows/static-analysis.yml` runs. A failure is fixed by `vendor/bin/phpcbf` followed by `vendor/bin/pint`, never by hand (`rules/php.md`, Formatting).
3. **Static analysis.** `docker exec BILLEX-app vendor/bin/phpstan analyse --memory-limit=4G` at the level in `phpstan.neon`. Report every error; an error is fixed in the code, never with a baseline or an ignore comment. The controller list of `EloquentOutsideControllersRule` only shrinks (`rules/architecture.md`, Architecture guards).
4. **Tests.** CI runs the full suite on every push to a PR, so run locally only what the change can break:
   - **Full suite** (`docker exec BILLEX-app php artisan test --parallel --drop-databases`) when the change can reach anything: dependencies or vendor (`composer.lock`, a package upgrade), `phpunit.xml`, `tests/TestCase.php` and shared test traits, providers and bindings, middleware, routes, config, migrations and seeders, the `Shared` context and shared layouts or components, renaming or deleting classes or keys across the project, a rebase with conflicts in code.
   - **Targeted tests** otherwise: the tests of the changed class, route or view and every test that refers to it (`grep -rln '<ClassName>\|<route name>\|<translation key>' tests/`), run with `--filter` or by path. A new test runs alone and must fail without the fix.
   - **No tests** for comments, documentation, `docs/TECH_DEBT.md` and `.claude/`.

   Say which tests ran and why; note the counts and every failing test.
   **Admin document forms in a browser.** When the change reaches the admin invoice, expense or product forms — anything in `app/Infrastructure/Forms/`, `resources/js/admin/` or `resources/views/admin/fields/`, and the invoice, expense, document party, invoice item and product controllers in `app/Http/Controllers/Admin/` with their requests in `app/Http/Requests/Admin/` — also run `node tests/Browser/admin-document-forms.mjs` and `node tests/Browser/admin-invoice-items.mjs` on the host. They drive headless `/usr/bin/google-chrome` against `BILLEX_BROWSER_URL` (default `http://billex-dev.local`), read the `DevelopmentDataSeeder` data and save nothing. Before running: `npm run build` after a JS change, and `docker exec BILLEX-app php artisan db:seed --class=DevelopmentDataSeeder` when the development data is not fresh. Screenshots land in `dev_docs/ai/tmp/browser-scripts/`, next to the browser profile each run creates and deletes. CI does not run them, so a failure is reported like a failing test.
5. **Architecture guards.** `bash .claude/skills/pre-pr-check/guards.sh` — `ddd:guard` and the provider snapshot diff, compared the way CI compares them.
6. **Interpret drift.** A provider snapshot drift is either an intended change — the branch adds or removes bindings on purpose; regenerate with `ddd:providers:snapshot --write` (`architecture/baselines/README.md`) and include the file in the commit — or an unintended one that needs a code fix. Ask the user which when the diff does not make it obvious.
7. **Dependency audit.** `docker exec BILLEX-app composer audit --locked` and `npx --yes audit-ci@7.1.0 --config .github/audit-ci.jsonc` (host; `npm audit` at the high level with the allowlist of that file) — what `.github/workflows/dependency_audit.yml` runs. A new advisory can fail a branch that changed no dependency; report it with the package and the fixed version.

## Report

One block per step: passed / failed / skipped, with the failing tests, guard names, files or diff excerpts. End with a single line: ready for commit, or what blocks it. Committing and pushing still follow the user's commit rules — this skill never commits.
