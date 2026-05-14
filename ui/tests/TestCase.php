<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Ensure Livewire testing macros are available even when APP_ENV != 'testing'
        // (happens in Docker where APP_ENV=local is set as a system env var)
        if (! \Illuminate\Testing\TestResponse::hasMacro('assertSeeLivewire')) {
            \Illuminate\Testing\TestResponse::macro('assertSeeLivewire', function ($component) {
                if (is_subclass_of($component, \Livewire\Component::class)) {
                    $component = app(\Livewire\Mechanisms\ComponentRegistry::class)->getName($component);
                }
                $escapedComponentName = trim(htmlspecialchars(json_encode(['name' => $component])), '{}');
                \PHPUnit\Framework\Assert::assertStringContainsString(
                    $escapedComponentName,
                    $this->getContent(),
                    "Cannot find Livewire component [{$component}] rendered on page."
                );
                return $this;
            });
        }

        if (! \Illuminate\Testing\TestResponse::hasMacro('assertDontSeeLivewire')) {
            \Illuminate\Testing\TestResponse::macro('assertDontSeeLivewire', function ($component) {
                if (is_subclass_of($component, \Livewire\Component::class)) {
                    $component = app(\Livewire\Mechanisms\ComponentRegistry::class)->getName($component);
                }
                $escapedComponentName = trim(htmlspecialchars(json_encode(['name' => $component])), '{}');
                \PHPUnit\Framework\Assert::assertStringNotContainsString(
                    $escapedComponentName,
                    $this->getContent(),
                    "Found Livewire component [{$component}] rendered on page when it should not be."
                );
                return $this;
            });
        }
    }
}
