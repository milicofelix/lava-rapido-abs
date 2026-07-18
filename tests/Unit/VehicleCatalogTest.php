<?php

namespace Tests\Unit;

use App\Support\Vehicles\VehicleCatalog;
use Tests\TestCase;

class VehicleCatalogTest extends TestCase
{
    public function test_catalog_has_unique_brand_model_pairs(): void
    {
        $pairs = collect(VehicleCatalog::all())
            ->map(fn (array $vehicle) => $vehicle['brand'].'|'.$vehicle['model']);

        $this->assertSame($pairs->count(), $pairs->unique()->count());
    }

    public function test_catalog_includes_common_cars_and_motorcycles(): void
    {
        $this->assertSame('carro', VehicleCatalog::typeFor('Hyundai', 'HB20'));
        $this->assertSame('suv', VehicleCatalog::typeFor('Toyota', 'Corolla Cross'));
        $this->assertSame('caminhonete', VehicleCatalog::typeFor('Chevrolet', 'S10'));
        $this->assertSame('moto', VehicleCatalog::typeFor('Honda', 'CG 160 Fan'));
        $this->assertSame('moto', VehicleCatalog::typeFor('Yamaha', 'Fazer 250'));
        $this->assertSame('moto', VehicleCatalog::typeFor('Shineray', 'Worker 125'));
    }
}
