<?php

namespace App\Support\Vehicles;

class VehicleCatalog
{
    /**
     * @return array<int, array{brand: string, model: string, type: string}>
     */
    public static function all(): array
    {
        return [
            ['brand' => 'Fiat', 'model' => 'Mobi', 'type' => 'carro'],
            ['brand' => 'Fiat', 'model' => 'Argo', 'type' => 'carro'],
            ['brand' => 'Fiat', 'model' => 'Cronos', 'type' => 'carro'],
            ['brand' => 'Fiat', 'model' => 'Uno', 'type' => 'carro'],
            ['brand' => 'Fiat', 'model' => 'Palio', 'type' => 'carro'],
            ['brand' => 'Fiat', 'model' => 'Siena', 'type' => 'carro'],
            ['brand' => 'Fiat', 'model' => 'Pulse', 'type' => 'suv'],
            ['brand' => 'Fiat', 'model' => 'Fastback', 'type' => 'suv'],
            ['brand' => 'Fiat', 'model' => 'Strada', 'type' => 'caminhonete'],
            ['brand' => 'Fiat', 'model' => 'Toro', 'type' => 'caminhonete'],
            ['brand' => 'Hyundai', 'model' => 'HB20', 'type' => 'carro'],
            ['brand' => 'Hyundai', 'model' => 'HB20S', 'type' => 'carro'],
            ['brand' => 'Hyundai', 'model' => 'Creta', 'type' => 'suv'],
            ['brand' => 'Hyundai', 'model' => 'Tucson', 'type' => 'suv'],
            ['brand' => 'Chevrolet', 'model' => 'Onix', 'type' => 'carro'],
            ['brand' => 'Chevrolet', 'model' => 'Onix Plus', 'type' => 'carro'],
            ['brand' => 'Chevrolet', 'model' => 'Prisma', 'type' => 'carro'],
            ['brand' => 'Chevrolet', 'model' => 'Cruze', 'type' => 'carro'],
            ['brand' => 'Chevrolet', 'model' => 'Spin', 'type' => 'carro'],
            ['brand' => 'Chevrolet', 'model' => 'Tracker', 'type' => 'suv'],
            ['brand' => 'Chevrolet', 'model' => 'Montana', 'type' => 'caminhonete'],
            ['brand' => 'Chevrolet', 'model' => 'S10', 'type' => 'caminhonete'],
            ['brand' => 'Volkswagen', 'model' => 'Gol', 'type' => 'carro'],
            ['brand' => 'Volkswagen', 'model' => 'Polo', 'type' => 'carro'],
            ['brand' => 'Volkswagen', 'model' => 'Virtus', 'type' => 'carro'],
            ['brand' => 'Volkswagen', 'model' => 'Fox', 'type' => 'carro'],
            ['brand' => 'Volkswagen', 'model' => 'Nivus', 'type' => 'suv'],
            ['brand' => 'Volkswagen', 'model' => 'T-Cross', 'type' => 'suv'],
            ['brand' => 'Volkswagen', 'model' => 'Taos', 'type' => 'suv'],
            ['brand' => 'Volkswagen', 'model' => 'Saveiro', 'type' => 'caminhonete'],
            ['brand' => 'Volkswagen', 'model' => 'Amarok', 'type' => 'caminhonete'],
            ['brand' => 'Toyota', 'model' => 'Etios', 'type' => 'carro'],
            ['brand' => 'Toyota', 'model' => 'Yaris', 'type' => 'carro'],
            ['brand' => 'Toyota', 'model' => 'Corolla', 'type' => 'carro'],
            ['brand' => 'Toyota', 'model' => 'Corolla Cross', 'type' => 'suv'],
            ['brand' => 'Toyota', 'model' => 'Hilux', 'type' => 'caminhonete'],
            ['brand' => 'Toyota', 'model' => 'SW4', 'type' => 'suv'],
            ['brand' => 'Honda', 'model' => 'Fit', 'type' => 'carro'],
            ['brand' => 'Honda', 'model' => 'City', 'type' => 'carro'],
            ['brand' => 'Honda', 'model' => 'Civic', 'type' => 'carro'],
            ['brand' => 'Honda', 'model' => 'HR-V', 'type' => 'suv'],
            ['brand' => 'Honda', 'model' => 'WR-V', 'type' => 'suv'],
            ['brand' => 'Renault', 'model' => 'Kwid', 'type' => 'carro'],
            ['brand' => 'Renault', 'model' => 'Sandero', 'type' => 'carro'],
            ['brand' => 'Renault', 'model' => 'Logan', 'type' => 'carro'],
            ['brand' => 'Renault', 'model' => 'Duster', 'type' => 'suv'],
            ['brand' => 'Renault', 'model' => 'Oroch', 'type' => 'caminhonete'],
            ['brand' => 'Jeep', 'model' => 'Renegade', 'type' => 'suv'],
            ['brand' => 'Jeep', 'model' => 'Compass', 'type' => 'suv'],
            ['brand' => 'Jeep', 'model' => 'Commander', 'type' => 'suv'],
            ['brand' => 'Ford', 'model' => 'Ka', 'type' => 'carro'],
            ['brand' => 'Ford', 'model' => 'Fiesta', 'type' => 'carro'],
            ['brand' => 'Ford', 'model' => 'EcoSport', 'type' => 'suv'],
            ['brand' => 'Ford', 'model' => 'Territory', 'type' => 'suv'],
            ['brand' => 'Ford', 'model' => 'Ranger', 'type' => 'caminhonete'],
            ['brand' => 'Nissan', 'model' => 'March', 'type' => 'carro'],
            ['brand' => 'Nissan', 'model' => 'Versa', 'type' => 'carro'],
            ['brand' => 'Nissan', 'model' => 'Kicks', 'type' => 'suv'],
            ['brand' => 'Nissan', 'model' => 'Frontier', 'type' => 'caminhonete'],
            ['brand' => 'Peugeot', 'model' => '208', 'type' => 'carro'],
            ['brand' => 'Peugeot', 'model' => '2008', 'type' => 'suv'],
            ['brand' => 'Citroen', 'model' => 'C3', 'type' => 'carro'],
            ['brand' => 'Citroen', 'model' => 'C4 Cactus', 'type' => 'suv'],
            ['brand' => 'Mitsubishi', 'model' => 'ASX', 'type' => 'suv'],
            ['brand' => 'Mitsubishi', 'model' => 'Eclipse Cross', 'type' => 'suv'],
            ['brand' => 'Mitsubishi', 'model' => 'L200 Triton', 'type' => 'caminhonete'],
            ['brand' => 'BMW', 'model' => '320i', 'type' => 'carro'],
            ['brand' => 'BMW', 'model' => 'X1', 'type' => 'suv'],
            ['brand' => 'Mercedes-Benz', 'model' => 'Classe A', 'type' => 'carro'],
            ['brand' => 'Mercedes-Benz', 'model' => 'GLA', 'type' => 'suv'],
            ['brand' => 'Audi', 'model' => 'A3', 'type' => 'carro'],
            ['brand' => 'Audi', 'model' => 'Q3', 'type' => 'suv'],
            ['brand' => 'Honda', 'model' => 'Biz 125', 'type' => 'moto'],
            ['brand' => 'Honda', 'model' => 'Pop 110i', 'type' => 'moto'],
            ['brand' => 'Honda', 'model' => 'CG 160 Fan', 'type' => 'moto'],
            ['brand' => 'Honda', 'model' => 'NXR 160 Bros', 'type' => 'moto'],
            ['brand' => 'Honda', 'model' => 'PCX 160', 'type' => 'moto'],
            ['brand' => 'Honda', 'model' => 'CB 300F', 'type' => 'moto'],
            ['brand' => 'Yamaha', 'model' => 'Factor 150', 'type' => 'moto'],
            ['brand' => 'Yamaha', 'model' => 'Fazer 250', 'type' => 'moto'],
            ['brand' => 'Yamaha', 'model' => 'XTZ 250 Lander', 'type' => 'moto'],
            ['brand' => 'Yamaha', 'model' => 'NMAX 160', 'type' => 'moto'],
            ['brand' => 'Kawasaki', 'model' => 'Ninja 400', 'type' => 'moto'],
            ['brand' => 'BMW', 'model' => 'G 310 GS', 'type' => 'moto'],
            ['brand' => 'Royal Enfield', 'model' => 'Meteor 350', 'type' => 'moto'],
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function brands(): array
    {
        return collect(self::all())
            ->pluck('brand')
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @return array<string, array<int, array{model: string, type: string}>>
     */
    public static function modelsByBrand(): array
    {
        return collect(self::all())
            ->groupBy('brand')
            ->map(fn ($models) => $models
                ->sortBy('model')
                ->map(fn (array $vehicle) => [
                    'model' => $vehicle['model'],
                    'type' => $vehicle['type'],
                ])
                ->values()
                ->all())
            ->sortKeys()
            ->all();
    }

    public static function typeFor(string $brand, string $model): ?string
    {
        $vehicle = collect(self::all())
            ->first(fn (array $vehicle) => $vehicle['brand'] === $brand && $vehicle['model'] === $model);

        return $vehicle['type'] ?? null;
    }
}
