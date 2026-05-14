<?php

namespace Tests\Feature;

use App\Livewire\Domains\DomainsList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class DomainsListTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        config()->set('networks', [
            'network_1' => [
                'name'    => 'Network 1',
                'api_url' => 'http://api1.test',
                'api_key' => '',
            ],
            'network_2' => [
                'name'    => 'Network 2',
                'api_url' => 'http://api2.test',
                'api_key' => '',
            ],
        ]);
    }

    private function fakeApis(array $network1 = [], array $network2 = []): void
    {
        Http::fake([
            'http://api1.test/api/domains' => Http::response($network1),
            'http://api2.test/api/domains' => Http::response($network2),
        ]);
    }

    // ── Rendering ────────────────────────────────────────────────────────────

    public function test_component_renders_successfully(): void
    {
        $this->fakeApis();

        Livewire::actingAs($this->user)
            ->test(DomainsList::class)
            ->assertStatus(200);
    }

    public function test_loads_domains_from_all_networks(): void
    {
        $this->fakeApis(
            [['id' => 1, 'domain' => 'one.com', 'nameserver_1' => 'ns1.a.com']],
            [['id' => 2, 'domain' => 'two.com', 'nameserver_1' => 'ns1.b.com']],
        );

        $component = Livewire::actingAs($this->user)
            ->test(DomainsList::class);

        $this->assertCount(2, $component->get('domains'));
        $this->assertFalse($component->get('loading'));
    }

    public function test_handles_api_failure_gracefully(): void
    {
        Http::fake([
            'http://api1.test/api/domains' => Http::response(null, 500),
            'http://api2.test/api/domains' => Http::response([
                ['id' => 1, 'domain' => 'ok.com', 'nameserver_1' => 'ns1.ok.com'],
            ]),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(DomainsList::class);

        // Should still have the one domain from the working network
        $this->assertCount(1, $component->get('domains'));
    }

    // ── Filtering ────────────────────────────────────────────────────────────

    public function test_search_filters_by_domain_name(): void
    {
        $this->fakeApis(
            [
                ['id' => 1, 'domain' => 'example.com', 'nameserver_1' => 'ns1.a.com'],
                ['id' => 2, 'domain' => 'other.com',   'nameserver_1' => 'ns1.a.com'],
            ],
        );

        $component = Livewire::actingAs($this->user)
            ->test(DomainsList::class)
            ->set('search', 'example');

        $filtered = $component->get('filteredDomains');
        $this->assertCount(1, $filtered);
        $this->assertEquals('example.com', $filtered[0]['domain']);
    }

    public function test_filter_by_network(): void
    {
        $this->fakeApis(
            [['id' => 1, 'domain' => 'one.com', 'nameserver_1' => 'ns1.a.com']],
            [['id' => 2, 'domain' => 'two.com', 'nameserver_1' => 'ns1.b.com']],
        );

        $component = Livewire::actingAs($this->user)
            ->test(DomainsList::class)
            ->set('filterNetwork', 'Network 2');

        $filtered = $component->get('filteredDomains');
        $this->assertCount(1, $filtered);
        $this->assertEquals('two.com', $filtered[0]['domain']);
    }

    public function test_network_options_lists_unique_networks(): void
    {
        $this->fakeApis(
            [['id' => 1, 'domain' => 'one.com', 'nameserver_1' => 'ns1.a.com']],
            [['id' => 2, 'domain' => 'two.com', 'nameserver_1' => 'ns1.b.com']],
        );

        $component = Livewire::actingAs($this->user)
            ->test(DomainsList::class);

        $this->assertEquals(['Network 1', 'Network 2'], $component->get('networkOptions'));
    }

    public function test_search_is_case_insensitive(): void
    {
        $this->fakeApis([
            ['id' => 1, 'domain' => 'MyDomain.COM', 'nameserver_1' => 'ns1.a.com'],
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(DomainsList::class)
            ->set('search', 'mydomain');

        $this->assertCount(1, $component->get('filteredDomains'));
    }

    public function test_clear_filters_resets_all(): void
    {
        $this->fakeApis([
            ['id' => 1, 'domain' => 'one.com', 'nameserver_1' => 'ns1.a.com'],
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(DomainsList::class)
            ->set('search', 'nothing')
            ->set('filterNetwork', 'Network 1')
            ->call('clearFilters');

        $this->assertEquals('', $component->get('search'));
        $this->assertEquals('', $component->get('filterNetwork'));
        $this->assertEquals(1, $component->get('page'));
    }

    // ── Pagination ───────────────────────────────────────────────────────────

    public function test_pagination_defaults(): void
    {
        $this->fakeApis();

        $component = Livewire::actingAs($this->user)
            ->test(DomainsList::class);

        $this->assertEquals(50, $component->get('perPage'));
        $this->assertEquals(1, $component->get('page'));
    }

    public function test_filter_change_resets_page_to_one(): void
    {
        $this->fakeApis();

        $component = Livewire::actingAs($this->user)
            ->test(DomainsList::class)
            ->set('page', 3)
            ->set('search', 'test');

        $this->assertEquals(1, $component->get('page'));
    }

    public function test_go_to_page_clamps_within_range(): void
    {
        $this->fakeApis([
            ['id' => 1, 'domain' => 'a.com', 'nameserver_1' => 'ns1.a.com'],
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(DomainsList::class)
            ->set('perPage', 10)
            ->call('goToPage', 999);

        $this->assertEquals(1, $component->get('page'));
    }

    // ── Selection & Session ──────────────────────────────────────────────────

    public function test_toggle_domain_selects_and_deselects(): void
    {
        $this->fakeApis([
            ['id' => 5, 'domain' => 'pick.com', 'nameserver_1' => 'ns1.a.com'],
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(DomainsList::class)
            ->call('toggleDomain', 5, 'Network 1');

        $selected = $component->get('selectedDomains');
        $this->assertArrayHasKey('Network 1:5', $selected);
        $this->assertEquals('pick.com', $selected['Network 1:5']['domain']);

        // Toggle again to deselect
        $component->call('toggleDomain', 5, 'Network 1');
        $this->assertEmpty($component->get('selectedDomains'));
    }

}
