<?php

namespace Tests\Feature;

use App\Livewire\Domains\BulkContentCreate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class BulkContentCreateTest extends TestCase
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
        ]);

        Http::fake([
            'http://api1.test/api/domains' => Http::response([
                ['id' => 1, 'domain' => 'example.com', 'nameserver_1' => 'ns1.a.com'],
                ['id' => 2, 'domain' => 'other.com',   'nameserver_1' => 'ns1.b.com'],
            ]),
        ]);
    }

    // ── Rendering ────────────────────────────────────────────────────────────

    public function test_component_renders_for_authenticated_user(): void
    {
        Livewire::actingAs($this->user)
            ->test(BulkContentCreate::class)
            ->assertStatus(200);
    }

    public function test_loads_domains_on_mount(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(BulkContentCreate::class);

        $this->assertCount(2, $component->get('domains'));
        $this->assertFalse($component->get('loading'));
    }

    // ── Filtering ────────────────────────────────────────────────────────────

    public function test_search_filters_domains(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(BulkContentCreate::class)
            ->set('search', 'example');

        $this->assertCount(1, $component->get('filteredDomains'));
    }

    public function test_filter_by_network(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(BulkContentCreate::class)
            ->set('filterNetwork', 'NonExistent');

        $this->assertCount(0, $component->get('filteredDomains'));
    }

    // ── Selection ────────────────────────────────────────────────────────────

    public function test_toggle_domain_selects(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(BulkContentCreate::class)
            ->call('toggleDomain', 1, 'Network 1');

        $selected = $component->get('selectedDomains');
        $this->assertArrayHasKey('Network 1:1', $selected);
    }

    public function test_select_all_selects_filtered_domains(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(BulkContentCreate::class)
            ->call('selectAll');

        $this->assertCount(2, $component->get('selectedDomains'));
    }

    public function test_select_all_respects_search_filter(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(BulkContentCreate::class)
            ->set('search', 'example')
            ->call('selectAll');

        $this->assertCount(1, $component->get('selectedDomains'));
    }

    public function test_deselect_all_clears_selection(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(BulkContentCreate::class)
            ->call('selectAll')
            ->call('deselectAll');

        $this->assertEmpty($component->get('selectedDomains'));
    }

    // ── Validation ───────────────────────────────────────────────────────────

    public function test_create_content_validates_title_required(): void
    {
        Livewire::actingAs($this->user)
            ->test(BulkContentCreate::class)
            ->call('selectAll')
            ->set('form.title', '')
            ->set('form.body', 'Some body')
            ->call('createContent')
            ->assertHasErrors(['form.title' => 'required']);
    }

    public function test_create_content_validates_body_required(): void
    {
        Livewire::actingAs($this->user)
            ->test(BulkContentCreate::class)
            ->call('selectAll')
            ->set('form.title', 'A Title')
            ->set('form.body', '')
            ->call('createContent')
            ->assertHasErrors(['form.body' => 'required']);
    }

    public function test_create_content_validates_title_max_length(): void
    {
        Livewire::actingAs($this->user)
            ->test(BulkContentCreate::class)
            ->call('selectAll')
            ->set('form.title', str_repeat('x', 256))
            ->set('form.body', 'Body')
            ->call('createContent')
            ->assertHasErrors(['form.title' => 'max']);
    }

    // ── Content creation ─────────────────────────────────────────────────────

    public function test_create_content_sends_to_selected_domains(): void
    {
        Http::fake([
            'http://api1.test/api/domains'           => Http::response([
                ['id' => 1, 'domain' => 'example.com', 'nameserver_1' => 'ns1.a.com'],
            ]),
            'http://api1.test/api/domains/1/content' => Http::response([
                'id' => 10, 'title' => 'My Title', 'body' => 'My Body',
            ], 201),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(BulkContentCreate::class)
            ->call('toggleDomain', 1, 'Network 1')
            ->set('form.title', 'My Title')
            ->set('form.body', 'My Body')
            ->call('createContent');

        $results = $component->get('results');
        $this->assertCount(1, $results);
        $this->assertTrue($results[0]['success']);
        $this->assertEquals('example.com', $results[0]['domain']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/api/domains/1/content')
                && $request['title'] === 'My Title';
        });
    }

    public function test_create_content_does_nothing_when_no_domains_selected(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test(BulkContentCreate::class)
            ->set('form.title', 'Title')
            ->set('form.body', 'Body')
            ->call('createContent');

        $this->assertEmpty($component->get('results'));
    }

    public function test_create_content_collects_failure_results(): void
    {
        Http::fake([
            'http://api1.test/api/domains'           => Http::response([
                ['id' => 1, 'domain' => 'dup.com', 'nameserver_1' => 'ns1.a.com'],
            ]),
            'http://api1.test/api/domains/1/content' => Http::response([
                'message' => 'Content already exists for this user and domain',
            ], 409),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(BulkContentCreate::class)
            ->call('toggleDomain', 1, 'Network 1')
            ->set('form.title', 'Title')
            ->set('form.body', 'Body')
            ->call('createContent');

        $results = $component->get('results');
        $this->assertCount(1, $results);
        $this->assertFalse($results[0]['success']);
        $this->assertStringContainsString('already exists', $results[0]['message']);
    }

    public function test_form_resets_on_full_success(): void
    {
        Http::fake([
            'http://api1.test/api/domains'           => Http::response([
                ['id' => 1, 'domain' => 'ok.com', 'nameserver_1' => 'ns1.a.com'],
            ]),
            'http://api1.test/api/domains/1/content' => Http::response([
                'id' => 10, 'title' => 'T', 'body' => 'B',
            ], 201),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(BulkContentCreate::class)
            ->call('toggleDomain', 1, 'Network 1')
            ->set('form.title', 'Title')
            ->set('form.body', 'Body')
            ->call('createContent');

        $this->assertEquals('', $component->get('form.title'));
        $this->assertEquals('', $component->get('form.body'));
    }

    public function test_form_does_not_reset_on_partial_failure(): void
    {
        Http::fake([
            'http://api1.test/api/domains' => Http::response([
                ['id' => 1, 'domain' => 'ok.com',   'nameserver_1' => 'ns1.a.com'],
                ['id' => 2, 'domain' => 'fail.com', 'nameserver_1' => 'ns1.a.com'],
            ]),
            'http://api1.test/api/domains/1/content' => Http::response([
                'id' => 10, 'title' => 'T', 'body' => 'B',
            ], 201),
            'http://api1.test/api/domains/2/content' => Http::response([
                'message' => 'Content already exists for this user and domain',
            ], 409),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(BulkContentCreate::class)
            ->call('toggleDomain', 1, 'Network 1')
            ->call('toggleDomain', 2, 'Network 1')
            ->set('form.title', 'Keep Me')
            ->set('form.body', 'Preserved')
            ->call('createContent');

        // Form should NOT reset because one failed
        $this->assertEquals('Keep Me', $component->get('form.title'));
        $this->assertEquals('Preserved', $component->get('form.body'));
    }

}
