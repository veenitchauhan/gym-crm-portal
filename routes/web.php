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
use App\Http\Controllers\Admin\RoleManagementController;
use App\Http\Controllers\Admin\StaffImpersonationController;
use App\Http\Controllers\Admin\StaffTemporaryPasswordController;
use App\Http\Controllers\Admin\TrainerController;
use App\Http\Controllers\Admin\UserManagementController;
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

Route::post('/admin/staff-impersonation/exit', [StaffImpersonationController::class, 'destroy'])
    ->middleware('auth')
    ->name('admin.staff-impersonation.destroy');

Route::middleware(['auth', 'password_changed', 'active_gym', 'gym_resource'])->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::put('/admin/active-gym/{gym}', [ActiveGymController::class, 'update'])
        ->middleware('role:admin')
        ->name('admin.active-gym.update');

    Route::get('/admin/dashboard', [DashboardController::class, 'admin'])
        ->defaults('module', 'overview')
        ->middleware(['role:admin', 'permission:overview,view'])
        ->name('admin.dashboard');

    Route::get('/admin/members/{member}', [MemberProfileController::class, 'show'])
        ->middleware(['role:admin', 'permission:members,view'])
        ->name('admin.members.show');

    Route::get('/admin/{module}', [DashboardController::class, 'admin'])
        ->whereIn('module', ['members', 'payments', 'trainers', 'schedule', 'leads'])
        ->middleware(['role:admin', 'permission:route,view'])
        ->name('admin.module');

    Route::post('/admin/members', [MemberController::class, 'store'])
        ->middleware(['role:admin', 'permission:members,create'])
        ->name('admin.members.store');
    Route::put('/admin/members/{member}', [MemberController::class, 'update'])
        ->middleware(['role:admin', 'permission:members,edit'])
        ->name('admin.members.update');
    Route::delete('/admin/members/{member}', [MemberController::class, 'destroy'])
        ->middleware(['role:admin', 'permission:members,delete'])
        ->name('admin.members.destroy');
    Route::post('/admin/membership-plans', [MembershipPlanController::class, 'store'])->middleware(['role:admin', 'permission:settings,create'])->name('membership-plans.store');
    Route::put('/admin/membership-plans/{membership_plan}', [MembershipPlanController::class, 'update'])->middleware(['role:admin', 'permission:settings,edit'])->name('membership-plans.update');
    Route::delete('/admin/membership-plans/{membership_plan}', [MembershipPlanController::class, 'destroy'])->middleware(['role:admin', 'permission:settings,delete'])->name('membership-plans.destroy');
    Route::post('/admin/payments', [PaymentController::class, 'store'])->middleware(['role:admin', 'permission:payments,create'])->name('payments.store');
    Route::put('/admin/payments/{payment}', [PaymentController::class, 'update'])->middleware(['role:admin', 'permission:payments,edit'])->name('payments.update');
    Route::delete('/admin/payments/{payment}', [PaymentController::class, 'destroy'])->middleware(['role:admin', 'permission:payments,delete'])->name('payments.destroy');
    Route::post('/admin/attendances', [AttendanceController::class, 'store'])->middleware(['role:admin', 'permission:members,create'])->name('attendances.store');
    Route::put('/admin/attendances/{attendance}', [AttendanceController::class, 'update'])->middleware(['role:admin', 'permission:members,edit'])->name('attendances.update');
    Route::delete('/admin/attendances/{attendance}', [AttendanceController::class, 'destroy'])->middleware(['role:admin', 'permission:members,delete'])->name('attendances.destroy');
    Route::post('/admin/trainers', [TrainerController::class, 'store'])->middleware(['role:admin', 'permission:trainers,create'])->name('trainers.store');
    Route::put('/admin/trainers/{trainer}', [TrainerController::class, 'update'])->middleware(['role:admin', 'permission:trainers,edit'])->name('trainers.update');
    Route::delete('/admin/trainers/{trainer}', [TrainerController::class, 'destroy'])->middleware(['role:admin', 'permission:trainers,delete'])->name('trainers.destroy');
    Route::post('/admin/gym-sessions', [GymSessionController::class, 'store'])->middleware(['role:admin', 'permission:schedule,create'])->name('gym-sessions.store');
    Route::put('/admin/gym-sessions/{gym_session}', [GymSessionController::class, 'update'])->middleware(['role:admin', 'permission:schedule,edit'])->name('gym-sessions.update');
    Route::delete('/admin/gym-sessions/{gym_session}', [GymSessionController::class, 'destroy'])->middleware(['role:admin', 'permission:schedule,delete'])->name('gym-sessions.destroy');
    Route::post('/admin/bookings', [BookingController::class, 'store'])->middleware(['role:admin', 'permission:schedule,create'])->name('bookings.store');
    Route::delete('/admin/bookings/{booking}', [BookingController::class, 'destroy'])->middleware(['role:admin', 'permission:schedule,delete'])->name('bookings.destroy');
    Route::post('/admin/leads/{lead}/convert', [LeadController::class, 'convert'])
        ->middleware(['role:admin', 'permission:leads,create'])
        ->name('admin.leads.convert');
    Route::post('/admin/leads', [LeadController::class, 'store'])->middleware(['role:admin', 'permission:leads,create'])->name('leads.store');
    Route::put('/admin/leads/{lead}', [LeadController::class, 'update'])->middleware(['role:admin', 'permission:leads,edit'])->name('leads.update');
    Route::delete('/admin/leads/{lead}', [LeadController::class, 'destroy'])->middleware(['role:admin', 'permission:leads,delete'])->name('leads.destroy');

    Route::resource('/admin/users', UserManagementController::class)->only(['index', 'store', 'update', 'destroy'])
        ->middleware('role:admin')
        ->middlewareFor('index', 'permission:users,view')
        ->middlewareFor('store', 'permission:users,create')
        ->middlewareFor('update', 'permission:users,edit')
        ->middlewareFor('destroy', 'permission:users,delete');
    Route::put('/admin/users/{user}/temporary-password', StaffTemporaryPasswordController::class)
        ->middleware(['role:admin', 'permission:users,edit', 'throttle:6,1'])
        ->name('admin.users.temporary-password.update');
    Route::post('/admin/users/{user}/login', [StaffImpersonationController::class, 'store'])
        ->middleware(['role:admin', 'permission:users,view'])
        ->name('admin.users.login');
    Route::resource('/admin/roles', RoleManagementController::class)->only(['index', 'store', 'update', 'destroy'])
        ->middleware('role:admin')
        ->middlewareFor('index', 'permission:roles,view')
        ->middlewareFor('store', 'permission:roles,create')
        ->middlewareFor('update', 'permission:roles,edit')
        ->middlewareFor('destroy', 'permission:roles,delete');

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
        ->middleware(['role:admin', 'permission:settings,edit'])
        ->name('dropdown-options.bulk-update');
    Route::resource('/settings/dropdown-options', DropdownOptionController::class)
        ->only(['store', 'update', 'destroy'])
        ->middleware('role:admin')
        ->middlewareFor('store', 'permission:settings,create')
        ->middlewareFor('update', 'permission:settings,edit')
        ->middlewareFor('destroy', 'permission:settings,delete');
});
