<?php

declare(strict_types=1);

namespace Core\Infrastructure\PostgreSql;

use App\Models\User;
use Core\Application\Users\UserRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentUserRepository implements UserRepository
{
    public function findById(int $id): ?User
    {
        return User::query()->find($id);
    }

    public function findByEmail(string $email): ?User
    {
        return User::query()
            ->where('email', mb_strtolower($email))
            ->first();
    }

    public function create(array $attributes): User
    {
        $attributes['email'] = mb_strtolower($attributes['email']);

        return User::query()->create($attributes);
    }

    public function update(User $user, array $attributes): User
    {
        if (isset($attributes['email'])) {
            $attributes['email'] = mb_strtolower((string) $attributes['email']);
        }

        $user->fill($attributes);
        $user->save();

        return $user->refresh();
    }

    public function delete(User $user): void
    {
        $user->delete();
    }

    public function paginateForAdmin(int $perPage = 50): LengthAwarePaginator
    {
        return User::query()
            ->latest('id')
            ->paginate($perPage);
    }
}
