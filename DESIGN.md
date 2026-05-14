# DomainsMode — Design Document

Current state of the system as built. Use this as the reference when generating new code, adding features, or onboarding.

---

## Architecture

### Development (local)

```
  Browser
    │
    │  https://localhost:8443 / :8444 / :8445
    ▼
┌─────────────────────────────────────────────────────────────────┐
│  nginx  (docker/nginx/Dockerfile)                               │
│  TLS termination — self-signed cert auto-generated on startup   │
│                                                                 │
│  :8443  SSL → ui:9000    (PHP-FPM)                              │
│  :8444  SSL → api1:9000  (PHP-FPM)                              │
│  :8445  SSL → api2:9000  (PHP-FPM)                              │
└────────────┬──────────────────────┬────────────────────────────┘
             │                      │
             ▼                      ▼
┌────────────────────┐   ┌─────────────────────────────────────┐
│  UI  (PHP-FPM)     │   │  nginx → API1 / API2 (PHP-FPM)     │
│  Laravel 12        │   │  Bearer api1_key / api2_key         │
│  Livewire 3        │   │  DB: network_1 / network_2          │
│  DB: domainsmode_ui│   │  same Laravel codebase              │
└────────────────────┘   └─────────────────────────────────────┘
             │                      │
             └──────────┬───────────┘
                        ▼
               ┌─────────────────┐
               │  MySQL :3306    │
               │  network_1      │
               │  network_2      │
               │  domainsmode_ui │
               └─────────────────┘
```

### Production (VPS)

```
  Browser
    │
    │  https://domainsmode.nsh.one
    ▼
┌──────────────┐
│  Cloudflare  │  DNS proxy, DDoS protection, edge caching
│              │  SSL mode: Full (Strict) via page rule
└──────┬───────┘
       │
       ▼
┌─────────────────────────────────────────────────────────────────┐
│  nginx  (docker/nginx/Dockerfile.prod)                          │
│  TLS termination — Let's Encrypt via Certbot                    │
│                                                                 │
│  :443   SSL → ui:9000     (PHP-FPM)   domainsmode.nsh.one      │
│  :443   SSL → grafana:3000 (proxy)    domainsmode.nsh.one/grafana│
│  :8081  HTTP → api1:9000   (PHP-FPM)  internal only             │
│  :8082  HTTP → api2:9000   (PHP-FPM)  internal only             │
│  :80    ACME challenge + redirect → 443                         │
│                                                                 │
│  UI → API calls use internal Docker URLs (http://nginx:8081/    │
│  8082) — plain HTTP within Docker network, never exposed        │
└────────────┬──────────────────────┬────────────────────────────┘
             │                      │
             ▼                      ▼
┌────────────────────┐   ┌─────────────────────────────────────┐
│  UI  (PHP-FPM)     │   │  API1 / API2 (PHP-FPM)             │
│  Laravel 12        │   │  Bearer api1_key / api2_key         │
│  Livewire 3        │   │  DB: network_1 / network_2          │
│  DB: domainsmode_ui│   │  same Laravel codebase              │
└────────────────────┘   └─────────────────────────────────────┘
             │                      │
             └──────────┬───────────┘
                        ▼
               ┌─────────────────┐
               │  MySQL :3306    │    ┌────────────────────┐
               │  network_1      │    │  certbot (sidecar) │
               │  network_2      │    │  auto-renews certs │
               │  domainsmode_ui │    │  every 12 hours    │
               └─────────────────┘    └────────────────────┘
```

---

## Docker Services

### Development (`docker-compose.yml`)

