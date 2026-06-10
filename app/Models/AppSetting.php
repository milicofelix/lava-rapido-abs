<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AppSetting extends Model
{
    public const THEME_LIGHT = 'light';
    public const THEME_DARK = 'dark';
    public const THEME_SYSTEM = 'system';

    public const DEFAULTS = [
        'company_name' => 'Lava Rapido Central',
        'company_whatsapp' => '',
        'theme' => self::THEME_LIGHT,
        'module_cash_register' => true,
        'module_credit_receivables' => true,
    ];

    protected $fillable = [
        'key',
        'value',
    ];

    protected $casts = [
        'value' => 'array',
    ];

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $settings = self::allSettings();

        return $settings[$key] ?? $default;
    }

    public static function allSettings(): array
    {
        return Cache::rememberForever('app_settings.all', function (): array {
            $stored = self::query()
                ->get(['key', 'value'])
                ->mapWithKeys(fn (self $setting) => [
                    $setting->key => $setting->value['value'] ?? null,
                ])
                ->all();

            return array_replace(self::DEFAULTS, $stored);
        });
    }

    public static function setValue(string $key, mixed $value): self
    {
        $setting = self::query()->updateOrCreate(
            ['key' => $key],
            ['value' => ['value' => $value]],
        );

        Cache::forget('app_settings.all');

        return $setting;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public static function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            self::setValue($key, $value);
        }
    }

    public static function isModuleEnabled(string $module): bool
    {
        return (bool) self::getValue($module, self::DEFAULTS[$module] ?? false);
    }

    public static function theme(): string
    {
        $theme = (string) self::getValue('theme', self::THEME_LIGHT);

        return in_array($theme, [self::THEME_LIGHT, self::THEME_DARK, self::THEME_SYSTEM], true)
            ? $theme
            : self::THEME_LIGHT;
    }
}
