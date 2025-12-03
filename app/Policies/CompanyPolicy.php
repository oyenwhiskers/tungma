<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;

class CompanyPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'admin']);
    }

    public function view(User $user, Company $company): bool
    {
        return $user->role === 'super_admin' || ($user->role === 'admin' && $user->company_id === $company->id);
    }

    public function create(User $user): bool
    {
        return $user->role === 'super_admin' || $user->role === 'admin';
    }

    public function update(User $user, Company $company): bool
    {
        return $user->role === 'super_admin' || ($user->role === 'admin' && $user->company_id === $company->id);
    }

    public function delete(User $user, Company $company): bool
    {
        return $user->role === 'super_admin';
    }
}
