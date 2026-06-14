<?php

namespace App\Support;

class MapsCoordinates
{
    /**
     * @return array{latitude: float, longitude: float}|null
     */
    public static function extractFromUrl(?string $url): ?array
    {
        if ($url === null || trim($url) === '') {
            return null;
        }

        $decodedUrl = rawurldecode($url);

        $patterns = [
            '/@(-?\d+(?:\.\d+)?),\s*(-?\d+(?:\.\d+)?)/',
            '/!3d(-?\d+(?:\.\d+)?)!4d(-?\d+(?:\.\d+)?)/',
            '/[?&](?:ll|q)=(-?\d+(?:\.\d+)?),\s*(-?\d+(?:\.\d+)?)/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $decodedUrl, $matches) !== 1) {
                continue;
            }

            $latitude = (float) $matches[1];
            $longitude = (float) $matches[2];

            if (self::isValid($latitude, $longitude)) {
                return [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                ];
            }
        }

        return null;
    }

    private static function isValid(float $latitude, float $longitude): bool
    {
        return $latitude >= -90
            && $latitude <= 90
            && $longitude >= -180
            && $longitude <= 180;
    }
}
