# Aveno Marketplace — API

Laravel backend for the classifieds app (cars / real estate / motorcycles) described in the Aveno Code proposal to Mr. Hosam Wardah — buy/sell ads, category-specific dynamic specs, geo search, in-app chat, ratings, admin review, and Sham Cash-powered featured-ad packages.

Two clients use it: the Flutter app (built separately), through the JSON API under `/api`, and human moderators, through the admin panel web UI at `/admin-panel`. See [`docs/API_CONTRACT.md`](docs/API_CONTRACT.md) for every route the app depends on, and [`docs/ERD.md`](docs/ERD.md) for the data model.

## Stack

- PHP 8.2+, Laravel 12
- MySQL locally, PostgreSQL in production (Render), SQLite for tests/CI
- Laravel Sanctum — token auth for the mobile app; session login for the admin panel
- spatie/laravel-permission — the `admin` role that gates the admin API and panel
- intervention/image — resizes uploaded ad photos
- Optional, off until credentials are set: Firebase push (FCM), S3-compatible image storage (e.g. Cloudflare R2), Sentry error tracking — see `.env.example`

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

The seeders create: the `admin` role, Syria's 14 governorates with sample cities, the 15/30/60-day ad packages, three seed categories (سيارات / عقارات / دراجات نارية) with their dynamic spec fields, and one admin login (`phone: 0999999999`). Its password is whatever `ADMIN_PASSWORD` is set to in `.env` (or the server's environment) — set it before seeding; with it empty the account exists but no password works. The seeders run on every boot in Docker, so changing `ADMIN_PASSWORD` and redeploying is how the admin password is changed. See `database/seeders/AdminUserSeeder.php`.

Run the test suite:

```bash
php artisan test
```

## What's built and what isn't

Built and covered by the test suite: auth, categories with dynamic spec fields, ads (create/edit with photo upload + resize, search with a typo-tolerant fallback, filters, sorting, map-area bounds, similar ads, mark-sold/relist), favorites, chat, ratings, reporting, in-app + push notifications, home-screen banners, seller stats, the `/admin/*` moderation API, and the `/admin-panel` web UI (log in at `/admin-panel/login`).

Not built yet:

1. **Sham Cash payments** — `PaymentController@checkout` / `@webhook` still return `501`. `app/Services/ShamCash/ShamCashClient.php`, the webhook signature-check skeleton and the `payments` table are ready; the HTTP calls wait on Sham Cash's real API docs.
2. **Email notifications** — notifications are delivered in-app (`GET /notifications`) and by push only. The `MAIL_*` settings in `.env.example` aren't used by anything yet.

[`docs/HOW_IT_WORKS.md`](docs/HOW_IT_WORKS.md) § Known gaps has the full list, including what needs an external account to switch on.

## Deployment

The `Dockerfile` runs on Render: on every boot it migrates, seeds (idempotent), starts a queue worker in the background and serves on `$PORT`. The Postman collection in `docs/` points at the live deployment.

## Docs

- [`docs/HOW_IT_WORKS.md`](docs/HOW_IT_WORKS.md) — screen-by-screen walkthrough; start here
- [`docs/API_REFERENCE.md`](docs/API_REFERENCE.md) — every endpoint's fields, responses and errors (PDF copy alongside)
- [`docs/API_CONTRACT.md`](docs/API_CONTRACT.md) — the short route list the Flutter app builds against
- [`docs/ERD.md`](docs/ERD.md) — data model
- [`docs/Aveno_Marketplace.postman_collection.json`](docs/Aveno_Marketplace.postman_collection.json) — click-to-test requests
- [`docs/GIT_WORKFLOW.md`](docs/GIT_WORKFLOW.md) — branching and commits

## Project conventions

- **Controllers** stay thin: validate (via a `FormRequest` when the rules are non-trivial), touch Eloquent, return a `JsonResource`. Anything with real logic (recomputing a rating average, the Sham Cash HTTP calls) goes in `app/Services/`, not the controller.
- **Every route** is documented in `docs/API_CONTRACT.md` — update it in the same commit as the route.
- **Every schema change** is a new migration, never an edit to an already-merged one (see `docs/GIT_WORKFLOW.md`).
- Formatting: `vendor/bin/pint` (Laravel Pint) before every commit.

See [`docs/GIT_WORKFLOW.md`](docs/GIT_WORKFLOW.md) for branching, commits, and how this repo is meant to be shared with the Flutter side of the team.
