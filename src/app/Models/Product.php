<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
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

    protected $casts = [
        'price' => 'decimal:2',
        'stock' => 'integer',
        'available' => 'boolean',
        'attributes' => 'array',
    ];

    /** @return BelongsTo<Category, $this>  */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
