<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Api\Concerns\RespondsWithUser;
use App\Http\Controllers\Controller;
use Core\Application\Auth\Handlers\GetAuthenticatedUserHandler;
use Core\Application\Auth\Queries\GetAuthenticatedUserQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

final class MeController extends Controller
{
    use RespondsWithUser;

    #[OA\Get(
        path: '/auth/me',
        summary: 'Get authenticated user',
        security: [['bearerAuth' => []]],
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Authenticated user.',
                content: new OA\JsonContent(ref: '#/components/schemas/MeResponse'),
            ),
            new OA\Response(response: 401, description: 'Missing or invalid bearer token.'),
        ],
    )]
    public function __invoke(Request $request, GetAuthenticatedUserHandler $handler): JsonResponse
    {
        $user = $handler->handle(new GetAuthenticatedUserQuery((int) $request->user()->getKey()));

        return response()->json([
            'user' => $this->userPayload($user),
        ]);
    }
}
