# Crwydro - Context

Offline-first, bilingual (Welsh/English) PWA field journal for walkers in the Welsh hills. The device is the primary store (IndexedDB via Dexie); Laravel is the backup and second view. Playground repo for PWA, IndexedDB and resilient sync patterns.

## Runtime

| Service | Image | Host port | Notes |
|---------|-------|-----------|-------|
| `app` | `dunglas/frankenphp:php8.4` + Node 24 (see `Dockerfile`) | 8000, 5173 (Vite) | Project mounted at `/app`, runs as host UID/GID |
| `mysql` | `mysql:8.4` | 3307 | 3306 is taken on the dev host; `FORWARD_DB_PORT` |
| `redis` | `redis:7-alpine` | not exposed | Sessions, cache, queue |

All artisan, composer, npm and test commands run inside the container: `docker compose exec app ...`

`make dev` runs `php artisan dev` in the container. `AppServiceProvider` drops its `server` process (FrankenPHP already serves), leaving queue, logs and Vite. `make assets` builds for production and removes `public/hot`; offline and service worker testing needs a production build.

## Architecture at a glance

| Path | Purpose |
|------|---------|
| `app/` | Laravel API and server-rendered views |
| `routes/api.php` | JSON sync API (Sanctum token auth) |
| `resources/views/app.blade.php`, `resources/views/shell/` | Single app shell page; views toggle via Alpine stores |
| `resources/js/` | PWA client: hash router (`router.js`), Dexie store, outbox processor, UI |
| `docker-compose.yml`, `Dockerfile` | Containerised runtime |
| `Makefile` | All developer commands |
| `request.http` | Example API requests |
| `tests/` | Pest feature and unit tests (SQLite in-memory via `phpunit.xml`) |

## Key decisions

| Decision | Why |
|----------|-----|
| Client-generated ULIDs for entries and photos | A retried `PUT` is an upsert on the same key, so retries never duplicate |
| Client-driven outbox, rows removed only on server confirmation | A failed or interrupted sync never loses data |
| Last-write-wins on `client_updated_at` | v1 is single-user; server ignores updates older than what it holds |
| Photos uploaded one per request | One failed large upload never blocks text entries |
| Sanctum personal access tokens, long-lived | Walkers can be offline for days; expiry must not strand the outbox |
| Redis for queue, cache and sessions | Queued thumbnail and EXIF jobs; one fast backing service |
| MySQL | Project default stack |
| Photos stored at `photos/{user_id}/{entry_id}/{photo_id}.jpg` on `PHOTOS_DISK` (`config/crwydro.php`) | Deterministic path makes retries overwrite rather than orphan files; local in dev, S3 in production |
| Hash routes (`/#/entries/new`) with one Blade shell at `/` | Only one HTML document to precache; every view works offline |
| Fonts self-hosted via `laravel-vite-plugin/fonts` and `@fonts` | No third-party font request, so the shell renders identically offline |
| Service worker via `vite-plugin-pwa` `injectManifest` (`resources/js/sw.js`) | Custom worker needed later for Background Sync; worker emitted to `public/sw.js` so its scope is `/` |
| `/` shell precached with a per-build revision (`__BUILD_ID__`) | Server-rendered shell refreshes with each deploy alongside hashed assets |
| `registerType: 'prompt'` (`resources/js/pwa.js`) | Never swap code under a walker mid-trip; they choose when to update |
| Alpine.js over Vue | Views are small; revisit if they grow |
| Millisecond timestamps on `entries` (`$dateFormat = 'Y-m-d H:i:s.v'`, precision 3 columns) | Eloquent drops ms by default; last-write-wins and pull cursors need them |

## Auth

| Item | Detail |
|------|--------|
| Login | `POST /api/login` with `email`, `password`, `device_name`; returns `token` and `user` |
| Token | Sanctum personal access token, `sanctum.expiration` null (never expires); new token per login, named by `device_name` |
| Guard | `auth:sanctum` on all `/api/*` routes except `login` and `ping` |
| Throttle | `throttle:10,1` on login |
| Demo user | `walker@crwydro.test` / `password` (seeded) |

## Data model

| Table | Key columns |
|-------|-------------|
| `entries` | ULID `id` (client-minted), `user_id`, `title`, `notes`, `tag` (`App\Enums\Tag`), `lat`, `lng`, `accuracy_m`, `altitude_m`, `recorded_at`, `client_updated_at`, soft deletes; index `user_id, updated_at` |
| `entry_photos` | ULID `id` (client-minted), `entry_id`, `path`, `thumb_path`, `width`, `height`, `bytes` |

## Sync contract

| Endpoint | Behaviour |
|----------|-----------|
| `GET /api/ping` | No auth, `Cache-Control: no-store`; client checks it before draining the outbox |
| `PUT /api/entries/{ulid}` | Upsert via `App\Actions\UpsertEntry`: 201 new, 200 otherwise. Ignores writes with `client_updated_at` <= stored. Never resurrects a soft-deleted entry. 403 if another user owns the ULID. A concurrent duplicate insert falls back to an update |
| `DELETE /api/entries/{ulid}` | Soft delete, 204 even if unknown or already deleted |
| `PUT /api/entries/{ulid}/photos/{ulid}` | Raw JPEG body (`Content-Type: image/jpeg`), not multipart, because PHP does not parse multipart on `PUT`. Via `App\Actions\StoreEntryPhoto`: 201 new, 200 if the photo ULID already exists. Server reads dimensions itself. 404 unknown entry, 403 not owner, 413 over `PHOTO_MAX_BYTES`, 422 not JPEG / 4 photos already / ULID on another entry |

Timestamps in and out are ISO 8601 UTC with milliseconds (`2026-10-11T10:15:30.123Z`), via `App\Http\Resources\EntryResource`.

## PWA

| Item | Detail |
|------|--------|
| Worker | `resources/js/sw.js` built to `public/sw.js` (gitignored); precaches `build/assets/*.{js,css,woff2,woff}`, `icons/*.png`, `/`, `build/manifest.webmanifest` |
| Navigation | `NavigationRoute` serves precached `/` for `/` only; `/api/*` is never cached |
| Manifest | `build/manifest.webmanifest`; the inline `webManifestPrecachePath` plugin in `vite.config.js` fixes its precache path |
| Icons | `public/icons/` (192, 512, maskable 512, Apple touch 180, favicon 48) |
| Dev | Service worker disabled under the Vite dev server; test offline with `make assets` |
