<?php

namespace Tests\Unit;

use App\Services\NetworkApiClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NetworkApiClientTest extends TestCase
{
    // ── getDomains ───────────────────────────────────────────────────────────

    public function test_get_domains_returns_collection_with_network_field(): void
    {
        Http::fake([
            'http://api1.test/api/domains' => Http::response([
                ['id' => 1, 'domain' => 'example.com', 'nameserver_1' => 'ns1.test.com'],
                ['id' => 2, 'domain' => 'other.com',   'nameserver_1' => 'ns1.test.com'],
            ]),
        ]);

        $client  = new NetworkApiClient('http://api1.test', 'Network 1');
        $domains = $client->getDomains();

        $this->assertCount(2, $domains);
        $this->assertEquals('Network 1', $domains[0]['network']);
        $this->assertEquals('Network 1', $domains[1]['network']);
        $this->assertEquals('example.com', $domains[0]['domain']);
    }

    public function test_get_domains_returns_empty_collection_on_failure(): void
    {
        Http::fake([
            'http://api1.test/api/domains' => Http::response(null, 500),
        ]);

        $client  = new NetworkApiClient('http://api1.test', 'Network 1');
        $domains = $client->getDomains();

        $this->assertCount(0, $domains);
    }

    public function test_get_domains_returns_empty_collection_on_404(): void
    {
        Http::fake([
            'http://api1.test/api/domains' => Http::response(null, 404),
        ]);

        $client  = new NetworkApiClient('http://api1.test', 'Network 1');
        $domains = $client->getDomains();

        $this->assertCount(0, $domains);
    }

    // ── createContent ────────────────────────────────────────────────────────

    public function test_create_content_returns_success_on_201(): void
    {
        Http::fake([
            'http://api1.test/api/domains/1/content' => Http::response([
                'id'    => 10,
                'title' => 'Test Title',
                'body'  => 'Test Body',
            ], 201),
        ]);

        $client = new NetworkApiClient('http://api1.test', 'Network 1');
        $result = $client->createContent(1, 42, 'Test Title', 'Test Body');

        $this->assertTrue($result['success']);
        $this->assertEquals(201, $result['status']);
        $this->assertEquals('Test Title', $result['data']['title']);

        Http::assertSent(function ($request) {
            return $request->url() === 'http://api1.test/api/domains/1/content'
                && $request->header('X-User-Id')[0] == 42
                && $request['title'] === 'Test Title'
                && $request['body'] === 'Test Body';
        });
    }

    public function test_create_content_returns_failure_on_409(): void
    {
        Http::fake([
            'http://api1.test/api/domains/1/content' => Http::response([
                'message' => 'Content already exists for this user and domain',
            ], 409),
        ]);

        $client = new NetworkApiClient('http://api1.test', 'Network 1');
        $result = $client->createContent(1, 42, 'Title', 'Body');

        $this->assertFalse($result['success']);
        $this->assertEquals(409, $result['status']);
        $this->assertStringContainsString('already exists', $result['data']['message']);
    }

    public function test_create_content_returns_failure_on_422(): void
    {
        Http::fake([
            'http://api1.test/api/domains/1/content' => Http::response([
                'message' => 'Validation failed',
                'errors'  => ['title' => ['The title field is required.']],
            ], 422),
        ]);

        $client = new NetworkApiClient('http://api1.test', 'Network 1');
        $result = $client->createContent(1, 42, '', 'Body');

        $this->assertFalse($result['success']);
        $this->assertEquals(422, $result['status']);
    }

    // ── Auth header ──────────────────────────────────────────────────────────

    public function test_sends_bearer_token_when_api_key_provided(): void
    {
        Http::fake([
            'http://api1.test/api/domains' => Http::response([]),
        ]);

        $client = new NetworkApiClient('http://api1.test', 'Network 1', 'my-secret-key');
        $client->getDomains();

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization')
                && $request->header('Authorization')[0] === 'Bearer my-secret-key';
        });
    }

    public function test_no_bearer_token_when_api_key_empty(): void
    {
        Http::fake([
            'http://api1.test/api/domains' => Http::response([]),
        ]);

        $client = new NetworkApiClient('http://api1.test', 'Network 1', '');
        $client->getDomains();

        Http::assertSent(function ($request) {
            return ! $request->hasHeader('Authorization');
        });
    }
}
