<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

// Root -> dashboard
Route::get('/', function () { return redirect()->route('dashboard'); });

// Authentication routes assumed provided by Laravel auth starter (login required)
Route::middleware(['web'])->group(function () {
    // Dashboard placeholder
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // Minimal auth
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

// Role-based routes
Route::middleware(['web', 'auth'])->group(function () {
    // Companies CRUD (Super Admin + Admin with scope)
    Route::resource('companies', App\Http\Controllers\CompanyController::class);

    // Users: Admins and Staff managed by Super Admin/Admin
    Route::resource('admins', App\Http\Controllers\AdminUserController::class);
    Route::resource('staff', App\Http\Controllers\StaffUserController::class);

    // Courier Policies
    Route::resource('policies', App\Http\Controllers\CourierPolicyController::class);

    // Bills
    Route::resource('bills', App\Http\Controllers\BillController::class);

    // Password management
    Route::post('/users/{user}/reset-default', [App\Http\Controllers\PasswordController::class, 'resetToDefault'])
        ->name('password.resetToDefault');
    Route::post('/profile/change-password', [App\Http\Controllers\PasswordController::class, 'changePassword'])
        ->name('profile.changePassword');
    Route::get('/profile', [App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');

    // Storage management (Super Admin)
    Route::get('/storage/metrics', [App\Http\Controllers\StorageController::class, 'metrics'])
        ->name('storage.metrics');
    Route::post('/storage/clear', [App\Http\Controllers\StorageController::class, 'clear'])
        ->name('storage.clear');

    // Analytics
    Route::get('/analytics', [App\Http\Controllers\AnalyticsController::class, 'index'])->name('analytics.index');

    // Deleted lists with restore
    Route::get('/deleted/staff', [App\Http\Controllers\StaffUserController::class, 'deleted'])
        ->name('staff.deleted');
    Route::post('/deleted/staff/{id}/restore', [App\Http\Controllers\StaffUserController::class, 'restore'])
        ->name('staff.restore');
    Route::get('/deleted/admins', [App\Http\Controllers\AdminUserController::class, 'deleted'])
        ->name('admins.deleted');
    Route::post('/deleted/admins/{id}/restore', [App\Http\Controllers\AdminUserController::class, 'restore'])
        ->name('admins.restore');
    Route::get('/deleted/companies', [App\Http\Controllers\CompanyController::class, 'deleted'])
        ->name('companies.deleted');
    Route::post('/deleted/companies/{id}/restore', [App\Http\Controllers\CompanyController::class, 'restore'])
        ->name('companies.restore');
    Route::get('/deleted/bills', [App\Http\Controllers\BillController::class, 'deleted'])
        ->name('bills.deleted');
    Route::post('/deleted/bills/{id}/restore', [App\Http\Controllers\BillController::class, 'restore'])
        ->name('bills.restore');
    Route::get('/deleted/policies', [App\Http\Controllers\CourierPolicyController::class, 'deleted'])
        ->name('policies.deleted');
    Route::post('/deleted/policies/{id}/restore', [App\Http\Controllers\CourierPolicyController::class, 'restore'])
        ->name('policies.restore');
});
