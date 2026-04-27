<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Concerns\RespondsWithUser;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

final class MeController extends Controller
{
    use RespondsWithUser;

    #[OA\Get(
        path: '/admin/me',
        summary: 'Get admin profile',
        security: [['bearerAuth' => []]],
        tags: ['Admin'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Admin user profile.',
                content: new OA\JsonContent(ref: '#/components/schemas/AdminMeResponse'),
            ),
            new OA\Response(response: 401, description: 'Missing or invalid bearer token.'),
            new OA\Response(response: 403, description: 'User is not an administrator.'),
        ],
    )]
    public function __invoke(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $this->userPayload($request->user()),
            'panel' => 'admin',
        ]);
    }
}
