<?php

namespace App\Livewire\Domains;

use App\Services\NetworkApiClient;
use Livewire\Component;

class DomainsList extends Component
{
    public array $domains = [];
    public array $selectedDomains = [];
    public bool $loading = true;

    // Search & filters
    public string $search = '';
    public string $filterNetwork = '';
    public string $filterNameserver = '';
    public string $filterMx = '';
    public string $filterARecord = '';

    // Pagination
    public int $perPage = 50;
    public int $page = 1;
    public array $perPageOptions = [10, 25, 50, 100, 250];

    public function mount(): void
    {
        $this->selectedDomains = session('selected_domains', []);
        $this->loadDomains();
    }

    public function loadDomains(): void
    {
        $this->loading = true;
        $allDomains = collect();

        foreach (config('networks') as $key => $network) {
            $client = new NetworkApiClient($network['api_url'], $network['name'], $network['api_key'] ?? '');
            $allDomains = $allDomains->merge($client->getDomains());
        }

        $this->domains = $allDomains->toArray();
        $this->loading = false;
    }

    // ── Filters ──────────────────────────────────────────────────────────────

    public function getFilteredDomainsProperty(): array
    {
        return array_values(array_filter($this->domains, function ($domain) {
            // Domain name search
            if ($this->search !== '' &&
                ! str_contains(strtolower($domain['domain']), strtolower($this->search))) {
                return false;
            }
            // Network filter
            if ($this->filterNetwork !== '' && $domain['network'] !== $this->filterNetwork) {
                return false;
            }
            // Nameserver filter (searches ns1 and ns2)
            if ($this->filterNameserver !== '') {
                $needle = strtolower($this->filterNameserver);
                $ns1    = strtolower($domain['nameserver_1'] ?? '');
                $ns2    = strtolower($domain['nameserver_2'] ?? '');
                if (! str_contains($ns1, $needle) && ! str_contains($ns2, $needle)) {
                    return false;
                }
            }
            // MX record filter
            if ($this->filterMx !== '' &&
                ! str_contains(strtolower($domain['mx_record'] ?? ''), strtolower($this->filterMx))) {
                return false;
            }
            // A record filter
            if ($this->filterARecord !== '' &&
                ! str_contains(strtolower($domain['a_record'] ?? ''), strtolower($this->filterARecord))) {
                return false;
            }

            return true;
        }));
    }

    public function getNetworkOptionsProperty(): array
    {
        return collect($this->domains)
            ->pluck('network')
            ->unique()
            ->sort()
            ->values()
            ->toArray();
    }

    // ── Pagination ────────────────────────────────────────────────────────────

    public function getPaginatedDomainsProperty(): array
    {
        $offset = ($this->page - 1) * $this->perPage;
        return array_slice($this->filteredDomains, $offset, $this->perPage);
    }

    public function getTotalPagesProperty(): int
    {
        return max(1, (int) ceil(count($this->filteredDomains) / $this->perPage));
    }

    /**
     * Returns page numbers to display, inserting null as an ellipsis placeholder.
     */
    public function getPageLinksProperty(): array
    {
        $total   = $this->totalPages;
        $current = $this->page;

        if ($total <= 7) {
            return range(1, $total);
        }

        $links = [];
        for ($i = 1; $i <= $total; $i++) {
            if ($i === 1 || $i === $total ||
                ($i >= $current - 2 && $i <= $current + 2)) {
                $links[] = $i;
            } elseif (end($links) !== null) {
                $links[] = null; // ellipsis
            }
        }

        return $links;
    }

    public function goToPage(int $page): void
    {
        $this->page = max(1, min($page, $this->totalPages));
    }

    // ── Reset page on any filter/search/per-page change ──────────────────────

    public function updatedSearch(): void            { $this->page = 1; }
    public function updatedFilterNetwork(): void     { $this->page = 1; }
    public function updatedFilterNameserver(): void  { $this->page = 1; }
    public function updatedFilterMx(): void          { $this->page = 1; }
    public function updatedFilterARecord(): void     { $this->page = 1; }
    public function updatedPerPage(): void           { $this->page = 1; }

    public function clearFilters(): void
    {
        $this->reset(['search', 'filterNetwork', 'filterNameserver', 'filterMx', 'filterARecord']);
        $this->page = 1;
    }

    // ── Selection ─────────────────────────────────────────────────────────────

    public function toggleDomain(int $domainId, string $network): void
    {
        $key = "{$network}:{$domainId}";

        if (isset($this->selectedDomains[$key])) {
            unset($this->selectedDomains[$key]);
        } else {
            $this->selectedDomains[$key] = [
                'id'      => $domainId,
                'network' => $network,
                'domain'  => collect($this->domains)
                    ->firstWhere(fn ($d) => $d['id'] == $domainId && $d['network'] == $network)['domain'] ?? '',
            ];
        }

        session(['selected_domains' => $this->selectedDomains]);
    }

    public function isDomainSelected(int $domainId, string $network): bool
    {
        return isset($this->selectedDomains["{$network}:{$domainId}"]);
    }

    // ── Render ────────────────────────────────────────────────────────────────

    public function render()
    {
        return view('livewire.pages.domains.domains-list', [
            'filteredDomains'  => $this->filteredDomains,
            'paginatedDomains' => $this->paginatedDomains,
            'networkOptions'   => $this->networkOptions,
            'totalPages'       => $this->totalPages,
            'pageLinks'        => $this->pageLinks,
        ]);
    }
}
