<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class WashLocation extends Model
{
    use HasFactory;

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
        'logo_path',
        'legal_name',
        'document',
        'address',
        'address_number',
        'district',
        'city',
        'state',
        'status',
        'account_status',
        'subscription_status',
        'public_visible',
        'trial_started_at',
        'trial_ends_at',
        'subscription_ends_at',
        'blocked_at',
        'approved_location_request_id',
        'map_x',
        'map_y',
        'latitude',
        'longitude',
        'active_orders_count',
        'phone',
        'opening_hours',
        'business_hours',
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
            'subscription_ends_at' => 'datetime',
            'blocked_at' => 'datetime',
            'business_hours' => 'array',
        ];
    }

    public function washOrders(): HasMany
    {
        return $this->hasMany(WashOrder::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function currentSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->latestOfMany();
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)
            ->where('status', Subscription::STATUS_ACTIVE)
            ->latestOfMany();
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

    /**
     * @return array<string, string>
     */
    public static function businessHourDays(): array
    {
        return [
            'monday' => 'Segunda',
            'tuesday' => 'Terça',
            'wednesday' => 'Quarta',
            'thursday' => 'Quinta',
            'friday' => 'Sexta',
            'saturday' => 'Sábado',
            'sunday' => 'Domingo',
        ];
    }

    /**
     * @return array<string, array{is_open: bool, opens: string, closes: string}>
     */
    public static function defaultBusinessHours(): array
    {
        return collect(self::businessHourDays())
            ->mapWithKeys(fn (string $label, string $day) => [
                $day => [
                    'is_open' => $day !== 'sunday',
                    'opens' => '08:00',
                    'closes' => '18:00',
                ],
            ])
            ->all();
    }

    /**
     * @return array<string, array{is_open: bool, opens: string, closes: string}>
     */
    public function normalizedBusinessHours(): array
    {
        $hours = is_array($this->business_hours) && $this->business_hours !== []
            ? $this->business_hours
            : self::defaultBusinessHours();

        return collect(self::businessHourDays())
            ->mapWithKeys(function (string $label, string $day) use ($hours) {
                $dayHours = is_array($hours[$day] ?? null) ? $hours[$day] : [];

                return [
                    $day => [
                        'is_open' => (bool) ($dayHours['is_open'] ?? false),
                        'opens' => $this->normalizeHour((string) ($dayHours['opens'] ?? '08:00')),
                        'closes' => $this->normalizeHour((string) ($dayHours['closes'] ?? '18:00')),
                    ],
                ];
            })
            ->all();
    }

    public function isOpenNow(?Carbon $moment = null): bool
    {
        $moment ??= now();

        return $this->isOpenAt($moment);
    }

    public function publicStatus(?Carbon $moment = null): string
    {
        if ($this->status === self::STATUS_CLOSED) {
            return self::STATUS_CLOSED;
        }

        if (! $this->isOpenNow($moment)) {
            return self::STATUS_CLOSED;
        }

        return $this->status === self::STATUS_BUSY ? self::STATUS_BUSY : self::STATUS_OPEN;
    }

    public function publicStatusLabel(?Carbon $moment = null): string
    {
        return self::statuses()[$this->publicStatus($moment)] ?? $this->publicStatus($moment);
    }

    public function canOpenWashOrderAt(?Carbon $moment = null): bool
    {
        if (! is_array($this->business_hours) || $this->business_hours === []) {
            return $this->status !== self::STATUS_CLOSED;
        }

        return in_array($this->publicStatus($moment), [self::STATUS_OPEN, self::STATUS_BUSY], true);
    }

    public function openingHoursSummary(): string
    {
        $hours = $this->normalizedBusinessHours();

        return collect(self::businessHourDays())
            ->map(function (string $label, string $day) use ($hours) {
                $dayHours = $hours[$day];

                if (! $dayHours['is_open']) {
                    return $label.': fechado';
                }

                return $label.': '.$dayHours['opens'].' as '.$dayHours['closes'];
            })
            ->implode('; ');
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
        $cityState = trim(($this->city ?? '').'/'.($this->state ?? ''), '/');

        $street = trim(collect([$this->address, $this->address_number])->filter()->implode(', '));

        return collect([$street, $this->district, $cityState])
            ->filter()
            ->implode(' - ');
    }

    public function logoUrl(): string
    {
        if ($this->logo_path) {
            return asset('storage/'.$this->logo_path);
        }

        return asset('images/autoflow-logo.png');
    }

    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    public function mapLatitude(): ?float
    {
        if ($this->latitude !== null) {
            return (float) $this->latitude;
        }

        return null;
    }

    public function mapLongitude(): ?float
    {
        if ($this->longitude !== null) {
            return (float) $this->longitude;
        }

        return null;
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

    private function isOpenAt(Carbon $moment): bool
    {
        $hours = $this->normalizedBusinessHours();
        $dayKey = strtolower($moment->englishDayOfWeek);
        $dayHours = $hours[$dayKey] ?? null;

        if ($this->isMomentInsideDayHours($moment, $dayHours)) {
            return true;
        }

        $previousDayKey = strtolower($moment->copy()->subDay()->englishDayOfWeek);
        $previousDayHours = $hours[$previousDayKey] ?? null;

        return $this->isMomentInsideDayHours($moment, $previousDayHours, true);
    }

    /**
     * @param  array{is_open: bool, opens: string, closes: string}|null  $dayHours
     */
    private function isMomentInsideDayHours(Carbon $moment, ?array $dayHours, bool $fromPreviousDay = false): bool
    {
        if (! $dayHours || ! $dayHours['is_open']) {
            return false;
        }

        $open = $moment->copy()->setTimeFromTimeString($dayHours['opens']);
        $close = $moment->copy()->setTimeFromTimeString($dayHours['closes']);
        $isOvernight = $close->lessThanOrEqualTo($open);

        if ($fromPreviousDay && ! $isOvernight) {
            return false;
        }

        if ($fromPreviousDay) {
            $open->subDay();
        }

        if ($isOvernight && ! $fromPreviousDay) {
            $close->addDay();
        }

        return $moment->greaterThanOrEqualTo($open) && $moment->lessThan($close);
    }

    private function normalizeHour(string $hour): string
    {
        if (preg_match('/^\d{2}:\d{2}$/', $hour) === 1) {
            return $hour;
        }

        return '08:00';
    }

    public function subscriptionStatus(): string
    {
        return $this->subscription_status ?: $this->account_status;
    }

    public function accountStatusLabel(): string
    {
        return self::accountStatuses()[$this->subscriptionStatus()] ?? $this->subscriptionStatus();
    }

    public function isTrialActive(): bool
    {
        return $this->subscriptionStatus() === self::ACCOUNT_STATUS_TRIAL
            && $this->trial_ends_at !== null
            && $this->trial_ends_at->endOfDay()->isFuture();
    }

    public function isSubscriptionActive(): bool
    {
        if ($this->subscriptionStatus() !== self::ACCOUNT_STATUS_ACTIVE) {
            return false;
        }

        return $this->subscription_ends_at === null || $this->subscription_ends_at->endOfDay()->isFuture();
    }

    public function isSubscriptionExpired(): bool
    {
        if (in_array($this->subscriptionStatus(), [self::ACCOUNT_STATUS_EXPIRED, self::ACCOUNT_STATUS_SUSPENDED], true)) {
            return true;
        }

        if ($this->subscriptionStatus() === self::ACCOUNT_STATUS_TRIAL) {
            return $this->trial_ends_at !== null && $this->trial_ends_at->endOfDay()->isPast();
        }

        if ($this->subscriptionStatus() === self::ACCOUNT_STATUS_ACTIVE) {
            return $this->subscription_ends_at !== null && $this->subscription_ends_at->endOfDay()->isPast();
        }

        return false;
    }

    public function canAccessOperationalArea(): bool
    {
        return $this->isTrialActive() || $this->isSubscriptionActive();
    }

    public function trialDaysRemaining(): ?int
    {
        if (! $this->trial_ends_at || $this->subscriptionStatus() !== self::ACCOUNT_STATUS_TRIAL) {
            return null;
        }

        return max(0, (int) now()->startOfDay()->diffInDays($this->trial_ends_at->copy()->startOfDay(), false));
    }

    public function isPubliclyVisible(): bool
    {
        return $this->public_visible && $this->canAccessOperationalArea();
    }
}
