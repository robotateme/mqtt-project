<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Api\Concerns\RespondsWithUser;
use App\Http\Controllers\Controller;
use Core\Application\Auth\Commands\LoginCommand;
use Core\Application\Auth\Handlers\LoginHandler;
use Core\Application\Users\UserRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

final class LoginController extends Controller
{
    use RespondsWithUser;

    #[OA\Post(
        path: '/auth/login',
        summary: 'Authenticate user',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'admin@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password'),
                ],
                type: 'object',
            ),
        ),
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Authenticated user and JWT token.',
                content: new OA\JsonContent(ref: '#/components/schemas/AuthResponse'),
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error.',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationError'),
            ),
        ],
        security: [],
    )]
    public function __invoke(Request $request, LoginHandler $handler, UserRepository $users): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $token = $handler->handle(new LoginCommand($data['email'], $data['password']));
        $user = $users->findByEmail($data['email']);

        return response()->json([
            'user' => $this->userPayload($user),
            'token' => $token->toArray(),
        ]);
    }
}
