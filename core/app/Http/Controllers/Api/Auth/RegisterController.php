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

final class RegisterController extends Controller
{
    use RespondsWithUser;

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
