<?php

use App\Http\Controllers\AccessController;
use App\Http\Controllers\CoordinatorController;
use App\Http\Controllers\LegacyController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PanelController;
use Illuminate\Support\Facades\Route;

Route::pattern('id', '[0-9]+');
Route::pattern('student', '[0-9]+');

Route::middleware('guest')->group(function () {
    Route::view('/login', 'login')->name('login');
    Route::view('/acceso-administrativo', 'login', ['administrative' => true])->name('login.administrativo');
    Route::post('/login', [AccessController::class, 'login'])->name('login.submit');
});
Route::match(['get', 'post'], '/', [LegacyController::class, 'landing'])->name('home');
Route::match(['get', 'post'], '/index.php', [LegacyController::class, 'dispatch']);
Route::match(['get', 'post'], '/{area}/web/index.php', [LegacyController::class, 'dispatch'])->where('area', 'backend|frontend');
Route::middleware(['auth', 'role:1,2,3'])->group(function () {
    Route::get('/inicio', [PanelController::class, 'index'])->name('inicio');
    Route::post('/logout', [AccessController::class, 'logout'])->name('logout');
});
require __DIR__.'/domain.php';
Route::middleware(['auth', 'role:1,2'])->group(function () {
    Route::get('/panel', [PanelController::class, 'dashboard'])->name('panel');
    Route::get('/notificaciones', [NotificationController::class, 'index'])->name('notificaciones');
    Route::post('/notificaciones/alta', [NotificationController::class, 'approve'])->name('notificaciones.alta');
});
Route::middleware(['auth', 'role:1'])->group(function () {
    Route::post('/coordinadores/designar', [CoordinatorController::class, 'designate'])->name('coordinadores.designar');
    Route::get('/coordinadores', [AccessController::class, 'coordinadores'])->name('coordinadores');
    Route::post('/coordinadores', [AccessController::class, 'crearCoordinador'])->name('coordinadores.create');
});
