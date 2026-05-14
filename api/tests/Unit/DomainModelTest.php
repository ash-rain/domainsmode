<?php

namespace Tests\Unit;

use App\Models\Content;
use App\Models\Domain;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DomainModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_fillable_attributes(): void
    {
        $domain = new Domain();
        $expected = ['domain', 'nameserver_1', 'nameserver_2', 'nameserver_3', 'nameserver_4', 'mx_record', 'a_record'];

        $this->assertEquals($expected, $domain->getFillable());
    }

    public function test_contents_relationship_is_has_many(): void
    {
        $domain = new Domain();

        $this->assertInstanceOf(HasMany::class, $domain->contents());
    }

    public function test_contents_relationship_returns_related_content(): void
    {
        $domain = Domain::create([
            'domain'       => 'test.com',
            'nameserver_1' => 'ns1.test.com',
        ]);

        Content::create([
            'domain_id' => $domain->id,
            'user_id'   => 1,
            'title'     => 'Title A',
            'body'      => 'Body A',
        ]);

        Content::create([
            'domain_id' => $domain->id,
            'user_id'   => 2,
            'title'     => 'Title B',
            'body'      => 'Body B',
        ]);

        $this->assertCount(2, $domain->contents);
        $this->assertEquals('Title A', $domain->contents->first()->title);
    }

    public function test_domain_name_must_be_unique(): void
    {
        Domain::create(['domain' => 'unique.com']);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Domain::create(['domain' => 'unique.com']);
    }
}
