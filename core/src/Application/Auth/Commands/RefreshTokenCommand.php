<?php

namespace Core\Application\Auth\Commands;

final readonly class RefreshTokenCommand
{
    public function __construct(public string $refreshToken)
    {
    }
}
