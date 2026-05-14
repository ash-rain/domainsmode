<?php

namespace Tests\Unit;

use App\Http\Middleware\VerifyApiKey;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Tests\TestCase;

class VerifyApiKeyTest extends TestCase
{
    private VerifyApiKey $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new VerifyApiKey();
    }

    private function passThrough(): \Closure
    {
        return fn () => new JsonResponse(['ok' => true]);
    }

    public function test_passes_when_api_key_is_empty(): void
    {
        putenv('API_KEY=');
        $_ENV['API_KEY'] = '';
        $_SERVER['API_KEY'] = '';

        $request = Request::create('/api/domains', 'GET');

        $response = $this->middleware->handle($request, $this->passThrough());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['ok' => true], $response->getData(true));
    }

    public function test_rejects_when_no_bearer_token_provided(): void
    {
        putenv('API_KEY=secret-token');
        $_ENV['API_KEY'] = 'secret-token';
        $_SERVER['API_KEY'] = 'secret-token';

        $request = Request::create('/api/domains', 'GET');

        $response = $this->middleware->handle($request, $this->passThrough());

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('Unauthorized', $response->getData(true)['message']);
    }

    public function test_rejects_when_bearer_token_is_wrong(): void
    {
        putenv('API_KEY=secret-token');
        $_ENV['API_KEY'] = 'secret-token';
        $_SERVER['API_KEY'] = 'secret-token';

        $request = Request::create('/api/domains', 'GET');
        $request->headers->set('Authorization', 'Bearer wrong-token');

        $response = $this->middleware->handle($request, $this->passThrough());

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function test_passes_when_bearer_token_matches(): void
    {
        putenv('API_KEY=secret-token');
        $_ENV['API_KEY'] = 'secret-token';
        $_SERVER['API_KEY'] = 'secret-token';

        $request = Request::create('/api/domains', 'GET');
        $request->headers->set('Authorization', 'Bearer secret-token');

        $response = $this->middleware->handle($request, $this->passThrough());

        $this->assertEquals(200, $response->getStatusCode());
    }
}
