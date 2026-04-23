<?php

declare(strict_types=1);

namespace Core\Domain\Users;

enum UserRole: string
{
    case User = 'user';
    case Admin = 'admin';
}
