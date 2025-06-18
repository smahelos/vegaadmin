---
mode: 'agent'
tools: ['terminalCommand']
description: 'Docker container usage and testing commands for Laravel application'
---

# 🚨 CRITICAL: Docker Container Execution Rules

## All Artisan Commands Must Use Docker Container
Always use Docker container `vegaadmin-app` to run Artisan Commands.
**NEVER run artisan commands directly on host system.**

## Testing Commands - NEVER Use Verbose Options
- **NEVER use `-v` or `--verbose` options** when running unit tests
- These options cause "Unknown option" error in our testing environment
- Use standard `php artisan test` without verbose flags

## ✅ Correct Command Examples

### Testing Commands
```bash
# Run specific test file
docker exec vegaadmin-app php artisan test tests/Unit/Http/Requests/Admin/InvoiceRequestTest.php

# Run all tests in directory
docker exec vegaadmin-app php artisan test tests/Unit/Http/Requests/Admin/

# Run tests with filter
docker exec vegaladmin-app php artisan test --filter=RequestTest

# Run all tests
docker exec vegaadmin-app php artisan test
```

### Standard Artisan Commands
```bash
# Cache commands
docker exec vegaadmin-app php artisan cache:clear
docker exec vegaadmin-app php artisan config:clear
docker exec vegaadmin-app php artisan basset:clear
docker exec vegaadmin-app php artisan optimize:clear

# Database commands
docker exec vegaadmin-app php artisan migrate
docker exec vegaadmin-app php artisan migrate:rollback
docker exec vegaadmin-app php artisan db:seed

# Queue commands
docker exec vegaadmin-app php artisan queue:work
docker exec vegaadmin-app php artisan queue:restart
```

## ❌ Wrong Commands - Will Cause Errors
```bash
# Wrong - will cause "Unknown option" error
docker exec vegaadmin-app php artisan test file.php -v
docker exec vegaadmin-app php artisan test file.php --verbose

# Wrong - missing docker container
php artisan test
php artisan migrate
php artisan cache:clear
```

## Container Information
- **Container Name**: `vegaadmin-app`
- **Working Directory**: `/_Data/Dockers/Production/vegaadmin`
- **Environment**: Production-ready Laravel 12 setup
