<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
        ];
    }
}
