<?php

namespace Tests\Unit;

use App\Models\Content;
use App\Models\Domain;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentModelTest extends TestCase
{
    use RefreshDatabase;

    private Domain $domain;

    protected function setUp(): void
    {
        parent::setUp();

        $this->domain = Domain::create([
            'domain'       => 'test.com',
            'nameserver_1' => 'ns1.test.com',
        ]);
    }

    public function test_fillable_attributes(): void
    {
        $content = new Content();

        $this->assertEquals(['domain_id', 'user_id', 'title', 'body'], $content->getFillable());
    }

    public function test_domain_relationship_is_belongs_to(): void
    {
        $content = new Content();

        $this->assertInstanceOf(BelongsTo::class, $content->domain());
    }

    public function test_domain_relationship_returns_parent_domain(): void
    {
        $content = Content::create([
            'domain_id' => $this->domain->id,
            'user_id'   => 1,
            'title'     => 'Test',
            'body'      => 'Body',
        ]);

        $this->assertEquals($this->domain->id, $content->domain->id);
        $this->assertEquals('test.com', $content->domain->domain);
    }

    public function test_unique_constraint_on_domain_id_and_user_id(): void
    {
        Content::create([
            'domain_id' => $this->domain->id,
            'user_id'   => 1,
            'title'     => 'First',
            'body'      => 'First body',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Content::create([
            'domain_id' => $this->domain->id,
            'user_id'   => 1,
            'title'     => 'Second',
            'body'      => 'Second body',
        ]);
    }

    public function test_same_user_can_have_content_on_different_domains(): void
    {
        $domain2 = Domain::create([
            'domain'       => 'other.com',
            'nameserver_1' => 'ns1.other.com',
        ]);

        Content::create([
            'domain_id' => $this->domain->id,
            'user_id'   => 1,
            'title'     => 'Content A',
            'body'      => 'Body A',
        ]);

        Content::create([
            'domain_id' => $domain2->id,
            'user_id'   => 1,
            'title'     => 'Content B',
            'body'      => 'Body B',
        ]);

        $this->assertDatabaseCount('contents', 2);
    }

    public function test_content_is_deleted_when_domain_is_deleted(): void
    {
        Content::create([
            'domain_id' => $this->domain->id,
            'user_id'   => 1,
            'title'     => 'Will be cascade deleted',
            'body'      => 'Body',
        ]);

        $this->assertDatabaseCount('contents', 1);

        $this->domain->delete();

        $this->assertDatabaseCount('contents', 0);
    }
}
