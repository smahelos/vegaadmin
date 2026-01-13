---
mode: 'agent'
tools: ['terminalCommand']
description: 'Docker container usage and testing commands for Laravel application'
---


# 🚨 CRITICAL: Docker Container Execution Rules (Project-specific)

## All Artisan Commands Must Use Docker Container
- Vždy používej Docker kontejner `INVOICE-php-fpm` pro všechny Artisan příkazy a testy.
- **NEVER** spouštěj artisan příkazy přímo na host systému.
- Všechny příkazy spouštěj z kořenového adresáře projektu: `/_Data/Dockers/Production/Invoice/data/www/html`

## Testing Commands - NEVER Use Verbose Options
- **NEVER** používej `-v` nebo `--verbose` při spouštění testů (způsobí chybu "Unknown option").
- Vždy používej pouze standardní `php artisan test` bez verbose flagů.

## ✅ Correct Command Examples

### Testing Commands
```bash
# Run specific test file
docker exec INVOICE-php-fpm php artisan test tests/Unit/Http/Requests/Admin/InvoiceRequestTest.php

# Run all tests in directory
docker exec INVOICE-php-fpm php artisan test tests/Unit/Http/Requests/Admin/

# Run tests with filter
docker exec INVOICE-php-fpm php artisan test --filter=RequestTest

# Run all tests
docker exec INVOICE-php-fpm php artisan test
```

### Standard Artisan Commands
```bash
# Cache commands
docker exec INVOICE-php-fpm php artisan cache:clear
docker exec INVOICE-php-fpm php artisan config:clear
docker exec INVOICE-php-fpm php artisan basset:clear
docker exec INVOICE-php-fpm php artisan optimize:clear

# Database commands
docker exec INVOICE-php-fpm php artisan migrate
docker exec INVOICE-php-fpm php artisan migrate:rollback
docker exec INVOICE-php-fpm php artisan db:seed

# Queue commands
docker exec INVOICE-php-fpm php artisan queue:work
docker exec INVOICE-php-fpm php artisan queue:restart
```

## ❌ Wrong Commands - Will Cause Errors
```bash
# Wrong - will cause "Unknown option" error
docker exec INVOICE-php-fpm php artisan test file.php -v
docker exec INVOICE-php-fpm php artisan test file.php --verbose

# Wrong - missing docker container
php artisan test
php artisan migrate
php artisan cache:clear
```

## Container Information
- **Container Name**: `INVOICE-php-fpm`
- **Working Directory**: `/_Data/Dockers/Production/Invoice/data/www/html`
- **Environment**: Production-ready Laravel 12 setup
- V projektu není `docker-compose.yml`, kontejnery jsou spravovány externě (ručně nebo CI/CD).
- Všechny změny v kontejneru (např. instalace závislostí) prováděj přes správce kontejneru, ne přímo na hostu.

---
*Tento soubor je závazný pro práci s Docker kontejnery a testováním. Při nejasnostech ověř aktuální stav v kódu, dokumentaci a infrastruktuře.*
