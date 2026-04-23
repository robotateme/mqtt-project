<?php

declare(strict_types=1);

namespace Core\Application\Auth\Data;

final readonly class JwtPayload
{
    public function __construct(
        public int $userId,
        public string $role,
        public string $type,
        public int $expiresAt,
    ) {
    }
}
