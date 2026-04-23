<?php

declare(strict_types=1);

namespace Core\Application\Users;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface UserRepository
{
    public function findById(int $id): ?User;

    public function findByEmail(string $email): ?User;

    /**
     * @param array{name: string, email: string, password: string, role?: string} $attributes
     */
    public function create(array $attributes): User;

    /**
     * @param array<string, mixed> $attributes
     */
    public function update(User $user, array $attributes): User;

    public function delete(User $user): void;

    /**
     * @return LengthAwarePaginator<int, User>
     */
    public function paginateForAdmin(int $perPage = 50): LengthAwarePaginator;
}
