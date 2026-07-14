<?php

namespace App\Services\Customers;

use App\Models\Customer;
use App\Models\Vehicle;
use App\Support\Vehicles\VehicleCatalog;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportCustomersAndVehiclesService
{
    /**
     * @return array<string, mixed>
     */
    public function handle(UploadedFile $file, int $washLocationId): array
    {
        $handle = fopen($file->getRealPath(), 'r');

        if (! $handle) {
            return $this->emptySummary(['Não foi possível ler o arquivo enviado.']);
        }

        $firstLine = fgets($handle) ?: '';
        rewind($handle);

        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
        $headers = fgetcsv($handle, 0, $delimiter);

        if (! is_array($headers)) {
            fclose($handle);

            return $this->emptySummary(['O arquivo precisa ter uma linha de cabeçalho.']);
        }

        $headers = $this->normalizeHeaders($headers);
        $summary = $this->emptySummary();
        $rowNumber = 1;

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rowNumber++;

            if ($this->isBlankRow($row)) {
                continue;
            }

            $data = $this->combineRow($headers, $row);
            $errors = $this->validateRow($data);

            if ($errors !== []) {
                $summary['skipped_rows']++;
                $summary['errors'][] = 'Linha '.$rowNumber.': '.implode(' ', $errors);

                continue;
            }

            DB::transaction(function () use ($data, $washLocationId, &$summary): void {
                [$customer, $customerWasCreated] = $this->upsertCustomer($data, $washLocationId);

                $customerWasCreated
                    ? $summary['created_customers']++
                    : $summary['updated_customers']++;

                if (! $this->rowHasVehicle($data)) {
                    return;
                }

                [$vehicle, $vehicleWasCreated] = $this->upsertVehicle($data, $customer, $washLocationId);

                $vehicleWasCreated
                    ? $summary['created_vehicles']++
                    : $summary['updated_vehicles']++;
            });

            $summary['imported_rows']++;
        }

        fclose($handle);

        return $summary;
    }

    /**
     * @param  array<int, string|null>  $headers
     * @return array<string, mixed>
     */
    private function emptySummary(array $errors = []): array
    {
        return [
            'imported_rows' => 0,
            'skipped_rows' => 0,
            'created_customers' => 0,
            'updated_customers' => 0,
            'created_vehicles' => 0,
            'updated_vehicles' => 0,
            'errors' => $errors,
        ];
    }

    /**
     * @param  array<int, string|null>  $headers
     * @return array<int, string>
     */
    private function normalizeHeaders(array $headers): array
    {
        return collect($headers)
            ->map(fn ($header) => $this->headerKey((string) $header))
            ->all();
    }

    private function headerKey(string $header): string
    {
        $key = Str::of($header)
            ->replace("\xEF\xBB\xBF", '')
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_')
            ->toString();

        return [
            'cliente' => 'name',
            'nome' => 'name',
            'nome_cliente' => 'name',
            'name' => 'name',
            'telefone' => 'phone',
            'whatsapp' => 'phone',
            'celular' => 'phone',
            'phone' => 'phone',
            'e_mail' => 'email',
            'email' => 'email',
            'cpf' => 'cpf',
            'documento' => 'cpf',
            'observacao' => 'notes',
            'observacoes' => 'notes',
            'notes' => 'notes',
            'placa' => 'plate',
            'plate' => 'plate',
            'marca' => 'brand',
            'brand' => 'brand',
            'modelo' => 'model',
            'model' => 'model',
            'cor' => 'color',
            'color' => 'color',
            'observacao_veiculo' => 'vehicle_notes',
            'observacoes_veiculo' => 'vehicle_notes',
            'vehicle_notes' => 'vehicle_notes',
        ][$key] ?? $key;
    }

    /**
     * @param  array<int, string|null>  $row
     */
    private function isBlankRow(array $row): bool
    {
        return collect($row)->every(fn ($value) => trim((string) $value) === '');
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, string|null>  $row
     * @return array<string, string>
     */
    private function combineRow(array $headers, array $row): array
    {
        $data = [];

        foreach ($headers as $index => $header) {
            if ($header === '') {
                continue;
            }

            $data[$header] = trim((string) ($row[$index] ?? ''));
        }

        $data['plate'] = $this->normalizePlate($data['plate'] ?? '');

        return $data;
    }

    /**
     * @param  array<string, string>  $data
     * @return array<int, string>
     */
    private function validateRow(array $data): array
    {
        $errors = [];

        if (($data['name'] ?? '') === '') {
            $errors[] = 'Informe o nome do cliente.';
        }

        if (($data['phone'] ?? '') === '') {
            $errors[] = 'Informe o telefone do cliente.';
        }

        if (($data['email'] ?? '') !== '' && filter_var($data['email'], FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = 'Informe um e-mail válido.';
        }

        if (! $this->rowHasVehicle($data)) {
            return $errors;
        }

        foreach (['plate' => 'placa', 'brand' => 'marca', 'model' => 'modelo', 'color' => 'cor'] as $field => $label) {
            if (($data[$field] ?? '') === '') {
                $errors[] = 'Informe '.$label.' do veículo.';
            }
        }

        if (($data['brand'] ?? '') !== '' && ! in_array($data['brand'], VehicleCatalog::brands(), true)) {
            $errors[] = 'Marca de veículo não encontrada no catálogo.';
        }

        if (($data['brand'] ?? '') !== '' && ($data['model'] ?? '') !== '' && VehicleCatalog::typeFor($data['brand'], $data['model']) === null) {
            $errors[] = 'Modelo não pertence à marca informada.';
        }

        return $errors;
    }

    /**
     * @param  array<string, string>  $data
     */
    private function rowHasVehicle(array $data): bool
    {
        return collect(['plate', 'brand', 'model', 'color', 'vehicle_notes'])
            ->contains(fn (string $field) => ($data[$field] ?? '') !== '');
    }

    /**
     * @param  array<string, string>  $data
     * @return array{0: Customer, 1: bool}
     */
    private function upsertCustomer(array $data, int $washLocationId): array
    {
        $query = Customer::query()->where('wash_location_id', $washLocationId);

        $customer = ($data['cpf'] ?? '') !== ''
            ? (clone $query)->where('cpf', $data['cpf'])->first()
            : (clone $query)->where('phone', $data['phone'])->where('name', $data['name'])->first();

        $attributes = [
            'wash_location_id' => $washLocationId,
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => ($data['email'] ?? '') !== '' ? $data['email'] : null,
            'cpf' => ($data['cpf'] ?? '') !== '' ? $data['cpf'] : null,
            'notes' => ($data['notes'] ?? '') !== '' ? $data['notes'] : null,
        ];

        if (! $customer) {
            return [Customer::query()->create($attributes), true];
        }

        $customer->update($attributes);

        return [$customer, false];
    }

    /**
     * @param  array<string, string>  $data
     * @return array{0: Vehicle, 1: bool}
     */
    private function upsertVehicle(array $data, Customer $customer, int $washLocationId): array
    {
        $vehicle = Vehicle::query()
            ->where('wash_location_id', $washLocationId)
            ->where('plate', $data['plate'])
            ->first();

        $attributes = [
            'wash_location_id' => $washLocationId,
            'customer_id' => $customer->id,
            'plate' => $data['plate'],
            'brand' => $data['brand'],
            'model' => $data['model'],
            'color' => $data['color'],
            'type' => VehicleCatalog::typeFor($data['brand'], $data['model']),
            'notes' => ($data['vehicle_notes'] ?? '') !== '' ? $data['vehicle_notes'] : null,
        ];

        if (! $vehicle) {
            return [Vehicle::query()->create($attributes), true];
        }

        $vehicle->update($attributes);

        return [$vehicle, false];
    }

    private function normalizePlate(string $plate): string
    {
        return preg_replace('/[^A-Z0-9]/', '', mb_strtoupper($plate)) ?? '';
    }
}
