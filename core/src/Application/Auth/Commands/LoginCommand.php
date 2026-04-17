<?php

namespace Core\Application\Auth\Commands;

final readonly class LoginCommand
{
    public function __construct(
        public string $email,
        public string $password,
    ) {
    }
}
