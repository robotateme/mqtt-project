<?php

declare(strict_types=1);

namespace Core\Application\Users\Handlers;

use App\Models\User;
use Core\Application\Users\Queries\ListUsersQuery;
use Core\Application\Users\UserRepository;
use Illuminate\Pagination\LengthAwarePaginator;

final readonly class ListUsersHandler
{
    /** @psalm-suppress PossiblyUnusedMethod Laravel resolves the handler through the container. */
    public function __construct(private UserRepository $users)
    {
    }

    /**
     * @return LengthAwarePaginator<int, User>
     */
    public function handle(ListUsersQuery $query): LengthAwarePaginator
    {
        return $this->users->paginateForAdmin($query->perPage);
    }
}
