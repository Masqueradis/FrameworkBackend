<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Data\CategorySaveData;
use App\Http\Controllers\ApiController;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

class CategoryController extends ApiController
{
    public function __construct(
        private readonly CategoryService $categoryService
    ) {}

    #[OA\Get(
        path: '/api/categories',
        description: 'Returns a flat list of all store categories, sorted alphabetically.',
        summary: 'Get a list of categories',
        tags: ['Catalog'],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Successfully retrieved list',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Categories retrieved successfully'),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'name', type: 'string', example: 'Видеокарты'),
                                    new OA\Property(property: 'slug', type: 'string', example: 'videocards'),
                                    new OA\Property(property: 'description', type: 'string', example: 'Graphics processing components'),
                                ],
                                type: 'object'
                            )
                        ),
                    ]
                )
            ),
        ]
    )]
    public function index(): JsonResponse
    {
        $categories = $this->categoryService->getAllCategories();

        return $this->respondSuccess(
            data: CategoryResource::collection($categories),
            message: 'Categories retrieved successfully'
        );
    }

    #[OA\Post(
        path: '/api/categories',
        description: 'Creates a new category in the catalog. Requires admin privileges.',
        summary: 'Create a new category',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Processors'),
                    new OA\Property(property: 'parent_id', type: 'integer', nullable: true, example: null),
                ]
            )
        ),
        tags: ['Catalog'],
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Category created successfully'
            ),
            new OA\Response(
                response: Response::HTTP_UNPROCESSABLE_ENTITY,
                description: 'Validation errors'
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: 'Unauthenticated'
            ),
        ]
    )]
    public function store(CategorySaveData $request): JsonResponse
    {
        $category = $this->categoryService->createCategory($request);

        return $this->respondSuccess(
            data: new CategoryResource($category),
            message: 'Category created successfully',
            code: Response::HTTP_CREATED
        );
    }

    #[OA\Get(
        path: '/api/categories/{category}',
        description: 'Returns the details of a single category.',
        summary: 'Get a specific category',
        tags: ['Catalog'],
        parameters: [
            new OA\Parameter(
                name: 'category',
                description: 'ID of the category',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Category retrieved successfully'
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: 'Category not found'
            ),
        ]
    )]
    public function show(Category $category): JsonResponse
    {
        return $this->respondSuccess(
            data: new CategoryResource($category),
            message: 'Category retrieved successfully'
        );
    }

    #[OA\Put(
        path: '/api/categories/{category}',
        description: 'Updates an existing category. Requires admin privileges.',
        summary: 'Update a specific category',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Updated Processors'),
                    new OA\Property(property: 'parent_id', type: 'integer', nullable: true, example: 1),
                ]
            )
        ),
        tags: ['Catalog'],
        parameters: [
            new OA\Parameter(
                name: 'category',
                description: 'ID of the category to update',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Category updated successfully'
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: 'Category not found'
            ),
            new OA\Response(
                response: Response::HTTP_UNPROCESSABLE_ENTITY,
                description: 'Validation errors'
            ),
        ]
    )]
    public function update(Category $category, CategorySaveData $request): JsonResponse
    {
        $updatedCategory = $this->categoryService->updateCategory($category, $request);

        return $this->respondSuccess(
            data: new CategoryResource($updatedCategory),
            message: 'Category updated successfully',
        );
    }

    #[OA\Delete(
        path: '/api/categories/{category}',
        description: 'Deletes a specific category. Requires admin privileges.',
        summary: 'Delete a category',
        security: [['bearerAuth' => []]],
        tags: ['Catalog'],
        parameters: [
            new OA\Parameter(
                name: 'category',
                description: 'ID of the category to delete',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_NO_CONTENT,
                description: 'Category deleted successfully'
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: 'Category not found'
            ),
        ]
    )]
    public function destroy(Category $category): JsonResponse
    {
        $this->categoryService->deleteCategory($category);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
