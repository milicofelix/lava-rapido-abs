<?php

namespace Database\Seeders\Regional;

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
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

abstract class RegionalWashLocationSeeder extends Seeder
{
    private const CUSTOMERS_PER_LOCATION = 12;

    private const ORDERS_PER_LOCATION_PER_WEEK = 1;

    private static ?string $passwordHash = null;

    /**
     * @return array<int, array<string, mixed>>
     */
    abstract protected function locations(): array;

    abstract protected function regionCode(): string;

    public function run(): void
    {
        fake()->seed($this->fakerSeed());

        DB::transaction(function (): void {
            foreach ($this->locations() as $locationData) {
                $location = $this->upsertLocation($locationData);

                DefaultServices::seedForLocation($location);

                if ($this->regionOrdersAlreadyExist($location)) {
                    $this->command?->info("Massa regional de {$location->name} ja existe. Pulando lavagens.");

                    continue;
                }

                $users = $this->usersFor($location);
                $services = Service::query()
                    ->where('wash_location_id', $location->id)
                    ->where('active', true)
                    ->get();
                $customers = $this->customersFor($location);
                $vehicles = $this->vehiclesFor($location, $customers);

                $this->createOrders($location, $users, $services, $vehicles);

                $this->command?->info("Regiao {$this->regionCode()}: {$location->name} criada/atualizada com lavagens.");
            }
        });
    }

    protected function fakerSeed(): int
    {
        return crc32($this->regionCode());
    }

    /**
     * @param  array<string, mixed>  $locationData
     */
    private function upsertLocation(array $locationData): WashLocation
    {
        return WashLocation::query()->updateOrCreate(
            ['name' => $locationData['name']],
            [
                'slug' => $locationData['slug'],
                'legal_name' => $locationData['legal_name'] ?? $locationData['name'].' Ltda.',
                'document' => $locationData['document'] ?? null,
                'address' => $locationData['address'],
                'address_number' => $locationData['address_number'],
                'district' => $locationData['district'],
                'city' => $locationData['city'],
                'state' => $locationData['state'] ?? 'SP',
                'status' => $locationData['status'] ?? WashLocation::STATUS_OPEN,
                'account_status' => WashLocation::ACCOUNT_STATUS_ACTIVE,
                'subscription_status' => WashLocation::ACCOUNT_STATUS_ACTIVE,
                'public_visible' => true,
                'trial_started_at' => now()->subDays(30),
                'trial_ends_at' => now()->addDays(15),
                'subscription_ends_at' => now()->addMonths(3),
                'blocked_at' => null,
                'map_x' => $locationData['map_x'] ?? 90,
                'map_y' => $locationData['map_y'] ?? 42,
                'latitude' => $locationData['latitude'],
                'longitude' => $locationData['longitude'],
                'active_orders_count' => $locationData['active_orders_count'] ?? 0,
                'phone' => $locationData['phone'],
                'opening_hours' => $locationData['opening_hours'] ?? 'Seg a sab: 08:00 as 18:00',
                'business_hours' => $locationData['business_hours'] ?? WashLocation::defaultBusinessHours(),
            ],
        );
    }

    private function regionOrdersAlreadyExist(WashLocation $location): bool
    {
        return WashOrder::query()
            ->where('wash_location_id', $location->id)
            ->where('code', 'like', $this->orderCodePrefix($location).'%')
            ->exists();
    }

