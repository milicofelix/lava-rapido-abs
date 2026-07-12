<?php

namespace App\Console\Commands;

use App\Models\WashLocation;
use App\Support\AddressGeocoder;
use Illuminate\Console\Command;

class ReprocessWashLocationCoordinatesCommand extends Command
{
    protected $signature = 'app:reprocess-location-coordinates
        {--limit=50 : Quantidade maxima de unidades processadas}
        {--all : Reprocessa tambem unidades que ja possuem coordenadas}
        {--dry-run : Simula o processamento sem gravar no banco}';

    protected $description = 'Reprocessa latitude e longitude de unidades pelo endereco cadastrado.';

    public function handle(AddressGeocoder $geocoder): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $dryRun = (bool) $this->option('dry-run');
        $processAll = (bool) $this->option('all');

        $locations = WashLocation::query()
            ->when(! $processAll, function ($query): void {
                $query->where(function ($query): void {
                    $query->whereNull('latitude')
                        ->orWhereNull('longitude');
                });
            })
            ->orderBy('id')
            ->limit($limit)
            ->get();

        if ($locations->isEmpty()) {
            $this->info('Nenhuma unidade com coordenadas pendentes encontrada.');

            return self::SUCCESS;
        }

        $updated = 0;
        $notFound = 0;
        $skipped = 0;

        foreach ($locations as $location) {
            $address = $this->addressForGeocoding($location);

            if ($address === '') {
                $skipped++;
                $this->warn("[IGNORADO] {$location->name}: endereco vazio.");

                continue;
            }

            $coordinates = $geocoder->geocode($address);

            if ($coordinates === null) {
                $notFound++;
                $this->warn("[PENDENTE] {$location->name}: coordenadas nao encontradas para {$address}.");

                continue;
            }

            $line = sprintf(
                '[OK] %s: %.7f, %.7f',
                $location->name,
                $coordinates['latitude'],
                $coordinates['longitude'],
            );

            if ($dryRun) {
                $this->info($line.' (simulacao)');
                $updated++;

                continue;
            }

            $location->forceFill([
                'latitude' => $coordinates['latitude'],
                'longitude' => $coordinates['longitude'],
            ])->save();

            $updated++;
            $this->info($line);
        }

        $this->newLine();
        $this->info("Resumo: {$updated} atualizada(s), {$notFound} pendente(s), {$skipped} ignorada(s).");

        return self::SUCCESS;
    }

    private function addressForGeocoding(WashLocation $location): string
    {
        return collect([
            trim(collect([$location->address, $location->address_number])->filter()->implode(', ')),
            $location->district,
            $location->city,
            $location->state,
            'Brasil',
        ])->filter()->implode(', ');
    }
}