| Service | Dockerfile                  | Ports (host)    | Database        | Notes                             |
|---------|-----------------------------|-----------------|-----------------|-----------------------------------|
| mysql   | mysql:8 (image)             | 3307→3306       | —               | Imports network_1/2 SQL dumps     |
| api1    | docker/api.Dockerfile       | —               | network_1       | API_KEY env var set               |
| api2    | docker/api.Dockerfile       | —               | network_2       | API_KEY env var set (differs)     |
| nginx   | docker/nginx/Dockerfile     | 8443, 8444, 8445 | —              | TLS termination, reverse proxy, /grafana proxy |
| ui      | docker/ui.Dockerfile        | —               | domainsmode_ui  | Node 20 for asset build           |
| loki    | grafana/loki:3.5.0 (image)  | 3100            | —               | Log aggregation                   |
| grafana | grafana/grafana:11.6.0 (image) | 3000         | —               | Log visualisation via /grafana path |

### Production (`docker-compose.prod.yml`)

| Service | Dockerfile                  | Ports (host)       | Database        | Notes                             |
|---------|-----------------------------|---------------------|-----------------|-----------------------------------|
| mysql   | mysql:8 (image)             | —                   | —               | Not exposed to host               |
| api1    | docker/api.Dockerfile       | —                   | network_1       | API_KEY from .env                 |
| api2    | docker/api.Dockerfile       | —                   | network_2       | API_KEY from .env                 |
| nginx   | docker/nginx/Dockerfile.prod | 80, 443             | —              | Let's Encrypt certs, /grafana proxy |
| ui      | docker/ui.Dockerfile        | —                   | domainsmode_ui  | APP_URL=https://${DOMAIN}         |
| certbot | certbot/certbot (image)     | —                   | —               | Renews certs every 12h            |

### Entrypoints

Both Dockerfiles set `ENTRYPOINT ["entrypoint.sh"]` so containers self-initialise on every `docker-compose up`:

**`docker/api-entrypoint.sh`**
```sh
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
# wait for MySQL...
php artisan migrate --force
exec php-fpm
```

**`docker/ui-entrypoint.sh`**
```sh
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
# wait for MySQL...
php artisan migrate --force
php artisan db:seed --force
exec php-fpm
```

Volume mounts (`./api:/var/www/html`, `./ui:/var/www/html`) override Dockerfile permission changes, so the entrypoints fix ownership on every start.

### Default Credentials

The UI seeder (`DatabaseSeeder.php`) runs `User::firstOrCreate` on every startup — idempotent.

| Email                     | Password   |
|---------------------------|------------|
| `admin@domainsmode.local` | `password` |

---

## Database Schema

### Pre-existing (imported from SQL dumps — do not migrate)

```sql
-- In network_1 and network_2:
CREATE TABLE domains (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    domain        VARCHAR(255) UNIQUE,
    nameserver_1  VARCHAR(255),
    nameserver_2  VARCHAR(255),
    nameserver_3  VARCHAR(255),
    nameserver_4  VARCHAR(255),
    mx_record     VARCHAR(255),
    a_record      VARCHAR(255),
    created_at    TIMESTAMP,
    updated_at    TIMESTAMP
);
-- ~450 rows each
```

### Added by migration

```sql
-- In network_1 and network_2 (via api artisan migrate):
CREATE TABLE contents (
    id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    domain_id  BIGINT UNSIGNED REFERENCES domains(id) ON DELETE CASCADE,
    user_id    BIGINT UNSIGNED,
    title      VARCHAR(255),
    body       TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE KEY uq_domain_user (domain_id, user_id)
);
```

---

## API Layer

### Authentication

Each API container requires a Bearer token in `Authorization: Bearer <key>`. Tokens are set via the `API_KEY` environment variable in `docker-compose.yml` and differ between api1 and api2. If `API_KEY` is empty the middleware passes all requests — used in PHPUnit to avoid mocking auth headers.

Middleware: `app/Http/Middleware/VerifyApiKey.php`
Registered as alias `api.key` in `bootstrap/app.php`.

### Endpoints

All routes are wrapped in `Route::middleware('api.key')`.

