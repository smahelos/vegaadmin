---
mode: 'agent'
description: 'Laravel 12 specific instructions and best practices'
---



# Laravel 12 Framework Instructions (Project-specific)

> **This project uses Test-Driven Development (TDD). All new or changed Laravel code must be covered by Unit and Feature tests written before or together with implementation. Never merge code without tests.**

## Framework Version
- Vždy používej Laravel 12 a jeho aktuální syntax a API.
- Dodržuj konvence a best practices Laravel 12.
- Všechny úpravy musí být v souladu s projektovými pravidly (viz copilot-instructions.md).

## Code Standards
- Všechen kód, komentáře a dokumentace musí být v angličtině.
- Používej popisné názvy proměnných a metod.
- Dodržuj PSR-4 autoloading.
- Všude používej type hints a return types.

## Laravel Features to Use
- Vždy používej vestavěný překladový systém Laravel 12 (multilingual support).
- Všechny validace, chybové zprávy a atributy musí být přeložitelné (viz `lang/`).
- Využívej Laravel 12 validation system, Eloquent ORM, form requests, service container a dependency injection.
- Vždy používej moderní Laravel 12 funkce (attributes, policies, API resources, atd.).

## File Organization
- Controllers: `app/Http/Controllers/` (Frontend), `app/Http/Controllers/Admin/` (Backend), `app/Http/Controllers/Api/` (API)
- Requests: `app/Http/Requests/` (Frontend), `app/Http/Requests/Admin/` (Backend)
- Models: `app/Models/`
- Services: `app/Services/`
- Repositories: `app/Repositories/`
- Traits: `app/Traits/`
- Helpers: `app/Helpers/`

## Translation System
- Vždy používej `__('key')` pro všechny překladatelné řetězce.
- Překlady ukládej do `lang/{locale}/` (cs, de, en, sk).
- Používej dot notaci pro vnořené klíče.

## Testing & Project Conventions
- Všechny nové třídy a změny musí být pokryty testy dle projektové struktury a pravidel.
- **Testy musí být vždy psány před implementací nebo současně s ní (TDD).**
- Všechny testy, validace a překlady musí být v souladu s copilot-instructions.md a další projektovou dokumentací.

---
*Tento soubor je závazný pro práci s Laravel 12 v tomto projektu. Při nejasnostech ověř aktuální stav v kódu a dokumentaci.*
