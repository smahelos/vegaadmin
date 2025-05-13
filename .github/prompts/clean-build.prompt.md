---
mode: 'agent'
tools: ['terminalCommand']
description: 'Clear Cache and Build Laravel 12 Application'
---
Clear Cache and Build Application.

Open terminal and perform next comands:

* cd /_Data/Dockers/Production/vegaadmin
* docker exec vegaadmin-app php artisan cache:clear
* docker exec vegaadmin-app php artisan config:clear
* docker exec vegaadmin-app php artisan view:clear
* docker exec vegaadmin-app php artisan route:clear
* docker exec vegaadmin-app php artisan optimize
* npm run build
