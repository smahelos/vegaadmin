# Billex

Invoicing application: Laravel 12, Backpack admin panel, a public frontend (Blade, Livewire, Alpine, Tailwind), GoPay payments, PDF invoices with QR payment codes. UI languages: Czech, German, English, Slovak.

## Local installation (Docker)

### Requirements

- Docker with the Compose plugin
- Node.js 20 or newer with npm (frontend assets are built on the host)
- Free host ports 80 (web), 3306 (MySQL), 8888 (phpMyAdmin), 1025 and 8025 (MailHog)

### Steps

1. Create the environment file and fill in the empty passwords (`DB_PASSWORD`, `DB_ROOT_PASSWORD`):

   ```bash
   cp .env.example .env
   ```

   If your host user is not `1000:1000` (`id -u`, `id -g`), set `WWW_UID` and `WWW_GID`: the project is bind-mounted into the containers and `www-data` gets your UID/GID.

2. Point the host name of `APP_URL` to your machine, e.g. in `/etc/hosts`:

   ```
   127.0.0.1 billex-dev.local
   ```

3. Create the Redis data directory (`data/` is mounted into the containers, `data/redis` is not in git) and start the stack:

   ```bash
   mkdir -p data/redis
   docker compose up -d --build
   ```

   Wait until the database is healthy (`docker compose ps`); MySQL needs a while on the first start.

4. Install the PHP dependencies, generate the application key and create the database:

   ```bash
   docker exec BILLEX-app composer install
   docker exec BILLEX-app php artisan key:generate
   docker exec BILLEX-app php artisan migrate --seed
   ```

   The container names start with `CONTAINER_PREFIX` from `.env` (`BILLEX-app`).

5. Download the admin assets that Basset loads from CDNs (Backpack theme, air-datepicker) to local storage:

   ```bash
   docker exec BILLEX-app php artisan basset:cache
   ```

6. Build the frontend assets:

   ```bash
   npm ci
   npm run build
   ```

7. Create the first admin user and give it the `admin` role:

   ```bash
   docker exec BILLEX-app php artisan backpack:user --name="Admin" --email=admin@example.com --password='change-me'
   docker exec BILLEX-app php artisan user:permissions assign-role --user=admin@example.com --role=admin
   ```

### Services

| Service | URL |
|---|---|
| Application | http://billex-dev.local |
| Admin panel | http://billex-dev.local/admin |
| MailHog (outgoing e-mails) | http://localhost:8025 |
| phpMyAdmin | http://localhost:8888 |

The `cron` container runs the Laravel scheduler (`php artisan schedule:work`), the `queue` container the queue worker (`php artisan queue:work` on the `database` connection).

## Development

- The `app`, `cron` and `queue` containers mount the project, so code changes apply without a rebuild; the queue worker keeps the code in memory and picks up changes after `docker exec BILLEX-app php artisan queue:restart`. Rebuild (`docker compose up -d --build app cron queue`) only after changing `app.dockerfile` or `WWW_UID`/`WWW_GID`.
- After a `composer.lock` change: `docker exec BILLEX-app composer install`.
- After a `package-lock.json` change: `npm ci`; `npm run dev` runs the Vite dev server.
- In `local`, `php artisan migrate:fresh --seed` rebuilds the development database with reference data and development accounts (`database/seeders/DevelopmentDataSeeder.php`, password `backpack`): `admin@billex.test` and `backend@billex.test` for the admin panel, `user1@` / `user2@billex.test` (`frontend_user`) and `plus1@` / `plus2@billex.test` (`frontend_user_plus`) with a year of invoices, clients and expenses.
- Tests run inside the app container on MySQL, in the `invoice_testing` database next to the development one; `phpunit.xml` names the database, the connection comes from `.env` through `docker-compose.yaml`. Create the database once and let the application user manage every `invoice_testing*` database (a parallel run creates `invoice_testing_test_N`):

  ```bash
  docker exec -it BILLEX-db mysql -u root -p -e "CREATE DATABASE invoice_testing; GRANT ALL ON \`invoice\\_testing%\`.* TO '<DB_USERNAME>'@'%';"
  docker exec BILLEX-app php artisan test --parallel --drop-databases
  ```

  `--drop-databases` removes the `invoice_testing_test_N` databases after the run.
