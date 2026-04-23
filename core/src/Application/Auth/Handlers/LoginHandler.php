<?php

declare(strict_types=1);

namespace Core\Application\Auth\Handlers;

use Core\Application\Auth\Commands\LoginCommand;
use Core\Application\Auth\Data\AuthToken;
use Core\Application\Auth\JwtTokenService;
use Core\Application\Users\UserRepository;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Hash;

final readonly class LoginHandler
{
    public function __construct(
        private UserRepository $users,
        private JwtTokenService $tokens,
    ) {
    }

    public function handle(LoginCommand $command): AuthToken
    {
        $user = $this->users->findByEmail($command->email);

        if ($user === null || !Hash::check($command->password, $user->password)) {
            throw new AuthenticationException('Invalid credentials.');
        }

        return $this->tokens->issueFor($user);
    }
}
