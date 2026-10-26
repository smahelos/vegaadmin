---
name: add-translation
description: >-
  Add or change a translation key in all four Billex locales (cs, de, en, sk) and verify none is
  missing. Use whenever work introduces or rewords a user-facing string — a Blade label, flash or
  validation message, email, PDF text, Livewire or JS-visible text — not only when asked to
  "add a translation".
---

# Add a translation

Called mid-task with the key's purpose and Czech text. Conventions (key shape, which strings are translated): `rules/translations.md`.

1. **Choose the key.** `<file>.<section>.<name>`, snake_case segments, in the file of the bounded context or screen area (`lang/cs/contacts.php` → `contacts.messages.created`); words shared across screens go to `general.php`. Search `lang/cs/` first (`grep -rn` on the Czech text and on likely key names) and reuse an existing key with the same meaning instead of adding a near-duplicate.
2. **Write it in all four locales.** Add the key at the same path in `lang/cs`, `lang/de`, `lang/en` and `lang/sk`, keeping the files' existing order and nesting. Translate from the Czech text yourself; `en` is British English. Placeholders (`:name`, `:count`) and pluralisation (`|`) stay identical in every locale.
3. **Use it.** `__('file.section.name')` in PHP/Blade; never `trans()` or `@lang`.
4. **Verify.** `docker exec BILLEX-app php artisan translations:check de --compare=cs`, then `en` and `sk`. Done when all three report no keys missing for the files you touched; keys reported missing elsewhere are existing debt (`docs/TECH_DEBT.md`, Translations) — mention them, do not fix them here.

Return to the calling task with the key name.
