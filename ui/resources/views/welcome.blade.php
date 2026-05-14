<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DomainsMode</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased font-sans bg-gray-50 text-gray-900">

{{-- ─── Navbar ──────────────────────────────────────────────────── --}}
<header class="bg-white border-b border-gray-200 sticky top-0 z-10">
    <div class="max-w-5xl mx-auto px-6 py-4 flex items-center justify-between">
        <span class="text-lg font-bold tracking-tight text-gray-900">DomainsMode</span>
        <nav class="flex items-center gap-3">
            <a href="{{ route('login') }}"
               class="px-4 py-1.5 text-sm font-medium text-gray-700 hover:text-gray-900 rounded-md hover:bg-gray-100 transition">
                {{ __('welcome.log_in') }}
            </a>
            @if (Route::has('register'))
            <a href="{{ route('register') }}"
               class="px-4 py-1.5 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-md transition">
                {{ __('welcome.register') }}
            </a>
            @endif
        </nav>
    </div>
</header>

{{-- ─── Hero ────────────────────────────────────────────────────── --}}
<section class="bg-white border-b border-gray-200">
    <div class="max-w-5xl mx-auto px-6 py-16 md:py-24">
        <div class="max-w-2xl">
            <p class="text-xs font-semibold tracking-widest uppercase text-indigo-600 mb-3">{{ __('welcome.tagline') }}</p>
            <h1 class="text-4xl md:text-5xl font-bold leading-tight text-gray-900 mb-5">
                DomainsMode
            </h1>
            <p class="text-lg text-gray-600 mb-8 leading-relaxed">
                {{ __('welcome.hero_description') }}
            </p>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('login') }}"
                   class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-md transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/></svg>
                    {{ __('welcome.sign_in') }}
                </a>
                <a href="{{ route('register') }}"
                   class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md transition">
                    {{ __('welcome.create_account') }}
                </a>
            </div>
        </div>
    </div>
</section>

{{-- ─── Feature grid ────────────────────────────────────────────── --}}
<section class="max-w-5xl mx-auto px-6 py-14">
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">

        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="w-10 h-10 rounded-lg bg-indigo-50 flex items-center justify-center mb-4">
                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M5 12l4-4m-4 4l4 4"/></svg>
            </div>
            <h3 class="font-semibold text-gray-900 mb-1">{{ __('welcome.feature_networks') }}</h3>
            <p class="text-sm text-gray-500 leading-relaxed">{{ __('welcome.feature_networks_desc') }}</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="w-10 h-10 rounded-lg bg-indigo-50 flex items-center justify-center mb-4">
                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z"/></svg>
            </div>
            <h3 class="font-semibold text-gray-900 mb-1">{{ __('welcome.feature_bulk') }}</h3>
            <p class="text-sm text-gray-500 leading-relaxed">{{ __('welcome.feature_bulk_desc') }}</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="w-10 h-10 rounded-lg bg-indigo-50 flex items-center justify-center mb-4">
                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 15.803a7.5 7.5 0 0010.607 0z"/></svg>
            </div>
            <h3 class="font-semibold text-gray-900 mb-1">{{ __('welcome.feature_filter') }}</h3>
            <p class="text-sm text-gray-500 leading-relaxed">{{ __('welcome.feature_filter_desc') }}</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="w-10 h-10 rounded-lg bg-indigo-50 flex items-center justify-center mb-4">
                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
            </div>
            <h3 class="font-semibold text-gray-900 mb-1">{{ __('welcome.feature_auth') }}</h3>
            <p class="text-sm text-gray-500 leading-relaxed">{{ __('welcome.feature_auth_desc') }}</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="w-10 h-10 rounded-lg bg-indigo-50 flex items-center justify-center mb-4">
                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 01-3-3m3 3a3 3 0 100 6h13.5a3 3 0 100-6m-16.5-3a3 3 0 013-3h13.5a3 3 0 013 3m-19.5 0a4.5 4.5 0 01.9-2.7L5.737 5.1a3.375 3.375 0 012.7-1.35h7.126c1.062 0 2.062.5 2.7 1.35l2.587 3.45a4.5 4.5 0 01.9 2.7m0 0a3 3 0 01-3 3m0 3h.008v.008h-.008v-.008zm0-6h.008v.008h-.008v-.008zm-3 6h.008v.008h-.008v-.008zm0-6h.008v.008h-.008v-.008z"/></svg>
            </div>
            <h3 class="font-semibold text-gray-900 mb-1">{{ __('welcome.feature_docker') }}</h3>
            <p class="text-sm text-gray-500 leading-relaxed">{!! __('welcome.feature_docker_desc', ['command' => '<code class="text-xs bg-gray-100 px-1 py-0.5 rounded">docker-compose up -d --build</code>']) !!}</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="w-10 h-10 rounded-lg bg-indigo-50 flex items-center justify-center mb-4">
                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <h3 class="font-semibold text-gray-900 mb-1">{{ __('welcome.feature_unique') }}</h3>
            <p class="text-sm text-gray-500 leading-relaxed">{!! __('welcome.feature_unique_desc', ['constraint' => '<code class="text-xs bg-gray-100 px-1 py-0.5 rounded">(domain_id, user_id)</code>']) !!}</p>
        </div>

    </div>
