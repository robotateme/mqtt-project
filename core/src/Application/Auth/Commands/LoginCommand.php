<?php

declare(strict_types=1);

namespace Core\Application\Auth\Commands;

final readonly class LoginCommand
{
    public function __construct(
        public string $email,
        public string $password,
    ) {
    }
}
