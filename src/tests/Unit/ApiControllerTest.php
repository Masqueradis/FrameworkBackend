<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Controllers\ApiController;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApiControllerTest extends TestCase
{
    #[Test]
    public function returnErrorResponse(): void
    {
        $controller = new class extends \App\Http\Controllers\ApiController {
            public function callRespondError(string $message, int $code, mixed $errors = null): JsonResponse
            {
                return $this->respondError($message, $code, $errors);
            }
        };

        $errors = ['email' => ['The email field is required.']];

        $response = $controller->callRespondError(
            'Wrong validation',
            Response::HTTP_UNPROCESSABLE_ENTITY,
            $errors
        );

        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());

        $responseData = $response->getData(true);

        $this->assertEquals([
            'success' => false,
            'message' => 'Wrong validation',
            'errors' => $errors,
        ], $responseData);
    }
}
