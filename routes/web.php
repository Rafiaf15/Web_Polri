<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\CutiController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\MemberController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Redirect root to login
Route::get('/', function () {
    return redirect()->route('login');
});

// Login routes
Route::get('/login', [LoginController::class, 'login'])->name('login');
Route::post('/login', [LoginController::class, 'getLogin'])->name('login.post');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Protected routes (require authentication)
Route::middleware(['auth'])->group(function () {
    
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Schedule routes
    Route::get('/schedule/piket', [ScheduleController::class, 'piket'])->name('schedule.piket');
    Route::get('/schedule/import-pdf', [ScheduleController::class, 'showImportPdf'])->name('schedule.import-pdf');
    Route::post('/schedule/import-pdf', [ScheduleController::class, 'importFromPdf'])->name('schedule.import-pdf.post');
    Route::post('/schedule/preview-pdf', [ScheduleController::class, 'previewPdf'])->name('schedule.preview-pdf');
    Route::post('/schedule/debug-regex', [ScheduleController::class, 'debugRegex'])->name('schedule.debug-regex');
    Route::resource('schedule', ScheduleController::class)->except(['show']);

    // Cuti routes
    Route::get('/cuti/dashboard', [CutiController::class, 'dashboard'])->name('cuti.dashboard');
    Route::post('/cuti/{id}/approve', [CutiController::class, 'approve'])->name('cuti.approve');
    Route::post('/cuti/{id}/reject', [CutiController::class, 'reject'])->name('cuti.reject');
    Route::resource('cuti', CutiController::class)->except(['show']);

    // Sisa Cuti per Anggota
    Route::get('/cuti/sisa', [MemberController::class, 'index'])->name('cuti.sisa');

    // Notification routes
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/get', [NotificationController::class, 'getNotifications'])->name('notifications.get');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.readAll');
});