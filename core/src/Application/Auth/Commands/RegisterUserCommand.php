<?php

declare(strict_types=1);

namespace Core\Application\Auth\Commands;

final readonly class RegisterUserCommand
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
    ) {
    }
}
