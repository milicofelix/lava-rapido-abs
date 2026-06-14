<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;

class AddressGeocoder
{
    /**
     * @return array{latitude: float, longitude: float}|null
     */
    public function geocode(string $address): ?array
    {
        $queries = $this->queryVariations($address);

        if ($queries === []) {
            return null;
        }

        foreach ($queries as $query) {
            $response = Http::timeout(8)
                ->retry(1, 200)
                ->withHeaders([
                    'User-Agent' => 'AutoFlow/1.0 contato@autoflow.local',
                ])
                ->get('https://nominatim.openstreetmap.org/search', [
                    'format' => 'jsonv2',
                    'limit' => 1,
                    'countrycodes' => 'br',
                    'q' => $query,
                ]);

            if (! $response->ok()) {
                continue;
            }

            $result = $response->json('0');

            if (! is_array($result) || ! isset($result['lat'], $result['lon'])) {
                continue;
            }

            $latitude = (float) $result['lat'];
            $longitude = (float) $result['lon'];

            if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
                continue;
            }

            return [
                'latitude' => $latitude,
                'longitude' => $longitude,
            ];
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private function queryVariations(string $address): array
    {
        $address = trim($address);

        if ($address === '') {
            return [];
        }

        $withoutZipCode = trim(preg_replace('/\b\d{5}-?\d{3}\b/', '', $address) ?? $address, " \t\n\r\0\x0B,");
        $withoutNumber = trim(preg_replace('/,\s*\d+\b/', '', $withoutZipCode) ?? $withoutZipCode, " \t\n\r\0\x0B,");

        $queries = [
            $address,
            $withoutZipCode,
            $withoutNumber,
            $this->ascii($address),
            $this->ascii($withoutZipCode),
            $this->ascii($withoutNumber),
        ];

        preg_match('/\b\d{5}-?\d{3}\b/', $address, $zipCode);

        if (($zipCode[0] ?? '') !== '') {
            $queries[] = $zipCode[0].', Brasil';
        }

        return collect($queries)
            ->map(fn (string $query) => trim($query, " \t\n\r\0\x0B,"))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function ascii(string $value): string
    {
        $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);

        return $converted === false ? $value : $converted;
    }
}
