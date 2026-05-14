<?php

namespace Tests\Unit;

use App\Http\Requests\StoreContentRequest;
use Tests\TestCase;

class StoreContentRequestTest extends TestCase
{
    public function test_authorize_returns_true(): void
    {
        $request = new StoreContentRequest();

        $this->assertTrue($request->authorize());
    }

    public function test_rules_require_title_and_body(): void
    {
        $request = new StoreContentRequest();
        $rules   = $request->rules();

        $this->assertArrayHasKey('title', $rules);
        $this->assertArrayHasKey('body', $rules);
        $this->assertStringContainsString('required', $rules['title']);
        $this->assertStringContainsString('required', $rules['body']);
        $this->assertStringContainsString('max:255', $rules['title']);
    }
}