```
GET  /api/domains
     → 200  array of domain objects, each with nested content array
     → 401  missing or invalid Bearer token

POST /api/domains/{domain}/content
     Headers: Authorization: Bearer <key>
              X-User-Id: <int>
     Body:    { "title": "...", "body": "..." }
     → 201  created content object
     → 401  auth failure
     → 409  content already exists for this user + domain
     → 422  validation error (title or body missing)
     → 404  domain not found
```

### Key Files

```
api/app/Models/Domain.php                        HasMany contents
api/app/Models/Content.php                       BelongsTo domain
api/app/Http/Controllers/Api/DomainController.php
api/app/Http/Requests/StoreContentRequest.php    Form Request (validation + X-User-Id check)
api/app/Policies/DomainPolicy.php                Gates: domain.viewAny, domain.createContent
api/app/Http/Middleware/VerifyApiKey.php
api/routes/api.php
api/tests/TestCase.php                           putenv('API_KEY=') in setUp
api/tests/Feature/DomainApiTest.php              Endpoint integration tests
api/tests/Unit/VerifyApiKeyTest.php              Middleware unit tests
api/tests/Unit/DomainPolicyTest.php              Policy unit tests
api/tests/Unit/StoreContentRequestTest.php       Form Request unit tests
api/tests/Unit/DomainModelTest.php               Domain model unit tests
api/tests/Unit/ContentModelTest.php              Content model unit tests
```

---

## UI Layer

### Config

`ui/config/networks.php` — maps network keys to API URLs and Bearer tokens:

```php
return [
    'network_1' => [
        'name'    => 'Network 1',
        'api_url' => env('API1_URL', 'http://api1:8001'),
        'api_key' => env('API1_KEY', ''),
    ],
    'network_2' => [
        'name'    => 'Network 2',
        'api_url' => env('API2_URL', 'http://api2:8002'),
        'api_key' => env('API2_KEY', ''),
    ],
];
```

### NetworkApiClient

`ui/app/Services/NetworkApiClient.php` — single point of contact with API containers.

```
Constructor:  __construct(string $baseUrl, string $networkName, string $apiKey = '')
getDomains(): Collection          GET /api/domains, tags each domain with network metadata
createContent(domainId, userId, title, body): array
                                  POST /api/domains/{id}/content
                                  Returns ['success' => bool, 'status' => int, 'data' => [...]]
```

Injects `Authorization: Bearer` header when `$apiKey` is non-empty. Handles timeouts and HTTP errors — never throws, always returns a structured result.

### Livewire Components

**`app/Livewire/Domains/DomainsList`**
View: `livewire.pages.domains.domains-list`

| Property            | Default | Description                                 |
|---------------------|---------|---------------------------------------------|
| `$search`           | `''`    | Text filter on domain name                  |
| `$filterNetwork`    | `''`    | Filter by network key (network_1/network_2) |
| `$filterNameserver` | `''`    | Substring match on any nameserver field     |
| `$filterMx`         | `''`    | Substring match on mx_record                |
| `$filterARecord`    | `''`    | Substring match on a_record                 |
| `$perPage`          | `50`    | Rows per page (options: 10/25/50/100/250)   |
| `$page`             | `1`     | Current page                                |
| `$selectedDomains`  | `[]`    | Array of selected domain identifiers        |

Computed properties: `filteredDomains`, `paginatedDomains`, `totalPages`, `pageLinks`, `networkOptions`.

All `updated*` hooks reset `$page = 1`. Selection is toggled only by the checkbox (`wire:click`) — the table row has no click handler.

**`app/Livewire/Domains/BulkContentCreate`**
Route: `GET /domains/bulk-create` (named `domains.bulk-create`)
View: `livewire.pages.domains.bulk-content-create`
Full-page Livewire component with `#[Layout('layouts.app')]`.

Has its own domain loading, search/filter, and selection (select all / deselect all). Reads and writes `session('selected_domains')` so selection persists across page navigation — domains selected on the DomainsList page carry over to this page and vice versa.

