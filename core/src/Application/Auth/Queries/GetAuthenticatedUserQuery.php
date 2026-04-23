<?php

declare(strict_types=1);

namespace Core\Application\Auth\Queries;

final readonly class GetAuthenticatedUserQuery
{
    public function __construct(public int $userId)
    {
    }
}
