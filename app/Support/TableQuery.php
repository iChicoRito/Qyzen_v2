<?php

namespace App\Support;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class TableQuery
{
    public static function perPage(Request $request): int
    {
        $perPage = (int) $request->query('per_page', 10);

        return in_array($perPage, [10, 25, 50], true) ? $perPage : 10;
    }

    public static function search(Builder $query, ?string $term, array $columns): Builder
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term, $columns) {
            foreach ($columns as $column) {
                if ($column instanceof Closure) {
                    $column($q, $term);
                } else {
                    $q->orWhere($column, 'like', "%{$term}%");
                }
            }
        });
    }

    public static function filters(Builder $query, Request $request, array $filters): Builder
    {
        foreach ($filters as $key => $column) {
            $value = $request->query($key);
            if ($value === null || $value === '') {
                continue;
            }

            if ($column instanceof Closure) {
                $column($query, $value);
            } elseif (is_bool($value)) {
                $query->where($column, $value);
            } elseif (in_array($value, ['active', 'inactive'], true)) {
                $query->where($column, $value === 'active');
            } else {
                $query->where($column, $value);
            }
        }

        return $query;
    }

    public static function sort(Builder $query, Request $request, array $allowed, string $default, string $defaultDirection = 'asc'): Builder
    {
        $sort = (string) $request->query('sort', $default);
        $direction = strtolower((string) $request->query('direction', $defaultDirection)) === 'desc' ? 'desc' : 'asc';
        $column = $allowed[$sort] ?? $allowed[$default] ?? $default;

        if ($column instanceof Closure) {
            $column($query, $direction);
        } else {
            $query->orderBy($column, $direction);
        }

        return $query;
    }
}
