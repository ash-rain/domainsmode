@props([
    'domains'         => [],
    'selectedDomains' => [],
    'networkOptions'  => [],
    'loading'         => false,
    'compact'         => false,

    // Full mode only
    'page'            => 1,
    'perPage'         => 50,
    'perPageOptions'  => [],
    'totalPages'      => 1,
    'pageLinks'       => [],
    'hasExtraFilters' => false,
])

{{-- ── Search & Filters ─────────────────────────────────────────────── --}}
<div class="mb-3 space-y-2">
    <div class="flex gap-2">
        <input
            wire:model.live.debounce.300ms="search"
            type="text"
            placeholder="{{ __('domains.search_placeholder') }}"
            class="flex-1 rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
        />
        <select
            wire:model.live="filterNetwork"
            class="rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
        >
            <option value="">{{ __('domains.all_networks') }}</option>
            @foreach($networkOptions as $net)
                <option value="{{ $net }}">{{ $net }}</option>
            @endforeach
        </select>
    </div>

    {{-- Extra filter row (full mode only) --}}
    @if(! $compact && $hasExtraFilters)
        <div class="flex gap-2">
            <input
                wire:model.live.debounce.300ms="filterNameserver"
                type="text"
                placeholder="{{ __('domains.filter_nameserver') }}"
                class="flex-1 rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
            />
            <input
                wire:model.live.debounce.300ms="filterMx"
                type="text"
                placeholder="{{ __('domains.filter_mx') }}"
                class="flex-1 rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
            />
            <input
                wire:model.live.debounce.300ms="filterARecord"
                type="text"
                placeholder="{{ __('domains.filter_a_record') }}"
                class="flex-1 rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
            />
            @if($this->search || $this->filterNetwork || $this->filterNameserver || $this->filterMx || $this->filterARecord)
                <button
                    wire:click="clearFilters"
                    class="px-3 py-1.5 text-sm text-gray-600 border border-gray-300 rounded-md hover:bg-gray-50 whitespace-nowrap"
                >
                    {{ __('domains.clear_filters') }}
                </button>
            @endif
        </div>
    @endif
</div>

@if($loading)
    <div class="text-center py-8 text-gray-500">{{ __('domains.loading') }}</div>
@else
    {{-- ── Summary bar ───────────────────────────────────────────────── --}}
    <div class="mb-2 flex items-center justify-between text-sm text-gray-600">
        <span>
            @php $total = count($domains); @endphp
            @if(! $compact)
                @php
                    $start = min(($page - 1) * $perPage + 1, $total);
                    $end   = min($page * $perPage, $total);
                @endphp
                @if($total === 0)
                    {{ __('domains.none_found') }}
                @else
                    {{ trans_choice('domains.showing_range', $total, ['start' => $start, 'end' => $end, 'total' => $total]) }}
                @endif
            @else
                {{ trans_choice('domains.domain_count', $total, ['count' => $total]) }}
            @endif
            @if(count($selectedDomains) > 0)
                &middot; <span class="font-semibold text-indigo-600">{{ __('domains.selected_count', ['count' => count($selectedDomains)]) }}</span>
            @endif
        </span>

        <div class="flex items-center gap-2">
            {{-- Bulk selection controls (compact mode) --}}
            @if($compact)
                @if(count($selectedDomains) > 0)
                    <button
                        wire:click="deselectAll"
                        class="px-3 py-1.5 text-sm text-gray-600 border border-gray-300 rounded-md hover:bg-gray-50"
                    >
                        {{ __('domains.deselect_all') }}
                    </button>
                @endif
                <button
                    wire:click="selectAll"
                    class="px-3 py-1.5 text-sm text-indigo-600 border border-indigo-300 rounded-md hover:bg-indigo-50"
                >
                    {{ __('domains.select_all_visible') }}
                </button>
            @else
                {{-- Per-page dropdown (full mode) --}}
                <label for="per-page" class="whitespace-nowrap">{{ __('domains.rows_per_page') }}</label>
                <select
                    id="per-page"
                    wire:model.live="perPage"
                    class="rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 py-1"
                >
                    @foreach($perPageOptions as $opt)
                        <option value="{{ $opt }}">{{ $opt }}</option>
                    @endforeach
                </select>
            @endif
        </div>
    </div>

    {{-- ── Table ─────────────────────────────────────────────────────── --}}
    <div class="overflow-x-auto border rounded-lg" @if($compact) style="max-height: 28rem; overflow-y: auto;" @endif>
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50 @if($compact) sticky top-0 @endif">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-10"></th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('domains.col_domain') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('domains.col_network') }}</th>
                    @if(! $compact)
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('domains.col_nameservers') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('domains.col_mx') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('domains.col_a_record') }}</th>
                    @endif
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($domains as $domain)
                    <tr class="hover:bg-gray-50 {{ $this->isDomainSelected($domain['id'], $domain['network']) ? 'bg-indigo-50' : '' }}">
                        <td class="px-4 py-2">
                            <input
                                type="checkbox"
                                {{ $this->isDomainSelected($domain['id'], $domain['network']) ? 'checked' : '' }}
                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer"
                                wire:click="toggleDomain({{ $domain['id'] }}, '{{ $domain['network'] }}')"
                            />
                        </td>
                        <td class="px-4 py-2 text-sm font-medium text-gray-900">{{ $domain['domain'] }}</td>
                        <td class="px-4 py-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $domain['network'] === 'Network 1' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' }}">
                                {{ $domain['network'] }}
                            </span>
                        </td>
                        @if(! $compact)
                            <td class="px-4 py-2 text-xs text-gray-500">
                                {{ $domain['nameserver_1'] ?? '-' }}
                                @if($domain['nameserver_2'] ?? null), {{ $domain['nameserver_2'] }}@endif
                            </td>
                            <td class="px-4 py-2 text-xs text-gray-500">{{ $domain['mx_record'] ?? '-' }}</td>
                            <td class="px-4 py-2 text-xs text-gray-500">{{ $domain['a_record'] ?? '-' }}</td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $compact ? 3 : 6 }}" class="px-4 py-8 text-center text-gray-500">{{ __('domains.none_found') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ── Pagination (full mode only) ───────────────────────────────── --}}
    @if(! $compact && $totalPages > 1)
        <div class="mt-4 flex items-center justify-between text-sm">
            <button
                wire:click="goToPage({{ $page - 1 }})"
                @disabled($page <= 1)
                class="px-3 py-1.5 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed"
            >
                {!! __('domains.previous') !!}
            </button>

            <div class="flex items-center gap-1">
                @foreach($pageLinks as $link)
                    @if($link === null)
                        <span class="px-2 text-gray-400">&hellip;</span>
                    @else
                        <button
                            wire:click="goToPage({{ $link }})"
                            class="min-w-[2rem] px-2 py-1 rounded-md border text-sm
                                {{ $link === $page
                                    ? 'bg-indigo-600 text-white border-indigo-600 font-semibold'
                                    : 'border-gray-300 text-gray-700 hover:bg-gray-50' }}"
                        >
                            {{ $link }}
                        </button>
                    @endif
                @endforeach
            </div>

            <button
                wire:click="goToPage({{ $page + 1 }})"
                @disabled($page >= $totalPages)
                class="px-3 py-1.5 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed"
            >
                {!! __('domains.next') !!}
            </button>
        </div>
    @endif
@endif
