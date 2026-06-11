<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Los tests no dependen de assets compilados (evita "Vite manifest not found")
        $this->withoutVite();
    }
}
