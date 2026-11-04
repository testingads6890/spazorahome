# Interior Furniture — implementation and deployment notes

This project is an existing Laravel 12 application. This patch extends its product data capture, normalized dispatch-location master, and background image processing without replacing the existing marketplace workflows.

## Changelog: 4 Nov 2026 (aaj ka kaam)

**Product type, category aur subcategory**
- Naya `product_types` table. Har top-level category ek type ke neeche hai (Furniture, Decor, Lighting, Textiles, Outdoor, Kids, Kitchen & Dining).
- Admin, vendor aur bulk enquiry mein ek hi picker: product type chunne par uski categories, aur category chunne par subcategories, dono multiple selection ke saath.
- "+ Add" buttons: naya product type, aur naya subcategory (chuni hui category ke neeche). Dono turant dropdown mein aate hain.
- Product save par chuni hui categories aur subcategories `category_product` mein save hoti hain.
- Storefront category filter ab multi-category links bhi dekhta hai.
- Starter subcategories (51) main categories ke neeche add hui hain. Admin mein edit ya delete ho sakti hain.

**Units aur PIN codes**
- Units database mein hain (`product_units`). Default: Running ft, Running metre, Sq ft, Sq m, Roll, Box, Piece, Set, Pair. "+ Add new unit" se naya unit bana sakte hain.
- Har unit ke saath length, width, height aur measuring unit (ft, m, cm) ke inputs.
- Ek product ke liye ek se zyada PIN codes chun sakte hain.
- Location: sirf country required (India default). State, district, city, PIN optional. Matching sabse narrow level par hota hai.

**Bulk enquiry**
- Public page `/bulk-enquiry`, header mein link ke saath.
- Product type, categories, subcategories, WhatsApp number aur other number.
- Admin menu mein "Bulk enquiries": list, detail, status aur note.

**Reviews aur contact**
- Koi bhi signed-in customer product page se review de sakta hai. Admin approve karega.
- "Review us on Google" button (`SHOP_GOOGLE_REVIEW_URL`).
- Homepage par customer reviews slider (auto-scroll, arrows, mobile/tablet/desktop), customer ki photo ya initial avatar ke saath.
- Sticky right-side buttons: WhatsApp (animated), Instagram, Facebook, Email, Call us.

**Branding aur design**
- Brand naam "SpazoraHome". Footer mein "Designed & Developed by Srkwebapp Innovations LLP" link ke saath.
- Copyright year apne-aap current year dikhata hai.
- Logo file na mile to default logo dikhta hai. Admin ke primary aur secondary colours poori site par lagte hain.
- Footer naye layout mein hai (brand aur about, teen link columns, address, bottom bar).
- Store pages default mein band hain (`SHOP_STORES_ENABLED=false`).

**Registration aur profile**
- Customer sign-up, customer profile aur vendor (seller) application mein WhatsApp aur other number.

**Category admin**
- Category list mein Sr. No., Category ID aur image dikhte hain. Add aur edit mein image upload hota hai.

**Backup aur deploy**
- Poora project + MySQL database ka zip server par: `/home/u966147115/backups/interiorfurniture-backup-2026-11-04-1102am.zip` (`.env` isme nahi hai).
- Code GitHub par: `testingads6890/spazorahome` (commit `bfd3ebb`).

**Tests:** 554 tests pass.

---

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
