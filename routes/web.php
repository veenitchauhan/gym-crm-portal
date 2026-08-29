<?php

use App\Http\Controllers\Admin\MemberController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PublicHomeController;
use App\Http\Controllers\Settings\DropdownOptionController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\SuperAdmin\AuthController as SuperAdminAuthController;
use App\Http\Controllers\SuperAdmin\ClientImpersonationController;
use App\Http\Controllers\SuperAdmin\GymController;
use Illuminate\Support\Facades\Route;

Route::get('/super-admin', [SuperAdminAuthController::class, 'home'])->name('super-admin.home');
Route::get('/', PublicHomeController::class)->name('home');

Route::middleware('guest:super_admin')->group(function (): void {
    Route::get('/super-admin/login', [SuperAdminAuthController::class, 'create'])->name('super-admin.login');
    Route::post('/super-admin/login', [SuperAdminAuthController::class, 'store'])->middleware('throttle:6,1');
});

Route::middleware('auth:super_admin')->prefix('super-admin')->name('super-admin.')->group(function (): void {
    Route::post('/logout', [SuperAdminAuthController::class, 'destroy'])->name('logout');
    Route::post('/gyms/{gym}/login', [ClientImpersonationController::class, 'store'])->name('gyms.login');
    Route::post('/impersonation/exit', [ClientImpersonationController::class, 'destroy'])->name('impersonation.exit');
    Route::resource('gyms', GymController::class)->only(['index', 'store', 'update', 'destroy']);
});

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:6,1');
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->middleware('throttle:6,1');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/admin/dashboard', [DashboardController::class, 'admin'])
        ->defaults('module', 'overview')
        ->middleware('role:admin')
        ->name('admin.dashboard');

    Route::get('/admin/{module}', [DashboardController::class, 'admin'])
        ->whereIn('module', ['members', 'attendance', 'memberships', 'payments', 'trainers', 'schedule', 'leads'])
        ->middleware('role:admin')
        ->name('admin.module');

    Route::put('/admin/members/{member}', [MemberController::class, 'update'])
        ->middleware('role:admin')
        ->name('admin.members.update');
    Route::delete('/admin/members/{member}', [MemberController::class, 'destroy'])
        ->middleware('role:admin')
        ->name('admin.members.destroy');

    Route::get('/member/dashboard', [DashboardController::class, 'member'])
        ->middleware('role:member')
        ->name('member.dashboard');

    Route::get('/settings/profile', [ProfileController::class, 'edit'])->name('settings.profile.edit');
    Route::patch('/settings/profile', [ProfileController::class, 'update'])->name('settings.profile.update');
    Route::put('/settings/password', [ProfileController::class, 'password'])->name('settings.password.update');
    Route::put('/settings/dropdown-options', [DropdownOptionController::class, 'bulkUpdate'])
        ->middleware('role:admin')
        ->name('dropdown-options.bulk-update');
    Route::resource('/settings/dropdown-options', DropdownOptionController::class)
        ->only(['store', 'update', 'destroy'])
        ->middleware('role:admin');
});
