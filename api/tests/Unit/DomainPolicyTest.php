<?php

namespace Tests\Unit;

use App\Models\Domain;
use App\Policies\DomainPolicy;
use Illuminate\Http\Request;
use Tests\TestCase;

class DomainPolicyTest extends TestCase
{
    private DomainPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new DomainPolicy();
    }

    public function test_view_any_allows_all_requests(): void
    {
        $request = Request::create('/api/domains', 'GET');

        $this->assertTrue($this->policy->viewAny($request));
    }

    public function test_create_content_allows_all_requests(): void
    {
        $request = Request::create('/api/domains/1/content', 'POST');
        $domain  = new Domain(['domain' => 'example.com']);

        $this->assertTrue($this->policy->createContent($request, $domain));
    }
}
