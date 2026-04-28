<?php

declare(strict_types=1);

namespace Core\Application\Users\Queries;

final readonly class ListUsersQuery
{
    public function __construct(public int $perPage = 50)
    {
    }
}
