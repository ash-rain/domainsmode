<?php

namespace Tests\Unit;

use App\Policies\ContentPolicy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class ContentPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_allows_any_authenticated_user(): void
    {
        $policy = new ContentPolicy();
        $user   = User::factory()->create();

        $this->assertTrue($policy->create($user));
    }

    public function test_gate_definition_exists(): void
    {
        $this->assertTrue(Gate::has('create-content'));
    }

    public function test_gate_allows_authenticated_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $this->assertTrue(Gate::allows('create-content'));
    }

    public function test_gate_denies_guest(): void
    {
        $this->assertFalse(Gate::allows('create-content'));
    }
}
