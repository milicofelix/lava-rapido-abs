<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\AppSetting;
use App\Models\LoyaltyProgram;
use App\Models\RolePermissionSetting;
use App\Models\Service;
use App\Models\User;
use App\Services\Loyalty\EvaluateLoyaltyProgramService;
use App\Support\AuditLogger;
use App\Support\Access\AccessControl;
use App\Support\MapsCoordinates;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(): View
    {
        $currentLocation = TenantContext::currentLocation();
        $rolePermissionGroups = AccessControl::configurableRolePermissions();
        $rolePermissionValues = RolePermissionSetting::valuesForLocation(
            TenantContext::currentLocationId(),
            $rolePermissionGroups,
        );

        return view('app.settings.edit', [
            'settings' => AppSetting::allSettings(),
            'currentLocation' => $currentLocation,
            'loyaltyProgram' => $this->loyaltyProgramForCurrentLocation(),
            'loyaltyCountScopes' => LoyaltyProgram::countScopes(),
            'loyaltyRewardTypes' => LoyaltyProgram::rewardTypes(),
            'loyaltyServices' => TenantContext::scopeServices(Service::query())->where('active', true)->orderBy('name')->get(['id', 'name', 'category']),
            'loyaltyCategories' => TenantContext::scopeServices(Service::query())->where('active', true)->select('category')->distinct()->orderBy('category')->pluck('category')->filter()->values(),
            'roleLabels' => User::roleLabels(),
            'permissionLabels' => AccessControl::permissionLabels(),
            'permissionDescriptions' => AccessControl::permissionDescriptions(),
            'rolePermissionGroups' => $rolePermissionGroups,
            'rolePermissionValues' => $rolePermissionValues,
            'permissionMatrix' => $this->permissionMatrix($rolePermissionGroups, $rolePermissionValues),
            'themes' => [
                AppSetting::THEME_LIGHT => 'Padrao claro',
                AppSetting::THEME_DARK => 'Dark',
                AppSetting::THEME_SYSTEM => 'Sistema',
            ],
            'businessHourDays' => \App\Models\WashLocation::businessHourDays(),
            'businessHours' => $currentLocation?->normalizedBusinessHours() ?? \App\Models\WashLocation::defaultBusinessHours(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $currentLocation = TenantContext::currentLocation();
        $this->mergeCoordinatesFromMapsUrl($request);

        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:120'],
            'company_whatsapp' => ['nullable', 'string', 'max:30'],
            'legal_name' => ['nullable', 'string', 'max:160'],
            'document' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'address_number' => ['nullable', 'string', 'max:30'],
            'district' => ['nullable', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'size:2'],
            'google_maps_url' => ['nullable', 'string', 'max:3000'],
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
            'opening_hours' => ['nullable', 'string', 'max:2000'],
            'business_hours' => ['nullable', 'array'],
            'business_hours.*.is_open' => ['nullable', 'boolean'],
            'business_hours.*.opens' => ['nullable', 'date_format:H:i'],
            'business_hours.*.closes' => ['nullable', 'date_format:H:i'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:max_width=3000,max_height=3000'],
            'theme' => ['required', Rule::in([
                AppSetting::THEME_LIGHT,
                AppSetting::THEME_DARK,
                AppSetting::THEME_SYSTEM,
            ])],
            'module_cash_register' => ['nullable', 'boolean'],
            'module_credit_receivables' => ['nullable', 'boolean'],
            'module_schedule' => ['nullable', 'boolean'],
            'loyalty_is_active' => ['nullable', 'boolean'],
            'loyalty_threshold' => ['nullable', 'integer', 'min:2', 'max:99'],
            'loyalty_count_scope' => ['nullable', Rule::in(array_keys(LoyaltyProgram::countScopes()))],
            'loyalty_qualifying_service_id' => [
                'nullable',
                Rule::requiredIf(fn () => $request->boolean('loyalty_is_active') && $request->input('loyalty_count_scope') === LoyaltyProgram::COUNT_SERVICE),
                'integer',
                Rule::exists('services', 'id')->where('wash_location_id', TenantContext::currentLocationId()),
            ],
            'loyalty_qualifying_category' => [
                'nullable',
                Rule::requiredIf(fn () => $request->boolean('loyalty_is_active') && $request->input('loyalty_count_scope') === LoyaltyProgram::COUNT_CATEGORY),
                'string',
                'max:120',
            ],
            'loyalty_reward_type' => ['nullable', Rule::in(array_keys(LoyaltyProgram::rewardTypes()))],
            'loyalty_reward_service_id' => [
                'nullable',
                'integer',
                Rule::exists('services', 'id')->where('wash_location_id', TenantContext::currentLocationId()),
            ],
            'loyalty_discount_value' => [
                'nullable',
                Rule::requiredIf(fn () => $request->boolean('loyalty_is_active') && in_array($request->input('loyalty_reward_type'), [LoyaltyProgram::REWARD_DISCOUNT_AMOUNT, LoyaltyProgram::REWARD_DISCOUNT_PERCENT], true)),
                'numeric',
                'min:0.01',
                'max:9999.99',
            ],
            'loyalty_coupon_valid_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'role_permissions' => ['nullable', 'array'],
        ], [], [
            'loyalty_threshold' => 'meta de lavagens',
            'loyalty_count_scope' => 'forma de contagem',
            'loyalty_qualifying_service_id' => 'serviço contado',
            'loyalty_qualifying_category' => 'categoria contada',
            'loyalty_reward_type' => 'prêmio',
            'loyalty_reward_service_id' => 'serviço do cupom',
            'loyalty_discount_value' => 'valor do desconto',
            'loyalty_coupon_valid_days' => 'validade do cupom',
            'logo' => 'logo da unidade',
        ]);

        $businessHours = $request->has('business_hours') ? $this->normalizeBusinessHours($request) : null;

        if ($currentLocation) {
            $locationData = [
                'name' => $data['company_name'],
                'phone' => $data['company_whatsapp'] ?? null,
                'legal_name' => $data['legal_name'] ?? null,
                'document' => $data['document'] ?? null,
                'address' => $data['address'] ?? $currentLocation->address,
                'address_number' => $data['address_number'] ?? $currentLocation->address_number,
                'district' => $data['district'] ?? null,
                'city' => $data['city'] ?? $currentLocation->city,
                'state' => isset($data['state']) ? mb_strtoupper($data['state']) : null,
                'opening_hours' => $businessHours !== null ? $this->summarizeBusinessHours($businessHours) : ($data['opening_hours'] ?? null),
            ];

            if ($businessHours !== null) {
                $locationData['business_hours'] = $businessHours;
            }

            if ($request->has('latitude') || $request->has('longitude')) {
                $locationData['latitude'] = $data['latitude'] ?? null;
                $locationData['longitude'] = $data['longitude'] ?? null;
            }

            if ($request->hasFile('logo')) {
                if ($currentLocation->logo_path) {
                    Storage::disk('public')->delete($currentLocation->logo_path);
                }

                $locationData['logo_path'] = $request->file('logo')->store('wash-location-logos', 'public');
            }

            $before = $currentLocation->only(array_keys($locationData));
            $currentLocation->update($locationData);
            $after = $currentLocation->fresh()->only(array_keys($locationData));
            $changedFields = collect($after)
                ->filter(fn ($value, string $field) => json_encode($value) !== json_encode($before[$field] ?? null))
                ->keys()
                ->all();

            if ($changedFields !== []) {
                AuditLogger::record(
                    AuditLog::ACTION_LOCATION_PROFILE_UPDATED,
                    $request->user()->name.' atualizou o perfil da unidade '.$currentLocation->name.'.',
                    $currentLocation->fresh(),
                    ['changed_fields' => $changedFields],
                );
            }
        }

        AppSetting::setMany([
            'company_name' => $data['company_name'],
            'company_whatsapp' => $data['company_whatsapp'] ?? '',
            'theme' => $data['theme'],
            'module_cash_register' => $request->boolean('module_cash_register'),
            'module_credit_receivables' => $request->boolean('module_credit_receivables'),
            'module_schedule' => $request->boolean('module_schedule'),
        ]);

        if ($currentLocation) {
            $loyaltyProgram = $this->updateLoyaltyProgram($request, (int) $currentLocation->id);

            if ($loyaltyProgram->is_active) {
                app(EvaluateLoyaltyProgramService::class)->handleEligibleCustomers($loyaltyProgram->loadMissing(['qualifyingService', 'rewardService']));
            }

            $permissionChanges = $this->updateRolePermissions($request, (int) $currentLocation->id);

            if ($permissionChanges !== []) {
                AuditLogger::record(
                    AuditLog::ACTION_ROLE_PERMISSIONS_UPDATED,
                    $request->user()->name.' atualizou permissões da equipe da unidade '.$currentLocation->name.'.',
                    $currentLocation,
                    ['changes' => $permissionChanges],
                );
            }
        }

        return back()->with('status', 'Configurações salvas com sucesso.');
    }

    /**
     * @return array<string, array{is_open: bool, opens: string, closes: string}>
     */
    private function normalizeBusinessHours(Request $request): array
    {
        $submitted = $request->input('business_hours', []);
        $hours = [];
        $errors = [];

        foreach (\App\Models\WashLocation::businessHourDays() as $day => $label) {
            $dayInput = is_array($submitted[$day] ?? null) ? $submitted[$day] : [];
            $isOpen = (bool) ($dayInput['is_open'] ?? false);
            $opens = (string) ($dayInput['opens'] ?? '08:00');
            $closes = (string) ($dayInput['closes'] ?? '18:00');

            if ($isOpen) {
                if (! preg_match('/^\d{2}:\d{2}$/', $opens)) {
                    $errors["business_hours.{$day}.opens"] = "Informe o horário de abertura de {$label}.";
                }

                if (! preg_match('/^\d{2}:\d{2}$/', $closes)) {
                    $errors["business_hours.{$day}.closes"] = "Informe o horário de fechamento de {$label}.";
                }

                if ($opens === $closes) {
                    $errors["business_hours.{$day}.closes"] = "O horário de fechamento de {$label} deve ser diferente da abertura.";
                }
            }

            $hours[$day] = [
                'is_open' => $isOpen,
                'opens' => $opens !== '' ? $opens : '08:00',
                'closes' => $closes !== '' ? $closes : '18:00',
            ];
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $hours;
    }

    /**
     * @param  array<string, array{is_open: bool, opens: string, closes: string}>  $businessHours
     */
    private function summarizeBusinessHours(array $businessHours): string
    {
        return collect(\App\Models\WashLocation::businessHourDays())
            ->map(function (string $label, string $day) use ($businessHours) {
                $dayHours = $businessHours[$day] ?? ['is_open' => false, 'opens' => '08:00', 'closes' => '18:00'];

                if (! $dayHours['is_open']) {
                    return $label.': fechado';
                }

                return $label.': '.$dayHours['opens'].' as '.$dayHours['closes'];
            })
            ->implode('; ');
    }

    private function loyaltyProgramForCurrentLocation(): LoyaltyProgram
    {
        return LoyaltyProgram::query()->firstOrNew(
            ['wash_location_id' => TenantContext::currentLocationId()],
            [
                'is_active' => false,
                'threshold' => 10,
                'count_scope' => LoyaltyProgram::COUNT_ANY,
                'reward_type' => LoyaltyProgram::REWARD_FIXED_SERVICE,
                'coupon_valid_days' => 30,
            ],
        );
    }

    private function updateLoyaltyProgram(Request $request, int $locationId): LoyaltyProgram
    {
        $countScope = $request->input('loyalty_count_scope', LoyaltyProgram::COUNT_ANY);
        $rewardType = $request->input('loyalty_reward_type', LoyaltyProgram::REWARD_FIXED_SERVICE);

        return LoyaltyProgram::query()->updateOrCreate(
            ['wash_location_id' => $locationId],
            [
                'is_active' => $request->boolean('loyalty_is_active'),
                'threshold' => (int) $request->input('loyalty_threshold', 10),
                'count_scope' => $countScope,
                'qualifying_service_id' => $countScope === LoyaltyProgram::COUNT_SERVICE ? $request->input('loyalty_qualifying_service_id') : null,
                'qualifying_category' => $countScope === LoyaltyProgram::COUNT_CATEGORY ? $request->input('loyalty_qualifying_category') : null,
                'reward_type' => $rewardType,
                'reward_service_id' => $rewardType === LoyaltyProgram::REWARD_FIXED_SERVICE ? $request->input('loyalty_reward_service_id') : null,
                'discount_value' => in_array($rewardType, [LoyaltyProgram::REWARD_DISCOUNT_AMOUNT, LoyaltyProgram::REWARD_DISCOUNT_PERCENT], true)
                    ? $request->input('loyalty_discount_value')
                    : null,
                'coupon_valid_days' => (int) $request->input('loyalty_coupon_valid_days', 30),
            ],
        );
    }

    /**
     * @return array<int, array{role: string, permission: string, from: bool, to: bool}>
     */
    private function updateRolePermissions(Request $request, int $locationId): array
    {
        $submitted = $request->input('role_permissions', []);
        $before = RolePermissionSetting::valuesForLocation($locationId, AccessControl::configurableRolePermissions());
        $changes = [];

        foreach (AccessControl::configurableRolePermissions() as $role => $permissions) {
            $roleInput = is_array($submitted[$role] ?? null) ? $submitted[$role] : [];
            $values = [];

            foreach ($permissions as $permission) {
                $values[$permission] = array_key_exists($permission, $roleInput);

                if (($before[$role][$permission] ?? false) !== $values[$permission]) {
                    $changes[] = [
                        'role' => $role,
                        'permission' => $permission,
                        'from' => (bool) ($before[$role][$permission] ?? false),
                        'to' => $values[$permission],
                    ];
                }
            }

            RolePermissionSetting::setForLocation($locationId, $role, $values);
        }

        return $changes;
    }

    /**
     * @param  array<string, array<int, string>>  $configurableGroups
     * @param  array<string, array<string, bool>>  $configuredValues
     * @return array<string, array{base: array<int, string>, configurable: array<int, string>, enabled: array<int, string>, blocked: array<int, string>}>
     */
    private function permissionMatrix(array $configurableGroups, array $configuredValues): array
    {
        $roles = [
            User::ROLE_OWNER,
            User::ROLE_ADMIN,
            User::ROLE_ATTENDANT,
            User::ROLE_OPERATOR,
        ];

        return collect($roles)
            ->mapWithKeys(function (string $role) use ($configurableGroups, $configuredValues): array {
                $base = AccessControl::rolePermissions()[$role] ?? [];
                $configurable = $configurableGroups[$role] ?? [];
                $enabled = collect($configurable)
                    ->filter(fn (string $permission): bool => (bool) ($configuredValues[$role][$permission] ?? false))
                    ->values()
                    ->all();

                $blocked = collect($configurable)
                    ->reject(fn (string $permission): bool => in_array($permission, $enabled, true))
                    ->values()
                    ->all();

                return [$role => [
                    'base' => $base,
                    'configurable' => $configurable,
                    'enabled' => $enabled,
                    'blocked' => $blocked,
                ]];
            })
            ->all();
    }

    private function mergeCoordinatesFromMapsUrl(Request $request): void
    {
        if (filled($request->input('latitude')) && filled($request->input('longitude'))) {
            return;
        }

        $coordinates = MapsCoordinates::extractFromUrl($request->input('google_maps_url'));

        if ($coordinates === null) {
            return;
        }

        $request->merge([
            'latitude' => $coordinates['latitude'],
            'longitude' => $coordinates['longitude'],
        ]);
    }
}
