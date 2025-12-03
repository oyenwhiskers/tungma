<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;
use App\Models\Company;
use App\Models\Bill;
use App\Models\CourierPolicy;
use App\Policies\CompanyPolicy;
use App\Policies\BillPolicy;
use App\Policies\CourierPolicyPolicy;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register policies (if AuthServiceProvider not present)
        Gate::policy(Company::class, CompanyPolicy::class);
        Gate::policy(Bill::class, BillPolicy::class);
        Gate::policy(CourierPolicy::class, CourierPolicyPolicy::class);

        // Simple RBAC gates
        Gate::define('super-admin', function (User $user) {
            return $user->role === 'super_admin';
        });
        Gate::define('admin', function (User $user) {
            return $user->role === 'admin' || $user->role === 'super_admin';
        });
        Gate::define('staff', function (User $user) {
            return $user->role === 'staff' || $user->role === 'admin' || $user->role === 'super_admin';
        });
    }
}
