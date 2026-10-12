# Crwydro

<img width="3196" height="1640" alt="Screenshot 2026-10-12 131953" src="https://github.com/user-attachments/assets/b649514e-7134-4afb-bf00-1e04cb7566ac" />


An offline-first field journal for walkers in the Welsh hills: log a photo, a note and a GPS position with no signal, and it syncs itself once a connection returns.

*Crwydro* is Welsh for "to wander or roam" (said "KROO-id-roh").

## What this demonstrates

<img width="3150" height="1658" alt="Screenshot 2026-10-12 132051" src="https://github.com/user-attachments/assets/0c3f65ae-3ee1-4650-aed5-975c41c4d714" />


Most note and photo apps quietly fail in mobile dead zones like Eryri, Bannau Brycheiniog and the Cambrian Mountains. Crwydro turns the usual model around: **the phone is the source of truth and the server is the backup**. Building it properly touches most of the hard parts of offline web apps, which is the point of this repo.

| Concept | Where it shows up |
|---------|-------------------|
| **An outbox that never loses data** | Every local change writes an outbox row in the same IndexedDB transaction. A row is removed only after the server confirms it, so a crash, a closed tab or a dropped connection cannot lose an entry |
| **Idempotent, resumable sync** | The device mints every ID as a ULID, so a retried `PUT` is an upsert on the same key and can never duplicate. Photos upload one per request, so a failed 4 MB upload never blocks the text |
| **Realistic failure handling** | A ping before syncing (because `navigator.onLine` lies), exponential backoff with jitter, 401 pausing for a re-login without touching the outbox, and 4xx errors shown to the walker instead of retried forever |
| **Last-write-wins with millisecond timestamps** | Eloquent drops milliseconds by default; this repo shows why that breaks conflict resolution and how to keep them |
| **Pull sync for a second device** | A cursor on `(updated_at, id)` so changes in the same millisecond are never skipped, page-by-page application in one transaction, and lazy photo downloads |
| **A service worker you can trust** | Workbox `injectManifest`, a precached app shell, cached map tiles that respect the OSM tile policy, Background Sync on Chromium, and an update prompt that never reloads under a walker mid-entry |
| **Safari and iOS reality** | Persistent storage requests, quota warnings, an install prompt with iOS instructions, and a "free up space" option that keeps thumbnails |
| **Schema migrations on the device** | Dexie version upgrades, tested by opening a version 1 database full of data and upgrading it |
| **Bilingual from day one** | Welsh and English throughout, with Laravel's `lang/` files exported to JSON at build time so translations work offline |
| **Local geography, offline** | Ordnance Survey grid references (WGS84 to OSGB36 in the browser) and national park detection by point-in-polygon on cached boundaries |

## Tech stack

| Layer | Choice |
|-------|--------|
| Back end | Laravel 13, PHP 8.4, MySQL 8.4, Redis (queue, cache, sessions) |
| Auth | Sanctum personal access tokens for the app; session login for the web map |
| Runtime | Docker with FrankenPHP; separate queue worker container |
| Front end | Blade app shell, Alpine.js, Tailwind CSS v4, Vite |
| Offline | `vite-plugin-pwa` (Workbox `injectManifest`), Dexie.js over IndexedDB |
| Maps | Leaflet with OpenStreetMap tiles; walk routes and park boundaries from OpenStreetMap (ODbL) |
| Images | Client-side resize to 1600px JPEG; Intervention Image v4 queued job for thumbnails and EXIF stripping |
| Tests | Pest (PHP), Vitest with `fake-indexeddb` (sync engine), Playwright (end to end, offline included) |
| Quality | Laravel Pint with strict types, Husky git hooks, osv-scanner |

## Getting started

### Prerequisites

- **Docker** with Compose, and **Make**
- **PHP 8.4, Node and npm on the host**, for the git hooks: run `npm install` once in the project root to activate Husky (the pre-commit hook runs Pint)
- **osv-scanner** for the pre-commit security audit: `go install github.com/google/osv-scanner/cmd/osv-scanner@latest`
- Free ports: **8000** (app), **3307** (MySQL; 3306 is commonly taken), **5173** (Vite dev server). Change `APP_PORT`, `FORWARD_DB_PORT` or `VITE_PORT` in `.env` if they clash

**MCP servers for Claude Code** (configured in `.mcp.json`):

- **GitHub** authenticates with `gh auth token`; run `gh auth login` once on each machine
- **MySQL** reads `MYSQL_HOST`, `MYSQL_PORT`, `MYSQL_USER`, `MYSQL_PASSWORD` and `MYSQL_DATABASE` from your shell (default `127.0.0.1:3306`, user `root`). For this project export `MYSQL_PORT=3307`, `MYSQL_USER=crwydro`, `MYSQL_PASSWORD=secret`, `MYSQL_DATABASE=crwydro`
- **Playwright** and **Context7** need only `npx`

### Run it

Copy `.env.example` to `.env`, then:

1. `make build` builds the app image (FrankenPHP, PHP extensions, Node)
2. `make up` starts the app, queue worker, MySQL and Redis
3. `make setup` installs dependencies, creates the app key, migrates and seeds, and builds the assets
4. Open **http://localhost:8000** and log in under Sync as `walker@crwydro.test` / `password`

Then, as needed:

5. `make dev` runs Vite with hot reload (the service worker is off in dev; use `make assets` to test offline behaviour)
6. `make tunnel` shares the app on a temporary public HTTPS URL
7. `make test` runs Pest, `make test-js` runs Vitest, `make e2e` runs the Playwright suite in its own container
8. `make help` lists every target

