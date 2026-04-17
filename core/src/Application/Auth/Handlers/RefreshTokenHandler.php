<?php

namespace Core\Application\Auth\Handlers;

use Core\Application\Auth\Commands\RefreshTokenCommand;
use Core\Application\Auth\Data\AuthToken;
use Core\Application\Auth\JwtTokenService;

final class RefreshTokenHandler
{
    public function __construct(private JwtTokenService $tokens)
    {
    }

    public function handle(RefreshTokenCommand $command): AuthToken
    {
        return $this->tokens->refresh($command->refreshToken);
    }
}