Uses `BulkContentForm` (Livewire Form object) for validation and `Gate::authorize('create-content')` for access control. On submit:
1. Authorizes via the `create-content` gate (backed by `ContentPolicy`)
2. Validates via `$this->form->validate()`
3. Groups selected domains by network
4. Calls `NetworkApiClient::createContent()` for each selected domain
5. Aggregates results into `$results` array (per-domain success/failure)
6. Displays inline result messages — green for 201, red for 409/other errors

**`app/Livewire/Forms/BulkContentForm`**
Livewire Form object — owns `$title` and `$body` with `#[Validate]` attributes.

**`app/Policies/ContentPolicy`**
Registered as gate `create-content` in `AppServiceProvider`. Currently allows any authenticated user. Extend this when role-based restrictions are needed.

### Shared Blade Components

**`components/domains/table.blade.php`**
Shared domain table used by both `DomainsList` and `BulkContentCreate`. Controlled via props:

| Prop              | Default | Description                                       |
|-------------------|---------|---------------------------------------------------|
| `compact`         | `false` | Compact mode: 3 columns, scrollable, select all   |
| `domains`         | `[]`    | Array of domain records to display                 |
| `selectedDomains` | `[]`    | Currently selected domains                         |
| `networkOptions`  | `[]`    | Network names for filter dropdown                  |
| `loading`         | `false` | Show loading spinner                               |
| `hasExtraFilters` | `false` | Show nameserver/MX/A record filter row             |
| `page`, `perPage`, `totalPages`, `pageLinks`, `perPageOptions` | — | Pagination (full mode only) |

The component uses `wire:` directives that bind to whichever parent Livewire component includes it. Both parents must expose: `toggleDomain()`, `isDomainSelected()`, `$search`, `$filterNetwork`. Full mode additionally needs: `clearFilters()`, `goToPage()`, `$filterNameserver`, `$filterMx`, `$filterARecord`, `$perPage`. Compact mode needs: `selectAll()`, `deselectAll()`.

### View Paths

```
ui/resources/views/
  components/
    domains/
      table.blade.php                   ← shared domain table component
  livewire/
    pages/
      domains/
        domains-list.blade.php
        bulk-content-create.blade.php
  dashboard.blade.php                   ← embeds DomainsList only
```

Dashboard embeds `DomainsList` only. A "Bulk Create Content" button in the header links to the dedicated page:
```blade
<livewire:domains.domains-list />
```

`BulkContentCreate` is a full-page component at `/domains/bulk-create` with its own layout, domain selection, and a "Back to Domains" link.

---

## Testing

### Running Tests

```bash
docker-compose exec api1 php artisan test     # API suite (covers both APIs)
docker-compose exec ui   php artisan test     # UI suite
```

### API Tests

API tests use SQLite in-memory DB (`phpunit.xml`) and bypass auth via `API_KEY=""` env override.

| File                                    | Tests | Covers                                          |
|-----------------------------------------|-------|-------------------------------------------------|
| `tests/Feature/DomainApiTest.php`       | 6     | Endpoints: list domains, create content, 409/422/404 |
| `tests/Unit/VerifyApiKeyTest.php`       | 4     | Middleware: empty key, missing/wrong/correct bearer  |
| `tests/Unit/DomainPolicyTest.php`       | 2     | Policy: viewAny, createContent                  |
| `tests/Unit/StoreContentRequestTest.php`| 2     | Form Request: authorize, rules                  |
| `tests/Unit/DomainModelTest.php`        | 4     | Model: fillable, HasMany, uniqueness            |
| `tests/Unit/ContentModelTest.php`       | 5     | Model: fillable, BelongsTo, unique constraint, cascade delete |

### UI Tests

UI tests use MySQL (`phpunit.xml` points at `domainsmode_ui`), `RefreshDatabase`, and `Http::fake()` for API calls.

