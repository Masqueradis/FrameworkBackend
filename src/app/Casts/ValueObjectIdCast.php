<?php

declare(strict_types=1);

namespace App\Casts;

use App\ValueObjects\AbstractId;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class ValueObjectIdCast
{
    public function __construct(protected string $class)
    {}

    /**
     * @param Model $model
     * @param string $key
     * @param mixed $value
     * @param array<string, mixed> $attributes
     * @return mixed
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if($value === null) {
            return null;
        }

        if ($value instanceof AbstractId) {
            return new $this->class($value->value);
        }

        return new $this->class((int) $value);
    }

    /**
     * @param Model $model
     * @param string $key
     * @param mixed $value
     * @param array<string, mixed> $attributes
     * @return mixed
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof AbstractId) {
            return $value->value;
        }

        if(is_numeric($value)) {
            return (int) $value;
        }

        throw new InvalidArgumentException('Value must be an instance of ' . $this->class);
    }
}
