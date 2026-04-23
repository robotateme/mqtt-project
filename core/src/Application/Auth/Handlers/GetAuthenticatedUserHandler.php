<?php

declare(strict_types=1);

namespace Core\Application\Auth\Handlers;

use App\Models\User;
use Core\Application\Auth\Queries\GetAuthenticatedUserQuery;
use Core\Application\Users\UserRepository;

final readonly class GetAuthenticatedUserHandler
{
    public function __construct(private UserRepository $users)
    {
    }

    public function handle(GetAuthenticatedUserQuery $query): ?User
    {
        return $this->users->findById($query->userId);
    }
}
