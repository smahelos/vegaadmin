---
mode: 'agent'
tools: ['terminalCommand']
description: 'Use Docker to run Artisan Commands'
---
Always use Docker container vegaadmin-app to run Artisan Commands.

Example commands to run:

* docker exec vegaadmin-app php artisan cache:clear
* docker exec vegaadmin-app php artisan config:clear
* docker exec vegaadmin-app php artisan basset:clear
* docker exec vegaadmin-app php artisan optimize:clear
