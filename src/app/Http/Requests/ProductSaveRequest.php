<?php

namespace App\Http\Requests;

use App\DTO\ProductSaveDTO;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductSaveRequest extends FormRequest
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
        $productId = $this->route('product') ? $this->route('product')->id : null;
        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'available' => ['required', 'boolean'],
            'description' => ['nullable', 'string'],
            'attributes' => ['nullable', 'array'],
            'sku' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('products')->ignore($productId),
            ],
        ];
    }

    public function toDTO(): ProductSaveDTO
    {
        return new ProductSaveDTO(
            categoryId: (int) $this->validated('category_id'),
            name: (string) $this->validated('name'),
            price: (float) $this->validated('price'),
            stock: (float) $this->validated('stock'),
            available: (bool) $this->validated('available', true),
            attributes: (array) $this->validated('attributes') ?? [],
            description: (string) $this->validated('description'),
            sku: (string) $this->validated('sku'),
        );
    }
}
