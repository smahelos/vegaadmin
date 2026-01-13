---
mode: 'agent'
description: 'Project architecture and design patterns'
---


# Project Architecture and Design Patterns (Project-specific)

## Clean Architecture Principles
- Udržuj projekt maximálně čistý a přehledný, odděluj jednotlivé vrstvy (Controllers, Services, Repositories, Traits, Helpers).
- Všude používej dependency injection a service container.
- Dodržuj SOLID principy a jednotný kódovací styl.

## Form Request Organization
- **Frontend Requests**: `app/Http/Requests/` – pro veřejné/formulářové požadavky uživatelů.
- **Admin Requests**: `app/Http/Requests/Admin/` – pro admin rozhraní (Backpack).
- Vždy obsahují autorizaci, validační pravidla, atributy a chybové zprávy.
- Všechny texty musí být přeložitelné pomocí Laravel translations.

## Controller Responsibilities
- **Api Controllers**: `app/Http/Controllers/Api/` – pouze API logika, odpovědi přes API Resources, žádné business nebo datové operace.
- **Admin Controllers**: `app/Http/Controllers/Admin/` – Backpack admin rozhraní, pouze HTTP logika, business logiku deleguj do Services.
- **Frontend Controllers**: `app/Http/Controllers/Frontend/` – veřejné rozhraní, pouze HTTP logika, business logiku deleguj do Services.
- Všechny controllery musí být tenké, řeší pouze request/response, validaci a předání dat do Services/Repositories.

## Service Layer Pattern
- Business logika je vždy v `app/Services/`.
- Služby jsou stateless, zaměřené na jednu oblast, využívají dependency injection.
- Všechny složitější operace, které nejsou čistě datové, patří do Services.

## Repository Pattern
- Datové operace jsou v `app/Repositories/`.
- Repositáře abstrahují práci s databází, používají Eloquent modely.
- Implementuj rozhraní pro lepší testovatelnost.

## Trait Usage
- Společná logika pro formulářová pole je v `app/Traits/` (např. BankFormFields, InvoiceFormFields, ...).
- Traity slouží i pro sdílené chování modelů, validace a pomocné metody controllerů.

## Helper / Support Utilities Organization
- Původní globální `app/Helpers/` byl nahrazen doménově orientovanými Support/Helper třídami.
- Sdílené pomocné utility patří do `App/Domain/Shared/Support/` (např. `DateHelper`).
- Uživatelsky specifické utility patří do `App/Domain/User/Support/` (např. `UserHelpers`).
- Nepřidávej nové soubory do kořenové složky `app/Helpers/` (guard pravidlo zabrání regresi).
- Zachovej čisté (side‑effect free) statické metody nebo zvaž Value Objects / Services podle složitosti.

## Permission & Admin Layer
- Pro admin část vždy používej Backpack a PermissionManager (spatie/laravel-permission, Backpack rozhraní).
- Oprávnění a role spravuj pouze přes PermissionManager.

## Translations & Consistency
- Všechny validace, chybové zprávy, atributy a popisky musí být přeložitelné (Laravel translations v `lang/`).
- Kód i komentáře vždy v angličtině.

---
*Tento soubor je závazný pro architekturu projektu. Při nejasnostech ověř aktuální stav v kódu a dokumentaci.*