| File                                     | Tests | Covers                                          |
|------------------------------------------|-------|-------------------------------------------------|
| `tests/Unit/ContentPolicyTest.php`       | 4     | Policy + Gate: auth allows, guest denied        |
| `tests/Unit/NetworkApiClientTest.php`    | 8     | API client: getDomains, createContent, bearer token |
| `tests/Unit/BulkContentFormTest.php`     | 3     | Form object: class exists, properties           |
| `tests/Feature/RoutingTest.php`          | 6     | Routes: guest redirects, auth access, welcome redirect |
| `tests/Feature/DomainsListTest.php`      | 12    | Livewire: render, load, filter, pagination, selection |
| `tests/Feature/BulkContentCreateTest.php`| 14    | Livewire: render, filter, select/deselect, validation, create content, form reset |
| `tests/Feature/Auth/*.php`               | 15    | Breeze auth: login, register, password, email verification |
| `tests/Feature/ProfileTest.php`          | 4     | Profile: display, update, delete                |

### Testing Patterns

- **API tests**: no mocking needed — SQLite in-memory DB, auth disabled via env
- **UI Livewire tests**: `config()->set('networks', [...])` to control network config, `Http::fake()` to stub API responses, `Livewire::actingAs($user)->test(Component::class)` for authenticated component testing
- **Validation tests**: use `assertHasErrors(['form.field' => 'rule'])` on Livewire components
- **Session state**: tested within a single component lifecycle (toggle on → assert present → toggle off → assert absent). Cross-component session persistence is verified manually

---

## Environment Variables

### Development (hardcoded in `docker-compose.yml`)

#### API containers (api1 and api2)

```env
APP_KEY=...
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=network_1          # network_2 for api2
DB_USERNAME=root
DB_PASSWORD=secret
API_KEY=<bearer-token>         # different value per container
```

#### UI container

```env
APP_KEY=...
DB_CONNECTION=mysql
DB_HOST=mysql
DB_DATABASE=domainsmode_ui
DB_USERNAME=root
DB_PASSWORD=secret
API1_URL=https://nginx:8444          # dev: HTTPS (self-signed)
API2_URL=https://nginx:8445          # prod: http://nginx:8081 / :8082
API1_KEY=<same as api1 API_KEY>
API2_KEY=<same as api2 API_KEY>
```

### Production (`.env` on VPS, never committed)

```env
DOMAIN=domainsmode.nsh.one
CERTBOT_EMAIL=admin@nsh.one

DB_PASSWORD=<strong-random-password>
APP_KEY_API=base64:<generate-with-artisan>
APP_KEY_UI=base64:<generate-with-artisan>
API1_KEY=<random-hex-64>
API2_KEY=<random-hex-64>
```

All production secrets are referenced via `${VAR}` in `docker-compose.prod.yml`. Template provided as `.env.prod.example`.

---

## Log Monitoring

### Architecture

```
┌────────┐  ┌────────┐  ┌────────┐  ┌────────┐
│  api1  │  │  api2  │  │   ui   │  │ nginx  │
└───┬────┘  └───┬────┘  └───┬────┘  └───┬────┘
    │           │           │           │
    └───────────┴─────┬─────┴───────────┘
                      │ Docker Loki logging driver
                      ▼
               ┌─────────────┐
               │  Loki :3100 │
               └──────┬──────┘
                      │
                      ▼
               ┌──────────────┐
               │ Grafana :3000│
               └──────────────┘
```

### How It Works

All application containers (api1, api2, ui, nginx) use the Docker Loki logging driver to ship stdout/stderr to Loki. Each container has a `service` label for filtering.

### Prerequisites

Install the Loki Docker driver once on the Docker host:

```bash
docker plugin install grafana/loki-docker-driver:3.7.2-arm64 --alias loki --grant-all-permissions
# Intel Mac / Linux amd64: use 3.7.2-amd64 instead
```

### Grafana Access

