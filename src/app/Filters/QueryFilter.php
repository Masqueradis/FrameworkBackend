<?php

declare(strict_types=1);

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @template TModel of Model
 */
abstract class QueryFilter
{
    /** @var Builder<TModel> */
    protected Builder $builder;

    /** @var array<int, string> */
    protected array $allowedFilters = [];

    /** @param array<string, mixed> $request */
    public function __construct(protected array $request) {}

    /**
     * @param Builder<TModel> $builder
     * @return Builder<TModel>
     */
    public function apply(Builder $builder): Builder
    {
        $this->builder = $builder;

        foreach ($this->request as $name => $value) {
            $methodName = Str::camel($name);

            if (
                in_array($name, $this->allowedFilters, true) &&
                $value !== null &&
                $value !== '' &&
                method_exists($this, $methodName)
            ) {
                $this->$methodName($value);
            }
        }

        return $this->builder;
    }
}
