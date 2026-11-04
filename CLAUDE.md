# Interior Furniture / SpazoraHome: project context

Read this first. It explains what the project is, how it runs, how it is deployed, and what is still open.

## What it is
- Laravel 12 marketplace (PHP 8.2+), brand **SpazoraHome**. Three kinds of users: customer (and guest), seller (vendor), and admin (with staff roles: vendor manager, product manager, order manager, support manager, support executive).
- Live site: https://interiorfurniture.khobargarden.com/public/ (also https://khobargarden.com/interiorfurniture/public/).
- Live folder on the server: `public_html/interiorfurniture` (hosting: Hostinger). Database: MySQL `u966147115_interiorfurnit`.
- GitHub repo: `testingads6890/spazorahome` (public, so never commit customer data, `.env` or secrets).

## Setup (local or new machine)
1. `git clone https://github.com/testingads6890/spazorahome.git`
2. Create `.env` from `.env.example` (not in git). Set `DB_*`, `APP_URL`, and optionally `SHOP_*`, `CONTACT_*`, `SHOP_STORES_ENABLED=false`, `PAYMENT_MODE=test`, `COURIER_MODE=test`.
3. `composer install` then `php artisan key:generate`.
4. Import `database/schema-and-reference-2026-11-04.sql` into an empty MySQL database (structure plus categories, product types, units, locations). Then `php artisan migrate --force`.
5. `php artisan storage:link`, `npm ci && npm run build` (optional for assets).
6. Tests: `php vendor/bin/phpunit` (uses in-memory SQLite, does not touch the real DB). Last run: 554 passed, 10 skipped, 1 incomplete (known `&nbsp;` trailing paragraph bug in `HtmlTest`).

## Important rules and decisions
- **Stores are off** by default (`SHOP_STORES_ENABLED=false`). Store links, pages and sitemap entries are hidden.
- **Payments** (Razorpay, Cashfree) and **couriers** (Shiprocket, Blue Dart) run in **test mode** until live keys are set in `.env` (`PAYMENT_MODE=live`, `COURIER_MODE=live`).
- **Delivery PIN logic:** a product with a dispatch location (PIN, city, district or state) is only deliverable to a PIN inside it. A product without one is sold across India. The narrowest chosen level decides. Country alone does not limit.
- **Category model:** product type → categories (top-level, `categories.product_type_id`) → subcategories (`parent_id`). Products link to several categories through `category_product`; `category_id` is the main category.
- **Units:** `product_units` table (defaults: Running ft, Running metre, Sq ft, Sq m, Roll, Box, Piece, Set, Pair). Every unit uses the same inputs: length, width, height and "measured in".
- **Branding:** "SpazoraHome" everywhere. Logo comes from Admin > Theme (`site_logo`); if the file is missing, the default `public/images/logo.png` is used. Primary and secondary colours come from Admin > Theme and are applied via CSS variables (`--accent`, `--brand`, `--brand-dark`, `--brand-rgb`).
- **Reviews:** any signed-in customer can review a product (admin approves). Google review button uses `SHOP_GOOGLE_REVIEW_URL`.
- **Floating contact buttons** (WhatsApp, Instagram, Facebook, Email, Call) are always shown. Placeholder links until real ones are set in `.env` (`CONTACT_WHATSAPP`, `CONTACT_INSTAGRAM`, `CONTACT_FACEBOOK`, `SHOP_CONTACT_EMAIL`, `SHOP_CONTACT_PHONE`).

## Where things live
- Product forms (admin and seller) and bulk enquiry use one picker: `resources/views/partials/category-picker.blade.php`, data in `app/Support/CategoryPicker.php`.
- Seller sign-up form: `resources/views/auth/vendor-register.blade.php`, saved by `app/Http/Controllers/Auth/RegisterController.php`.
- Extra routes that were kept out of `routes/web.php` are in `routes/delivery.php` (delivery PIN, product types, subcategories, unit, product reviews, bulk enquiry, admin bulk enquiries).
- Search filters: `app/Http/Controllers/StorefrontController.php` and `resources/views/search.blade.php`.
- Footer and header: `resources/views/components/footer.blade.php`, `header.blade.php`. Floating buttons: `components/floating-contact.blade.php`.
- Admin side menu: `app/Support/PanelNav.php`.

## Backups
- Server backups are zips in `/home/u966147115/backups/` (project plus full MySQL dump). They contain customer data, so they are private and never go to GitHub.
- Before risky database changes, take a `mysqldump` first.

## Pending (open items)
1. **Storefront search filter:** product type dropdown → its categories → subcategories, on the search page (was in progress; not started in code yet).
2. **26 states' PIN codes:** official India Post PIN file still needed (`php artisan location:import file.csv`). Until then, products in those states cannot be saved with a PIN.
3. **Browser checks** (not done automatically): product forms cascade, bulk enquiry, registrations on mobile, sticky buttons, logo, reviews slider. Test on phone, tablet and desktop.
4. **Real contact links:** Instagram, Facebook, email, phone, WhatsApp, Google review URL (put them in `.env`).
5. **Logo:** upload again in Admin > Theme (server has no custom logo file yet).
6. **Demo data:** remove dummy products, orders and users from live DB before launch (the factory products are deactivated; orders and demo accounts still exist).
7. **Live payments and couriers:** keys and mode switch when going live; test real payment and refund first.
8. **Cleanup on server:** old `public.841/` folder out of the web area; purge Hostinger CDN cache.
9. **Add-subcategory and add-type** buttons need a signed-in user (guests get "Could not add"). Decide if guests may add.
10. **Vendor product categories** saved to `category_product`; check storefront shows a product under all its categories (done in code; verify in browser).

## Working agreements
- Never commit `.env`, `vendor/`, zip or SQL with customer data.
- Keep this file and `README.md` (changelog) up to date after each release.
