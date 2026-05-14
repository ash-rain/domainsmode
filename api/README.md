# DomainsMode — API

Stateless REST API serving domain and content data. One codebase, two containers (api1 → `network_1`, api2 → `network_2`).

## Endpoints

```
GET  /api/domains                  → list domains with nested content
POST /api/domains/{id}/content     → create content (X-User-Id header required)
```

Bearer token auth via `API_KEY` env var. See `app/Http/Middleware/VerifyApiKey.php`.

## Running Tests

```bash
docker-compose exec api1 php artisan test
```

Uses SQLite in-memory. Auth bypassed via `API_KEY=""` in `phpunit.xml`.

## Full Documentation

See [DESIGN.md](../DESIGN.md) in the project root.
