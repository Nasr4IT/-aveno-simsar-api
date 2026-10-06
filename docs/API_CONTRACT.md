# API Contract — Aveno Marketplace

Base URL (local dev): `http://localhost:8000/api`
Auth: Laravel Sanctum bearer token. Register or log in once, store the `token`, then send `Authorization: Bearer <token>` on every authenticated call. There are no cookies/CSRF to deal with from Flutter.

All responses are JSON. List endpoints are paginated (Laravel's default shape: `data`, `links`, `meta`). Errors follow Laravel's default validation shape: `{"message": "...", "errors": {"field": ["..."]}}` with HTTP 422, or `{"message": "..."}` with 401/403/404/501.

This file is the contract between the Laravel backend and the Flutter app. **A route, field, or status code doesn't change without this file changing in the same commit** — that's the rule that keeps two people building against each other without breaking each other daily.

## Auth

| Method | Path | Auth | Body | Notes |
|---|---|---|---|---|
| POST | `/auth/register` | – | `name, phone, password, email?, governorate_id?, city_id?` | 201, returns `{user, token}` |
| POST | `/auth/login` | – | `phone, password` | 200, returns `{user, token}`; 422 on bad credentials, 403 if banned |
| POST | `/auth/logout` | ✓ | – | revokes the current token |
| GET | `/auth/me` | ✓ | – | current user profile |

## Categories

| Method | Path | Auth | Notes |
|---|---|---|---|
| GET | `/categories` | – | top-level categories + `children`, for the home screen |
| GET | `/categories/{id}` | – | one category + `attributes` (the dynamic spec fields to render for "post an ad") |

## Ads

| Method | Path | Auth | Notes |
|---|---|---|---|
| GET | `/ads` | – | public feed, `status=approved` only. Query params: `category_id, governorate_id, city_id, min_price, max_price, q, sort` (`newest`\|`price_asc`\|`price_desc`\|`popular`\|`nearest`; `nearest` additionally requires `lat`, `lng`) + one param per filterable `category_attribute.key` (e.g. `?fuel_type=بنزين`) |
| GET | `/ads/{id}` | – | full detail, increments `views_count` |
| GET | `/my/ads` | ✓ | the caller's own ads, any status |
| POST | `/ads` | ✓ | multipart: `category_id, governorate_id, city_id, title, description, price?, currency?, latitude?, longitude?, images[] (1-12), attributes[<key>]=<value>`. Created as `status=pending` |
| PUT/PATCH | `/ads/{id}` | ✓ (owner) | partial update; edits re-queue the ad for review |
| DELETE | `/ads/{id}` | ✓ (owner) | soft delete |

## Favorites

| Method | Path | Auth |
|---|---|---|
| GET | `/favorites` | ✓ |
| POST | `/ads/{id}/favorite` | ✓ |
| DELETE | `/ads/{id}/favorite` | ✓ |

## Chat

| Method | Path | Auth | Notes |
|---|---|---|---|
| GET | `/conversations` | ✓ | caller's conversations, as buyer or seller |
| POST | `/ads/{id}/conversations` | ✓ | start (or fetch existing) conversation about this ad |
| GET | `/conversations/{id}/messages` | ✓ (participant) | paginated, oldest first |
| POST | `/conversations/{id}/messages` | ✓ (participant) | body: `{body}` |

MVP is polling — have the Flutter app refresh `/messages` every few seconds while a chat screen is open. A push/broadcast upgrade later doesn't change these routes.

## Ratings

| Method | Path | Auth | Notes |
|---|---|---|---|
| GET | `/users/{id}/ratings` | – | public, shown on a seller's profile |
| POST | `/users/{id}/ratings` | ✓ | body: `{ad_id?, score (1-5), comment?}`; one rating per (rater, rated, ad) |

## Notifications

| Method | Path | Auth |
|---|---|---|
| GET | `/notifications` | ✓ |
| POST | `/notifications/{id}/read` | ✓ |
| POST | `/notifications/read-all` | ✓ |

## Banners (sponsored, home-screen carousel)

Separate from `ads` — a business pays the admin directly (outside this app) for a spot in the home-screen carousel; no in-app payment flow.

| Method | Path | Auth | Body | Notes |
|---|---|---|---|---|
| GET | `/banners` | – | – | only banners currently inside their window and not paused, ordered by `sort_order` |
| GET | `/admin/banners` | admin | – | every banner, any status (management view) |
| POST | `/admin/banners` | admin | multipart: `image, title?, link_url?, starts_at, ends_at, sort_order?` | `link_url` must be `https://...` or `tel:...` if given |
| PUT/PATCH | `/admin/banners/{id}` | admin | any of: `title, link_url, starts_at, ends_at, sort_order, is_active` | no image replacement — delete + recreate instead |
| DELETE | `/admin/banners/{id}` | admin | – | also deletes the image file |

## Payments (Sham Cash featured-ad packages)

| Method | Path | Auth | Notes |
|---|---|---|---|
| GET | `/ad-packages` | – | the 15/30/60-day catalog |
| POST | `/ads/{id}/checkout` | ✓ (owner) | body: `{ad_package_id}` → returns a Sham Cash checkout URL for the app to open |
| POST | `/payments/shamcash/webhook` | – (HMAC-verified) | Sham Cash → server callback; flips the payment to `paid` and activates `is_featured` |

## Admin (requires the `admin` role, in addition to auth)

| Method | Path | Notes |
|---|---|---|
| GET/POST/PUT/DELETE | `/admin/categories[/{id}]` | category CRUD |
| POST | `/admin/categories/{id}/attributes` | add a dynamic spec field to a category |
| GET | `/admin/users?q=` | search/list users |
| POST | `/admin/users/{id}/ban` \| `/unban` | – |
| GET | `/admin/ads?status=pending` | review queue |
| POST | `/admin/ads/{id}/approve` | – |
| POST | `/admin/ads/{id}/reject` | body: `{reason}` |

## Current implementation status

Fully implemented: `Auth`, `Categories` (read), `Ads` (create/read/update/destroy, incl. image upload + resize and dynamic attributes), ad photo management (`POST`/`DELETE /ads/{id}/images[/{imageId}]`), `Favorites`, `Chat`, `Ratings`, `Notifications` (read), `Banners`, `Admin ▸ Categories/Users/Ads/Banners`.

Stubbed (`501 Not Implemented`): `Payments@checkout`/`@webhook` only — real Sham Cash HTTP calls still need to be written in `app/Services/ShamCash/ShamCashClient.php`.

See [`HOW_IT_WORKS.md`](HOW_IT_WORKS.md) for how all of the above fit together screen-by-screen.
