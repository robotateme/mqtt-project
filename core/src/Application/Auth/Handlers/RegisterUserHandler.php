<?php

declare(strict_types=1);

namespace Core\Application\Auth\Handlers;

use Core\Application\Auth\Commands\RegisterUserCommand;
use Core\Application\Auth\Data\AuthToken;
use Core\Application\Auth\JwtTokenService;
use Core\Application\Users\UserRepository;
use Core\Domain\Users\UserRole;
use Illuminate\Support\Facades\Hash;

final readonly class RegisterUserHandler
{
    public function __construct(
        private UserRepository $users,
        private JwtTokenService $tokens,
    ) {
    }

    public function handle(RegisterUserCommand $command): AuthToken
    {
        $user = $this->users->create([
            'name' => $command->name,
            'email' => $command->email,
            'password' => Hash::make($command->password),
            'role' => UserRole::User->value,
        ]);

        return $this->tokens->issueFor($user);
    }
}
