---
mode: 'agent'
description: 'Check databse connection with MCP'
---

# Database Connection Instructions (Project-specific)

## Where to check database connection settings
- **Primary configuration:** `.env` file (DB_CONNECTION, DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD)
- **Laravel config:** `config/database.php` (uses values from `.env`)
- **MCP server:** `.vscode/mcp.json` (section `servers.mysql`)

## How to verify and troubleshoot
1. Zkontroluj hodnoty v `.env` a ujisti se, že odpovídají skutečnému serveru/databázi.
2. Pro MCP server ověř údaje v `.vscode/mcp.json` (host, port, user, password, database).
3. Pro SQLite je cesta k databázi v `.env` (`DB_DATABASE`) a v `config/database.php`.
4. Po změně připojení vždy restartuj aplikaci a/nebo příslušný kontejner.
5. Nikdy neukládej hesla a citlivé údaje do repozitáře (používej `.env`, `.env.example` bez citlivých údajů).

## Další poznámky
- Pokud připojení nefunguje, zkontroluj logy aplikace a správnost údajů.
- Pro testovací prostředí používej `.env.testing`.
- Vždy ověř aktuální stav v kódu a dokumentaci.

---
*Tento soubor je závazný pro správu databázového připojení. Při nejasnostech ověř aktuální stav v kódu a konfiguraci.*
