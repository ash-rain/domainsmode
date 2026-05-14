# DomainsMode

Multi-network domain management system. Two API backends serve domain data from separate networks, orchestrated by a Livewire UI with bulk content operations.

## Architecture

```
┌──────────────────────────────────────────────┐
│  UI App                                      │
│  Laravel 12 + Breeze + Livewire              │
│  DB: domainsmode_ui (users, sessions)        │
│                                              │
│  ┌──────────────┐  ┌─────────────────────┐   │
│  │ Domains List │  │ Bulk Content Create │   │
│  │ (merged)     │  │ (multi-network)     │   │
│  └──────────────┘  └─────────────────────┘   │
└──────────┬───────────────────┬───────────────┘
           │ HTTP (internal)   │ HTTP (internal)
           ▼                   ▼
┌─────────────────┐  ┌─────────────────┐
│ API1 (internal) │  │ API2 (internal) │
│ DB: network_1   │  │ DB: network_2   │
│                 │  │                 │
│ GET  /api/domains         (list)    │
│ POST /api/domains/{id}/content      │
└─────────────────┘  └─────────────────┘
           │                   │
           └─────────┬─────────┘
                     ▼
            ┌─────────────────┐
            │ MySQL (:3306)   │
            │ network_1       │
            │ network_2       │
            │ domainsmode_ui  │
            └─────────────────┘
```

## Tech Stack

- **PHP 8.3+** / **Laravel 12**
- **Livewire 3** (via Breeze Livewire stack)
- **MySQL 8** (single server, three databases)
- **Docker** (docker-compose for all services)
- **PHPUnit** (unit + feature testing)

## Project Structure

```
domainsmode/
├── docker-compose.yml
├── docker/
│   ├── api.Dockerfile
│   ├── ui.Dockerfile
│   ├── api-entrypoint.sh     # fix perms → migrate → php-fpm
│   ├── ui-entrypoint.sh      # fix perms → migrate → seed → php-fpm
│   ├── nginx/
│   │   ├── Dockerfile
│   │   ├── entrypoint.sh     # auto-generates self-signed SSL cert
│   │   ├── api1.conf
│   │   ├── api2.conf
│   │   └── ui.conf
│   ├── loki/
│   │   └── loki-config.yml
│   ├── grafana/
│   │   └── provisioning/
│   │       ├── datasources/loki.yml
│   │       └── dashboards/
│   │           ├── dashboards.yml
│   │           └── json/domainsmode.json
│   └── mysql/
│       ├── init.sql          # creates databases, imports dumps
│       ├── network_1.sql
│       └── network_2.sql
├── api/                      # single Laravel codebase for both APIs
│   ├── app/
│   │   ├── Models/
│   │   │   ├── Domain.php
│   │   │   └── Content.php
│   │   └── Http/Controllers/Api/
│   │       └── DomainController.php
│   ├── database/migrations/
│   │   └── create_contents_table.php
│   └── routes/api.php
├── ui/                       # Laravel + Breeze + Livewire
│   ├── app/
│   │   ├── Livewire/
│   │   │   └── Domains/
│   │   │       ├── DomainsList.php
│   │   │       └── BulkContentCreate.php
│   │   └── Services/
│   │       └── NetworkApiClient.php
│   ├── resources/views/
│   │   └── livewire/
│   │       └── pages/
│   │           └── domains/
│   │               ├── domains-list.blade.php
│   │               └── bulk-content-create.blade.php
│   └── routes/web.php
├── CLAUDE.md
├── DESIGN.md
└── README.md
```

## Design Decisions

### One API Codebase, Two Instances

The two APIs share identical code. Differentiation is purely configuration (`.env`): port and database name. They run as separate Docker containers from the same image. If the networks diverge in the future, fork the codebase.

### Content Table

Added to both `network_1` and `network_2` via migration:

```sql
contents
├── id              BIGINT UNSIGNED AUTO_INCREMENT
├── domain_id       BIGINT UNSIGNED (FK → domains.id)
├── user_id         BIGINT UNSIGNED (from UI app auth)
├── title           VARCHAR(255)
├── body            TEXT
├── created_at      TIMESTAMP
├── updated_at      TIMESTAMP
└── UNIQUE(domain_id, user_id)
```

The `UNIQUE(domain_id, user_id)` constraint enforces the business rule: one content record per user per domain. Attempts to create duplicate content return a `409 Conflict`.

### API Authentication

Each API container is protected by a static Bearer token set via the `API_KEY` environment variable. The UI passes the correct token in the `Authorization: Bearer <key>` header for every request. Tokens are different per container (api1 vs api2) and are defined in `docker-compose.yml`. If `API_KEY` is empty the middleware is a no-op, which keeps PHPUnit tests simple without needing to mock auth headers.

### Bulk Update Flow

1. User selects domains (checkboxes) from the merged list
2. Fills in title + body
3. UI groups selected domains by network
4. Sends parallel HTTP requests to API1 and API2
5. Aggregates responses — shows per-domain success/failure
6. Domains with existing content from this user get a `409` error displayed inline

## Quick Start

```bash
# Clone and start
git clone https://github.com/ash-rain/domainsmode domainsmode
cd domainsmode
docker-compose up -d --build

# That's it. Migrations and seeding run automatically inside each container.
# Wait ~15 seconds for MySQL to initialize and the containers to finish seeding.

# Access (HTTPS — self-signed cert, accept the browser warning)
# UI:      https://localhost:8443
# API1:    https://localhost:8444
# API2:    https://localhost:8445
# Grafana: https://localhost:8443/grafana  (admin/admin)
```

