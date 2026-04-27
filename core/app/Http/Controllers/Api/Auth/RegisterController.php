<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Api\Concerns\RespondsWithUser;
use App\Http\Controllers\Controller;
use Core\Application\Auth\Commands\RegisterUserCommand;
use Core\Application\Auth\Handlers\RegisterUserHandler;
use Core\Application\Users\UserRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

final class RegisterController extends Controller
{
    use RespondsWithUser;

    #[OA\Post(
        path: '/auth/register',
        summary: 'Register user',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'password'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Admin'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'admin@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 8, example: 'password'),
                ],
                type: 'object',
            ),
        ),
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Registered user and JWT token.',
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
    public function __invoke(Request $request, RegisterUserHandler $handler, UserRepository $users): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $token = $handler->handle(new RegisterUserCommand(
            $data['name'],
            $data['email'],
            $data['password'],
        ));

        $user = $users->findByEmail($data['email']);

        return response()->json([
            'user' => $this->userPayload($user),
            'token' => $token->toArray(),
        ], 201);
    }
}
