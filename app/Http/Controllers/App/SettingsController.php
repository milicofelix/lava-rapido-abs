<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\AppSetting;
use App\Models\LoyaltyProgram;
use App\Models\RolePermissionSetting;
use App\Models\Service;
use App\Models\User;
use App\Support\AuditLogger;
use App\Support\Access\AccessControl;
use App\Support\MapsCoordinates;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(): View
    {
        return view('app.settings.edit', [
            'settings' => AppSetting::allSettings(),
            'currentLocation' => TenantContext::currentLocation(),
            'loyaltyProgram' => $this->loyaltyProgramForCurrentLocation(),
            'loyaltyCountScopes' => LoyaltyProgram::countScopes(),
            'loyaltyRewardTypes' => LoyaltyProgram::rewardTypes(),
            'loyaltyServices' => TenantContext::scopeServices(Service::query())->where('active', true)->orderBy('name')->get(['id', 'name', 'category']),
            'loyaltyCategories' => TenantContext::scopeServices(Service::query())->where('active', true)->select('category')->distinct()->orderBy('category')->pluck('category')->filter()->values(),
            'roleLabels' => User::roleLabels(),
            'permissionLabels' => AccessControl::permissionLabels(),
            'permissionDescriptions' => AccessControl::permissionDescriptions(),
            'rolePermissionGroups' => AccessControl::configurableRolePermissions(),
            'rolePermissionValues' => RolePermissionSetting::valuesForLocation(
                TenantContext::currentLocationId(),
                AccessControl::configurableRolePermissions(),
            ),
            'themes' => [
                AppSetting::THEME_LIGHT => 'Padrao claro',
                AppSetting::THEME_DARK => 'Dark',
                AppSetting::THEME_SYSTEM => 'Sistema',
            ],
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
            'logo' => ['nullable', 'image', 'max:5120'],
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
                Rule::requiredIf(fn () => $request->boolean('loyalty_is_active') && $request->input('loyalty_reward_type') === LoyaltyProgram::REWARD_FIXED_SERVICE),
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
        ]);

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
                'opening_hours' => $data['opening_hours'] ?? null,
            ];

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
            $changedFields = array_keys(array_diff_assoc($after, $before));

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
            $this->updateLoyaltyProgram($request, (int) $currentLocation->id);

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

        return back()->with('status', 'Configuracoes salvas com sucesso.');
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

    private function updateLoyaltyProgram(Request $request, int $locationId): void
    {
        $countScope = $request->input('loyalty_count_scope', LoyaltyProgram::COUNT_ANY);
        $rewardType = $request->input('loyalty_reward_type', LoyaltyProgram::REWARD_FIXED_SERVICE);

        LoyaltyProgram::query()->updateOrCreate(
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
