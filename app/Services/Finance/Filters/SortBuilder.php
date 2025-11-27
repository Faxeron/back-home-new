<?php

namespace App\Services\Finance\Filters;

use App\Domain\Finance\DTO\BaseFilterDTO;
use Illuminate\Database\Eloquent\Builder;

class SortBuilder
{
    public function apply(Builder $query, BaseFilterDTO $f, array $allowedFields, string $default = 'created_at'): Builder
    {
        $field = in_array($f->sort, $allowedFields, true) ? $f->sort : $default;
        $direction = strtolower($f->direction ?? 'desc');
        $direction = in_array($direction, ['asc', 'desc'], true) ? $direction : 'desc';

        return $query->orderBy($field, $direction);
    }
}
