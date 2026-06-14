<?php

namespace Tests\Unit;

use App\Support\MapsCoordinates;
use PHPUnit\Framework\TestCase;

class MapsCoordinatesTest extends TestCase
{
    public function test_extracts_coordinates_from_google_maps_place_url(): void
    {
        $url = 'https://www.google.com/maps/place/Av.+Nordestina,+4660+-+Vila+Nova+Curuca,+S%C3%A3o+Paulo+-+SP,+08032-000/@-23.5191405,-46.4207678,17z/data=!3m1!4b1!4m6!3m5!1s0x94ce640cd8043355:0x58a019a956587895!8m2!3d-23.5191405!4d-46.4207678!16s%2Fg%2F11c4dryd_5?entry=ttu';

        $this->assertSame([
            'latitude' => -23.5191405,
            'longitude' => -46.4207678,
        ], MapsCoordinates::extractFromUrl($url));
    }

    public function test_extracts_coordinates_from_google_maps_data_coordinates(): void
    {
        $url = 'https://www.google.com/maps/place/Endereco/data=!3d-23.5191405!4d-46.4207678';

        $this->assertSame([
            'latitude' => -23.5191405,
            'longitude' => -46.4207678,
        ], MapsCoordinates::extractFromUrl($url));
    }
}
