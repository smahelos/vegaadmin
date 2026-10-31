# Translations

## Keys

- Every user-facing string — Blade, Livewire, flash messages, validation messages, emails, PDFs, JS-visible text — is a translation key used through `__()`. Never `trans()`, `@lang` or a hardcoded string. Text a script shows is passed from Blade with `@js(__('…'))`.
- A key exists in all four locales (`lang/{cs,de,en,sk}`), same file and same path. Czech is the reference: `docker exec BILLEX-app php artisan translations:check {locale} --compare=cs` reports nothing missing, and `tests/Feature/Architecture/TranslationKeysExistTest.php` fails on any literal key missing in a locale.
- A key built at runtime takes its last segment from an enum value (`'invoices.units.' . $unit->value`), and every case of that enum has the key. Free strings never become key segments.
- Translations are for people using the app. Log messages, exception messages and console output of developer tools are plain English strings, not keys.
- Locale-dependent formats (money, numbers, dates) come from formatters, not from translation strings.

## Files and sections

- Keys are `<file>.<section>.<name>`, snake_case segments (`contacts.messages.created`, `invoices.fields.due_date`).
- One file per bounded context or entity, plural name (`contacts.php`, `payment_methods.php`), read by frontend and admin alike. A sub-entity is a section of its parent (`products.categories.fields.name`) unless it is large enough for its own file.
- Values several entities of one context share are labelled once in the context file: the party profile, address and bank account fields of businesses, contacts and document parties live in `parties.php` (`parties.fields.company_number`, `parties.bank_accounts.fields.iban`); the entity files keep only what is their own.
- Sections, used the same way in every file: `entity` (`singular`, `plural`), `fields` (labels, list columns and validation attributes), `hints`, `placeholders`, `actions`, `messages`, `validation` (only rules Laravel's standard messages cannot express), `statuses` (enum labels), and where needed `filters`, `titles`, `sections`.
- A field label lives in its entity file even when the same word appears elsewhere; a slug shown in a URL is labelled "URL klíč", a fixed system identifier "Identifikátor".
- `general.php` holds only interface words that belong to no entity: navigation, yes/no, pagination, empty states.
- `admin.php` holds only admin screens that are not an entity (menu, dashboard); a large admin area gets `lang/*/admin/<area>.php`, used as `__('admin/database.dashboard.title')`.
- A screen that is not an entity gets its own section in the file of its context (`pages.homepage_empty.*`).

## Requests and vendor texts

- Form requests name their fields through `attributes()` from `<entity>.fields.*` and keep `messages()` only for texts that differ from Laravel's standard messages in `lang/*/validation.php`.
- A package that lacks a locale gets it in `lang/vendor/<namespace>/<locale>/` (`lang/vendor/backpack/cs/permissionmanager.php`). Vendor views that use English sentences as keys are replaced by Billex views (`frontend.md`, pagination), not translated through `lang/*.json`.
