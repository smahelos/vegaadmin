---
paths:
  - "app/Http/Controllers/Admin/**"
  - "app/Http/Requests/Admin/**"
  - "app/Infrastructure/Forms/**"
  - "routes/backpack/**"
  - "resources/views/admin/**"
  - "resources/views/vendor/backpack/**"
  - "config/backpack/**"
---

# Admin (Backpack)

Backpack 7 (free packages only — no PRO licence) runs the admin.

## CRUD controllers

- Backpack CRUD works on Eloquent models: `CRUD::setModel()`, list columns, fields and filters may reference models and relations. That is the only place delivery touches models (`architecture.md`).
- Only reference data is persisted through models. Anything with an owner or with domain rules (invoices, payments, subscriptions) overrides `store()` / `update()` and runs a Command through the gate, so admin writes obey the same numbering, totals, events and entity limits as the frontend.
- Field definitions live in `app/Infrastructure/Forms/<Context>/<Entity>FormFields`; the controller only adds them. Validation through the admin form request `App\Http\Requests\Admin\<Entity>Request` (`http.md`, Form requests).
- Operations and imports at the top with `use`; columns and fields defined explicitly, never `CRUD::setFromDb()`.
- Every CRUD operation is denied by default in `setup()` and allowed by its own `Permission` case, the same one the frontend uses (`<entity>.view` for List/Show, `<entity>.create`, `<entity>.update`, `<entity>.delete`; `permissions.md`, Where permissions are checked).

## Own field, column and filter types

- Billex's own types live in `resources/views/admin/{fields,columns,filters}`, registered first in `view_namespaces` (`config/backpack/crud.php`), so they win over a package view of the same name. They are named without a prefix and keep Backpack's type names (`select2`, `relationship`, `datepicker`) — Backpack picks some of them automatically for relations.
- Shared partials of these types live in `resources/views/admin/inc/` (`select2_assets`, `datepicker_assets`, `summernote_theme`, `language-switcher`).
- `resources/views/vendor/backpack/` holds only true overrides of installed package templates. Each override is compared with the package view before a Backpack upgrade.
- No template copied from Backpack PRO — the repository is public and must not ship paid code.

## Assets and styling

- Third-party JS/CSS load through Basset with a pinned version in the URL (`@basset('https://cdn.jsdelivr.net/npm/air-datepicker@3.6.0/…')`), never by copying files into `public/packages`.
- Colours and spacing come from Tabler CSS variables (`--tblr-*`), so light and dark mode (`data-bs-theme`) work without extra rules.
- Datepicker language and formats come from `lang/*/admin.php` key `datepicker`.