    /**
     * @return Collection<int, User>
     */
    private function usersFor(WashLocation $location): Collection
    {
        $people = [
            ['name' => 'Responsavel '.$this->regionCode(), 'role' => User::ROLE_OWNER],
            ['name' => 'Atendente '.$this->regionCode(), 'role' => User::ROLE_ATTENDANT],
            ['name' => 'Operador Lavagem '.$this->regionCode(), 'role' => User::ROLE_OPERATOR],
            ['name' => 'Operador Acabamento '.$this->regionCode(), 'role' => User::ROLE_OPERATOR],
        ];

        foreach ($people as $index => $person) {
            User::query()->updateOrCreate(
                ['email' => Str::lower($this->regionCode()).'.'.$location->id.'.'.$index.'@autoflow.test'],
                [
                    'name' => $person['name'],
                    'role' => $person['role'],
                    'wash_location_id' => $location->id,
                    'phone' => '(11) 9'.Str::padLeft((string) ($location->id * 1000 + $index), 8, '0'),
                    'is_active' => true,
                    'password' => self::$passwordHash ??= Hash::make('password'),
                ],
            );
        }

        return User::query()
            ->where('wash_location_id', $location->id)
            ->whereIn('role', [User::ROLE_OWNER, User::ROLE_ATTENDANT, User::ROLE_OPERATOR])
            ->get();
    }