- **Dev:** `https://localhost:8443/grafana`
- **Prod:** `https://<DOMAIN>/grafana`
- Credentials: admin / admin (anonymous read access also enabled)
- Pre-provisioned dashboard: **DomainsMode Logs** with service filter dropdown
- Grafana serves from a sub-path via `GF_SERVER_ROOT_URL` and `GF_SERVER_SERVE_FROM_SUB_PATH=true`. Nginx proxies `/grafana/` to the Grafana container on port 3000.

### Provisioning

- `docker/loki/loki-config.yml` — Loki server config (filesystem storage, single-instance)
- `docker/grafana/provisioning/datasources/loki.yml` — auto-provisions Loki as default datasource
- `docker/grafana/provisioning/dashboards/json/domainsmode.json` — pre-built dashboard with three panels: all logs, per-service filter, error-only

### Useful LogQL Queries

```logql
{service="ui"}                                    # all UI logs
{service="api1"} |~ "(?i)error"                   # API1 errors
{service=~"api1|api2"} |= "content"               # content-related across both APIs
{service="nginx"} |~ "POST" |~ "4[0-9]{2}"        # nginx 4xx on POST requests
```

---

## Key Design Decisions

### One API codebase, two containers

The two APIs share identical code. Differentiation is purely configuration: each container gets a different `DB_DATABASE` and `API_KEY`. This avoids duplicating code for what is today identical behaviour. If network business logic diverges in the future, fork the codebase at that point.

### TLS — dual strategy

**Development:** `docker/nginx/Dockerfile` extends `nginx:alpine` with `openssl`. The entrypoint generates a 4096-bit RSA self-signed cert on first start into a named volume (`nginx_ssl`). HTTPS only — `:8443` UI, `:8444`/`:8445` APIs. `NetworkApiClient` uses `withoutVerifying()` for internal calls since the cert is self-signed.

**Production:** `docker/nginx/Dockerfile.prod` uses Let's Encrypt certificates issued by Certbot. On first deploy, the entrypoint generates a temporary self-signed fallback so nginx can start and serve the ACME challenge. `deploy.sh` then runs Certbot to obtain real certs and reloads nginx. A `certbot` sidecar container renews certs every 12 hours.

Port strategy in production: `:443` for the UI (behind Cloudflare). Port `:80` serves only the ACME challenge and redirects to HTTPS. APIs are **internal-only** — nginx exposes them on `:8081`/`:8082` inside the Docker network (plain HTTP, no SSL). The UI calls APIs via `http://nginx:8081` / `:8082` — no TLS overhead or `withoutVerifying()` needed for internal traffic. APIs are never exposed to the public internet.

Security headers on the UI vhost: `Strict-Transport-Security`, `X-Frame-Options`, `X-Content-Type-Options`. `fastcgi_param HTTPS on` ensures Laravel generates correct `https://` URLs and secure cookies.

### Cloudflare integration

Cloudflare proxies the domain (`domainsmode.nsh.one`) providing DDoS protection and edge caching. SSL mode is **Full (Strict)** via a page rule (`domainsmode.nsh.one/*`) since the origin has valid Let's Encrypt certs. Cloudflare passes ACME challenge requests through to the origin, so HTTP-01 validation works behind the proxy. For initial cert issuance, temporarily grey-cloud the DNS record so Certbot can reach the origin directly.

Firewall on the VPS should allow ports 80, 443 from Cloudflare IP ranges only (plus SSH). No other ports need to be exposed — APIs and Grafana are accessed via the main HTTPS vhost.

### No Sanctum on the APIs

User identity is passed as `X-User-Id` header from the UI. A static Bearer token is sufficient for service-to-service auth. In production the APIs are internal-only (Docker network, no public ports) and protected by Bearer tokens — treat API keys as secrets.

### Bulk create partial success

The UI never batches all domains into one API call. It fans out — one HTTP request per selected domain — so each can succeed or fail independently. The results array is built from all responses and rendered inline. A 409 on domain X does not affect domain Y.

