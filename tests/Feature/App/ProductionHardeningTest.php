<?php

namespace Tests\Feature\App;

use Tests\TestCase;

class ProductionHardeningTest extends TestCase
{
    public function test_health_check_endpoint_is_available(): void
    {
        $this->get('/up')
            ->assertOk();
    }

    public function test_security_headers_are_applied_to_http_responses(): void
    {
        $this->get('/up')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'accelerometer=(), camera=(), geolocation=(self), gyroscope=(), magnetometer=(), microphone=(), payment=(), usb=()');
    }

    public function test_hsts_header_is_applied_to_secure_requests(): void
    {
        $this->get('https://localhost/up')
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }
}
