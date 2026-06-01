<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use OpenApi\Attributes as OA;
use App\DTO\Product\ProductSaveDTO;
use App\DTO\Product\UploadImageDTO;
use App\Models\Product;
use App\Services\CategoryService;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

readonly class AdminProductController implements HasMiddleware
{
    public function __construct(
        private ProductService  $productService,
        private CategoryService $categoryService,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('can:viewAny,App\Models\Product', only: ['index']),
            new Middleware('can:create,App\Models\Product', only: ['create', 'store']),
            new Middleware('can:update,product', only: ['edit', 'update', 'uploadImage']),
            new Middleware('can:delete,product', only: ['destroy']),
        ];
    }

    public function index(): View
    {
        $products = $this->productService->getPaginatedProductsForAdmin(15);
        return view('admin.products.index', compact('products'));
    }

    public function create(): View
    {
        $categories = $this->categoryService->getCategoriesForDropdown();
        return view('admin.products.form', compact('categories'));
    }

    public function store(ProductSaveDTO $data): RedirectResponse
    {
        $this->productService->createProduct($data);
        return redirect()->route('admin.products.index')
            ->with('success', 'Product created successfully.');
    }

    public function edit(Product $product): View
    {
        $categories = $this->categoryService->getCategoriesForDropdown();
        return view('admin.products.form', compact('product', 'categories'));
    }

    public function update(ProductSaveDTO $data, Product $product): RedirectResponse
    {
        $this->productService->updateProduct($product, $data);
        return redirect()->route('admin.products.index')
            ->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->productService->deleteProduct($product);
        return redirect()->route('admin.products.index')
            ->with('success', 'Product deleted successfully.');
    }

    #[OA\Post(
        path: '/admin/products/{product}/images',
        description: 'Accepts an image file and attaches it to the specified product.',
        summary: 'Upload an image for a product',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['image'],
                    properties: [
                        new OA\Property(
                            property: 'image',
                            description: 'Image file (JPEG, PNG, up to 2MB)',
                            type: 'string',
                            format: 'binary'
                        )
                    ]
                )
            )
        ),
        tags: ['Admin Products'],
        parameters: [
            new OA\Parameter(
                name: 'product',
                description: 'Product ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Image uploaded successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Image added successfully.'),
                        new OA\Property(
                            property: 'image',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 5),
                                new OA\Property(property: 'product_id', type: 'integer', example: 1),
                                new OA\Property(property: 'path', type: 'string', example: 'products/images/xyz123.jpg')
                            ],
                            type: 'object'
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized (missing or invalid token)'
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden (e.g., user lacks required permissions)'
            ),
            new OA\Response(
                response: 404,
                description: 'Product with the specified ID not found'
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error (e.g., file too large or invalid format)'
            )
        ]
    )]
    public function uploadImage(Product $product, UploadImageDTO $data): JsonResponse
    {
        $image = $this->productService->addImage($product, $data);

        return response()->json([
            'message' => 'Image added successfully.',
            'image' => $image,
        ], Response::HTTP_OK);
    }
}
