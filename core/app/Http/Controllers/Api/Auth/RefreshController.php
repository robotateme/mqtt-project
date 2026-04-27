<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Core\Application\Auth\Commands\RefreshTokenCommand;
use Core\Application\Auth\Handlers\RefreshTokenHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

final class RefreshController extends Controller
{
    #[OA\Post(
        path: '/auth/refresh',
        summary: 'Refresh JWT token',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['refresh_token'],
                properties: [
                    new OA\Property(property: 'refresh_token', type: 'string'),
                ],
                type: 'object',
            ),
        ),
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Refreshed JWT token.',
                content: new OA\JsonContent(ref: '#/components/schemas/TokenResponse'),
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error.',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationError'),
            ),
        ],
        security: [],
    )]
    public function __invoke(Request $request, RefreshTokenHandler $handler): JsonResponse
    {
        $data = $request->validate([
            'refresh_token' => ['required', 'string'],
        ]);

        return response()->json([
            'token' => $handler->handle(new RefreshTokenCommand($data['refresh_token']))->toArray(),
        ]);
    }
}
