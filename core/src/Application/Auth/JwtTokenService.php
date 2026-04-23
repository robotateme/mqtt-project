<?php

declare(strict_types=1);

namespace Core\Application\Auth;

use App\Models\User;
use Core\Application\Auth\Data\AuthToken;
use Core\Application\Auth\Data\JwtPayload;

interface JwtTokenService
{
    public function issueFor(User $user): AuthToken;

    public function refresh(string $refreshToken): AuthToken;

    public function decode(string $token): JwtPayload;
}
