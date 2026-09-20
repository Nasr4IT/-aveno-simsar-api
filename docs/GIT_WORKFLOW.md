# Git & Team Workflow

Two repos, one contract: `aveno-marketplace-api` (this one, Laravel) and a separate Flutter repo your friend owns. They never share a git history — they share `docs/API_CONTRACT.md`. Whichever of you changes a route, a field name, or a status code edits that file in the same commit and pings the other person; that one habit prevents almost every "why is the app crashing" bug you'll otherwise chase for an hour.

## Repo setup (do this first)

1. Create a **private** GitHub repo per project: `aveno-marketplace-api` and (your friend) `aveno-marketplace-app`. Add each other as collaborators on both, even though you'll each mostly live in your own.
2. Protect `main` on both repos: Settings → Branches → require a pull request before merging, no direct pushes. This is the single biggest "feels like a real company" habit — nobody, including you, pushes straight to `main`.
3. Put `docs/API_CONTRACT.md` and `docs/ERD.md` somewhere your friend actually reads them — pin the repo, or better, treat the API repo as the shared spec and have them clone it read-only for reference even though their code lives elsewhere.

## Branching

```
main                    — always deployable; every commit here has been reviewed
└─ develop               (optional at your size — skip it, merge feature branches straight into main via PR)
└─ feature/ads-store-endpoint
└─ feature/shamcash-checkout
└─ fix/rating-average-not-updating
```

At a two-person scale, skip `develop` and `git-flow` ceremony — it adds overhead without adding safety here. One rule that matters: **`main` is never broken.** Every feature, however small, gets its own branch off `main`, named `feature/<short-description>` or `fix/<short-description>`.

## Commit messages

Conventional Commits — cheap to write, and six months from now `git log --oneline` actually tells you what happened:

```
feat(ads): implement store/update with image upload
fix(chat): prevent starting a conversation with yourself
docs(api): document the checkout response shape
refactor(ratings): move average recompute into RatingService
test(auth): cover banned-user login rejection
chore(deps): bump laravel/sanctum to ^4.1
```

## Pull requests

Even solo-with-one-collaborator, PRs matter for exactly one reason: a second pass over your own diff before it's permanent. Small habit, keep it:

1. Push the branch, open a PR into `main`.
2. PR description: what changed, why, and — if it touches `routes/api.php` — a one-line note that `docs/API_CONTRACT.md` was updated too.
3. Self-review the diff on GitHub before requesting review (you'll catch a surprising number of your own bugs this way).
4. `php artisan test` and `vendor/bin/pint --test` pass locally before you open the PR (wire these into GitHub Actions once you're comfortable — see "CI" below).
5. Squash-merge. One clean commit per feature on `main` keeps the history readable.

## Environment & secrets

- `.env` is git-ignored (already set up) and **never** committed — it holds the Sham Cash keys, DB password, `APP_KEY`.
- `.env.example` is the checked-in template every teammate copies from; keep it updated whenever you add a new config key (you'll see this pattern already in this scaffold — `GOOGLE_MAPS_API_KEY`, `SHAMCASH_*`, etc.).
- Share real secrets (Sham Cash sandbox keys, Google Maps key) over a password manager (Bitwarden, 1Password) or an encrypted note — never in Slack/WhatsApp, never in a commit, never in a screenshot.

## Keeping the Flutter side unblocked

Your friend can start building UI before every endpoint has real logic behind it:

- `docs/API_CONTRACT.md` is written *before* you fully implement a route — it's the spec, not the changelog.
- For endpoints still stubbed (`Ads@store`, `Payments@checkout` right now — see `README.md`), your friend can point Flutter at a mock server (Postman Mock Server, or a `json-server` fixture matching the contract) so their UI work isn't blocked on your backend sprint order.
- Weekly (or after every merged feature), a 10-minute sync: "here's what changed in the contract" — cheaper than them discovering it from a 422 error.

## CI (add once the MVP core routes are stable — don't over-invest before then)

A minimal `.github/workflows/tests.yml` that runs on every PR:

```yaml
name: tests
on: [pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with: { php-version: '8.3' }
      - run: composer install --no-interaction --prefer-dist
      - run: cp .env.example .env && php artisan key:generate
      - run: touch database/database.sqlite
      - run: php artisan migrate --force
        env: { DB_CONNECTION: sqlite, DB_DATABASE: database/database.sqlite }
      - run: php artisan test
```

That's the whole thing worth having at this stage — it stops a broken `main` before it reaches your friend's Flutter build.
