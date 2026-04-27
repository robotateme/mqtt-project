<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

final class LogoutController extends Controller
{
    #[OA\Post(
        path: '/auth/logout',
        summary: 'Logout current user',
        security: [['bearerAuth' => []]],
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 204, description: 'Session closed.'),
            new OA\Response(response: 401, description: 'Missing or invalid bearer token.'),
        ],
    )]
    public function __invoke(): JsonResponse
    {
        return response()->json(null, 204);
    }
}
