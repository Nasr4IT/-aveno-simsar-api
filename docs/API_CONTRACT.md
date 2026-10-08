# API Contract — Aveno Marketplace

Base URL (local dev): `http://localhost:8000/api`
Auth: Laravel Sanctum bearer token. Register or log in once, store the `token`, then send `Authorization: Bearer <token>` on every authenticated call. There are no cookies/CSRF to deal with from Flutter.

All responses are JSON. List endpoints are paginated (Laravel's default shape: `data`, `links`, `meta`). Errors follow Laravel's default validation shape: `{"message": "...", "errors": {"field": ["..."]}}` with HTTP 422, or `{"message": "..."}` with 401/403/404/409/501. An action that isn't allowed in the resource's current state (e.g. marking a pending ad sold) is also a `422`, but with only `{"message": "..."}`. Exact messages per endpoint: [`API_REFERENCE.md`](API_REFERENCE.md).

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
| GET | `/ads/{id}` | – | full detail, increments `views_count`; visible for `approved` and `sold` |
| GET | `/ads/{id}/similar` | – | same-category ads, price within ±30% when the ad has one — unpaginated, max 10 |
| GET | `/my/ads` | ✓ | the caller's own ads, any status |
| GET | `/my/ads/stats` | ✓ | aggregate counts across the caller's ads: by status, total views, favorites, conversations |
| POST | `/ads` | ✓ | multipart: `category_id, governorate_id, city_id, title, description, price?, currency?, latitude?, longitude?, images[] (1-12), attributes[<key>]=<value>`. Created as `status=pending` |
| PUT/PATCH | `/ads/{id}` | ✓ (owner) | partial update; edits re-queue the ad for review |
| DELETE | `/ads/{id}` | ✓ (owner) | soft delete |
| POST | `/ads/{id}/images` | ✓ (owner) | multipart: `images[]` — appends photos, up to 12 total; re-queues for review |
| DELETE | `/ads/{id}/images/{imageId}` | ✓ (owner) | can't remove the ad's last photo |
| POST | `/ads/{id}/mark-sold` | ✓ (owner) | `approved` → `sold`; drops out of the public feed but stays viewable |
| POST | `/ads/{id}/relist` | ✓ (owner) | `sold`/`expired` → `pending`, re-queues for review |
| POST | `/ads/{id}/report` | ✓ | body: `{reason: spam\|inappropriate\|scam\|other, details?}` |

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
| POST | `/users/{id}/report` | ✓ | body: `{reason: spam\|inappropriate\|scam\|other, details?}` |

## Notifications

| Method | Path | Auth |
|---|---|---|
| GET | `/notifications` | ✓ |
| POST | `/notifications/{id}/read` | ✓ |
| POST | `/notifications/read-all` | ✓ |

## Push notification device registration

Code-complete but inactive without `FIREBASE_CREDENTIALS_JSON` — see `App\Services\PushNotificationService`.

| Method | Path | Auth | Body |
|---|---|---|---|
| POST | `/device-tokens` | ✓ | `{token, platform?: android\|ios\|web}` — upserts by token |
| DELETE | `/device-tokens` | ✓ | `{token}` — call on logout |

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

`403 {"message": "Admins only."}` for a non-admin **or a banned admin**. Bearer token only — the `/admin-panel` browser session is not accepted here.

| Method | Path | Notes |
|---|---|---|
| GET/POST/PUT/DELETE | `/admin/categories[/{id}]` | category CRUD. POST: `sort_order` ≥ 0 (default 0), returns `201` with stored defaults (`is_active: true`). DELETE: `409` while ads still use the category |
| POST | `/admin/categories/{id}/attributes` | add a dynamic spec field: `{key, label_ar, label_en?, type, options?, is_required?, is_filterable?}`. `key`: snake_case (`^[a-z][a-z0-9_]*$`), unique within the category, not a reserved feed param (`category_id, governorate_id, city_id, min_price, max_price, q, page, sort, lat, lng`). `options`: required non-empty array for `select`/`multiselect`, ignored otherwise. `is_filterable` defaults to `true`. Returns `201` with the stored row (not wrapped in `data`), defaults included |
| GET | `/admin/users?q=` | search/list users |
| POST | `/admin/users/{id}/ban` \| `/unban` | ban revokes all tokens and device tokens; `422` for an admin account |
| GET | `/admin/ads?status=pending` | review queue |
| POST | `/admin/ads/{id}/approve` | `pending` ads only, else `422` |
| POST | `/admin/ads/{id}/reject` | body: `{reason}`. `pending`, or `approved`/`sold` (takes a public ad down), else `422` |
| GET | `/admin/reports?status=pending` | review queue (reason, reporter, and the reported ad/user) |
| POST | `/admin/reports/{id}/resolve` \| `/dismiss` | triage marker only — doesn't itself ban/unlist anything |

**Admin panel:** everything above also has a server-rendered UI at `/admin-panel` (session-login, not Sanctum) — same rules, same backend, just a second client. See `HOW_IT_WORKS.md` § The Admin Side.

## Current implementation status

Fully implemented: `Auth`, `Categories` (read), `Ads` (create/read/update/destroy, incl. image upload + resize and dynamic attributes, similar-ads, mark-sold/relist), ad photo management (`POST`/`DELETE /ads/{id}/images[/{imageId}]`), seller stats (`/my/ads/stats`), `Favorites`, `Chat`, `Ratings`, `Reporting`, `Notifications` (read), `Banners`, `Admin ▸ Categories/Users/Ads/Banners/Reports`, the `/admin-panel` web UI.

Code-complete but needs an external account to actually activate (silently no-ops without it — see `HOW_IT_WORKS.md` § Known gaps): push notifications (`FIREBASE_CREDENTIALS_JSON`), S3/R2 image storage (`FILESYSTEM_DISK=s3` + `AWS_*`), Sentry error tracking (`SENTRY_LARAVEL_DSN`).

Stubbed (`501 Not Implemented`): `Payments@checkout`/`@webhook` only — real Sham Cash HTTP calls still need to be written in `app/Services/ShamCash/ShamCashClient.php`.

See [`HOW_IT_WORKS.md`](HOW_IT_WORKS.md) for how all of the above fit together screen-by-screen.
