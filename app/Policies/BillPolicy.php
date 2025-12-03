<?php

namespace App\Policies;

use App\Models\Bill;
use App\Models\User;

class BillPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'admin']);
    }

    public function view(User $user, Bill $bill): bool
    {
        return $user->role === 'super_admin' || ($user->role === 'admin' && $user->company_id === $bill->company_id);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'admin']);
    }

    public function update(User $user, Bill $bill): bool
    {
        return $user->role === 'super_admin' || ($user->role === 'admin' && $user->company_id === $bill->company_id);
    }

    public function delete(User $user, Bill $bill): bool
    {
        return $user->role === 'super_admin' || ($user->role === 'admin' && $user->company_id === $bill->company_id);
    }
}
