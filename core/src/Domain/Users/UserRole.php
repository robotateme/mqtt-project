<?php

namespace Core\Domain\Users;

enum UserRole: string
{
    case User = 'user';
    case Admin = 'admin';
}
