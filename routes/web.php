<?php

use App\Http\Controllers\Admin\ActiveGymController;
use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\GymSessionController;
use App\Http\Controllers\Admin\LeadController;
use App\Http\Controllers\Admin\MemberController;
use App\Http\Controllers\Admin\MemberProfileController;
use App\Http\Controllers\Admin\MembershipPlanController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\TrainerController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Member\BookingController as MemberBookingController;
use App\Http\Controllers\PublicHomeController;
use App\Http\Controllers\Settings\DropdownOptionController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\SuperAdmin\AdministratorBranchController;
use App\Http\Controllers\SuperAdmin\AdministratorController;
use App\Http\Controllers\SuperAdmin\AdministratorPasswordResetLinkController;
use App\Http\Controllers\SuperAdmin\AdministratorTemporaryPasswordController;
use App\Http\Controllers\SuperAdmin\AuthController as SuperAdminAuthController;
use App\Http\Controllers\SuperAdmin\ClientImpersonationController;
use App\Http\Controllers\SuperAdmin\GymBranchController;
use App\Http\Controllers\SuperAdmin\GymBranchStatusController;
use App\Http\Controllers\SuperAdmin\GymController;
use App\Http\Controllers\SuperAdmin\GymStatusController;
use App\Http\Controllers\SuperAdmin\OrganizationController;
use App\Http\Controllers\SuperAdmin\OrganizationMemberController;
use App\Http\Controllers\SuperAdmin\OrganizationMultiBranchController;
use App\Http\Controllers\SuperAdmin\PlatformMemberController;
use Illuminate\Support\Facades\Route;

Route::get('/super-admin', [SuperAdminAuthController::class, 'home'])->name('super-admin.home');
Route::get('/', PublicHomeController::class)->name('home');

Route::get('/super-admin/login', [SuperAdminAuthController::class, 'create'])->name('super-admin.login');
Route::post('/super-admin/login', [SuperAdminAuthController::class, 'store'])->middleware('throttle:6,1');

Route::middleware('super_admin')->prefix('super-admin')->name('super-admin.')->group(function (): void {
    Route::post('/logout', [SuperAdminAuthController::class, 'destroy'])->name('logout');
    Route::get('/members', [PlatformMemberController::class, 'index'])->name('members.index');
    Route::post('/gyms/{gym}/login', [ClientImpersonationController::class, 'store'])->name('gyms.login');
    Route::patch('/gyms/{gym}/status', [GymStatusController::class, 'update'])->name('gyms.status.update');
    Route::patch('/organizations/{organization}/multi-branch', OrganizationMultiBranchController::class)->name('organizations.multi-branch.update');
    Route::get('/organizations/{organization}', [OrganizationController::class, 'show'])->name('organizations.show');
    Route::get('/organizations/{organization}/members', [OrganizationMemberController::class, 'index'])->name('organizations.members.index');
    Route::get('/organizations/{organization}/branches/{branch}', [GymBranchController::class, 'show'])->name('organizations.branches.show');
    Route::post('/organizations/{organization}/branches', [GymBranchController::class, 'store'])->name('organizations.branches.store');
    Route::put('/organizations/{organization}/branches/{branch}', [GymBranchController::class, 'update'])->name('organizations.branches.update');
    Route::patch('/organizations/{organization}/branches/{branch}/status', GymBranchStatusController::class)->name('organizations.branches.status.update');
    Route::put('/organizations/{organization}/administrators/{administrator}', [AdministratorController::class, 'update'])->name('organizations.administrators.update');
    Route::post('/organizations/{organization}/administrators/{administrator}/password-reset-link', AdministratorPasswordResetLinkController::class)
        ->middleware('throttle:6,1')
        ->name('organizations.administrators.password-reset-link.store');
    Route::put('/organizations/{organization}/administrators/{administrator}/temporary-password', AdministratorTemporaryPasswordController::class)
        ->middleware('throttle:6,1')
        ->name('organizations.administrators.temporary-password.update');
    Route::put('/organizations/{organization}/administrators/{administrator}/branches', [AdministratorBranchController::class, 'update'])->name('organizations.administrators.branches.update');
    Route::post('/impersonation/exit', [ClientImpersonationController::class, 'destroy'])->name('impersonation.exit');
    Route::resource('gyms', GymController::class)->only(['index', 'store', 'update']);
});

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:6,1');
    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->middleware('throttle:6,1')->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->middleware('throttle:6,1')->name('password.update');
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->middleware('throttle:6,1');
});

Route::middleware(['auth', 'password_changed', 'active_gym', 'gym_resource'])->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::put('/admin/active-gym/{gym}', [ActiveGymController::class, 'update'])
        ->middleware('role:admin')
        ->name('admin.active-gym.update');

    Route::get('/admin/dashboard', [DashboardController::class, 'admin'])
        ->defaults('module', 'overview')
        ->middleware('role:admin')
        ->name('admin.dashboard');

    Route::get('/admin/members/{member}', [MemberProfileController::class, 'show'])
        ->middleware('role:admin')
        ->name('admin.members.show');

    Route::get('/admin/{module}', [DashboardController::class, 'admin'])
        ->whereIn('module', ['members', 'payments', 'trainers', 'schedule', 'leads'])
        ->middleware('role:admin')
        ->name('admin.module');

    Route::post('/admin/members', [MemberController::class, 'store'])
        ->middleware('role:admin')
        ->name('admin.members.store');
    Route::put('/admin/members/{member}', [MemberController::class, 'update'])
        ->middleware('role:admin')
        ->name('admin.members.update');
    Route::delete('/admin/members/{member}', [MemberController::class, 'destroy'])
        ->middleware('role:admin')
        ->name('admin.members.destroy');
    Route::resource('/admin/membership-plans', MembershipPlanController::class)
        ->only(['store', 'update', 'destroy'])
        ->middleware('role:admin');
    Route::resource('/admin/payments', PaymentController::class)
        ->only(['store', 'update', 'destroy'])
        ->middleware('role:admin');
    Route::resource('/admin/attendances', AttendanceController::class)
        ->only(['store', 'update', 'destroy'])
        ->middleware('role:admin');
    Route::resource('/admin/trainers', TrainerController::class)
        ->only(['store', 'update', 'destroy'])
        ->middleware('role:admin');
    Route::resource('/admin/gym-sessions', GymSessionController::class)
        ->only(['store', 'update', 'destroy'])
        ->middleware('role:admin');
    Route::resource('/admin/bookings', BookingController::class)
        ->only(['store', 'destroy'])
        ->middleware('role:admin');
    Route::post('/admin/leads/{lead}/convert', [LeadController::class, 'convert'])
        ->middleware('role:admin')
        ->name('admin.leads.convert');
    Route::resource('/admin/leads', LeadController::class)
        ->only(['store', 'update', 'destroy'])
        ->middleware('role:admin');

    Route::get('/member/dashboard', [DashboardController::class, 'member'])
        ->middleware('role:member')
        ->name('member.dashboard');
    Route::post('/member/sessions/{gymSession}/book', [MemberBookingController::class, 'store'])
        ->middleware('role:member')
        ->name('member.sessions.book');
    Route::delete('/member/bookings/{booking}', [MemberBookingController::class, 'destroy'])
        ->middleware('role:member')
        ->name('member.bookings.destroy');

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
