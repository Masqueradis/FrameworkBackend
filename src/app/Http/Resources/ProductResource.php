<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'slug' => $this->resource->slug,
            'sku' => $this->resource->sku,
            'description' => $this->resource->description,
            'price' => (float) $this->resource->price,
            'stock' => $this->resource->stock,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'images' => $this->whenLoaded('images', function () {
                return $this->images->map(fn($image) => [
                    'id' => $image->id,
                    'url' => Storage::disk('minio')->url($image->path),
                    'is_primary' => $image->is_primary,
                    'position' => $image->position,
                ]);
            }),
        ];
    }
}
