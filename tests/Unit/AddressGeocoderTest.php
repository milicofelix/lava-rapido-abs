<?php

namespace Tests\Unit;

use App\Support\AddressGeocoder;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AddressGeocoderTest extends TestCase
{
    public function test_geocoder_tries_address_variations_until_it_finds_coordinates(): void
    {
        Http::fakeSequence()
            ->push([], 200)
            ->push([], 200)
            ->push([
                [
                    'lat' => '-23.5191405',
                    'lon' => '-46.4207678',
                ],
            ], 200);

        $coordinates = app(AddressGeocoder::class)->geocode('Rua Américo Trufeli, 50, Parque Dourado, Ferraz de Vasconcelos, SP, 08527-052, Brasil');

        $this->assertSame([
            'latitude' => -23.5191405,
            'longitude' => -46.4207678,
        ], $coordinates);

        Http::assertSentCount(3);
    }
}