### Default Credentials

A default admin account is created automatically on first startup:

| Field    | Value                     |
|----------|---------------------------|
| Email    | `admin@domainsmode.local` |
| Password | `password`                |

This account is created by the UI container's entrypoint via `php artisan db:seed`. The seeder uses `firstOrCreate`, so re-running `docker-compose up` or rebuilding containers never creates duplicate records.

> **Change the password** after first login if the app is exposed outside localhost.

## Development Workflow

Every change follows a strict **Plan → Develop → Test** cycle defined in `CLAUDE.md`. The goal is zero drift between code and expected behaviour — nothing gets checked off until it's verified.

### The Cycle

```
┌───────────┐     ┌─────────┐     ┌──────────────────────┐
│  PLAN     │────▶│  CODE   │────▶│  TEST                │
│           │     │         │     │                      │
│ Read task │     │ Write   │     │ PHPUnit (unit +      │
│ from      │     │ the     │     │ feature tests)       │
│ todo.md   │     │ code    │     │                      │
│           │     │         │     │ All green?           │
│ Check     │     │         │     │                      │
│ DESIGN.md │     │         │     │                      │
└───────────┘     └─────────┘     └──────────┬───────────┘
                                             │
                                ┌─────────── │ ───────────┐
                                │ YES        │         NO │
                                ▼            │            ▼
                         ┌────────────┐      │     ┌────────────┐
                         │ Check off  │      │     │ Fix & re-  │
                         │ in todo.md │      │     │ run tests  │
                         │ Next task  │      │     └─────┬──────┘
                         └────────────┘      │           │
                                             └───────────┘
```

### Key Files

- **CLAUDE.md** — workflow orchestration rules, self-improvement loop, task management process
- **DESIGN.md** — single source of truth for architecture, schema, API specs, conventions, and lessons learned
- **tasks/todo.md** — per-task checklists, tracked during implementation

### Testing

Run after every change. API tests use SQLite in-memory, UI tests use Http::fake() to stub API responses.

```bash
# API tests — run on api1, covers both since same codebase
docker-compose exec api1 php artisan test

# UI tests — unit + feature + Livewire component tests
docker-compose exec ui php artisan test
```

See the Testing section in DESIGN.md for the full test inventory and patterns.

## SSL

**Development:** All services run over HTTPS via a shared nginx reverse proxy with a self-signed certificate. The certificate is generated automatically on first startup by the nginx entrypoint script and persists in the `nginx_ssl` Docker volume (RSA 4096-bit, valid 365 days). Ports: UI on `:8443`, API1 on `:8444`, API2 on `:8445`. The UI's `NetworkApiClient` skips certificate verification for self-signed certs (`verify => false`). To regenerate: `docker volume rm domainsmode_nginx_ssl && docker-compose up -d --build nginx`.

**Production:** Let's Encrypt certificates via Certbot, served behind Cloudflare (Full Strict). Only port 443 is publicly exposed for the UI. APIs are internal-only (plain HTTP on Docker network ports `:8081`/`:8082`, never exposed to the internet). Grafana is served at `/grafana` on the main HTTPS vhost.

## Log Monitoring

Container logs are shipped to **Loki** via the Docker Loki logging driver and visualised in **Grafana**.

### Prerequisites

Install the Loki Docker logging driver (one-time, on the Docker host):

```bash
docker plugin install grafana/loki-docker-driver:3.7.2-arm64 --alias loki --grant-all-permissions
# Intel Mac / Linux amd64: use 3.7.2-amd64 instead
```

### Access

Grafana is available at `https://localhost:8443/grafana` in development and `https://<DOMAIN>/grafana` in production (admin/admin). A pre-provisioned "DomainsMode Logs" dashboard shows all container logs with a service filter dropdown (ui, api1, api2, nginx).

### What's Logged

Each container has a `service` label. Use Loki's LogQL in Grafana to query:

```logql
{service="ui"}                                    # all UI logs
{service="api1"} |~ "(?i)error"                   # API1 errors
{service=~"api1|api2"} |= "content"               # content-related across both APIs
{service="nginx"} |~ "POST" |~ "4[0-9]{2}"        # nginx 4xx on POST requests
```

### Architecture

```
┌────────┐  ┌────────┐  ┌────────┐  ┌────────┐
│  api1  │  │  api2  │  │   ui   │  │ nginx  │
└───┬────┘  └───┬────┘  └───┬────┘  └───┬────┘
    │           │           │           │
    └───────────┴─────┬─────┴───────────┘
                      │ Docker Loki driver
                      ▼
               ┌─────────────┐
               │  Loki :3100 │
               └──────┬──────┘
                      │
                      ▼
               ┌─────────────┐
               │ Grafana :3000│
               └─────────────┘
```

## Common Commands

```bash
# Docker
docker-compose up -d --build          # start/rebuild all
docker-compose down -v                # full teardown (including volumes)
docker-compose logs -f ui             # tail UI logs
docker-compose exec api1 php artisan  # artisan in api1

# MySQL
docker-compose exec mysql mysql -u root -psecret

# Tests
docker-compose exec api1 php artisan test
docker-compose exec ui php artisan test
```
