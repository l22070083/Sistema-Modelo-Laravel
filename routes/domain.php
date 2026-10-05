<?php

use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CoordinatorController;
use App\Http\Controllers\DossierController;
use App\Http\Controllers\MicrosoftController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SurveyController;
use Illuminate\Support\Facades\Route;

Route::middleware(['guest', 'throttle:10,1'])->group(function () {
    Route::get('/registro', [RegistrationController::class, 'form'])->name('registro');
    Route::post('/registro', [RegistrationController::class, 'register'])->name('registro.submit');
    Route::view('/recuperar', 'recuperar')->name('password.request');
    Route::post('/recuperar', [RegistrationController::class, 'recovery']);
    Route::match(['get', 'post'], '/recuperar/{token}', [RegistrationController::class, 'reset'])->name('password.reset');
});
Route::view('/verificar/reenviar', 'recuperar')->name('email.resend.form');
Route::post('/verificar/reenviar', [RegistrationController::class, 'resend'])->middleware('throttle:3,1')->name('email.resend');
Route::get('/verificar/{token}', [RegistrationController::class, 'verify'])->middleware('throttle:10,1')->name('email.verify');
Route::get('/microsoft', [MicrosoftController::class, 'begin'])->middleware(['guest', 'throttle:10,1'])->name('microsoft.login');
Route::get('/microsoft/callback', [MicrosoftController::class, 'callback'])->middleware('throttle:20,1')->name('microsoft.callback');
Route::middleware(['auth', 'role:1,2,3'])->group(function () {
    Route::match(['get', 'post'], '/perfil', [RegistrationController::class, 'profile'])->name('perfil');
    Route::post('/microsoft/vincular', [MicrosoftController::class, 'begin'])->middleware('throttle:5,1')->name('microsoft.link');
    Route::get('/resultados/{student?}', [SurveyController::class, 'results'])->name('resultados');
});
Route::middleware(['auth', 'role:3'])->group(function () {
    Route::match(['get', 'post'], '/encuestas/responder/{id?}', [SurveyController::class, 'answer'])->name('encuesta.responder');
    Route::post('/encuestas/autoguardado', [SurveyController::class, 'autosave'])->name('encuesta.autoguardado');
    Route::get('/encuestas/{id}/finalizar', [SurveyController::class, 'finish'])->name('encuesta.finalizar');
    Route::get('/mi-expediente', [DossierController::class, 'show'])->name('mi-expediente');
    Route::match(['get', 'post'], '/mi-expediente/editar', [DossierController::class, 'edit'])->name('mi-expediente.editar');
    Route::get('/mi-constancia', [DossierController::class, 'certificate'])->name('mi-constancia');
    Route::post('/mi-expediente/pdf', [DossierController::class, 'exportPdf'])->name('mi-expediente.pdf');
});
Route::middleware(['auth', 'role:1,2'])->group(function () {
    Route::get('/alumnos', [StudentController::class, 'index'])->name('alumnos');
    Route::get('/alumnos/{id}', [StudentController::class, 'show'])->name('alumno.ver');
    Route::match(['get', 'post'], '/alumnos/{student}/expediente', [DossierController::class, 'create'])->name('expediente.crear');
    Route::match(['get', 'post'], '/alumnos/{id}/editar', [StudentController::class, 'edit'])->name('alumno.editar');
    Route::get('/expedientes', [DossierController::class, 'index'])->name('expedientes');
    Route::get('/expedientes/{id}', [DossierController::class, 'show'])->name('expediente.ver');
    Route::match(['get', 'post'], '/expedientes/{id}/editar', [DossierController::class, 'edit'])->name('expediente.editar');
    Route::post('/expedientes/{id}/pdf', [DossierController::class, 'exportPdf'])->name('expediente.pdf');
    Route::post('/expedientes/{id}/{action}', [DossierController::class, 'action'])->name('expediente.accion');
    Route::get('/expedientes/{id}/constancia', [DossierController::class, 'certificate'])->name('expediente.constancia');
    Route::get('/atencion', [DossierController::class, 'attention'])->name('atencion');
    Route::get('/reportes', [ReportController::class, 'index'])->name('reportes');
    Route::post('/reportes/exportar', [ReportController::class, 'export'])->name('reportes.exportar');
    Route::get('/alertas', [ReportController::class, 'alerts'])->name('alertas');
});
Route::middleware(['auth', 'role:1'])->group(function () {
    Route::match(['get', 'post'], '/alumnos/crear', [StudentController::class, 'create'])->name('alumno.create');
    Route::get('/catalogos/{catalog}', [CatalogController::class, 'index'])->name('catalogo');
    Route::get('/catalogos/{catalog}/{id}', [CatalogController::class, 'edit'])->name('catalogo.edit');
    Route::post('/catalogos/{catalog}/{id?}', [CatalogController::class, 'save'])->name('catalogo.save');
    Route::post('/catalogos/{catalog}/{id}/estado', [CatalogController::class, 'status'])->name('catalogo.status');
    Route::get('/coordinadores/{id}', [CoordinatorController::class, 'edit'])->name('coordinador.edit');
    Route::post('/coordinadores/{id}', [CoordinatorController::class, 'save'])->name('coordinador.save');
    Route::post('/coordinadores/{id}/estado', [CoordinatorController::class, 'status'])->name('coordinador.status');
    Route::post('/coordinadores/{id}/grupos', [CoordinatorController::class, 'groups'])->name('coordinador.grupos');
    Route::post('/coordinadores/{id}/permisos', [CoordinatorController::class, 'permissions'])->name('coordinador.permisos');
    Route::get('/encuestas', [SurveyController::class, 'manage'])->name('encuestas');
    Route::post('/encuestas/{id?}', [SurveyController::class, 'saveSurvey'])->name('encuesta.save');
    Route::post('/preguntas/{id?}', [SurveyController::class, 'saveQuestion'])->name('pregunta.save');
});