### Idempotent seeding

`DatabaseSeeder` uses `firstOrCreate` so `php artisan db:seed --force` (run on every `docker-compose up` by the entrypoint) is safe to call repeatedly without creating duplicates.

### Pagination and filtering in PHP, not SQL

The `DomainsList` component loads all domains from both APIs once on mount, then applies filtering and pagination in PHP via computed properties. This avoids round-trips to the API on every filter keystroke and keeps the API interface simple (no query params needed).

---

## CI/CD Pipeline

### GitHub Actions (`.github/workflows/ci.yml`)

```
  push / PR to main
        │
        ├─► test-api    PHP 8.3 + SQLite in-memory
        │                composer install → php artisan test
        │
        ├─► test-ui     PHP 8.3 + Node 20 + MySQL service
        │                composer install → npm ci → npm run build → php artisan test
        │
        └─► deploy      (only on push to main, after both tests pass)
                         SSH to VPS → git pull → docker compose up --build
                         → config:cache, route:cache, view:cache
```

### GitHub Secrets Required

| Secret       | Value                                |
|--------------|--------------------------------------|
| `VPS_HOST`   | VPS IP address or hostname           |
| `VPS_USER`   | SSH user (e.g. `deploy`)             |
| `VPS_SSH_KEY` | Private SSH key for the deploy user |

### First-Time VPS Setup (`deploy.sh`)

Run manually once:
1. Clones repo to `~/domainsmode`
2. Copies `.env.prod.example` → `.env` (edit with real secrets)
3. Starts all services (nginx boots with self-signed fallback)
4. Runs Certbot to obtain Let's Encrypt certs for the domain
5. Reloads nginx with real certs

After initial setup, GitHub Actions handles all subsequent deploys via SSH.

---

## Service URLs

### Development

| Endpoint | URL                            |
|----------|--------------------------------|
| UI       | https://localhost:8443         |
| API 1    | https://localhost:8444         |
| API 2    | https://localhost:8445         |
| Grafana  | https://localhost:8443/grafana |
| Loki     | http://localhost:3100          |

### Production

| Endpoint | URL                                    |
|----------|----------------------------------------|
| UI       | https://domainsmode.nsh.one            |
| Grafana  | https://domainsmode.nsh.one/grafana    |
| API 1    | http://nginx:8081 (internal only)      |
| API 2    | http://nginx:8082 (internal only)      |

---

## Common Commands

### Development

```bash
# Start / rebuild
docker-compose up -d --build

# Full teardown (drops volumes — MySQL data AND the SSL cert will be regenerated)
docker-compose down -v && docker-compose up -d --build

# Rebuild a single service
docker-compose up -d --build nginx

# Inspect the auto-generated certificate
docker-compose exec nginx openssl x509 -in /etc/nginx/ssl/cert.pem -noout -text | grep -E "Subject|Not (Before|After)|DNS"

# Reload nginx config without downtime (e.g. after editing vhost files)
docker exec domainsmode-nginx-1 nginx -s reload

# Run artisan in a container
docker-compose exec api1 php artisan <command>
docker-compose exec ui   php artisan <command>

# Tail logs
docker-compose logs -f ui
docker-compose logs -f nginx

# MySQL shell
docker-compose exec mysql mysql -u root -psecret

# PHPUnit
docker-compose exec api1 php artisan test
docker-compose exec ui   php artisan test
```

### Production

```bash
# First-time setup
scp .env.prod.example user@vps:~/domainsmode/.env  # then edit with real secrets
ssh user@vps 'cd ~/domainsmode && bash deploy.sh'

# Manual deploy (normally handled by GitHub Actions)
ssh user@vps 'cd ~/domainsmode && git pull && docker compose -f docker-compose.prod.yml up -d --build'

# View logs
ssh user@vps 'cd ~/domainsmode && docker compose -f docker-compose.prod.yml logs -f nginx'

# Renew certs manually
ssh user@vps 'cd ~/domainsmode && docker compose -f docker-compose.prod.yml run --rm certbot renew && docker compose -f docker-compose.prod.yml exec nginx nginx -s reload'

# MySQL shell (production)
ssh user@vps 'cd ~/domainsmode && docker compose -f docker-compose.prod.yml exec mysql mysql -u root -p'
```

