<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTO\ProductFilterDTO;
use App\Models\Product;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProductIndexRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'gte:min_price'],
            'search' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function toDTO(): ProductFilterDTO
    {
        return new ProductFilterDTO(
            categoryId: $this->validated('category_id') ? (int) $this->validated('category_id') : null,
            minPrice: $this->validated('min_price') ? (float) $this->validated('min_price') : null,
            maxPrice: $this->validated('max_price') ? (float) $this->validated('max_price') : null,
            search: $this->validated('search'),
        );
    }
}
