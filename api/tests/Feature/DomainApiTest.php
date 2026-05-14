<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\Domain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DomainApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a couple of test domains (seeding from scratch since we use RefreshDatabase)
        Domain::create([
            'domain' => 'test-domain.com',
            'nameserver_1' => 'ns1.example.com',
            'nameserver_2' => 'ns2.example.com',
            'mx_record' => 'mail.test-domain.com',
            'a_record' => '192.168.1.1',
        ]);

        Domain::create([
            'domain' => 'another-domain.net',
            'nameserver_1' => 'ns1.example.net',
            'mx_record' => 'mail.another-domain.net',
            'a_record' => '10.0.0.1',
        ]);
    }

    public function test_can_list_all_domains(): void
    {
        $response = $this->getJson('/api/domains');

        $response->assertStatus(200);
        $response->assertJsonCount(2);
        $response->assertJsonStructure([
            '*' => ['id', 'domain', 'nameserver_1', 'mx_record', 'a_record', 'contents'],
        ]);
    }

    public function test_can_create_content_for_domain(): void
    {
        $domain = Domain::first();

        $response = $this->withHeaders(['X-User-Id' => 1])
            ->postJson("/api/domains/{$domain->id}/content", [
                'title' => 'Test Title',
                'body' => 'Test body content',
            ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['id', 'domain_id', 'user_id', 'title', 'body']);
        $response->assertJson(['title' => 'Test Title', 'domain_id' => $domain->id]);

        $this->assertDatabaseHas('contents', [
            'domain_id' => $domain->id,
            'user_id' => 1,
            'title' => 'Test Title',
        ]);
    }

    public function test_cannot_create_duplicate_content_for_same_user(): void
    {
        $domain = Domain::first();

        Content::create([
            'domain_id' => $domain->id,
            'user_id' => 1,
            'title' => 'Existing Title',
            'body' => 'Existing body',
        ]);

        $response = $this->withHeaders(['X-User-Id' => 1])
            ->postJson("/api/domains/{$domain->id}/content", [
                'title' => 'New Title',
                'body' => 'New body',
            ]);

        $response->assertStatus(409);
        $response->assertJson(['message' => 'Content already exists for this domain']);
    }

    public function test_content_requires_title_and_body(): void
    {
        $domain = Domain::first();

        $response = $this->withHeaders(['X-User-Id' => 1])
            ->postJson("/api/domains/{$domain->id}/content", []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['title', 'body']);
    }

    public function test_returns_404_for_nonexistent_domain(): void
    {
        $response = $this->withHeaders(['X-User-Id' => 1])
            ->postJson('/api/domains/99999/content', [
                'title' => 'Test',
                'body' => 'Test body',
            ]);

        $response->assertStatus(404);
    }

    public function test_different_users_can_create_content_for_same_domain(): void
    {
        $domain = Domain::first();

        $this->withHeaders(['X-User-Id' => 1])
            ->postJson("/api/domains/{$domain->id}/content", [
                'title' => 'User 1 Content',
                'body' => 'Body 1',
            ])
            ->assertStatus(201);

        $this->withHeaders(['X-User-Id' => 2])
            ->postJson("/api/domains/{$domain->id}/content", [
                'title' => 'User 2 Content',
                'body' => 'Body 2',
            ])
            ->assertStatus(201);

        $this->assertDatabaseCount('contents', 2);
    }
}
