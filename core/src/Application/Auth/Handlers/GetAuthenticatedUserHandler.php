<?php

namespace Core\Application\Auth\Handlers;

use App\Models\User;
use Core\Application\Auth\Queries\GetAuthenticatedUserQuery;
use Core\Application\Users\UserRepository;

final class GetAuthenticatedUserHandler
{
    public function __construct(private UserRepository $users)
    {
    }

    public function handle(GetAuthenticatedUserQuery $query): ?User
    {
        return $this->users->findById($query->userId);
    }
}
