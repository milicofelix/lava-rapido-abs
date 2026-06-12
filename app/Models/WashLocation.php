<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class WashLocation extends Model
{
    public const STATUS_OPEN = 'open';

    public const STATUS_BUSY = 'busy';

    public const STATUS_CLOSED = 'closed';

    public const ACCOUNT_STATUS_TRIAL = 'trial';

    public const ACCOUNT_STATUS_ACTIVE = 'active';

    public const ACCOUNT_STATUS_SUSPENDED = 'suspended';

    public const ACCOUNT_STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'name',
        'slug',
        'address',
        'district',
        'city',
        'status',
        'account_status',
        'public_visible',
        'trial_started_at',
        'trial_ends_at',
        'approved_location_request_id',
        'map_x',
        'map_y',
        'latitude',
        'longitude',
        'active_orders_count',
        'phone',
    ];

    protected static function booted(): void
    {
        static::saving(function (WashLocation $location): void {
            if ($location->slug !== null && $location->slug !== '') {
                $location->slug = Str::slug($location->slug);
            }

            if ($location->slug === null || $location->slug === '' || $location->isDirty('name')) {
                $location->slug = static::uniqueSlugFor($location->name, $location->id);
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public static function uniqueSlugFor(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name) ?: 'lava-rapido';
        $slug = $baseSlug;
        $suffix = 2;

        while (static::query()
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = $baseSlug.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    protected function casts(): array
    {
        return [
            'map_x' => 'integer',
            'map_y' => 'integer',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'active_orders_count' => 'integer',
            'public_visible' => 'boolean',
            'trial_started_at' => 'datetime',
            'trial_ends_at' => 'datetime',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function owners(): HasMany
    {
        return $this->users()->where('role', User::ROLE_OWNER);
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_OPEN => 'Aberto',
            self::STATUS_BUSY => 'Em movimento',
            self::STATUS_CLOSED => 'Fechado',
        ];
    }

    public static function accountStatuses(): array
    {
        return [
            self::ACCOUNT_STATUS_TRIAL => 'Trial',
            self::ACCOUNT_STATUS_ACTIVE => 'Ativo',
            self::ACCOUNT_STATUS_SUSPENDED => 'Suspenso',
            self::ACCOUNT_STATUS_EXPIRED => 'Expirado',
        ];
    }

    public function fullAddress(): string
    {
        return collect([$this->address, $this->district, $this->city])
            ->filter()
            ->implode(' - ');
    }

    public function mapLatitude(): float
    {
        if ($this->latitude !== null) {
            return (float) $this->latitude;
        }

        return -23.55052 + (((int) $this->map_y - 50) / 1000);
    }

    public function mapLongitude(): float
    {
        if ($this->longitude !== null) {
            return (float) $this->longitude;
        }

        return -46.63331 + (((int) $this->map_x - 50) / 1000);
    }

    public function whatsappUrl(): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $this->phone);

        if ($digits === '') {
            return null;
        }

        if (strlen($digits) <= 11) {
            $digits = '55'.$digits;
        }

        return 'https://wa.me/'.$digits;
    }

    public function statusLabel(): string
    {
        return self::statuses()[$this->status] ?? $this->status;
    }

    public function accountStatusLabel(): string
    {
        return self::accountStatuses()[$this->account_status] ?? $this->account_status;
    }

    public function isPubliclyVisible(): bool
    {
        return $this->public_visible
            && in_array($this->account_status, [self::ACCOUNT_STATUS_TRIAL, self::ACCOUNT_STATUS_ACTIVE], true);
    }
}
