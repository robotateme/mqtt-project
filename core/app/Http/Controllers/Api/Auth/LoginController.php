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

final class LoginController extends Controller
{
    use RespondsWithUser;

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
