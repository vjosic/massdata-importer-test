<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\ImportController;
use App\Http\Controllers\Admin\ImportedDataController;
use App\Http\Controllers\Admin\ImportsController;

// Redirect root to login
Route::get('/', function () {
    return redirect('/login');
});

// Authentication routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Protected routes
Route::middleware('auth')->group(function () {
    Route::get('/admin', function () {
        return view('admin.dashboard');
    })->name('admin.dashboard');
    
    // User Management routes
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::resource('users', UserController::class);
        Route::resource('permissions', PermissionController::class);
        Route::get('permissions-assign', [PermissionController::class, 'assign'])->name('permissions.assign');
        Route::post('permissions-assign', [PermissionController::class, 'storeAssignment'])->name('permissions.store-assignment');
        
        // Data Import routes
        Route::get('import', [ImportController::class, 'index'])->name('import.index');
        Route::get('import/config', [ImportController::class, 'getConfig'])->name('import.config');
        Route::get('import/headers', [ImportController::class, 'getRequiredHeaders'])->name('import.headers');
        Route::post('import/upload', [ImportController::class, 'upload'])->name('import.upload');
        
        // Imported Data routes
        Route::get('data', [ImportedDataController::class, 'index'])->name('data.index');
        Route::get('data/{dataset}', [ImportedDataController::class, 'dataset'])->name('data.dataset');
        Route::get('data/{dataset}/export', [ImportedDataController::class, 'export'])->name('data.export');
        Route::delete('data/{dataset}/{id}', [ImportedDataController::class, 'delete'])->name('data.delete');
        Route::get('data/{dataset}/{id}/audits', [ImportedDataController::class, 'audits'])->name('data.audits');
        
        // Imports Management routes
        Route::get('imports', [ImportsController::class, 'index'])->name('imports.index');
        Route::get('imports/{import}', [ImportsController::class, 'show'])->name('imports.show');
        Route::get('imports/{import}/logs', [ImportsController::class, 'logs'])->name('imports.logs');
        Route::post('imports/{import}/retry', [ImportsController::class, 'retry'])->name('imports.retry');
    });
});
