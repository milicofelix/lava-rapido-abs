<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RolePermissionSetting extends Model
{
    protected $fillable = [
        'wash_location_id',
        'role',
        'permission',
        'allowed',
    ];

    protected $casts = [
        'allowed' => 'boolean',
    ];

    /** @var array<string, bool|null> */
    private static array $memo = [];

    public function washLocation(): BelongsTo
    {
        return $this->belongsTo(WashLocation::class);
    }

    public static function allowedFor(int $locationId, string $role, string $permission): ?bool
    {
        $key = "{$locationId}:{$role}:{$permission}";

        if (array_key_exists($key, self::$memo)) {
            return self::$memo[$key];
        }

        $value = self::query()
            ->where('wash_location_id', $locationId)
            ->where('role', $role)
            ->where('permission', $permission)
            ->value('allowed');

        return self::$memo[$key] = $value === null ? null : (bool) $value;
    }

    /**
     * @param  array<string, bool>  $permissions
     */
    public static function setForLocation(int $locationId, string $role, array $permissions): void
    {
        foreach ($permissions as $permission => $allowed) {
            self::query()->updateOrCreate(
                [
                    'wash_location_id' => $locationId,
                    'role' => $role,
                    'permission' => $permission,
                ],
                ['allowed' => $allowed],
            );
        }

        self::$memo = [];
    }

    /**
     * @param  array<string, array<int, string>>  $groups
     * @return array<string, array<string, bool>>
     */
    public static function valuesForLocation(?int $locationId, array $groups): array
    {
        $values = [];

        foreach ($groups as $role => $permissions) {
            foreach ($permissions as $permission) {
                $values[$role][$permission] = false;
            }
        }

        if (! $locationId) {
            return $values;
        }

        self::query()
            ->where('wash_location_id', $locationId)
            ->whereIn('role', array_keys($groups))
            ->whereIn('permission', collect($groups)->flatten()->all())
            ->get(['role', 'permission', 'allowed'])
            ->each(function (self $setting) use (&$values): void {
                $values[$setting->role][$setting->permission] = $setting->allowed;
            });

        return $values;
    }
}
