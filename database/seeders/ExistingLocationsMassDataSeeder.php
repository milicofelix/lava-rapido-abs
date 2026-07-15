<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WashLocation;
use App\Models\WashOrder;
use App\Support\DefaultServices;
use App\Support\Vehicles\VehicleCatalog;
use Carbon\CarbonPeriod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ExistingLocationsMassDataSeeder extends Seeder
{
    private const CODE_PREFIX = 'MASSA';

    private const CUSTOMERS_PER_LOCATION = 55;

    public function run(): void
    {
        fake()->seed(20260626);

        $locations = WashLocation::query()->orderBy('id')->get();

        if ($locations->isEmpty()) {
            $this->command?->warn('Nenhum lava-rapido encontrado. Cadastre/aprove uma unidade antes de rodar este seeder.');

            return;
        }

        DB::transaction(function () use ($locations): void {
            $locations->each(function (WashLocation $location): void {
                if (WashOrder::query()
                    ->where('wash_location_id', $location->id)
                    ->where('code', 'like', self::CODE_PREFIX.'-'.$location->id.'-%')
                    ->exists()
                ) {
                    $this->command?->info("Massa da unidade {$location->name} ja existe. Pulando.");

                    return;
                }

                DefaultServices::seedForLocation($location);

                $users = $this->usersFor($location);
                $services = Service::query()
                    ->where('wash_location_id', $location->id)
                    ->where('active', true)
                    ->get();

                if ($services->isEmpty()) {
                    $this->command?->warn("Unidade {$location->name} sem servicos ativos. Pulando.");

                    return;
                }

                $customers = $this->customersFor($location);
                $vehicles = $this->vehiclesFor($location, $customers);

                foreach (CarbonPeriod::create(now()->startOfYear(), now()) as $day) {
                    $ordersForDay = $this->ordersForDay(Carbon::instance($day));

                    for ($index = 1; $index <= $ordersForDay; $index++) {
                        $this->createWashOrder(
                            $location,
                            $users,
                            $services,
                            $vehicles,
                            Carbon::instance($day),
                            $index,
                        );
                    }
                }

                $this->command?->info("Massa criada para {$location->name}.");
            });
        });
    }

    /**
     * @return Collection<int, User>
     */
    private function usersFor(WashLocation $location): Collection
    {
        $users = User::query()
            ->where('wash_location_id', $location->id)
            ->whereIn('role', [User::ROLE_OWNER, User::ROLE_ADMIN, User::ROLE_ATTENDANT, User::ROLE_OPERATOR])
            ->get();

        if ($users->count() >= 4) {
            return $users;
        }

        collect([
            ['name' => 'Atendente Demo', 'role' => User::ROLE_ATTENDANT],
            ['name' => 'Operador Lavagem Demo', 'role' => User::ROLE_OPERATOR],
            ['name' => 'Operador Secagem Demo', 'role' => User::ROLE_OPERATOR],
            ['name' => 'Caixa Demo', 'role' => User::ROLE_ATTENDANT],
        ])->each(function (array $user, int $index) use ($location): void {
            User::query()->firstOrCreate(
                ['email' => 'massa.'.$location->id.'.'.$index.'@autoflow.test'],
                [
                    'name' => $user['name'],
                    'role' => $user['role'],
                    'wash_location_id' => $location->id,
                    'phone' => '(11) 90000-'.Str::padLeft((string) ($location->id * 100 + $index), 4, '0'),
                    'is_active' => true,
                    'password' => bcrypt('password'),
                ],
            );
        });

        return User::query()
            ->where('wash_location_id', $location->id)
            ->whereIn('role', [User::ROLE_OWNER, User::ROLE_ADMIN, User::ROLE_ATTENDANT, User::ROLE_OPERATOR])
            ->get();
    }

    /**
     * @return Collection<int, Customer>
     */
    private function customersFor(WashLocation $location): Collection
    {
        $existingCount = Customer::query()
            ->where('wash_location_id', $location->id)
            ->where('notes', 'Cliente criado pelo seeder de massa.')
            ->count();

        for ($index = $existingCount; $index < self::CUSTOMERS_PER_LOCATION; $index++) {
            Customer::query()->create([
                'wash_location_id' => $location->id,
                'name' => fake()->name(),
                'phone' => '(11) 9'.fake()->numerify('####-####'),
                'email' => fake()->optional(0.65)->safeEmail(),
                'notes' => 'Cliente criado pelo seeder de massa.',
            ]);
        }

        return Customer::query()
            ->where('wash_location_id', $location->id)
            ->with('vehicles')
            ->get();
    }

    /**
     * @param  Collection<int, Customer>  $customers
     * @return Collection<int, Vehicle>
     */
    private function vehiclesFor(WashLocation $location, Collection $customers): Collection
    {
        $catalog = collect(VehicleCatalog::all())->values();

        $customers->values()->each(function (Customer $customer, int $index) use ($catalog, $location): void {
            if ($customer->vehicles()->exists()) {
                return;
            }

            $vehicle = $catalog[$index % $catalog->count()];

            Vehicle::query()->create([
                'wash_location_id' => $location->id,
                'customer_id' => $customer->id,
                'plate' => $this->plateFor($location, $index),
                'brand' => $vehicle['brand'],
                'model' => $vehicle['model'],
                'type' => $vehicle['type'],
                'color' => fake()->randomElement(['Branco', 'Preto', 'Prata', 'Cinza', 'Vermelho', 'Azul', 'Marrom', 'Verde']),
                'notes' => 'Veiculo criado pelo seeder de massa.',
            ]);
        });

        return Vehicle::query()
            ->where('wash_location_id', $location->id)
            ->with('customer')
            ->get();
    }

    /**
     * @param  Collection<int, User>  $users
     * @param  Collection<int, Service>  $services
     * @param  Collection<int, Vehicle>  $vehicles
     */
    private function createWashOrder(
        WashLocation $location,
        Collection $users,
        Collection $services,
        Collection $vehicles,
        Carbon $day,
        int $index,
    ): void {
        $vehicle = $vehicles->random();
        $selectedServices = $this->selectedServices($services, $vehicle);
        $enteredAt = $day->copy()->setTime(
            fake()->numberBetween(7, 18),
            fake()->randomElement([0, 10, 15, 20, 30, 40, 45, 50]),
        );
        $estimatedMinutes = max(30, (int) $selectedServices->sum('estimated_minutes'));
        $completedAt = $enteredAt->copy()->addMinutes($estimatedMinutes + fake()->numberBetween(-10, 45));
        $status = $this->statusFor($day);
        $teamMemberIds = $users
            ->random(min($users->count(), fake()->numberBetween(1, 4)))
            ->pluck('id')
            ->values()
            ->all();

        $washOrder = WashOrder::query()->create([
            'code' => $this->codeFor($location, $enteredAt, $index),
            'wash_location_id' => $location->id,
            'customer_id' => $vehicle->customer_id,
            'vehicle_id' => $vehicle->id,
            'assigned_user_id' => $teamMemberIds[0] ?? null,
            'total_amount' => $selectedServices->sum('base_price'),
            'status' => $status,
            'payment_status' => WashOrder::PAYMENT_PENDING,
            'entered_at' => $enteredAt,
            'estimated_completion_at' => $enteredAt->copy()->addMinutes($estimatedMinutes),
            'completed_at' => in_array($status, [WashOrder::STATUS_DELIVERED, WashOrder::STATUS_READY], true) ? $completedAt : null,
            'notes' => 'Lavagem criada pelo seeder de massa.',
        ]);

        $this->attachServices($washOrder, $selectedServices);
        $washOrder->teamMembers()->sync($teamMemberIds);
        $this->createStatusHistories($washOrder, $users, $completedAt);

        if (! in_array($status, [WashOrder::STATUS_CANCELED, WashOrder::STATUS_AWAITING], true)) {
            $this->createPayment($washOrder, $users, $completedAt);
        }
    }

    private function ordersForDay(Carbon $day): int
    {
        if ($day->isSunday()) {
            return fake()->numberBetween(1, 4);
        }

        if ($day->isSaturday()) {
            return fake()->numberBetween(12, 22);
        }

        return fake()->numberBetween(7, 16);
    }

    /**
     * @param  Collection<int, Service>  $services
     * @return Collection<int, Service>
     */
    private function selectedServices(Collection $services, Vehicle $vehicle): Collection
    {
        $baseService = match ($vehicle->type) {
            'moto' => $services->firstWhere('name', 'Lavagem de moto'),
            'suv', 'caminhonete' => $services->firstWhere('name', 'Lavagem completa'),
            default => $services
                ->whereIn('name', ['Lavagem completa', 'Ducha simples', 'Ducha + aspiração'])
                ->values()
                ->random(),
        };

        $extras = $services
            ->whereIn('name', ['Cera', 'Higienização interna', 'Lavagem de motor', 'Polimento', 'Cristalização'])
            ->values();

        return collect([$baseService ?? $services->first()])
            ->filter()
            ->merge($extras->random(fake()->numberBetween(0, min(2, $extras->count()))))
            ->unique('id')
            ->values();
    }

    private function statusFor(Carbon $day): string
    {
        if ($day->isToday()) {
            return fake()->randomElement([
                WashOrder::STATUS_AWAITING,
                WashOrder::STATUS_WASHING,
                WashOrder::STATUS_FINISHING,
                WashOrder::STATUS_READY,
                WashOrder::STATUS_DELIVERED,
            ]);
        }

        return fake()->boolean(3)
            ? WashOrder::STATUS_CANCELED
            : WashOrder::STATUS_DELIVERED;
    }

    /**
     * @param  Collection<int, Service>  $services
     */
    private function attachServices(WashOrder $washOrder, Collection $services): void
    {
        $washOrder->services()->attach(
            $services->mapWithKeys(fn (Service $service) => [
                $service->id => [
                    'service_name' => $service->name,
                    'price' => $service->base_price,
                    'estimated_minutes' => $service->estimated_minutes,
                    'created_at' => $washOrder->entered_at,
                    'updated_at' => $washOrder->entered_at,
                ],
            ])->all(),
        );
    }

    /**
     * @param  Collection<int, User>  $users
     */
    private function createStatusHistories(WashOrder $washOrder, Collection $users, Carbon $completedAt): void
    {
        $steps = match ($washOrder->status) {
            WashOrder::STATUS_CANCELED => [WashOrder::STATUS_AWAITING, WashOrder::STATUS_CANCELED],
            WashOrder::STATUS_AWAITING => [WashOrder::STATUS_AWAITING],
            WashOrder::STATUS_WASHING => [WashOrder::STATUS_AWAITING, WashOrder::STATUS_WASHING],
            WashOrder::STATUS_FINISHING => [WashOrder::STATUS_AWAITING, WashOrder::STATUS_WASHING, WashOrder::STATUS_FINISHING],
            WashOrder::STATUS_READY => [WashOrder::STATUS_AWAITING, WashOrder::STATUS_WASHING, WashOrder::STATUS_FINISHING, WashOrder::STATUS_READY],
            default => [WashOrder::STATUS_AWAITING, WashOrder::STATUS_WASHING, WashOrder::STATUS_FINISHING, WashOrder::STATUS_READY, WashOrder::STATUS_DELIVERED],
        };

        $previousStatus = null;

        foreach ($steps as $offset => $status) {
            $history = $washOrder->statusHistories()->create([
                'user_id' => $users->random()->id,
                'from_status' => $previousStatus,
                'to_status' => $status,
                'notes' => $status === WashOrder::STATUS_AWAITING ? 'Ordem criada pela massa de teste.' : null,
            ]);

            $createdAt = $washOrder->entered_at->copy()->addMinutes($offset * fake()->numberBetween(12, 28));

            if (in_array($status, [WashOrder::STATUS_READY, WashOrder::STATUS_DELIVERED], true)) {
                $createdAt = $completedAt->copy()->subMinutes($status === WashOrder::STATUS_DELIVERED ? 0 : 10);
            }

            $history->forceFill([
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ])->save();

            $previousStatus = $status;
        }
    }

    /**
     * @param  Collection<int, User>  $users
     */
    private function createPayment(WashOrder $washOrder, Collection $users, Carbon $paidAt): void
    {
        $method = fake()->randomElement([
            Payment::METHOD_PIX,
            Payment::METHOD_PIX,
            Payment::METHOD_CASH,
            Payment::METHOD_DEBIT_CARD,
            Payment::METHOD_CREDIT_CARD,
            Payment::METHOD_CREDIT_CARD,
            Payment::METHOD_COURTESY,
            Payment::METHOD_CREDIT_PENDING,
        ]);

        $amount = in_array($method, [Payment::METHOD_COURTESY, Payment::METHOD_CREDIT_PENDING], true)
            ? 0
            : $washOrder->total_amount;

        $payment = $washOrder->payments()->create([
            'user_id' => $users->random()->id,
            'method' => $method,
            'amount' => $amount,
            'paid_at' => $paidAt,
            'notes' => 'Pagamento criado pelo seeder de massa.',
        ]);
        $payment->forceFill([
            'created_at' => $paidAt,
            'updated_at' => $paidAt,
        ])->save();

        $washOrder->forceFill([
            'payment_status' => match ($method) {
                Payment::METHOD_COURTESY => WashOrder::PAYMENT_COURTESY,
                Payment::METHOD_CREDIT_PENDING => WashOrder::PAYMENT_CREDIT_PENDING,
                default => WashOrder::PAYMENT_PAID,
            },
        ])->save();
    }

    private function codeFor(WashLocation $location, Carbon $enteredAt, int $index): string
    {
        return self::CODE_PREFIX.'-'.$location->id.'-'.$enteredAt->format('ymd').'-'.Str::padLeft((string) $index, 3, '0');
    }

    private function plateFor(WashLocation $location, int $index): string
    {
        $letters = range('A', 'Z');

        return $letters[(int) floor($index / 676) % 26]
            .$letters[(int) floor($index / 26) % 26]
            .$letters[$index % 26]
            .($location->id % 10)
            .$letters[($index + 7) % 26]
            .Str::padLeft((string) (($index * 17 + $location->id) % 100), 2, '0');
    }
}
