---
paths:
  - "resources/views/**"
  - "resources/js/**"
  - "resources/css/**"
  - "app/Livewire/**"
  - "app/View/**"
  - "vite.config.js"
---

# Frontend

Public frontend: Blade layouts and components, Livewire 3 (which ships Alpine), Tailwind 4 through Vite. Admin views follow `admin.md`.

## Where behaviour lives

- Interaction that needs server data (lists, filters, sorting, pagination, forms that look things up) is a Livewire component in `app/Livewire/<Context>/`. A component is delivery: it calls an application service and renders DTOs (`architecture.md`). Its public properties bound to the URL (`#[Url]`) are untrusted input — sort columns and directions are matched against an allowlist before use.
- A paginated list uses the `App\Livewire\Shared\Paginates` trait and renders its controls with `{{ $items->links() }}`; the one pagination view is `resources/views/livewire/pagination.blade.php`. No Livewire pagination themes, no hand-built page links.
- Purely local UI state (toggles, modals, tabs, dropdowns) is Alpine (`x-data`) in the Blade component that owns it.
- Remaining JavaScript is an ES module in `resources/js/`, imported through a Vite entry point. No inline `<script>` blocks with logic in Blade, no `window.*` globals for page code, no new standalone page scripts for something Livewire or Alpine covers.
- Behaviour shared by clients and suppliers (ARES lookup, form prefill) exists once, parameterised by party type.

## Blade

- Views receive DTOs and presenter output; they never query, call models or compute domain values (totals, due dates, statuses).
- No `@php` blocks with logic; prepare the data in a presenter or a view component (`app/View/Components`).
- Every user-facing string goes through `__()` (`translations.md`). Money is formatted by `MoneyFormatterInterface` (via presenter or `<x-money>`), dates by a locale-aware formatter — never `number_format()` or a fixed `d.m.Y`.
- Links use `route()` with the current locale.

## Styling

- Tailwind utility classes; repeated UI pieces are Blade components, not copied class lists.
- No inline `style=""` attributes; a value Tailwind cannot express goes into `resources/css/app.css`.
