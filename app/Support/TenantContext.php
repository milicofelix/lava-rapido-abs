<?php

namespace App\Support;

use App\Models\User;
use App\Models\WashLocation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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

    public static function hasTenant(): bool
    {
        return self::currentLocationId() !== null;
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>|Relation<TModel>  $query
     * @return Builder<TModel>|Relation<TModel>
     */
    public static function scopeByColumn(Builder|Relation $query, string $column = 'wash_location_id'): Builder|Relation
    {
        $locationId = self::currentLocationId();

        if ($locationId === null) {
            return $query;
        }

        return $query->where($column, $locationId);
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>|Relation<TModel>  $query
     * @return Builder<TModel>|Relation<TModel>
     */
    public static function scopeWashOrders(Builder|Relation $query): Builder|Relation
    {
        return self::scopeByColumn($query);
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>|Relation<TModel>  $query
     * @return Builder<TModel>|Relation<TModel>
     */
    public static function scopeCustomers(Builder|Relation $query): Builder|Relation
    {
        return self::scopeByColumn($query);
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>|Relation<TModel>  $query
     * @return Builder<TModel>|Relation<TModel>
     */
    public static function scopeVehicles(Builder|Relation $query): Builder|Relation
    {
        return self::scopeByColumn($query);
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>|Relation<TModel>  $query
     * @return Builder<TModel>|Relation<TModel>
     */
    public static function scopeServices(Builder|Relation $query): Builder|Relation
    {
        return self::scopeByColumn($query);
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>|Relation<TModel>  $query
     * @return Builder<TModel>|Relation<TModel>
     */
    public static function scopeUsers(Builder|Relation $query): Builder|Relation
    {
        return self::scopeByColumn($query);
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>|Relation<TModel>  $query
     * @return Builder<TModel>|Relation<TModel>
     */
    public static function scopeCashRegisters(Builder|Relation $query): Builder|Relation
    {
        return self::scopeByColumn($query);
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>|Relation<TModel>  $query
     * @return Builder<TModel>|Relation<TModel>
     */
    public static function scopePayments(Builder|Relation $query): Builder|Relation
    {
        $locationId = self::currentLocationId();

        if ($locationId === null) {
            return $query;
        }

        return $query->whereHas('washOrder', fn (Builder $washOrderQuery) => $washOrderQuery->where('wash_location_id', $locationId));
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>|Relation<TModel>  $query
     * @return Builder<TModel>|Relation<TModel>
     */
    public static function scopeStatusHistories(Builder|Relation $query): Builder|Relation
    {
        $locationId = self::currentLocationId();

        if ($locationId === null) {
            return $query;
        }

        return $query->whereHas('washOrder', fn (Builder $washOrderQuery) => $washOrderQuery->where('wash_location_id', $locationId));
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>|Relation<TModel>  $query
     * @return Builder<TModel>|Relation<TModel>
     */
    public static function scopeLocations(Builder|Relation $query): Builder|Relation
    {
        $locationId = self::currentLocationId();

        if ($locationId === null) {
            return $query;
        }

        return $query->whereKey($locationId);
    }

    public static function abortUnlessModelBelongsToTenant(Model $model, string $column = 'wash_location_id'): void
    {
        $locationId = self::currentLocationId();

        if ($locationId === null) {
            return;
        }

        if ((int) $model->getAttribute($column) !== $locationId) {
            throw new NotFoundHttpException;
        }
    }
}
