<?php

return [

    // ── Navigation ───────────────────────────────────────────────────────────
    'log_in'               => 'Log in',
    'register'             => 'Register',

    // ── Hero ─────────────────────────────────────────────────────────────────
    'tagline'              => 'Multi-network domain management',
    'hero_description'     => 'Two API backends, one unified UI. Browse domains across both networks, apply content in bulk, and track per-domain results — all in one authenticated dashboard.',
    'sign_in'              => 'Sign in',
    'create_account'       => 'Create account',

    // ── Feature grid ─────────────────────────────────────────────────────────
    'feature_networks'           => 'Two independent networks',
    'feature_networks_desc'      => 'API 1 and API 2 run as separate containers, each with their own MySQL database and Bearer token. The UI merges both into a single view.',
    'feature_bulk'               => 'Bulk content creation',
    'feature_bulk_desc'          => 'Select any number of domains across both networks, write title + body once, and submit. Results show per-domain — successes and 409 conflicts inline.',
    'feature_filter'             => 'Filter & paginate',
    'feature_filter_desc'        => 'Filter by network, nameserver, MX record, A record, or domain name. Configurable page size (10 – 250 rows). Pagination with smart ellipsis.',
    'feature_auth'               => 'API authentication',
    'feature_auth_desc'          => 'Each API container is protected by a static Bearer token. Tokens differ between api1 and api2. The UI injects the correct key per request automatically.',
    'feature_docker'             => 'Dockerised, zero setup',
    'feature_docker_desc'        => ':command starts all services, runs migrations, and seeds a default admin account automatically.',
    'feature_unique'             => 'One content per user / domain',
    'feature_unique_desc'        => 'A unique constraint on :constraint enforces one record per user per domain. Duplicates return a 409 displayed inline.',

    // ── Architecture ─────────────────────────────────────────────────────────
    'architecture'         => 'Architecture',

    // ── Quick start ──────────────────────────────────────────────────────────
    'quick_start'          => 'Quick start',
    'run_locally'          => 'Run locally',
    'auto_migrations'      => 'Migrations and seeding run automatically inside each container on startup.',
    'default_credentials'  => 'Default credentials',
    'field'                => 'Field',
    'value'                => 'Value',
    'email'                => 'Email',
    'password'             => 'Password',
    'seeder_note'          => 'Created by the UI container on first start via :seeder. Uses :method — safe to restart.',
    'services'             => 'Services',
    'service_ui'           => 'UI Dashboard',
    'service_api1'         => 'API — Network 1',
    'service_api2'         => 'API — Network 2',
    'service_grafana'      => 'Grafana Logs',

    // ── Footer ───────────────────────────────────────────────────────────────
    'footer'               => 'DomainsMode — multi-network domain management',

];
