<?php

namespace App\Livewire\Domains;

use App\Livewire\Forms\BulkContentForm;
use App\Services\NetworkApiClient;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class BulkContentCreate extends Component
{
    public BulkContentForm $form;

    public array $domains = [];
    public array $selectedDomains = [];
    public array $results = [];
    public bool $loading = true;
    public bool $submitting = false;

    // Search & filter
    public string $search = '';
    public string $filterNetwork = '';

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

    public function getFilteredDomainsProperty(): array
    {
        return array_values(array_filter($this->domains, function ($domain) {
            if ($this->search !== '' &&
                ! str_contains(strtolower($domain['domain']), strtolower($this->search))) {
                return false;
            }
            if ($this->filterNetwork !== '' && $domain['network'] !== $this->filterNetwork) {
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

    public function selectAll(): void
    {
        foreach ($this->filteredDomains as $domain) {
            $key = "{$domain['network']}:{$domain['id']}";
            $this->selectedDomains[$key] = [
                'id'      => $domain['id'],
                'network' => $domain['network'],
                'domain'  => $domain['domain'],
            ];
        }

        session(['selected_domains' => $this->selectedDomains]);
    }

    public function deselectAll(): void
    {
        $this->selectedDomains = [];
        session(['selected_domains' => []]);
    }

    public function createContent(): void
    {
        Gate::authorize('create-content');

        $this->form->validate();

        if (empty($this->selectedDomains)) {
            return;
        }

        $this->submitting = true;
        $this->results = [];

        $grouped = collect($this->selectedDomains)->groupBy('network');

        foreach ($grouped as $network => $domains) {
            $networkConfig = collect(config('networks'))->first(fn ($n) => $n['name'] === $network);
            if (! $networkConfig) {
                continue;
            }

            $client = new NetworkApiClient($networkConfig['api_url'], $network, $networkConfig['api_key'] ?? '');

            foreach ($domains as $domain) {
                $result = $client->createContent(
                    $domain['id'],
                    auth()->id(),
                    $this->form->title,
                    $this->form->body,
                );

                $this->results[] = [
                    'domain'  => $domain['domain'],
                    'network' => $network,
                    'success' => $result['success'],
                    'message' => $result['success']
                        ? __('content.created_successfully')
                        : ($result['data']['message'] ?? __('content.error_occurred')),
                ];
            }
        }

        $this->submitting = false;

        if (collect($this->results)->every('success')) {
            $this->form->reset();
        }
    }

    public function render()
    {
        return view('livewire.pages.domains.bulk-content-create', [
            'filteredDomains' => $this->filteredDomains,
            'networkOptions'  => $this->networkOptions,
        ]);
    }
}
