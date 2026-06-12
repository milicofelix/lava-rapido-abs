<?php

namespace App\Support;

use App\Models\User;
use App\Models\WashLocation;
use Illuminate\Database\Eloquent\Builder;

class TenantContext
{
    public static function user(): ?User
    {
        /** @var User|null $user */
        $user = auth()->user();

        return $user;
    }

    public static function currentLocationId(): ?int
    {
        $user = self::user();

        if (! $user || $user->isSuperAdmin()) {
            return null;
        }

        return $user->wash_location_id ? (int) $user->wash_location_id : null;
    }

    public static function currentLocation(): ?WashLocation
    {
        $user = self::user();

        if (! $user || $user->isSuperAdmin() || ! $user->wash_location_id) {
            return null;
        }

        return $user->washLocation;
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public static function scopeWashOrders(Builder $query): Builder
    {
        $locationId = self::currentLocationId();

        if ($locationId === null) {
            return $query;
        }

        return $query->where('wash_location_id', $locationId);
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public static function scopePayments(Builder $query): Builder
    {
        $locationId = self::currentLocationId();

        if ($locationId === null) {
            return $query;
        }

        return $query->whereHas('washOrder', fn (Builder $washOrderQuery) => $washOrderQuery->where('wash_location_id', $locationId));
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public static function scopeStatusHistories(Builder $query): Builder
    {
        $locationId = self::currentLocationId();

        if ($locationId === null) {
            return $query;
        }

        return $query->whereHas('washOrder', fn (Builder $washOrderQuery) => $washOrderQuery->where('wash_location_id', $locationId));
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public static function scopeLocations(Builder $query): Builder
    {
        $locationId = self::currentLocationId();

        if ($locationId === null) {
            return $query;
        }

        return $query->whereKey($locationId);
    }
}
