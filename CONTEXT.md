# Crwydro - Context

Offline-first, bilingual (Welsh/English) PWA field journal for walkers in the Welsh hills. The device is the primary store (IndexedDB via Dexie); Laravel is the backup and second view. Playground repo for PWA, IndexedDB and resilient sync patterns.

## Runtime

| Service | Image | Host port | Notes |
|---------|-------|-----------|-------|
| `app` | `dunglas/frankenphp:php8.4` + Node 24 (see `Dockerfile`) | 8000, 5173 (Vite) | Project mounted at `/app`, runs as host UID/GID |
| `mysql` | `mysql:8.4` | 3307 | 3306 is taken on the dev host; `FORWARD_DB_PORT` |
| `queue` | `crwydro-app` (same image) | none | `php artisan queue:work`; `make queue-restart` after code changes |
| `redis` | `redis:7-alpine` | not exposed | Sessions, cache, queue |

All artisan, composer, npm and test commands run inside the container: `docker compose exec app ...`

`make dev` runs `php artisan dev` in the container. `AppServiceProvider` drops its `server` and `queue` processes (FrankenPHP serves, the `queue` container works jobs), leaving logs and Vite. `make assets` builds for production and removes `public/hot`; offline and service worker testing needs a production build.

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
| `resources/js/sync/` | Outbox processor (`processor.js`), API wrapper with ping (`api.js`), backoff |
| `resources/js/data/` | Dexie schema (`db.js`), entry repository (`entries.js`), outbox queue (`outbox.js`) |
| `tests/` | Pest feature and unit tests (SQLite in-memory via `phpunit.xml`) |
| `tests/js/` | Vitest suite over `fake-indexeddb` (`make test-js`) |

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
| Server re-encodes every photo in `App\Jobs\ProcessEntryPhoto` (Intervention Image v4, GD): strips EXIF, caps at 1600px, writes `{id}_thumb.jpg` at 400px | Defence in depth for non-browser clients; precise locations live only in entry fields |
| Device map (`resources/js/ui/map.js`) lazy-imports Leaflet on first visit to `#/map` | Keeps Leaflet out of the journal views' startup; uses `circleMarker` so no marker image assets are needed |
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
| Client storage | Token, user and `authRequired` in Dexie `meta` (`resources/js/data/auth.js`); login is optional, entries work without it |
| Re-login | A 401 sets `authRequired`; header banner links to `#/login`; logging in again replaces only the token, never the outbox |
| Known limit | v1 assumes one walker per device: logging in as someone else would push the existing outbox to their account |

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
| Map tiles | `tile.openstreetmap.org` runtime-cached `CacheFirst` in `osm-tiles` (3000 tiles, 30 days, purged on quota error). Only tiles the walker views are cached; never bulk-prefetch (OSM tile policy) |
| Dev | Service worker disabled under the Vite dev server; test offline with `make assets` |

## Local data model (Dexie)

| Store | Indexes | Notes |
|-------|---------|-------|
| `entries` | `id, createdAt, updatedAt, tag, syncStatus, deletedAt, routeId` (v2) | camelCase fields; `syncStatus` is `local`, `syncing`, `synced` or `failed`; soft delete via `deletedAt` |
| `photos` | `id, entryId, syncStatus` | `blob` (max 1600px JPEG), `thumb` (320px JPEG), `width`, `height`, `bytes` |
| `outbox` | `++seq, kind, refId, entryId, attempts` | `kind` is `entry.put`, `entry.delete` or `photo.put`; also `rev`, `failed`, `nextAttemptAt`, `lastError` |
| `meta` | `key` | `getMeta` / `setMeta` key-value store |