---

## File Map — Monitoring

```
docker/loki/loki-config.yml                       Loki server configuration
docker/grafana/provisioning/datasources/loki.yml   Auto-provisions Loki datasource
docker/grafana/provisioning/dashboards/dashboards.yml  Dashboard provider config
docker/grafana/provisioning/dashboards/json/domainsmode.json  Pre-built log dashboard
```

## File Map — Production Additions

```
.github/workflows/ci.yml              GitHub Actions pipeline (test + deploy)
docker-compose.prod.yml                Production compose (certbot, Let's Encrypt, no exposed MySQL)
docker/nginx/Dockerfile.prod           Production nginx (openssl for fallback certs)
docker/nginx/prod-entrypoint.sh        Generates self-signed fallback if no LE cert exists
docker/nginx/prod/ui.conf.template     :443 SSL vhost for UI + /grafana proxy
docker/nginx/prod/api1.conf.template   :8081 internal HTTP vhost for API1
docker/nginx/prod/api2.conf.template   :8082 internal HTTP vhost for API2
.env.prod.example                      Template for production secrets
deploy.sh                              First-time VPS setup script
```

---

## Conventions

### Validation

- **API controllers**: never validate inline. Extract rules to a Form Request class (`app/Http/Requests/`). Type-hint the Form Request in the controller method signature — Laravel resolves and validates automatically.
- **Livewire components**: never put validation rules inline in `$this->validate()`. Use a Livewire Form object (`app/Livewire/Forms/`) with `#[Validate]` attributes on properties. Call `$this->form->validate()` in the action method.
- Blade `wire:model` and `@error` directives use the `form.` prefix (e.g. `wire:model="form.title"`, `@error('form.title')`).

### Access Control

- **UI**: use Gates and Policies for authorization. Call `Gate::authorize()` in Livewire action methods before any business logic. Register gates in `AppServiceProvider::boot()`.
- **API**: Bearer token middleware (`VerifyApiKey`) handles service-to-service auth. The `X-User-Id` header is validated inside the Form Request's `passedValidation()` hook. `DomainPolicy` is injected into the controller via constructor and called directly (not via `Gate` facade) — the API has no authenticated User model, so Laravel's Gate would reject all checks. The policy receives `Request` (and optionally the model) as arguments.
- Always add a Policy when introducing a new resource or action — even if it just returns `true` initially. This makes the authorization hook explicit and easy to tighten later.

### Cross-Page State

- Use `session()` to persist UI state that must survive page navigation (e.g. domain selection between DomainsList and BulkContentCreate).
- Write to session on every mutation (`session(['key' => $value])`), read on `mount()` (`session('key', $default)`).
- Both pages stay in sync — selecting on either page updates the shared session key.

### Internationalisation (i18n)

- All user-facing strings live in `lang/en/*.php` files, never hardcoded in Blade or PHP.
- Three language files: `domains.php` (table, filters, pagination), `content.php` (bulk create form, results), `welcome.php` (landing page).
- Use `__('file.key')` for simple strings, `trans_choice('file.key', $count, [...])` for pluralised strings.
- Use `{!! !!}` (unescaped) only when the translation contains trusted HTML (e.g. `<code>` tags in feature descriptions).
- Breeze auth views use their own `__()` calls — left as-is.

### Naming

- snake_case: database columns, config keys
- camelCase: PHP properties, methods, variables
- kebab-case: Blade filenames, CSS classes
- PascalCase: classes, Form Requests, Policies
