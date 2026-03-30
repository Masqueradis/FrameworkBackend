<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Http\Resources\CategoryResource;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

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
                response: 200,
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
}
