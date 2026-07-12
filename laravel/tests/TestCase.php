<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Bootstraps each test with a stateful SPA request context.
     *
     * Sanctum's EnsureFrontendRequestsAreStateful middleware only applies the
     * session/cookie/CSRF stack when fromFrontend() returns true — and that
     * requires an Origin (or Referer) header. Real browsers always send Origin;
     * test requests do not, so API routes never received session middleware and
     * any code touching $request->session() crashed with
     * "Session store not set on request". Emitting Origin mirrors the SPA client.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withHeader('Origin', config('app.url'));
    }
}