To try it on a phone or with a screen capture tool, run `make tunnel` for a temporary public HTTPS URL (a Cloudflare quick tunnel; service workers need HTTPS away from localhost). Unlike ngrok's free plan, it shows no warning page, so capture tools and the service worker reach the app directly. The URL can take a minute to resolve; stop it with Ctrl+C.

To try it offline: open the app once, then switch your browser to offline in DevTools (or put your phone in airplane mode) and keep logging entries.

## Environment variables

Crwydro-specific and changed defaults; everything else is standard Laravel.

| Key | Default | Description |
|-----|---------|-------------|
| `APP_URL` | `http://localhost:8000` | Base URL |
| `TRUSTED_PROXIES` | `*` | Proxies whose `X-Forwarded-*` headers are trusted, so URLs come out as `https` behind ngrok or a load balancer. Set the proxy's IPs in production |
| `APP_PORT` | `8000` | Host port for the app container |
| `VITE_PORT` | `5173` | Host port for the Vite dev server |
| `DB_HOST` / `DB_PORT` | `mysql` / `3306` | MySQL inside the Compose network |
| `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` | `crwydro` / `crwydro` / `secret` | MySQL credentials |
| `FORWARD_DB_PORT` | `3307` | Host port mapped to MySQL |
| `REDIS_HOST` | `redis` | Redis inside the Compose network |
| `QUEUE_CONNECTION` / `CACHE_STORE` / `SESSION_DRIVER` | `redis` | Queue for photo processing, cache, web sessions |
| `PHOTOS_DISK` | `local` | Filesystem disk for photos; `s3` in production |
| `PHOTO_MAX_BYTES` | `8388608` | Largest accepted photo upload (8 MB) |
| `MIN_CLIENT_API` | `1` | Oldest app API version still accepted; older cached apps get `426` and keep their outbox until they update |
| `APP_FAKER_LOCALE` | `en_GB` | Locale for seeded data |

## Endpoints

`request.http` at the project root has a ready-made request for each endpoint (VS Code REST Client and JetBrains HTTP Client). Log in first and paste the token into `@token`.

| Method | Endpoint | Purpose |
|--------|----------|---------|
| `POST` | `/api/login` | Issue a long-lived Sanctum token |
| `GET` | `/api/ping` | Reachability check before syncing (no auth, never cached) |
| `PUT` | `/api/entries/{ulid}` | Create or update an entry; repeats are no-ops, older edits are ignored |
| `DELETE` | `/api/entries/{ulid}` | Soft delete; safe to repeat |
| `PUT` | `/api/entries/{ulid}/photos/{ulid}` | Upload one JPEG as the raw body; repeats are no-ops |
| `GET` | `/api/sync?since={cursor}` | Pull everything changed since the cursor, including deletes |
| `GET` | `/api/photos/{photo}/{variant?}` | Download a photo or its thumbnail |
| `GET` | `/api/export/entries.gpx`, `.csv` | Export synced entries |
| `GET` | `/login`, `/map` | Web login and a map of every synced entry, for reviewing a trip on a laptop |

Every API response carries `X-Crwydro-Api`, and the app sends `X-Crwydro-Client-Api`, so a cached app can tell when it is out of date.

## Architecture notes

**Sync is a client-driven outbox.** `resources/js/sync/processor.js` drains rows in order. Once an entry's own write is waiting or failed, its later rows wait too (photos need the entry on the server first), but a failing photo holds nothing back. Each outbox row carries a revision number, so an edit made while its upload is in flight is never lost when the old request succeeds. The same code runs in the page and in the service worker, coordinated by the Web Locks API so two copies never drain at once.

**The server is idempotent by design, not by luck.** Because IDs come from the device, `PUT /api/entries/{ulid}` is a true upsert. A concurrent retry that loses the insert race becomes an update instead of a 500. A photo's storage path is built from its IDs, so a retried upload overwrites rather than orphans a file.

**Only one page is ever cached.** Every view is a hash route on `/`, so the service worker precaches a single Blade shell. Fonts are self-hosted, translations are bundled, and walk routes and park boundaries are precached GeoJSON: nothing the walker needs is fetched at runtime.

**Updates wait for the walker.** A new build shows an update bar instead of swapping code mid-entry. If the server's API moves on, an older cached app gets `426` before it can write in an outdated shape, and its outbox simply waits.

**Design follows the subject.** The look borrows from Ordnance Survey Explorer maps: heather, peat, tarn and bracken colours, contour lines on the login page, dashed routes, and grid references as the main way to show a location. Status colours mean something: bracken is "only on this device", moss is "safe on the server". The typeface is Atkinson Hyperlegible Next, chosen to stay readable in glare with cold hands. Every colour pairing passes WCAG AA in light and dark.

**Data sources and licences.** Walk routes come from OpenStreetMap hiking relations via the Waymarked Trails API, and national park boundaries from OpenStreetMap via Nominatim, both under the ODbL with attribution in the app. `make routes` and `make parks` re-import them. There is no OpenStreetMap route for "Tryfan and the Glyderau", so the nearby Cwm Idwal Walk stands in.

**Known limits for v1.** One walker per device. Photos already on the server can't be deleted. Background Sync is Chromium-only (Safari and Firefox sync while the app is open). The Welsh copy should be reviewed by a native speaker. `CONTEXT.md` has the fuller orientation.