    /**
     * @return Collection<int, Customer>
     */
    private function customersFor(WashLocation $location): Collection
    {
        $existingCount = Customer::query()
            ->where('wash_location_id', $location->id)
            ->where('notes', $this->customerNote())
            ->count();

        for ($index = $existingCount; $index < self::CUSTOMERS_PER_LOCATION; $index++) {
            Customer::query()->create([
                'wash_location_id' => $location->id,
                'name' => fake()->name(),
                'phone' => '(11) 9'.fake()->numerify('####-####'),
                'email' => fake()->optional(0.55)->safeEmail(),
                'notes' => $this->customerNote(),
            ]);
        }

        return Customer::query()
            ->where('wash_location_id', $location->id)
            ->where('notes', $this->customerNote())
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
                'color' => fake()->randomElement(['Branco', 'Preto', 'Prata', 'Cinza', 'Vermelho', 'Azul', 'Verde']),
                'notes' => 'Veiculo criado pelo seeder regional '.$this->regionCode().'.',
            ]);
        });

        return Vehicle::query()
            ->where('wash_location_id', $location->id)
            ->whereHas('customer', fn ($query) => $query->where('notes', $this->customerNote()))
            ->with('customer')
            ->get();
    }

    /**
     * @param  Collection<int, User>  $users
     * @param  Collection<int, Service>  $services
     * @param  Collection<int, Vehicle>  $vehicles
     */
    private function createOrders(
        WashLocation $location,
        Collection $users,
        Collection $services,
        Collection $vehicles,
    ): void {
        $index = 1;

        foreach (CarbonPeriod::create(now()->startOfYear(), '1 week', now()) as $weekStart) {
            for ($dayOffset = 0; $dayOffset < self::ORDERS_PER_LOCATION_PER_WEEK; $dayOffset++) {
                $day = Carbon::instance($weekStart)->addDays($dayOffset);

                if ($day->isFuture()) {
                    continue;
                }

                $this->createOrder($location, $users, $services, $vehicles, $day, $index);
                $index++;
            }
        }

        $todayOpenOrders = WashOrder::query()
            ->where('wash_location_id', $location->id)
            ->whereDate('entered_at', today())
            ->whereIn('status', WashOrder::activeStatuses())
            ->count();

        $location->forceFill(['active_orders_count' => $todayOpenOrders])->save();
    }

    /**
     * @param  Collection<int, User>  $users
     * @param  Collection<int, Service>  $services
     * @param  Collection<int, Vehicle>  $vehicles
     */
    private function createOrder(
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
            fake()->numberBetween(8, 17),
            fake()->randomElement([0, 10, 20, 30, 40, 50]),
        );
        $estimatedMinutes = max(25, (int) $selectedServices->sum('estimated_minutes'));
        $completedAt = $enteredAt->copy()->addMinutes($estimatedMinutes + fake()->numberBetween(0, 35));
        $status = $this->statusFor($day);
        $teamMemberIds = $users
            ->random(min($users->count(), fake()->numberBetween(1, 3)))
            ->pluck('id')
            ->values()
            ->all();

        $washOrder = WashOrder::query()->create([
            'code' => $this->orderCodePrefix($location).$enteredAt->format('ymd').'-'.Str::padLeft((string) $index, 3, '0'),
            'wash_location_id' => $location->id,
            'customer_id' => $vehicle->customer_id,
            'vehicle_id' => $vehicle->id,
            'assigned_user_id' => $teamMemberIds[0] ?? null,
            'total_amount' => $selectedServices->sum('base_price'),
            'status' => $status,
            'payment_status' => WashOrder::PAYMENT_PENDING,
            'entered_at' => $enteredAt,
            'estimated_completion_at' => $enteredAt->copy()->addMinutes($estimatedMinutes),
            'completed_at' => $status === WashOrder::STATUS_DELIVERED ? $completedAt : null,
            'notes' => 'Lavagem criada pelo seeder regional '.$this->regionCode().'.',
            'customer_review_rating' => $status === WashOrder::STATUS_DELIVERED && fake()->boolean(35) ? fake()->numberBetween(4, 5) : null,
            'customer_review_comment' => null,
            'customer_review_public' => false,
            'customer_reviewed_at' => null,
        ]);

        $this->attachServices($washOrder, $selectedServices);
        $washOrder->teamMembers()->sync($teamMemberIds);
        $this->createStatusHistories($washOrder, $users, $completedAt);

        if ($washOrder->customer_review_rating !== null) {
            $washOrder->forceFill([
                'customer_review_comment' => fake()->randomElement([
                    'Atendimento rapido e carro muito bem entregue.',
                    'Equipe cuidadosa, gostei bastante do acabamento.',
                    'Boa experiencia, voltaria para lavar novamente.',
                    'Servico honesto e dentro do prazo combinado.',
                ]),
                'customer_review_public' => true,
                'customer_reviewed_at' => $completedAt->copy()->addHours(2),
            ])->save();
        }

        if ($status === WashOrder::STATUS_DELIVERED) {
            $this->createPayment($washOrder, $users, $completedAt);
        }
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
            ->whereIn('name', ['Cera', 'Higienização interna', 'Lavagem de motor'])
            ->values();

        return collect([$baseService ?? $services->first()])
            ->filter()
            ->merge($extras->random(fake()->numberBetween(0, min(1, $extras->count()))))
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

        return fake()->boolean(4) ? WashOrder::STATUS_CANCELED : WashOrder::STATUS_DELIVERED;
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
            $createdAt = $washOrder->entered_at->copy()->addMinutes($offset * 15);

            if ($status === WashOrder::STATUS_DELIVERED) {
                $createdAt = $completedAt;
            }

            $history = $washOrder->statusHistories()->create([
                'user_id' => $users->random()->id,
                'from_status' => $previousStatus,
                'to_status' => $status,
                'notes' => $status === WashOrder::STATUS_AWAITING ? 'Ordem criada pelo seeder regional.' : null,
            ]);
            $history->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->save();

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
        ]);

        $payment = $washOrder->payments()->create([
            'user_id' => $users->random()->id,
            'method' => $method,
            'amount' => $washOrder->total_amount,
            'paid_at' => $paidAt,
            'notes' => 'Pagamento criado pelo seeder regional.',
        ]);
        $payment->forceFill(['created_at' => $paidAt, 'updated_at' => $paidAt])->save();

        $washOrder->forceFill(['payment_status' => WashOrder::PAYMENT_PAID])->save();
    }

    private function orderCodePrefix(WashLocation $location): string
    {
        return 'REG-'.$this->regionCode().'-'.$location->id.'-';
    }

    private function customerNote(): string
    {
        return 'Cliente criado pelo seeder regional '.$this->regionCode().'.';
    }

    private function plateFor(WashLocation $location, int $index): string
    {
        $letters = range('A', 'Z');

        return 'R'
            .$letters[$index % 26]
            .$letters[($index + 6) % 26]
            .($location->id % 10)
            .$letters[($index + 12) % 26]
            .Str::padLeft((string) (($index * 11 + $location->id) % 100), 2, '0');
    }
}
