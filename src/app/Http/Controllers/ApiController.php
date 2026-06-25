<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Routing\Controller;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

#[OA\Info(
    version: '1.0',
    description: 'Get API documentation.',
    title: 'API'
)]
#[OA\Server(
    url: 'http://localhost',
    description: 'Localhost'
)]
abstract class ApiController extends Controller
{
    protected function respondSuccess(mixed $data = null, string $message = 'Success', int $code = Response::HTTP_OK): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    protected function respondError(string $message = 'Success', int $code = Response::HTTP_BAD_REQUEST, mixed $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    protected function respondPaginated(ResourceCollection $resourceCollection, string $message = 'Success', int $code = Response::HTTP_OK): JsonResponse
    {
        $paginatedData = $resourceCollection->response()->getData(true);

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $paginatedData['data'],
            'meta' => $paginatedData['meta'] ?? null,
        ], $code);
    }
}
