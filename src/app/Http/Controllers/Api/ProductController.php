<?php

namespace App\Http\Controllers\Api;

use App\Data\ProductIndexData;
use App\Data\ProductSaveData;
use App\Http\Controllers\ApiController;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

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
                response: Response::HTTP_OK,
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
                response: Response::HTTP_UNPROCESSABLE_ENTITY,
                description: 'Parameter validation error'
            ),
        ]
    )]
    public function index(ProductIndexData $request): JsonResponse
    {
        $products = $this->productService->getFilteredProducts($request);
        return $this->respondSuccess(
            data: ProductResource::collection($products)->response()->getData(true),
            message: 'Products retrieved successfully'
        );
    }

    #[OA\Get(
        path: '/api/products/{product}',
        summary: 'Get a specific product',
        tags: ['Catalog'],
        parameters: [
            new OA\Parameter(name: 'product', description: 'Product ID', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Product retrieved successfully'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Product not found'),
        ]
    )]
    public function show(Product $product): JsonResponse
    {
        return $this->respondSuccess(
            data: new ProductResource($product),
            message: 'Product retrieved successfully'
        );
    }

    #[OA\Post(
        path: '/api/products',
        summary: 'Create a new product (Managers and Admins only)',
        security: [['bearerAuth' => []]],
        tags: ['Catalog'],
        responses: [
            new OA\Response(response: Response::HTTP_CREATED, description: 'Product created successfully'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden - No permission'),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Validation errors'),
        ]
    )]
    public function store(ProductSaveData $request): JsonResponse
    {
        $product = $this->productService->createProduct($request);

        return $this->respondSuccess(
            data: new ProductResource($product),
            message: 'Product created successfully',
            code: Response::HTTP_CREATED
        );
    }

    #[OA\Put(
        path: '/api/products/{product}',
        summary: 'Update a specific product (Managers and Admins only)',
        security: [['bearerAuth' => []]],
        tags: ['Catalog'],
        parameters: [
            new OA\Parameter(name: 'product', description: 'Product ID', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Product updated successfully'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden - No permission'),
        ]
    )]
    public function update(ProductSaveData $request, Product $product): JsonResponse
    {
        $updateProduct = $this->productService->updateProduct($product, $request);

        return $this->respondSuccess(
            data: new ProductResource($updateProduct),
            message: 'Product updated successfully',
        );
    }

    #[OA\Delete(
        path: '/api/products/{product}',
        summary: 'Delete a specific product (Admins only)',
        security: [['bearerAuth' => []]],
        tags: ['Catalog'],
        parameters: [
            new OA\Parameter(name: 'product', description: 'Product ID', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            ],
        responses: [
            new OA\Response(response: Response::HTTP_NO_CONTENT, description: 'Product deleted successfully'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Forbidden - Admins only'),
        ]
    )]
    public function destroy(Product $product): JsonResponse
    {
        $this->productService->deleteProduct($product);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