</section>

{{-- ─── Architecture ────────────────────────────────────────────── --}}
<section class="border-t border-gray-200 bg-white">
    <div class="max-w-5xl mx-auto px-6 py-14">
        <h2 class="text-xl font-bold text-gray-900 mb-8">{{ __('welcome.architecture') }}</h2>
        <div class="grid md:grid-cols-3 gap-4 text-sm">

            <div class="rounded-xl border border-gray-200 p-5">
                <div class="flex items-center gap-2 mb-3">
                    <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                    <span class="font-semibold text-gray-900">UI  <span class="text-gray-400 font-normal">:8000</span></span>
                </div>
                <ul class="space-y-1 text-gray-500">
                    <li>Laravel 12 + Livewire 3</li>
                    <li>Breeze auth</li>
                    <li>DB: <code class="text-xs bg-gray-100 px-1 rounded">domainsmode_ui</code></li>
                    <li>Fetches &amp; merges both networks</li>
                    <li>Bulk content orchestration</li>
                </ul>
            </div>

            <div class="rounded-xl border border-gray-200 p-5">
                <div class="flex items-center gap-2 mb-3">
                    <span class="w-2 h-2 rounded-full bg-teal-500"></span>
                    <span class="font-semibold text-gray-900">API 1  <span class="text-gray-400 font-normal">internal</span></span>
                </div>
                <ul class="space-y-1 text-gray-500">
                    <li>Laravel 12 JSON API</li>
                    <li>DB: <code class="text-xs bg-gray-100 px-1 rounded">network_1</code></li>
                    <li>Bearer token auth</li>
                    <li><code class="text-xs bg-gray-100 px-1 rounded">GET /api/domains</code></li>
                    <li><code class="text-xs bg-gray-100 px-1 rounded">POST /api/domains/{id}/content</code></li>
                </ul>
            </div>

            <div class="rounded-xl border border-gray-200 p-5">
                <div class="flex items-center gap-2 mb-3">
                    <span class="w-2 h-2 rounded-full bg-orange-400"></span>
                    <span class="font-semibold text-gray-900">API 2  <span class="text-gray-400 font-normal">internal</span></span>
                </div>
                <ul class="space-y-1 text-gray-500">
                    <li>Same codebase as API 1</li>
                    <li>DB: <code class="text-xs bg-gray-100 px-1 rounded">network_2</code></li>
                    <li>Different Bearer token</li>
                    <li>~450 domains, independent</li>
                    <li>Separate contents table</li>
                </ul>
            </div>

        </div>
    </div>
</section>