| Rule | Why |
|------|-----|
| Schema changes add a new `db.version(n)` with an `upgrade()`; never edit an old version | Devices upgrade in place when a new build opens; `tests/js/migrations.test.js` opens a v1 database with data and upgrades it |
| Every entry write and its outbox row share one Dexie transaction | No entry can exist without a queued upload |
| One pending `entry.put` per entry; edits bump its `rev` instead of adding rows | A put always sends the latest state; a send whose `rev` changed mid-flight keeps its row |
| Editing an entry revives a `failed` put | The walker fixing data is the retry |
| `updatedAt` always moves forward at least 1 ms (`nextTimestamp`) | Last-write-wins needs strictly ordered edits |
| Photos resized in the browser (`resources/js/ui/resize.js`) via `createImageBitmap` and canvas before storing | Keeps a 40-entry trip within quota; canvas re-encode drops EXIF (including camera GPS) and applies orientation |
| Up to 4 photos per entry; only unsynced photos can be removed | Matches the server limit; there is no photo delete endpoint in v1 |
| IDs from `ulid` `monotonicFactory` (`resources/js/data/ids.js`) | Sortable and unique even when minted in the same millisecond |

## Push sync (`resources/js/sync/processor.js`)

| Rule | Detail |
|------|--------|
| Preconditions | Token in `meta`, no `authRequired` flag, `GET /api/ping` answers within 3 s |
| Order | Rows sent in `seq` order. A waiting or failed entry put or delete holds back that entry's later rows (photos need the entry first); a waiting or failed photo holds back nothing, so text edits and other photos still go |
| Success | Row removed only if its `rev` is unchanged since sending; entry badge recomputed; synced deletes purged from the device |
| Network error or timeout | Row kept, backed off, run stops (`interrupted`) |
| 401 | Run stops, `meta.authRequired = true`, outbox untouched |
| 408, 409, 429, 5xx | Retry with backoff: 5 s doubling to a 15 min cap, plus or minus 20% jitter (`backoff.js`) |
| Other 4xx | Row `failed` with the server's message; entry badge `failed`; editing the entry or `retryFailed()` revives it |
| Single runner | `runSync()` uses the Web Locks API (`crwydro-sync`) so tabs and the service worker never drain at once |
| Environment | No `window` use, so the service worker can run it for Background Sync |
| Triggers (`resources/js/ui/sync.js`) | App open, `online`, `visibilitychange` to visible, Sync now, `crwydro:sync-requested` (fired after every local write and after login), a timer for the earliest backed-off row |
| Background Sync | Each trigger also registers the `crwydro-outbox` tag; `sw.js` runs `runSync()` on `sync` and rejects on `offline` or `interrupted` so Chromium retries. Safari and Firefox rely on the page triggers |
| Stale badges | A run resets any `syncing` badge left by a cut-off run before it starts |

## Featured walks

| Item | Detail |
|------|--------|
| Data | `public/routes/{id}.geojson`, precached; ids in `config/crwydro.php` `routes` and `resources/js/data/walks.js` |
| Source | OpenStreetMap hiking relations fetched via the Waymarked Trails API (Overpass was overloaded), simplified with Douglas-Peucker; `make routes` re-runs `scripts/import_routes.py` |
| Licence | ODbL; attribution shown on the Walks view and stored in each GeoJSON |
| Substitution | No OSM relation exists for "Tryfan and the Glyderau", so `cwm-idwal` (OSM 19400332, beneath Tryfan and the Glyderau) stands in |
| Entries | Optional `routeId` / `route_id`; the new entry form remembers the last walk in `meta.lastRouteId` |
| Look out for | Bilingual quick picks per area in `walks.js`; picking one fills the title (Welsh first) and tag |

## Server-rendered views

| Route | Detail |
|-------|--------|
| `GET/POST /login`, `POST /logout` | Session auth (`Web\SessionController`), separate from the PWA's Sanctum tokens; `/login` is a real path, unrelated to the app's `#/login` |
| `GET /map` | `Web\MapController`: the walker's synced, non-deleted entries as JSON in the page (`MapEntryResource`), drawn by `resources/js/web-map.js` (Leaflet) with their walks' routes |
| `GET /photos/{photo}/{variant?}` | Streams `full` or `thumb` from the private photos disk to the owner only (404 otherwise); thumb falls back to full until the queued job has run |
| `GET /export/entries.{gpx,csv}` (web) and `/api/export/entries.{gpx,csv}` (token) | `ExportController` via `App\Exports\EntryExporter`: GPX 1.1 waypoints for located entries; CSV of all entries with photo count; free text prefixed with `'` if it starts with `= + - @` (CSV injection guard) |
| Times | Stored UTC; server views render in `crwydro.display_timezone` (`Europe/London`) |
