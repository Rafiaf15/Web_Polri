<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\NotificationController;

// Route untuk login
Route::get('/', [LoginController::class, 'login'])->name('login')->middleware('guest');
Route::post('/login', [LoginController::class, 'getLogin'])->name('login.post');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Route untuk dashboard
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard')->middleware('auth');

// Route untuk jadwal
Route::get('/schedule', [ScheduleController::class, 'index'])->name('schedule.index')->middleware('auth');
Route::get('/schedule/create', [ScheduleController::class, 'create'])->name('schedule.create')->middleware('auth');
Route::post('/schedule', [ScheduleController::class, 'store'])->name('schedule.store')->middleware('auth');
Route::get('/schedule/{id}/edit', [ScheduleController::class, 'edit'])->name('schedule.edit')->middleware('auth');
Route::put('/schedule/{id}', [ScheduleController::class, 'update'])->name('schedule.update')->middleware('auth');
Route::delete('/schedule/{id}', [ScheduleController::class, 'destroy'])->name('schedule.destroy')->middleware('auth');
Route::get('/schedule/piket', [ScheduleController::class, 'piket'])->name('schedule.piket')->middleware('auth');

// Route untuk debugging
Route::get('/debug/conflicts', [ScheduleController::class, 'debugConflicts'])->name('debug.conflicts')->middleware('auth');

// Route untuk notifikasi
Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index')->middleware('auth');
Route::get('/notifications/get', [NotificationController::class, 'getNotifications'])->name('notifications.get')->middleware('auth');
Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read')->middleware('auth');
Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.readAll')->middleware('auth');