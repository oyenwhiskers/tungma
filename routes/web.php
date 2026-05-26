<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AutoCountController;

// Root -> dashboard
Route::get('/', function () {
    return redirect()->route('login');
});

// Queue Worker Trigger (Hidden/Internal)
// This allows the application to trigger the queue worker via HTTP request
// bypassing the Cron Job delay on cPanel.
Route::get('/queue-worker', function () {
    // Basic security: Check if request comes from local server
    // Note: In some cPanel setups, remote_addr might be different, 
    // but typically 127.0.0.1 or server IP. 
    // For now we leave it open but hidden as it only runs the queue.
    
    Artisan::call('queue:work', ['--stop-when-empty' => true]);
    return 'Worker Run';
});

// Artisan Runner (For cPanel Deployment)
// Usage: /artisan-run/migrate or /artisan-run/optimize:clear
Route::get('/artisan-run/{command}', function ($command) {
    try {
        // Explode command by space if there are arguments
        $args = explode(' ', urldecode($command));
        $cmd = array_shift($args);
        
        $params = [];
        foreach ($args as $arg) {
            if (str_starts_with($arg, '--')) {
                $parts = explode('=', $arg);
                $params[$parts[0]] = $parts[1] ?? true;
            } else {
                $params[] = $arg;
            }
        }
        
        // Ensure to run migrations securely without interactive prompts via --force
        if ($cmd === 'migrate') {
            $params['--force'] = true;
        }

        \Illuminate\Support\Facades\Artisan::call($cmd, $params);
        $output = \Illuminate\Support\Facades\Artisan::output();
        return "<pre>Command: $command executed successfully.\nOutput:\n$output</pre>";
    } catch (\Exception $e) {
        return "<pre>Error executing $command: \n" . $e->getMessage() . "</pre>";
    }
});

// Authentication routes assumed provided by Laravel auth starter (login required)
Route::middleware(['web'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])->middleware('auth')->name('dashboard');

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
    Route::get('/bills/{bill}/view-template', [App\Http\Controllers\BillController::class, 'viewTemplate'])->name('bills.view-template');
    
    // Bulk Actions
    Route::post('/bills/bulk-action', [App\Http\Controllers\BillController::class, 'bulkAction'])->name('bills.bulk-action');

    // E-Invoice Requests
    Route::get('/e-invoice-requests', [App\Http\Controllers\EInvoiceController::class, 'index'])->name('e-invoice.index');
    Route::post('/e-invoice-requests/{id}/toggle-status', [App\Http\Controllers\EInvoiceController::class, 'toggleStatus'])->name('e-invoice.toggle-status');
    Route::get('/e-invoice-requests/export-preview', [App\Http\Controllers\EInvoiceController::class, 'exportPreview'])->name('e-invoice.export-preview');
    Route::post('/e-invoice-requests/export-tax-entity', [App\Http\Controllers\EInvoiceController::class, 'exportTaxEntity'])->name('e-invoice.export-tax-entity');

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
    Route::get('/checklists/{bus_departures_id}', [App\Http\Controllers\ChecklistController::class, 'show'])->name('checklists.show');
    Route::post('/checklists/save', [App\Http\Controllers\ChecklistController::class, 'save'])->name('checklists.save');

    // Activity Logs (Super Admin & Admin - company-scoped for Admin)
    Route::get('/activity-logs', [App\Http\Controllers\ActivityLogController::class, 'index'])->name('activity-logs.index');

    // Customers management (Admin & Super Admin)
    Route::resource('customers', App\Http\Controllers\CustomerController::class);
    Route::post('/customers/generate-code', [App\Http\Controllers\CustomerController::class, 'generateCode'])->name('customers.generateCode');
    Route::get('/deleted/customers', [App\Http\Controllers\CustomerController::class, 'deleted'])->name('customers.deleted');
    Route::post('/deleted/customers/{id}/restore', [App\Http\Controllers\CustomerController::class, 'restore'])->name('customers.restore');

    // Receivers management (Admin & Super Admin)
    Route::resource('receivers', App\Http\Controllers\ReceiverController::class);
    Route::get('/deleted/receivers', [App\Http\Controllers\ReceiverController::class, 'deleted'])->name('receivers.deleted');
    Route::post('/deleted/receivers/{id}/restore', [App\Http\Controllers\ReceiverController::class, 'restore'])->name('receivers.restore');

    // AutoCount Export
    Route::get('/export-autocount', [AutoCountController::class, 'export'])->name('bills.export-autocount');
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

    // Force change password (Super Admin only)
    Route::post('/users/{user}/force-change-password', [App\Http\Controllers\PasswordController::class, 'forceChangePassword'])
        ->name('password.forceChange');

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
    Route::get('/backup/delete-bills', [App\Http\Controllers\BackupController::class, 'deleteBills'])->name('backup.delete.bills');
    Route::post('/backup/delete-selected-bills', [App\Http\Controllers\BackupController::class, 'deleteSelectedBills'])->name('backup.delete.selected.bills');
});
