<?php

namespace Tests\Unit;

use App\Livewire\Forms\BulkContentForm;
use Tests\TestCase;

class BulkContentFormTest extends TestCase
{
    public function test_form_class_exists(): void
    {
        $this->assertTrue(class_exists(BulkContentForm::class));
    }

    public function test_form_has_title_property(): void
    {
        $this->assertTrue(property_exists(BulkContentForm::class, 'title'));
    }

    public function test_form_has_body_property(): void
    {
        $this->assertTrue(property_exists(BulkContentForm::class, 'body'));
    }
}
