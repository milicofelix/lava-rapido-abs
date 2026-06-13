<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_SUPER_ADMIN = 'super_admin';

    public const ROLE_OWNER = 'owner';

    public const ROLE_ADMIN = 'admin';

    public const ROLE_ATTENDANT = 'attendant';

    public const ROLE_OPERATOR = 'operator';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'role',
        'wash_location_id',
        'is_active',
        'last_login_at',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function washLocation(): BelongsTo
    {
        return $this->belongsTo(WashLocation::class);
    }

    public function assignedWashOrders(): HasMany
    {
        return $this->hasMany(WashOrder::class, 'assigned_user_id');
    }

    public function washOrderTeams(): BelongsToMany
    {
        return $this->belongsToMany(WashOrder::class)->withTimestamps();
    }

    public static function roleLabels(): array
    {
        return [
            self::ROLE_SUPER_ADMIN => 'Dono do software',
            self::ROLE_OWNER => 'Dono',
            self::ROLE_ADMIN => 'Administrador',
            self::ROLE_ATTENDANT => 'Atendente',
            self::ROLE_OPERATOR => 'Operador',
        ];
    }

    public function roleLabel(): string
    {
        return self::roleLabels()[$this->role] ?? $this->role;
    }

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * @param  array<int, string>  $roles
     */
    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(self::ROLE_SUPER_ADMIN);
    }

    public function isOwner(): bool
    {
        return $this->hasRole(self::ROLE_OWNER);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(self::ROLE_ADMIN);
    }

    public function isTeamManager(): bool
    {
        return $this->hasAnyRole([self::ROLE_OWNER, self::ROLE_ADMIN]);
    }

    public function isOperationalUser(): bool
    {
        return $this->hasAnyRole([
            self::ROLE_OWNER,
            self::ROLE_ADMIN,
            self::ROLE_ATTENDANT,
            self::ROLE_OPERATOR,
        ]);
    }

    public function belongsToWashLocation(WashLocation|int|null $location): bool
    {
        if ($location === null || $this->wash_location_id === null) {
            return false;
        }

        $locationId = $location instanceof WashLocation ? $location->id : $location;

        return (int) $this->wash_location_id === (int) $locationId;
    }
}
