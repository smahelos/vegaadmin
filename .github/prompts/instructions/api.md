---
mode: 'agent'
description: 'API development standards and conventions'
---


# API Development Instructions (Project-specific)

## API Structure & Routing
- Všechny API routes jsou definovány v `routes/api.php` a rozděleny do tří hlavních skupin:
  - **Admin API** (`/admin` prefix): chráněno middleware `api.require.backpack`, pouze pro autentizované admin uživatele (Backpack guard).
  - **Frontend API**: chráněno middleware `api.require.frontend`, pouze pro autentizované frontend uživatele.
  - **Public API**: veřejné endpointy, případně chráněné pouze základním middleware.
- Vždy používej správné middleware a session refresh middleware dle vzoru v `api.php`.
- Pro admin API vždy používej Backpack guard a kontroluj oprávnění dle PermissionManageru.

## Kontrolery a odpovědi
- Kontrolery umisťuj do `app/Http/Controllers/Api/`.
- Vždy používej API Resources pro formátování odpovědí.
- Odpovědi musí mít jednotný formát:
```json
{
  "success": true,
  "data": {},
  "message": "...",
  "errors": []
}
```
- Pro seznamy používej Resource Collections.
- Chraň citlivá data, do odpovědí je nikdy nezařazuj.

## Autentizace a oprávnění
- Pro admin API používej Backpack guard (`auth('backpack')`), pro frontend API standardní Laravel autentizaci.
- Oprávnění spravuj přes PermissionManager (spatie/laravel-permission, Backpack rozhraní).
- Pro veřejné endpointy nikdy neposkytuj citlivá data.

## Validace
- Vždy používej Form Requesty (`App/Requests` pro frontend, `App/Requests/Admin` pro admin API).
- Chybové odpovědi musí být ve stejném formátu jako běžné odpovědi.
- Všechny validovatelné texty musí být přeložitelné přes Laravel translations.

## Chybové stavy
- Vždy vracej správné HTTP status kódy (např. 401, 403, 422, 500).
- Chybové zprávy musí být konzistentní a přeložitelné.
- Loguj chyby dle závažnosti.

## Dokumentace
- Každý endpoint musí být zdokumentován (včetně příkladů request/response, potřebných hlaviček, parametrů a chybových stavů).
- Dokumentace musí být v souladu s aktuální implementací v `routes/api.php`.

## Další pravidla
- Nikdy neduplikuj route definice ani kontrolery.
- Při změně API vždy aktualizuj tuto dokumentaci.

---
*Tento soubor je závazný pro všechny úpravy API v projektu. Při nejasnostech vždy ověř aktuální stav v kódu a v databázi.*
