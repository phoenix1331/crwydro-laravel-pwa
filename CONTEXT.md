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
| `resources/views/app.blade.php`, `resources/views/shell/` | Single app shell page; views toggle via Alpine stores. Phone: `header` + `nav` (bottom); `md` and up: `sidebar`. Nav items come from a view composer in `AppServiceProvider`; icons from `shell/icon.blade.php` |
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
| Fonts self-hosted via `laravel-vite-plugin/fonts` and `@fonts` (Atkinson Hyperlegible Next 400/700/800) | No third-party font request, so the shell renders identically offline |
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

| `GET /api/sync?since={cursor}` | Pull sync (`Api\SyncController`): own entries incl. soft deletes, ordered `updated_at, id`, 100 per page, each with `photos`; returns `next_cursor` and `has_more`. Cursor (`App\Sync\SyncCursor`) is opaque base64 of `updated_at|id`, so same-millisecond changes are never skipped; echoed when nothing changed; 422 if malformed |
| `GET /api/photos/{photo}/{variant?}` | Same `PhotoController` as the web route, token auth; `thumb` or full JPEG |

Adding or processing a photo touches its entry (`EntryPhoto::$touches`), so pull sync picks up photos uploaded after the entry. Known limit: a transaction committing after a later one could slip behind the cursor; acceptable for v1's single walker.

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

## Pull sync (`resources/js/sync/pull.js`)

| Rule | Detail |
|------|--------|
| Order | `runSync()` pushes first, then pulls only if the push finished (`done`), then downloads thumbnails, all inside the `crwydro-sync` lock |
| Cursor | `meta.pullCursor`; each page and its cursor are written in one Dexie transaction, so an interrupted pull resumes from the last complete page; a 422 resets the cursor and pulls from the start |
| Merge | Entries with any outbox rows are skipped (the device's copy wins and its push settles it on the server); otherwise the server copy is taken only if its `client_updated_at` is newer; server deletes remove the entry and its photos |
| Photos | Pulled as metadata (`remote: true`, no Blobs); `downloadThumbnails()` fetches up to 20 thumbnails per sync; the full image downloads the first time it is opened (`fetchFullPhoto`) |

## Design system (`resources/css/app.css`)

| Item | Detail |
|------|--------|
| Direction | OS Explorer map vernacular: heather, peat, tarn, bracken; one decorative element, the contour texture (`contour-panel`, `resources/images/contours.svg`, `make contours`) on the brand panel only |
| Colour | Semantic tokens as CSS variables with a dark set under `prefers-color-scheme`: `canvas`, `surface`, `sunk`, `line`, `line-strong`, `ink`, `muted`, `faint`, `accent` (heather), `ok` (moss), `warn` (bracken), `danger`, `link` (tarn), `panel*`. Use these, never literal palette classes |
| Status meaning | `warn` = only on this device, `ok` = safe on the server, `danger` = needs the walker |
| Type | Atkinson Hyperlegible Next; headings 800 with -0.02em tracking; tabular figures everywhere; 17px base on phones, 16px from 1024px |
| Radius | `rounded-panel` 20, `rounded-card` 14, `rounded-control` 12, chips `rounded-full` |
| Components | `@utility` classes: `btn`, `btn-primary`, `btn-secondary`, `btn-quiet`, `btn-danger`, `field`, `label`, `hint`, `card`, `chip`, `chip-on`, `notice`, `no-scrollbar` |
| Breakpoints | Phone below `md` (768px): contour header, bottom nav with central New. `md`+: 15.5rem contour sidebar with New, nav and sync summary. `lg` (1024px)+: list and detail side by side |
| Journal layout | `entries` and `entry` share a wrapper in `app.blade.php`: below `lg` they are separate screens (`max-lg:hidden` on the list while an entry is open); from `lg` the list sits beside a sticky detail panel, with a placeholder when nothing is chosen |
| Grid references | `resources/js/data/osgrid.js` converts WGS84 to an OS National Grid reference (Helmert to OSGB36, then Transverse Mercator); shown as the main location on an entry; tested against Yr Wyddfa, Pen y Fan and Cadair Idris summits |
| Walks, map, sync | Walk cards carry a sketch of each route's real shape (`routeSketch()` in `resources/js/ui/walks.js`, from the cached GeoJSON); routes draw in heather on both maps; laptop map has a floating walk picker and tag key; the map only refits when the chosen walk changes; sync is three stat cards |
| Server pages | `/login` is a split contour panel and form; `/map` reuses the sidebar pattern (exports, account) from `md`, a contour header on phones, map plus a `<details>` entry list that sits beside the map from `lg`. Both reuse `shell/icon` |
| Accessibility | Every token pairing passes WCAG AA (4.5:1) in both themes; visible `:focus-visible` ring; skip link to `#content`; photo viewer traps Tab, supports arrow keys and Escape, and returns focus to the opener; reduced motion respected |
| Visual QA | Sweep every view at 390, 768, 1280, 1440 in light and dark, checking for horizontal overflow (Playwright) |
| Avoid | Middle-dot metadata strings, arrows appended to links, all-caps labels, `font-semibold` (600 is not loaded) |

## Language (Welsh and English)

| Item | Detail |
|------|--------|
| Source | `lang/en/app.php` and `lang/cy/app.php`; Pest checks both have the same keys and placeholders |
| Offline | `php artisan crwydro:export-lang` writes `resources/js/lang/{en,cy}.json` (gitignored); npm `prebuild`, `predev` and `pretest` run it, so translations are bundled and precached |
| Client | `resources/js/i18n.js` (`t()`, no Alpine, safe in the service worker); `resources/js/ui/language.js` store `$store.i18n` plus `$t()` magic; helpers wrapped with `localised()` re-render on a switch |
| Choice | Saved in `localStorage` (`crwydro.locale`), default from the browser language; mirrored to the unencrypted `crwydro_locale` cookie |
| Server pages | `SetLocaleFromCookie` middleware on the web group, so `/login` and `/map` match the app |
| Place names | Welsh first with English in brackets in both languages; tag names and walk notes follow the chosen language |
| Review | Welsh copy was written without a native speaker; get it reviewed before release |
