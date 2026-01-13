
---
mode: 'agent'
description: 'Docker infrastructure setup for Invoice Laravel application'
---

# Docker Infrastructure Setup (Project-specific)

- Docker infrastruktura pro tento projekt je spravována mimo workspace (`/_Data/Dockers/Production/Invoice/data/www/html`).
- Ve workspace se nenachází žádný `docker-compose.yml` ani `.env` pro Docker – vše je spravováno centrálně DevOps týmem.
- Pro všechny příkazy a práci s kontejnery používej instrukce v `docker-container.md`.
- Pokud potřebuješ změnit infrastrukturu nebo konfiguraci kontejnerů, kontaktuj správce serveru nebo DevOps.

---
*Tento soubor je pouze informativní. Pro běžnou práci používej pouze existující kontejner `INVOICE-php-fpm` a řiď se projektovou dokumentací.*
