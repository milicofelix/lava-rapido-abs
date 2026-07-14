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

    public function test_geocoder_uses_more_precise_query_options_and_normalized_address(): void
    {
        Http::fake([
            '*' => Http::response([], 200),
        ]);

        app(AddressGeocoder::class)->geocode('Av. Nordestina, 4660, Vila Nova Curuca, Sao Paulo/SP');

        Http::assertSent(function ($request): bool {
            $data = $request->data();

            return $data['limit'] === 3
                && $data['addressdetails'] === 1
                && $data['dedupe'] === 1
                && $data['accept-language'] === 'pt-BR'
                && $data['countrycodes'] === 'br'
                && $data['q'] === 'Av. Nordestina, 4660, Vila Nova Curuca, Sao Paulo, SP, Brasil';
        });
    }

    public function test_geocoder_skips_invalid_result_and_uses_next_valid_coordinate(): void
    {
        Http::fakeSequence()
            ->push([
                [
                    'lat' => '120',
                    'lon' => '-46.4207678',
                ],
                [
                    'lat' => '-23.5191405',
                    'lon' => '-46.4207678',
                ],
            ], 200);

        $coordinates = app(AddressGeocoder::class)->geocode('Av. Nordestina, 4660, Sao Paulo, SP, Brasil');

        $this->assertSame([
            'latitude' => -23.5191405,
            'longitude' => -46.4207678,
        ], $coordinates);

        Http::assertSentCount(1);
    }
}
