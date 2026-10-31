<?php

namespace Tests;

use Database\Seeders\ContentSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;

abstract class TestCase extends BaseTestCase
{
    /**
     * Content from data/*.json is loaded once per run, when RefreshDatabase migrates;
     * every test then runs inside a transaction that is rolled back.
     */
    protected bool $seed = true;

    protected string $seeder = ContentSeeder::class;

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
