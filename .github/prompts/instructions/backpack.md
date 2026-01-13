---
mode: 'agent'
description: 'Backpack 6.8 admin panel instructions and conventions'
---


# Backpack 6.8 Admin Panel Instructions (Project-specific)

## Version & Conventions
- Vždy používej Backpack 6.8 a jeho aktuální syntax a komponenty.
- Dodržuj konvence a best practices Backpack 6.8.
- Všechny úpravy musí být v souladu s aktuálními konfiguračními soubory v `config/backpack/`.

## CRUD Controllers & Requests
- Všechny admin controllery dědí z `CrudController` a jsou v `app/Http/Controllers/Admin/`.
- Pro každý admin formulář používej request třídu z `App/Http/Requests/Admin/` (autorizace, validace, překlady).
- Všechna pole načítej přes vlastní traity v `App/Traits/` (např. BankFormFields, InvoiceFormFields, ...).

## Permissions & Authentication
- Vždy používej Laravel-Backpack/PermissionManager (spatie/laravel-permission, Backpack rozhraní).
- Všechna oprávnění a role spravuj pouze přes PermissionManager, používej správný guard (`backpack`).
- Oprávnění kontroluj v controllerech i v pohledech.
- Všechny admin requesty a validace musí být vázány na Backpack guard.

## Field Types, Operations & Validation
- Používej Backpack field types pro formuláře a column types pro seznamy.
- Implementuj vlastní operace pouze pokud je to nutné, jinak využívej vestavěné.
- Všechny validace a chybové zprávy musí být přeložitelné (Laravel translations).

## Admin Routes
- Admin routes definuj v `routes/backpack/` (např. custom.php).
- Vždy používej Backpack middleware a prefix (`admin`).
- Dodržuj Backpack URL konvence.

## Views & Templates
- Vždy rozšiřuj Backpack base šablony a používej Backpack blade komponenty.
- Vlastní úpravy ukládej do `resources/views/vendor/backpack/`.
- Dodržuj Backpack CSS/JS konvence.

---
*Tento soubor je závazný pro všechny úpravy admin rozhraní. Při nejasnostech ověř aktuální stav v kódu, konfiguraci a dokumentaci.*
