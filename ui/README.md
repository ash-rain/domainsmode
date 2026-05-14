# DomainsMode — UI

Laravel 12 + Livewire 3 frontend. Merges domains from both API networks into a single interface with bulk content operations.

## Key Components

- **DomainsList** — merged domain table with search, filters, pagination
- **BulkContentCreate** — select domains across networks, create content in bulk
- **NetworkApiClient** — single point of contact with API containers

## Running Tests

```bash
docker-compose exec ui php artisan test
```

Uses `Http::fake()` to stub API responses. Livewire components tested via `Livewire::test()`.

## Full Documentation

See [DESIGN.md](../DESIGN.md) in the project root.