{{-- ─── Quick start ─────────────────────────────────────────────── --}}
<section class="border-t border-gray-200">
    <div class="max-w-5xl mx-auto px-6 py-14">
        <h2 class="text-xl font-bold text-gray-900 mb-8">{{ __('welcome.quick_start') }}</h2>
        <div class="grid md:grid-cols-2 gap-8">

            <div>
                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3">{{ __('welcome.run_locally') }}</h3>
                <div class="bg-gray-900 rounded-xl overflow-hidden text-sm">
                    <div class="flex items-center gap-2 px-4 py-3 border-b border-gray-700">
                        <span class="w-3 h-3 rounded-full bg-red-500"></span>
                        <span class="w-3 h-3 rounded-full bg-yellow-500"></span>
                        <span class="w-3 h-3 rounded-full bg-green-500"></span>
                    </div>
                    <pre class="px-5 py-5 text-gray-300 overflow-x-auto leading-relaxed"><code><span class="text-gray-500"># clone and start everything</span>
git clone https://github.com/ash-rain/domainsmode
cd domainsmode
docker-compose up -d --build

<span class="text-gray-500"># done — open in browser</span>
open https://localhost:8443</code></pre>
                </div>
                <p class="text-xs text-gray-500 mt-3">{{ __('welcome.auto_migrations') }}</p>
            </div>

            <div>
                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3">{{ __('welcome.default_credentials') }}</h3>
                <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200">
                                <th class="text-left px-5 py-3 font-medium text-gray-500">{{ __('welcome.field') }}</th>
                                <th class="text-left px-5 py-3 font-medium text-gray-500">{{ __('welcome.value') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="border-b border-gray-100">
                                <td class="px-5 py-3 text-gray-600">{{ __('welcome.email') }}</td>
                                <td class="px-5 py-3 font-mono text-gray-900 text-xs">admin@domainsmode.local</td>
                            </tr>
                            <tr>
                                <td class="px-5 py-3 text-gray-600">{{ __('welcome.password') }}</td>
                                <td class="px-5 py-3 font-mono text-gray-900 text-xs">password</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p class="text-xs text-gray-500 mt-3">{!! __('welcome.seeder_note', [
                    'seeder' => '<code class="bg-gray-100 px-1 py-0.5 rounded">DatabaseSeeder</code>',
                    'method' => '<code class="bg-gray-100 px-1 py-0.5 rounded">firstOrCreate</code>',
                ]) !!}</p>

                <div class="mt-6">
                    <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3">{{ __('welcome.services') }}</h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex items-center justify-between bg-white border border-gray-200 rounded-lg px-4 py-2.5">
                            <span class="text-gray-600">{{ __('welcome.service_ui') }}</span>
                            <code class="text-xs text-indigo-600">localhost:8443</code>
                        </div>
                        <div class="flex items-center justify-between bg-white border border-gray-200 rounded-lg px-4 py-2.5">
                            <span class="text-gray-600">{{ __('welcome.service_api1') }}</span>
                            <code class="text-xs text-teal-600">localhost:8444</code>
                        </div>
                        <div class="flex items-center justify-between bg-white border border-gray-200 rounded-lg px-4 py-2.5">
                            <span class="text-gray-600">{{ __('welcome.service_api2') }}</span>
                            <code class="text-xs text-orange-500">localhost:8445</code>
                        </div>
                        <div class="flex items-center justify-between bg-white border border-gray-200 rounded-lg px-4 py-2.5">
                            <span class="text-gray-600">{{ __('welcome.service_grafana') }}</span>
                            <code class="text-xs text-purple-600">/grafana</code>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

{{-- ─── Footer ──────────────────────────────────────────────────── --}}
<footer class="border-t border-gray-200 bg-white">
    <div class="max-w-5xl mx-auto px-6 py-6 flex flex-col sm:flex-row items-center justify-between gap-3 text-sm text-gray-400">
        <span>{{ __('welcome.footer') }}</span>
        <div class="flex gap-4">
            <a href="{{ route('login') }}" class="hover:text-gray-600 transition">{{ __('welcome.log_in') }}</a>
            <a href="{{ route('register') }}" class="hover:text-gray-600 transition">{{ __('welcome.register') }}</a>
        </div>
    </div>
</footer>

</body>
</html>
