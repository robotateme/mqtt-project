<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin\Users;

use App\Http\Controllers\Api\Concerns\RespondsWithUser;
use App\Http\Controllers\Controller;
use Core\Application\Users\Handlers\ListUsersHandler;
use Core\Application\Users\Queries\ListUsersQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

final class IndexController extends Controller
{
    use RespondsWithUser;

    #[OA\Get(
        path: '/admin/users',
        summary: 'List users for admin tables',
        security: [['bearerAuth' => []]],
        tags: ['Admin'],
        parameters: [
            new OA\Parameter(
                name: 'per_page',
                description: 'Items per page.',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', maximum: 100, minimum: 1, example: 50),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Paginated users.',
                content: new OA\JsonContent(ref: '#/components/schemas/UserListResponse'),
            ),
            new OA\Response(response: 401, description: 'Missing or invalid bearer token.'),
            new OA\Response(response: 403, description: 'User is not an administrator.'),
        ],
    )]
    public function __invoke(Request $request, ListUsersHandler $handler): JsonResponse
    {
        $data = $request->validate([
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $users = $handler->handle(new ListUsersQuery((int) ($data['per_page'] ?? 50)));
        $payload = [];

        foreach ($users->items() as $user) {
            $payload[] = $this->userPayload($user);
        }

        return response()->json([
            'data' => $payload,
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }
}
