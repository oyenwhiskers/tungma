<?php

namespace App\Policies;

use App\Models\CourierPolicy;
use App\Models\User;

class CourierPolicyPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'admin']);
    }

    public function view(User $user, CourierPolicy $policy): bool
    {
        return $user->role === 'super_admin' || ($user->role === 'admin' && $user->company_id === $policy->company_id);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'admin']);
    }

    public function update(User $user, CourierPolicy $policy): bool
    {
        return $user->role === 'super_admin' || ($user->role === 'admin' && $user->company_id === $policy->company_id);
    }

    public function delete(User $user, CourierPolicy $policy): bool
    {
        return $user->role === 'super_admin' || ($user->role === 'admin' && $user->company_id === $policy->company_id);
    }
}
