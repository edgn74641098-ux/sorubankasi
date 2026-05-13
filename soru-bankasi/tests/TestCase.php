<?php

namespace Tests;

use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->withoutMiddleware(VerifyCsrfToken::class);
    }
}
