<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\CutiController;
use App\Http\Controllers\NotificationController;

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

// Login routes
Route::get('/', [LoginController::class, 'login'])->name('login');
Route::post('/login', [LoginController::class, 'getLogin'])->name('login.post');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Dashboard
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Schedule routes
Route::resource('schedule', ScheduleController::class)->except(['show']);
Route::get('/schedule/piket', [ScheduleController::class, 'piket'])->name('schedule.piket');

// PDF Import routes
Route::get('/schedule/import-pdf', [ScheduleController::class, 'showImportPdf'])->name('schedule.import-pdf');
Route::post('/schedule/import-pdf', [ScheduleController::class, 'importFromPdf'])->name('schedule.import-pdf.post');
Route::post('/schedule/preview-pdf', [ScheduleController::class, 'previewPdf'])->name('schedule.preview-pdf');
Route::post('/schedule/debug-regex', [ScheduleController::class, 'debugRegex'])->name('schedule.debug-regex');

// Cuti routes
Route::resource('cuti', CutiController::class);
Route::get('/cuti/dashboard', [CutiController::class, 'dashboard'])->name('cuti.dashboard');
Route::post('/cuti/{id}/approve', [CutiController::class, 'approve'])->name('cuti.approve');
Route::post('/cuti/{id}/reject', [CutiController::class, 'reject'])->name('cuti.reject');

// Notification routes
Route::get('/notifications/get', [NotificationController::class, 'getNotifications'])->name('notifications.get');
Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.readAll');
Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
