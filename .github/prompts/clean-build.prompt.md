---
mode: 'agent'
tools: ['terminalCommand']
description: 'Clear Cache and Build Laravel 12 Application'
---
Clear Cache and Build Application.

Open terminal and perform next comands:

* cd /_Data/Dockers/Production/Invoice/data/www/html
* docker exec INVOICE-php-fpm php artisan cache:clear
* docker exec INVOICE-php-fpm php artisan config:clear
* docker exec INVOICE-php-fpm php artisan view:clear
* docker exec INVOICE-php-fpm php artisan route:clear
* docker exec INVOICE-php-fpm php artisan optimize
* npm run build
