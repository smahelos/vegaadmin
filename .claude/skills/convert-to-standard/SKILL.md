---
name: convert-to-standard
description: Convert one bounded context or one docs/TECH_DEBT.md item of Billex to the standard in .claude/rules.
disable-model-invocation: true
---

# Convert to the standard

Brings one scope — a bounded context (`Party`, `Invoice`, …) or one item of `docs/TECH_DEBT.md` — fully in line with `.claude/rules/`. The user names the scope; one scope is one branch and one PR.

## 1. Inventory

Read every rule in `.claude/rules/` and `.claude/references/layers.md`, and every `docs/TECH_DEBT.md` item that touches the scope. Then go through every file of the scope — Domain, Application, Infrastructure, models, controllers, form requests, Livewire, views, JS, routes, translations, tests — and list each violation as `file:line — rule — what is wrong`.

Done when every file of the scope has been checked against every rule, including the files that turn out to be clean. Callers outside the scope that must change with it (a renamed class, a removed method) are listed too.

## 2. Plan

Present the inventory and an ordered plan to the user before changing code. Default order, inner layers first so each step compiles against finished ones:

1. Domain — value objects, data classes, ports, exceptions, events
2. Infrastructure — repositories, mappers, models, migrations
3. Application — use cases
4. Delivery — form requests, controllers, resources, Livewire, routes, views, JS
5. Tests — rewrite to behaviour tests, delete structural ones

Name every decision the rules leave open (a class name the rules do not dictate, merging business/contact code, behaviour changes) and get it settled before converting.

## 3. Convert

Work through the plan step by step. After each step, run the tests of what it touched (`docker exec BILLEX-app php artisan test <dir or --filter>`); a step is done when they pass.

- Before deleting a file, show the user what used it (git history), why it is no longer needed and why removing it is safe, and wait for the decision — one file at a time.
- Removed bindings and contracts: update the context's provider and refresh the snapshot (`docker exec BILLEX-app php artisan ddd:providers:snapshot --write`).
- Renamed routes, permissions, translation keys and columns: update every reference (`grep -rn` across `app`, `resources`, `routes`, `lang`, `tests`, `database`), new permissions through the `add-permission` skill, new strings through `add-translation`.
- Violations outside the scope that you notice along the way go to the user and to `docs/TECH_DEBT.md`, not into this branch.

## 4. Close

1. Update `docs/TECH_DEBT.md`: remove resolved items, shrink partly resolved ones to what remains (paths and counts).
2. If the conversion changed controller code on purpose, regenerate the affected baselines per `architecture/baselines/README.md`.
3. Run the `pre-pr-check` skill.
4. Report: what was converted, what was deleted, decisions made, remaining items. Committing follows the user's commit rules.

Done when the inventory's violations are all resolved or recorded in `docs/TECH_DEBT.md`, and `pre-pr-check` is green.
