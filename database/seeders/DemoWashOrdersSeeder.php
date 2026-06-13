<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WashOrder;
use Carbon\CarbonPeriod;
use Database\Factories\VehicleFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DemoWashOrdersSeeder extends Seeder
{
    public function run(): void
    {
        fake()->seed(20260609);

        $users = $this->users();

        if (WashOrder::query()->where('code', 'like', 'DEMO-%')->exists()) {
            $this->command?->info('Dados demo de lavagens ja existem. Nenhum registro novo foi criado.');

            return;
        }

        $services = Service::query()->where('active', true)->get();
        $customers = $this->customers();
        $vehicles = $this->fleet($customers);

        DB::transaction(function () use ($users, $services, $customers, $vehicles) {
            foreach (CarbonPeriod::create(now()->startOfYear(), now()) as $day) {
                $ordersForDay = $this->ordersForDay(Carbon::instance($day));

                for ($index = 1; $index <= $ordersForDay; $index++) {
                    $vehicle = $vehicles->random();
                    $customer = $vehicle->customer ?? $customers->random();
                    $selectedServices = $this->selectedServices($services, $vehicle);
                    $enteredAt = Carbon::instance($day)->setTime(
                        fake()->numberBetween(8, 17),
                        fake()->randomElement([0, 10, 15, 20, 30, 40, 45, 50]),
                    );
                    $estimatedMinutes = (int) $selectedServices->sum('estimated_minutes');
                    $completedAt = $enteredAt->copy()->addMinutes($estimatedMinutes + fake()->numberBetween(-10, 35));
                    $teamMemberIds = $users->random(fake()->numberBetween(1, min(4, $users->count())))->pluck('id')->values()->all();
                    $status = fake()->boolean(3) ? WashOrder::STATUS_CANCELED : fake()->randomElement([
                        WashOrder::STATUS_DELIVERED,
                        WashOrder::STATUS_DELIVERED,
                        WashOrder::STATUS_READY,
                    ]);

                    $washOrder = WashOrder::query()->create([
                        'code' => $this->codeFor($enteredAt, $index),
                        'customer_id' => $customer->id,
                        'vehicle_id' => $vehicle->id,
                        'assigned_user_id' => $teamMemberIds[0],
                        'total_amount' => $selectedServices->sum('base_price'),
                        'status' => $status,
                        'payment_status' => WashOrder::PAYMENT_PENDING,
                        'entered_at' => $enteredAt,
                        'estimated_completion_at' => $enteredAt->copy()->addMinutes($estimatedMinutes),
                        'completed_at' => $status === WashOrder::STATUS_CANCELED ? null : $completedAt,
                        'notes' => 'Registro demo para alimentar dashboard e relatorios.',
                    ]);

                    $this->attachServices($washOrder, $selectedServices);
                    $washOrder->teamMembers()->sync($teamMemberIds);
                    $this->createStatusHistory($washOrder, $users);

                    if ($status !== WashOrder::STATUS_CANCELED) {
                        $this->createPayment($washOrder, $users, $completedAt);
                    }
                }
            }
        });

        $this->command?->info('Dados demo criados de janeiro ate hoje.');
    }

    /**
     * @return Collection<int, User>
     */
    private function users(): Collection
    {
        collect([
            ['name' => 'Atendente Ana', 'email' => 'ana@lavaabs.test', 'role' => 'attendant'],
            ['name' => 'Lavador Bruno', 'email' => 'bruno@lavaabs.test', 'role' => 'operator'],
            ['name' => 'Lavador Carla', 'email' => 'carla@lavaabs.test', 'role' => 'operator'],
            ['name' => 'Caixa Diego', 'email' => 'diego@lavaabs.test', 'role' => 'attendant'],
            ['name' => 'Lavador Everton', 'email' => 'everton@lavaabs.test', 'role' => 'operator'],
            ['name' => 'Lavadora Fernanda', 'email' => 'fernanda@lavaabs.test', 'role' => 'operator'],
            ['name' => 'Lavador Gabriel', 'email' => 'gabriel@lavaabs.test', 'role' => 'operator'],
            ['name' => 'Atendente Helena', 'email' => 'helena@lavaabs.test', 'role' => 'attendant'],
        ])->each(fn (array $user) => User::query()->firstOrCreate(
            ['email' => $user['email']],
            [
                'name' => $user['name'],
                'role' => $user['role'],
                'password' => bcrypt('password'),
            ],
        ));

        return User::query()->whereIn('email', [
            'admin@lavaabs.test',
            'ana@lavaabs.test',
            'bruno@lavaabs.test',
            'carla@lavaabs.test',
            'diego@lavaabs.test',
            'everton@lavaabs.test',
            'fernanda@lavaabs.test',
            'gabriel@lavaabs.test',
            'helena@lavaabs.test',
        ])->get();
    }

    /**
     * @return Collection<int, Customer>
     */
    private function customers(): Collection
    {
        if (Customer::query()->count() < 90) {
            Customer::factory()
                ->count(90 - Customer::query()->count())
                ->create();
        }

        return Customer::query()->with('vehicles')->get();
    }

    /**
     * @param  Collection<int, Customer>  $customers
     * @return Collection<int, Vehicle>
     */
    private function fleet(Collection $customers): Collection
    {
        $catalog = collect(VehicleFactory::catalog());

        $catalog->each(function (array $vehicle, int $index) use ($customers) {
            Vehicle::query()->firstOrCreate(
                ['plate' => $this->plateFor($index)],
                [
                    'customer_id' => $customers->get($index % $customers->count())->id,
                    'brand' => $vehicle['brand'],
                    'model' => $vehicle['model'],
                    'type' => $vehicle['type'],
                    'color' => fake()->randomElement(['Branco', 'Preto', 'Prata', 'Cinza', 'Vermelho', 'Azul', 'Marrom', 'Verde']),
                    'notes' => 'Veiculo demo com marca e modelo coerentes.',
                ],
            );
        });

        if (Vehicle::query()->count() < 180) {
            Vehicle::factory()
                ->count(180 - Vehicle::query()->count())
                ->sequence(fn () => ['customer_id' => $customers->random()->id])
                ->create();
        }

        return Vehicle::query()->with('customer')->get();
    }

    private function ordersForDay(Carbon $day): int
    {
        if ($day->isSunday()) {
            return fake()->numberBetween(0, 2);
        }

        if ($day->isSaturday()) {
            return fake()->numberBetween(10, 18);
        }

        return fake()->numberBetween(5, 13);
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
                ->random(),
        };

        $extras = $services
            ->whereIn('category', ['Estetica'])
            ->whereNotIn('name', ['Lavagem de motor'])
            ->values();

        return collect([$baseService])
            ->filter()
            ->merge($extras->random(fake()->numberBetween(0, min(2, $extras->count()))))
            ->unique('id')
            ->values();
    }

    private function codeFor(Carbon $enteredAt, int $index): string
    {
        return 'DEMO-'.$enteredAt->format('ymd').'-'.Str::padLeft((string) $index, 3, '0');
    }

    private function plateFor(int $index): string
    {
        $letters = range('A', 'Z');

        return $letters[(int) floor($index / 676) % 26]
            .$letters[(int) floor($index / 26) % 26]
            .$letters[$index % 26]
            .($index % 10)
            .$letters[($index + 7) % 26]
            .Str::padLeft((string) (($index * 17) % 100), 2, '0');
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
            ])->all()
        );
    }

    /**
     * @param  Collection<int, User>  $users
     */
    private function createStatusHistory(WashOrder $washOrder, Collection $users): void
    {
        $washOrder->statusHistories()->create([
            'user_id' => $users->random()->id,
            'from_status' => null,
            'to_status' => WashOrder::STATUS_AWAITING,
            'notes' => 'Ordem demo criada.',
            'created_at' => $washOrder->entered_at,
            'updated_at' => $washOrder->entered_at,
        ]);

        if ($washOrder->status === WashOrder::STATUS_CANCELED) {
            $washOrder->statusHistories()->create([
                'user_id' => $users->random()->id,
                'from_status' => WashOrder::STATUS_AWAITING,
                'to_status' => WashOrder::STATUS_CANCELED,
                'notes' => 'Cancelada no fluxo demo.',
                'created_at' => $washOrder->entered_at->copy()->addMinutes(fake()->numberBetween(5, 30)),
                'updated_at' => $washOrder->entered_at->copy()->addMinutes(fake()->numberBetween(5, 30)),
            ]);

            return;
        }

        foreach ([WashOrder::STATUS_WASHING, WashOrder::STATUS_FINISHING, $washOrder->status] as $status) {
            $washOrder->statusHistories()->create([
                'user_id' => $users->random()->id,
                'from_status' => null,
                'to_status' => $status,
                'notes' => null,
                'created_at' => $washOrder->entered_at->copy()->addMinutes(fake()->numberBetween(10, 120)),
                'updated_at' => $washOrder->entered_at->copy()->addMinutes(fake()->numberBetween(10, 120)),
            ]);
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

        $washOrder->payments()->create([
            'user_id' => $users->random()->id,
            'method' => $method,
            'amount' => $amount,
            'paid_at' => $paidAt,
            'notes' => 'Pagamento demo.',
            'created_at' => $paidAt,
            'updated_at' => $paidAt,
        ]);

        $washOrder->forceFill([
            'payment_status' => match ($method) {
                Payment::METHOD_COURTESY => WashOrder::PAYMENT_COURTESY,
                Payment::METHOD_CREDIT_PENDING => WashOrder::PAYMENT_CREDIT_PENDING,
                default => WashOrder::PAYMENT_PAID,
            },
        ])->save();
    }
}
