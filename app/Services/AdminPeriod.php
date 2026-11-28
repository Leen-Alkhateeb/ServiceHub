<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AdminPeriod
{
    public const SESSION_KEY = 'admin.period';

    public static function dates(): array
    {
        return request()->hasSession() ? request()->session()->get(self::SESSION_KEY, []) : [];
    }

    public static function apply(Builder $query, string $column = 'created_at'): Builder
    {
        $dates = self::dates();
        $column = $query->qualifyColumn($column);
        if (isset($dates['from'])) {
            $query->where($column, '>=', $dates['from'].' 00:00:00');
        }
        if (isset($dates['to'])) {
            $query->where($column, '<=', $dates['to'].' 23:59:59.999999');
        }

        return $query;
    }

    public static function includes(Model $record, string $column = 'created_at'): bool
    {
        return self::dates() === [] || self::apply($record->newQueryWithoutScopes(), $column)->whereKey($record->getKey())->exists();
    }
}
