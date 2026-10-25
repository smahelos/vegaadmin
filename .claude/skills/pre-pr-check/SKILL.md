---
name: pre-pr-check
description: >-
  Run the local checks CI runs before a commit or a pull request in Billex: Pint, PHP_CodeSniffer, Larastan, tests (the full
  suite only when the change can reach anything, targeted tests otherwise), the architecture guards, baseline drift
  and the dependency audit. Use before proposing or making a commit, before opening or updating a PR, and when the
  user asks whether the branch is ready.
---

# Pre-PR check

Runs what CI checks, so a PR does not fail on something a local run would have caught. Report results; fixing is a separate decision.

1. **Container.** `docker ps --filter name=BILLEX-app --format '{{.Status}}'` shows `Up`. If not, tell the user and stop — never start or rebuild containers yourself.
2. **Formatting.** `docker exec BILLEX-app vendor/bin/phpcs` and `docker exec BILLEX-app vendor/bin/pint --test` — what `.github/workflows/static-analysis.yml` runs. A failure is fixed by `vendor/bin/phpcbf` followed by `vendor/bin/pint`, never by hand (`rules/php.md`, Formatting).
3. **Static analysis.** `docker exec BILLEX-app vendor/bin/phpstan analyse --memory-limit=4G` at the level in `phpstan.neon`. Report every error; an error is fixed in the code, never with a baseline or an ignore comment.
4. **Tests.** CI runs the full suite on every push to a PR, so run locally only what the change can break:
   - **Full suite** (`docker exec BILLEX-app php artisan test --parallel --drop-databases`) when the change can reach anything: dependencies or vendor (`composer.lock`, a package upgrade), `phpunit.xml`, `tests/TestCase.php` and shared test traits, providers and bindings, middleware, routes, config, migrations and seeders, the `Shared` context and shared layouts or components, renaming or deleting classes or keys across the project, a rebase with conflicts in code.
   - **Targeted tests** otherwise: the tests of the changed class, route or view and every test that refers to it (`grep -rln '<ClassName>\|<route name>\|<translation key>' tests/`), run with `--filter` or by path. A new test runs alone and must fail without the fix.
   - **No tests** for comments, documentation, `docs/TECH_DEBT.md` and `.claude/`.

   Say which tests ran and why; note the counts and every failing test.
5. **Architecture guards.** `bash .claude/skills/pre-pr-check/guards.sh` — `ddd:guard`, provider snapshot diff, and drift of the controller-thinness, frontend-Eloquent and API-Eloquent baselines, each compared the way CI compares them.
6. **Interpret drift.** A baseline drift is either an intended change — the branch adds or removes controller code or bindings on purpose; regenerate per `architecture/baselines/README.md` (provider snapshot: `ddd:providers:snapshot --write`) and include the file in the commit — or an unintended one that needs a code fix. Ask the user which when the diff does not make it obvious.
7. **Dependency audit.** `docker exec BILLEX-app composer audit --locked` and `npm audit --audit-level=high --package-lock-only` (host) — what `.github/workflows/dependency_audit.yml` runs. A new advisory can fail a branch that changed no dependency; report it with the package and the fixed version.

## Report

One block per step: passed / failed / skipped, with the failing tests, guard names, files or diff excerpts. End with a single line: ready for commit, or what blocks it. Committing and pushing still follow the user's commit rules — this skill never commits.
