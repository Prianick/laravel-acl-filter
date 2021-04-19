<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\UserAclSetting;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserAclSettingPolicy
{
    use HandlesAuthorization;

    public function before(User $user, $ability): bool
    {
        return $user->hasRole(User::ROLE_SUPERADMIN);
    }

    public function view(User $user, UserAclSetting $userAclSetting): void
    {
    }

    public function create(User $user): void
    {
    }

    public function update(User $user, UserAclSetting $userAclSetting): void
    {
    }

    public function delete(User $user, UserAclSetting $userAclSetting): void
    {
    }

    public function restore(User $user, UserAclSetting $userAclSetting): void
    {
    }

    public function forceDelete(User $user, UserAclSetting $userAclSetting): void
    {
    }
}
