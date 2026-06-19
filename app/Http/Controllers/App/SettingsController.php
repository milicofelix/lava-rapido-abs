<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\AppSetting;
use App\Models\RolePermissionSetting;
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
