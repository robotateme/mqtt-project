<?php

namespace App\Http\Middleware;

use Closure;
use Core\Application\Auth\JwtTokenService;
use Core\Application\Users\UserRepository;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class AuthenticateJwt
{
    public function __construct(
        private JwtTokenService $tokens,
        private UserRepository $users,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if ($token === null) {
            throw new AuthenticationException('Missing bearer token.');
        }

        $payload = $this->tokens->decode($token);

        if ($payload->type !== 'access') {
            throw new AuthenticationException('Invalid access token.');
        }

        $user = $this->users->findById($payload->userId);

        if ($user === null) {
            throw new AuthenticationException('User not found.');
        }

        Auth::setUser($user);
        $request->setUserResolver(static fn () => $user);

        return $next($request);
    }
}
