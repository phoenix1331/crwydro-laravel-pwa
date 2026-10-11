# Crwydro - Context

Offline-first, bilingual (Welsh/English) PWA field journal for walkers in the Welsh hills. The device is the primary store (IndexedDB via Dexie); Laravel is the backup and second view. Playground repo for PWA, IndexedDB and resilient sync patterns.

## Runtime

| Service | Image | Host port | Notes |
|---------|-------|-----------|-------|
| `app` | `dunglas/frankenphp:php8.4` (see `Dockerfile`) | 8000 | Project mounted at `/app`, runs as host UID/GID |
| `mysql` | `mysql:8.4` | 3307 | 3306 is taken on the dev host; `FORWARD_DB_PORT` |
| `redis` | `redis:7-alpine` | not exposed | Sessions, cache, queue |

All artisan, composer and test commands run inside the container: `docker compose exec app ...`

## Architecture at a glance

| Path | Purpose |
|------|---------|
| `app/` | Laravel API and server-rendered views |
| `routes/api.php` | JSON sync API (Sanctum token auth) |
| `resources/js/` | PWA client: Dexie store, outbox processor, UI |
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
| Alpine.js over Vue | Views are small; revisit if they grow |

## Auth

| Item | Detail |
|------|--------|
| Login | `POST /api/login` with `email`, `password`, `device_name`; returns `token` and `user` |
| Token | Sanctum personal access token, `sanctum.expiration` null (never expires); one token per device name |
| Guard | `auth:sanctum` on all `/api/*` routes except `login` and `ping` |
| Throttle | `throttle:10,1` on login |
| Demo user | `walker@crwydro.test` / `password` (seeded) |
