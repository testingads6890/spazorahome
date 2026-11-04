# Interior Furniture — implementation and deployment notes

This project is an existing Laravel 12 application. This patch extends its product data capture, normalized dispatch-location master, and background image processing without replacing the existing marketplace workflows.

## Modules changed

- **Location masters:** `countries`, `states`, `districts`, `cities`, and `pincodes`; JSON options endpoint at `/location-options/{level}/{parent?}`; cascading dispatch-location selectors on Admin and Vendor product forms.
- **Product data:** existing `material` is presented as **Primary Material**; existing `material_grade` is presented as **Primary Material Grade / Quality**; added `secondary_material_grade`; added `dispatch_time_days` and copied existing `lead_time_days` values into it.
- **Installation and warranty:** removed the separate assembly controls from the shared product form; retained the existing installation flag/charges and installation notes field; warranty and return terms remain grouped in one form section.
- **Product categories:** Admin and Vendor forms allow selecting a standard category or entering a custom category. The shared `CategoryInput` class creates/reuses the category without duplicating controller logic.
- **Product images:** image files are stored as before; a queued `OptimizeProductImage` job creates a WebP derivative when the PHP GD WebP encoder is available. The original remains untouched if the extension is unavailable.
- **Queue and scheduler:** database queue is the default; jobs dispatched inside transactions wait until commit. Existing stale guest-cart cleanup remains scheduled, and failed-job pruning is added.

## Database changes

The migration `2026_10_31_100000_create_location_masters_and_product_dispatch_fields.php` adds:

| Table / column | Purpose |
|---|---|
| `countries` | Country master, ISO-2 code and active flag |
| `states` | State/UT master linked to country |
| `districts` | District master linked to state |
| `cities` | City master linked to district |
| `pincodes` | One or more postal codes linked to a city |
| `products.secondary_material_grade` | Secondary material quality / grade |
| `products.dispatch_time_days` | Dispatch time in days |
| `products.country_id` … `pincode_id` | Optional normalized dispatch-location references |

The existing `products.material` and `products.material_grade` columns are reused for primary material and its grade. The existing `lead_time_days` column is retained for compatibility; its values are copied into `dispatch_time_days`. Do not drop the legacy column until all external integrations and older application versions have been retired.

### India location starter data

The migration inserts India. Run `LocationMasterSeeder` to insert India’s states and union territories:

```bash
php artisan db:seed --class="Database\Seeders\LocationMasterSeeder"
```

District, city and PIN-code rows are intentionally not fabricated. Import an authoritative postal dataset into `districts`, `cities` and `pincodes`. A city can have multiple PIN codes because the `pincodes` table has a unique `(city_id, code)` key. The cascading selector filters PIN codes by the chosen city.

### Migration steps

1. Back up the existing database and verify the backup can be restored.
2. Set `.env` database credentials; do not put production secrets in source control.
3. Install dependencies and generate the application key only for a new environment.
4. Run the Laravel migration and location seeder:

```bash
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
php artisan migrate --force
php artisan db:seed --class="Database\Seeders\LocationMasterSeeder" --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
npm ci
npm run build
```

If the application already has a production `.env` and `APP_KEY`, preserve them; do not generate a new key on an existing installation.

## Separate SQL file

`location_masters_and_product_updates.sql` is the standalone MySQL/MariaDB equivalent for the location tables, product columns, data copy, India country and state starter records.

**Run either the SQL file or the Laravel migration for the same database changes — never both.** The SQL file is a one-time schema update and should be run against a fresh backup. District, city and pincode rows still need an authoritative dataset import.

## Queue workers

`config/queue.php` defaults to the database queue and enables `after_commit`. The existing `jobs`, `failed_jobs` and `job_batches` tables are used.

Set in `.env`:

```dotenv
QUEUE_CONNECTION=database
DB_QUEUE=default
DB_QUEUE_RETRY_AFTER=120
```

A Supervisor example is provided at `deploy/supervisor-interiorfurniture-worker.conf`; replace the project path and service user for your server. Run a worker under Supervisor or another process manager:

```bash
php artisan queue:work database --queue=images,default --sleep=2 --tries=3 --timeout=120
```

After each deployment:

```bash
php artisan queue:restart
```

The image optimizer uses the `images` queue. It requires PHP GD with WebP support for conversion; if unavailable, the original image remains in place. The current source did not contain a Tally connector or a Tally/GST synchronization service, so no live accounting sync has been invented or claimed. Connect the accounting provider credentials and service adapter before dispatching those integration jobs. Existing CSV export endpoints currently stream results using lazy database iteration; they have not been silently changed into asynchronous downloads.

## Scheduler / cron

Laravel 12 schedules are declared in `routes/console.php`. Current scheduled tasks:

- `shop:cleanup-guest-carts` daily at 03:20.
- `queue:prune-failed --hours=168` daily at 03:40.

Add one cron entry on the server:

```cron
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

Ensure the cron user has the same PHP binary, environment and file permissions as the application.

## Validation and deployment checks

- Run `php artisan route:list` and confirm `location.options` is registered.
- Run the migration on a staging copy before production.
- Populate the location masters and test country → state → district → city → multiple PIN codes.
- Test custom category creation from both Admin and Vendor panels.
- Test product creation and editing with old `lead_time_days` values present.
- Confirm the worker processes the `images` queue and check `failed_jobs`.
- Run the existing PHPUnit suite after `composer install` and with the configured test database.
- Verify storage permissions, HTTPS, CSRF, role authorization, backup/restore and logs.

## Important compatibility note

The supplied source archive did not include a root `README.md` or Composer manifests. Composer manifests supplied separately have been placed at the project root for the deliverable. No `.env` or production credentials are included.
