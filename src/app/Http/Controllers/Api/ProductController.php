<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Http\Requests\ProductIndexRequest;
use App\Http\Resources\ProductResource;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class ProductController extends ApiController
{
    public function __construct(
        private readonly ProductService $productService
    ) {}

    #[OA\Get(
        path: '/api/products',
        description: 'Returns a list of only available products. Supports filtering by category, price range, and search by name.',
        summary: 'Get a list of products (with filtering and pagination)',
        tags: ['Catalog'],
        parameters: [
            new OA\Parameter(name: 'page', description: 'Page number', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'category_id', description: 'Category ID to filter', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'min_price', description: 'Minimum price', in: 'query', required: false, schema: new OA\Schema(type: 'number', format: 'float')),
            new OA\Parameter(name: 'max_price', description: 'Maximum price', in: 'query', required: false, schema: new OA\Schema(type: 'number', format: 'float')),
            new OA\Parameter(name: 'search', description: 'Search by product name', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successfully received product list',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Products retrieved successfully'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(
                                    property: 'data',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'id', type: 'integer', example: 1),
                                            new OA\Property(property: 'name', type: 'string', example: 'Video card RTX 4090'),
                                            new OA\Property(property: 'slug', type: 'string', example: 'rtx-4090'),
                                            new OA\Property(property: 'sku', type: 'string', example: 'SKU-1234-ABCD'),
                                            new OA\Property(property: 'price', type: 'number', format: 'float', example: 4500.00),
                                            new OA\Property(property: 'stock', type: 'integer', example: 5),
                                            new OA\Property(
                                                property: 'category',
                                                properties: [
                                                    new OA\Property(property: 'id', type: 'integer', example: 2),
                                                    new OA\Property(property: 'name', type: 'string', example: 'Video cards'),
                                                ],
                                                type: 'object',
                                                nullable: true
                                            ),
                                        ],
                                        type: 'object'
                                    )
                                ),
                                new OA\Property(
                                    property: 'meta',
                                    properties: [
                                        new OA\Property(property: 'current_page', type: 'integer', example: 1),
                                        new OA\Property(property: 'last_page', type: 'integer', example: 4),
                                        new OA\Property(property: 'per_page', type: 'integer', example: 15),
                                        new OA\Property(property: 'total', type: 'integer', example: 50),
                                    ],
                                    type: 'object'
                                ),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Parameter validation error'
            )
        ]
    )]
    public function index(ProductIndexRequest $request): JsonResponse
    {
        $dto = $request->toDTO();
        $products = $this->productService->getFilteredProducts($dto);
        return $this->respondSuccess(
            data: ProductResource::collection($products)->response()->getData(true),
            message: 'Products retrieved successfully'
        );
    }
}
