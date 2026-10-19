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

The `cron` container runs the Laravel scheduler (`php artisan schedule:work`).

## Development

- The `app` and `cron` containers mount the project, so code changes apply without a rebuild. Rebuild (`docker compose up -d --build app cron`) only after changing `app.dockerfile` or `WWW_UID`/`WWW_GID`.
- After a `composer.lock` change: `docker exec BILLEX-app composer install`.
- After a `package-lock.json` change: `npm ci`; `npm run dev` runs the Vite dev server.
- Tests run inside the app container on an in-memory SQLite database:

  ```bash
  docker exec BILLEX-app php artisan test --parallel
  ```
