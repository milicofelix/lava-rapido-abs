<?php

namespace Tests\Unit;

use App\Support\DefaultServices;
use Tests\TestCase;

class DefaultServicesTest extends TestCase
{
    public function test_default_services_include_production_catalog_with_prices(): void
    {
        $services = collect(DefaultServices::catalog())->keyBy('name');

        foreach ([
            'Ducha simples',
            'Ducha + aspiração',
            'Lavagem completa',
            'Lavagem completa SUV/caminhonete',
            'Cera',
            'Higienização interna',
            'Lavagem de motor',
            'Polimento',
            'Cristalização',
            'Vitrificação de pintura',
            'Ducha de moto',
            'Lavagem de moto',
            'Lavagem detalhada de moto',
        ] as $name) {
            $this->assertTrue($services->has($name), "Serviço padrão ausente: {$name}");
            $this->assertGreaterThan(0, $services[$name]['price']);
            $this->assertGreaterThan(0, $services[$name]['minutes']);
        }
    }
}
