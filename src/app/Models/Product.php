<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\ValueObjectIdCast;
use App\Filters\QueryFilter;
use App\ValueObjects\CategoryId;
use App\ValueObjects\ProductId;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'category_id',
        'name',
        'slug',
        'sku',
        'description',
        'price',
        'stock',
        'available',
        'attributes',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'stock' => 'integer',
            'available' => 'boolean',
            'attributes' => 'array',
        ];
    }

    /** @return BelongsTo<Category, $this>  */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @param Builder<Product> $query
     * @param QueryFilter $filter
     * @return Builder<Product>
     */
    public function scopeFilter(Builder $query, QueryFilter $filter): Builder
    {
        return $filter->apply($query);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }
}
