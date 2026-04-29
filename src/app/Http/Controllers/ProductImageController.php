<?php

namespace App\Http\Controllers;

use App\Data\UploadImageData;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProductImageController extends ApiController
{
    public function __construct(
        private readonly ProductService $productService
    ) {}

     public function store(Product $product, UploadImageData $data)
     {
         $image = $this->productService->addImage($product, $data);

         return $this->respondSuccess(
             data: $image,
             message: 'Image added',
             code: Response::HTTP_CREATED,
         );
     }
}
