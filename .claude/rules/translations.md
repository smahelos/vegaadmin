# Translations

- Every user-facing string — Blade, Livewire, flash messages, validation messages, emails, PDFs, JS-visible text — is a translation key used through `__()`. Never `trans()`, `@lang` or a hardcoded string.
- A key exists in all four locales (`lang/{cs,de,en,sk}`), same file and same path. Czech is the reference: `docker exec BILLEX-app php artisan translations:check {locale} --compare=cs` must report no missing keys.
- Keys are grouped by context and purpose: `<file>.<section>.<name>` (`clients.messages.created`, `invoices.fields.due_date`), snake_case segments. A file per bounded context or screen area; shared words in `general.php`.
- Translations are for people using the app. Log messages, exception messages and console output of developer tools are plain English strings, not keys.
- Locale-dependent formats (money, numbers, dates) come from formatters, not from translation strings.
