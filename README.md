# Aveno Marketplace — API

Laravel backend for the classifieds app (cars / real estate / motorcycles) described in the Aveno Code proposal to Mr. Hosam Wardah — buy/sell ads, category-specific dynamic specs, geo search, in-app chat, ratings, admin review, and Sham Cash-powered featured-ad packages.

This backend is API-only. The Flutter app (built separately) is the only client — see [`docs/API_CONTRACT.md`](docs/API_CONTRACT.md) for every route it depends on, and [`docs/ERD.md`](docs/ERD.md) for the data model.

## Stack

- PHP 8.2+, Laravel 11
- MySQL (SQLite for local/testing)
- Laravel Sanctum — token auth for the mobile app
- spatie/laravel-permission — the `admin` role that gates the admin API
- spatie/laravel-query-builder — filtering/sorting on `GET /ads`

## Local setup

```bash
composer install
cp .env.example .env      # already done if you cloned this scaffold as-is
php artisan key:generate

# point DB_* in .env at a local MySQL, or switch to SQLite for a quick start:
#   DB_CONNECTION=sqlite
#   touch database/database.sqlite

php artisan migrate --seed
php artisan storage:link
php artisan serve
```

The seeders create: the `admin` role, Syria's 14 governorates with sample cities, the 15/30/60-day ad packages, three seed categories (سيارات / عقارات / دراجات نارية) with their dynamic spec fields, and one admin login (`phone: 0999999999`, see `database/seeders/AdminUserSeeder.php` — **change that password before this ever touches a real server**).

Run the test suite:

```bash
php artisan test
```

## What's scaffolded vs. what's left to build

Everything below is in place and lints clean (`php -l` was run on every file): the full database schema (18 migrations), all 14 Eloquent models with their relationships, Sanctum auth, every route in `docs/API_CONTRACT.md`, request validation, API resources, and the admin-role middleware.

Two pieces are intentionally left as `501` stubs for you to implement first, since they're the meatiest business logic in the MVP:

1. **`AdController@store` / `@update`** — validated in `StoreAdRequest`/`UpdateAdRequest` already; what's missing is creating the `Ad` + looping the `attributes[]` payload into `AdAttributeValue` rows + storing uploaded images into `AdImage` (disk: `public`, see `config/filesystems.php`).
2. **`PaymentController@checkout` / `@webhook`** and `app/Services/ShamCash/ShamCashClient.php` — wire these against Sham Cash's real API docs once you have them; the webhook signature-verification skeleton and the `payments` table are ready.

Everything else (`Auth`, `Categories`, `Favorites`, `Chat`, `Ratings`, `Notifications` reads, all three `Admin` controllers) is fully implemented, not just stubbed.

This scaffold was generated without network access in the build environment, so `vendor/` isn't included and migrations haven't been run against a real database yet — do that first via `composer install && php artisan migrate --seed` and fix forward from whatever composer/PHP reports, though the code was written and lint-checked directly against Laravel 11 / Sanctum 4 / spatie/laravel-permission 6 conventions.

## Project conventions

- **Controllers** stay thin: validate (via a `FormRequest` when the rules are non-trivial), touch Eloquent, return a `JsonResource`. Anything with real logic (recomputing a rating average, the Sham Cash HTTP calls) goes in `app/Services/`, not the controller.
- **Every route** is documented in `docs/API_CONTRACT.md` — update it in the same commit as the route.
- **Every schema change** is a new migration, never an edit to an already-merged one (see `docs/GIT_WORKFLOW.md`).
- Formatting: `vendor/bin/pint` (Laravel Pint) before every commit.

See [`docs/GIT_WORKFLOW.md`](docs/GIT_WORKFLOW.md) for branching, commits, and how this repo is meant to be shared with the Flutter side of the team.
