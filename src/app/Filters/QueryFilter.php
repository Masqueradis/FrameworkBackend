<?php

declare(strict_types=1);

namespace App\Filters;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

abstract class QueryFilter
{
    /** @var Builder<Model> */
    protected Builder $builder;

    /** @param array<string, mixed> $request */
    public function __construct(protected array $request) {}

    /**
     * @template TModel of Model
     * @param Builder<TModel> $builder
     * @return Builder<TModel>
     */
    public function apply(Builder $builder): Builder
    {
        $this->builder = $builder;

        foreach ($this->request as $name => $value) {
            $methodName = str::camel($name);

            if (!empty($value) && method_exists($this, $methodName)) {
                $this->$methodName($value);
            }
        }

        return $this->builder;
    }
}
