<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RoutingTest extends TestCase
{
    use RefreshDatabase;

    // ── Guest access ─────────────────────────────────────────────────────────

    public function test_welcome_page_is_accessible(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_dashboard_redirects_guests_to_login(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_bulk_create_redirects_guests_to_login(): void
    {
        $response = $this->get('/domains/bulk-create');

        $response->assertRedirect('/login');
    }

    // ── Authenticated access ─────────────────────────────────────────────────

    public function test_authenticated_user_can_access_dashboard(): void
    {
        $user = User::factory()->create();

        Http::fake([
            'http://localhost:8001/*' => Http::response([]),
            'http://localhost:8002/*' => Http::response([]),
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
    }

    public function test_authenticated_user_can_access_bulk_create(): void
    {
        $user = User::factory()->create();

        Http::fake([
            'http://localhost:8001/*' => Http::response([]),
            'http://localhost:8002/*' => Http::response([]),
        ]);

        $response = $this->actingAs($user)->get('/domains/bulk-create');

        $response->assertStatus(200);
    }

    public function test_authenticated_user_on_welcome_redirects_to_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/');

        $response->assertRedirect(route('dashboard'));
    }
}
