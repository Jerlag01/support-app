<?php

namespace App\Policies;

use App\Models\User;

/**
 * Staff manage customer accounts; only admins manage staff accounts and roles.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, User $model): bool
    {
        return $user->isStaff();
    }

    public function create(User $user): bool
    {
        return $user->isStaff();
    }

    public function update(User $user, User $model): bool
    {
        return $user->isAdmin() || ($user->isStaff() && ! $model->isStaff());
    }

    public function delete(User $user, User $model): bool
    {
        return $user->isAdmin() && $user->isNot($model);
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function changeRole(User $user): bool
    {
        return $user->isAdmin();
    }
}
