<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

// Root -> dashboard
Route::get('/', function () {
    return redirect()->route('login'); });

// Authentication routes assumed provided by Laravel auth starter (login required)
Route::middleware(['web'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');

    // Minimal auth
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

// Role-based routes
Route::middleware(['web', 'auth', 'role.access'])->group(function () {
    // General routes (accessible by all authenticated users)
    Route::resource('bills', App\Http\Controllers\BillController::class);
    Route::resource('policies', App\Http\Controllers\CourierPolicyController::class);
    Route::resource('bus-departures', App\Http\Controllers\BusDeparturesController::class);
    Route::get('/analytics', [App\Http\Controllers\AnalyticsController::class, 'index'])->name('analytics.index');

    // Companies (Super Admin only for create/edit/delete, all can view assigned)
    Route::get('/companies', [App\Http\Controllers\CompanyController::class, 'index'])->name('companies.index');
    Route::get('/companies/create', [App\Http\Controllers\CompanyController::class, 'create'])->name('companies.create');
    Route::get('/companies/{company}', [App\Http\Controllers\CompanyController::class, 'show'])
        ->whereNumber('company')
        ->name('companies.show');
    Route::get('/deleted/companies', [App\Http\Controllers\CompanyController::class, 'deleted'])->name('companies.deleted');

    // Password management (all users)
    Route::post('/users/{user}/reset-default', [App\Http\Controllers\PasswordController::class, 'resetToDefault'])
        ->name('password.resetToDefault');
    Route::post('/profile/change-password', [App\Http\Controllers\PasswordController::class, 'changePassword'])
        ->name('profile.changePassword');
    Route::get('/profile', [App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');

    // Bill template/receipt
    Route::get('/bills/{bill}/template', [App\Http\Controllers\BillController::class, 'template'])->name('bills.template');

    // Deleted lists with restore
    Route::get('/deleted/bills', [App\Http\Controllers\BillController::class, 'deleted'])->name('bills.deleted');
    Route::post('/deleted/bills/{id}/restore', [App\Http\Controllers\BillController::class, 'restore'])->name('bills.restore');
    Route::get('/deleted/policies', [App\Http\Controllers\CourierPolicyController::class, 'deleted'])->name('policies.deleted');
    Route::post('/deleted/policies/{id}/restore', [App\Http\Controllers\CourierPolicyController::class, 'restore'])->name('policies.restore');

    // Staff management (Super Admin & Admin for their company)
    Route::resource('staff', App\Http\Controllers\StaffUserController::class);
    Route::get('/deleted/staff', [App\Http\Controllers\StaffUserController::class, 'deleted'])->name('staff.deleted');
    Route::post('/deleted/staff/{id}/restore', [App\Http\Controllers\StaffUserController::class, 'restore'])->name('staff.restore');

    // Checklists (Super Admin & Admin can view)
    Route::get('/checklists', [App\Http\Controllers\ChecklistController::class, 'index'])->name('checklists.index');
    Route::get('/checklists/{bus_datetime}', [App\Http\Controllers\ChecklistController::class, 'show'])->name('checklists.show');
    Route::post('/checklists/save', [App\Http\Controllers\ChecklistController::class, 'save'])->name('checklists.save');
});

// Super Admin only routes
Route::middleware(['web', 'auth', 'super.admin'])->group(function () {
    // Companies management (Super Admin only)
    Route::post('/companies', [App\Http\Controllers\CompanyController::class, 'store'])->name('companies.store');
    Route::get('/companies/create', [App\Http\Controllers\CompanyController::class, 'create'])->name('companies.create');
    Route::get('/companies/{company}/edit', [App\Http\Controllers\CompanyController::class, 'edit'])
        ->whereNumber('company')
        ->name('companies.edit');
    Route::put('/companies/{company}', [App\Http\Controllers\CompanyController::class, 'update'])
        ->whereNumber('company')
        ->name('companies.update');
    Route::delete('/companies/{company}', [App\Http\Controllers\CompanyController::class, 'destroy'])
        ->whereNumber('company')
        ->name('companies.destroy');
    Route::post('/deleted/companies/{id}/restore', [App\Http\Controllers\CompanyController::class, 'restore'])
        ->whereNumber('id')
        ->name('companies.restore');

    // Admins management (Super Admin only)
    Route::resource('admins', App\Http\Controllers\AdminUserController::class);
    Route::get('/deleted/admins', [App\Http\Controllers\AdminUserController::class, 'deleted'])->name('admins.deleted');
    Route::post('/deleted/admins/{id}/restore', [App\Http\Controllers\AdminUserController::class, 'restore'])->name('admins.restore');

    // Storage management (Super Admin)
    Route::get('/storage/metrics', [App\Http\Controllers\StorageController::class, 'metrics'])->name('storage.metrics');
    Route::post('/storage/clear', [App\Http\Controllers\StorageController::class, 'clear'])->name('storage.clear');

    // Backup & Restore Management (Super Admin)
    Route::get('/backup', [App\Http\Controllers\BackupController::class, 'index'])->name('backup.index');
    Route::post('/backup/export-all', [App\Http\Controllers\BackupController::class, 'exportAll'])->name('backup.export.all');
    Route::post('/backup/import-all', [App\Http\Controllers\BackupController::class, 'importAll'])->name('backup.import.all');
    Route::post('/backup/export-data', [App\Http\Controllers\BackupController::class, 'exportData'])->name('backup.export.data');
    Route::post('/backup/export-media', [App\Http\Controllers\BackupController::class, 'exportMedia'])->name('backup.export.media');
    Route::post('/backup/import-data', [App\Http\Controllers\BackupController::class, 'importData'])->name('backup.import.data');
    Route::post('/backup/import-media', [App\Http\Controllers\BackupController::class, 'importMedia'])->name('backup.import.media');
    Route::delete('/backup/delete', [App\Http\Controllers\BackupController::class, 'deleteBackup'])->name('backup.delete');
    Route::post('/backup/clear-storage', [App\Http\Controllers\BackupController::class, 'clearStorage'])->name('backup.clear.storage');
});
