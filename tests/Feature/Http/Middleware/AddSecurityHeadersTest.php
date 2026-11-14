<?php

namespace Tests\Feature\Http\Middleware;

use Tests\TestCase;

class AddSecurityHeadersTest extends TestCase
{
    public function test_pages_forbid_framing_and_type_sniffing(): void
    {
        $this->get(route('login'))
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }
}
