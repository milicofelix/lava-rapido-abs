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

    public function test_request_id_header_is_added_to_responses(): void
    {
        $this->get('/up')
            ->assertHeader('X-Request-Id');
    }

    public function test_valid_incoming_request_id_is_preserved(): void
    {
        $this->withHeader('X-Request-Id', 'monitor-123456')
            ->get('/up')
            ->assertHeader('X-Request-Id', 'monitor-123456');
    }

    public function test_invalid_incoming_request_id_is_replaced(): void
    {
        $response = $this->withHeader('X-Request-Id', '<script>')
            ->get('/up');

        $this->assertNotSame('<script>', $response->headers->get('X-Request-Id'));
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            (string) $response->headers->get('X-Request-Id'),
        );
    }

    public function test_hsts_header_is_applied_to_secure_requests(): void
    {
        $this->get('https://localhost/up')
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }
}
