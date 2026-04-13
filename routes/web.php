<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\DocumentoController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\GoController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DocumentoRecordatorioController;

Route::get('/go', GoController::class)->name('go');

//Route::middleware('auth')->group(function () {
Route::middleware(['auth', 'usuario.habilitado'])->group(function () {
    // Gestión de recordatorios para documentos
    Route::post('/documentos/{documento}/recordatorios', [DocumentoRecordatorioController::class, 'store'])
    ->name('documentos.recordatorios.store');
    Route::delete('/documentos/recordatorios/{recordatorio}', [DocumentoRecordatorioController::class, 'destroy'])
    ->name('documentos.recordatorios.destroy');
    Route::put('/documentos/recordatorios/{recordatorio}', [DocumentoRecordatorioController::class, 'update'])
    ->name('documentos.recordatorios.update');
    Route::patch('/documentos/recordatorios/{recordatorio}/toggle-activo', [DocumentoRecordatorioController::class, 'toggleActivo'])
    ->name('documentos.recordatorios.toggleActivo');
    Route::get('/mis-recordatorios', [DocumentoRecordatorioController::class, 'misRecordatorios'])
    ->name('recordatorios.mis');
    Route::patch('/recordatorios-ejecuciones/{ejecucion}/resolver', [DocumentoRecordatorioController::class, 'resolverEjecucion'])
    ->name('recordatorios.ejecuciones.resolver');
    Route::patch('/recordatorios-ejecuciones/{ejecucion}/postergar', [DocumentoRecordatorioController::class, 'postergarEjecucion'])
    ->name('recordatorios.ejecuciones.postergar');
    Route::get('/recordatorios/calendario', [DocumentoRecordatorioController::class, 'calendario'])
    ->name('recordatorios.calendario');
    Route::get('/recordatorios/eventos', [DocumentoRecordatorioController::class, 'eventosCalendario'])
    ->name('recordatorios.eventos');

    Route::post('/notificaciones/{id}/leer', function ($id) {
    $notificacion = auth()->user()->notifications()->findOrFail($id);
    $notificacion->markAsRead();
    return back();
    })->name('notificaciones.leer');

    Route::post('/notificaciones/leer-todas', function () {
    auth()->user()->unreadNotifications->markAsRead();
    return back();
    })->name('notificaciones.leerTodas');

    Route::post('/documentos/{id}/rechazar', [DocumentoController::class, 'rechazar'])->name('documentos.rechazar');
    Route::get('/documentos/exportar-pdf/{id}', [DocumentoController::class, 'exportarPdf'])->name('documentos.exportarPdf');
    Route::get('/documentos/{id}/validapermiso', [DocumentoController::class, 'validaPermiso'])->name('documentos.validaPermiso');
    Route::post('/documentos/{id}/aprobar', [DocumentoController::class, 'aprobar'])->name('documentos.aprobar');
    Route::put('/documentos/{id}/addVersion', [DocumentoController::class, 'addVersion'])->name('documentos.addVersion');
    #Route::put('/documentos/{id}', [DocumentoController::class, 'update'])->name('documentos.update');
    Route::post('documentos/{documento}/revert/{version}', [DocumentoController::class, 'revertToVersion'])->name('documentos.revert');
    Route::get('/documentos/download/{id}', [DocumentoController::class, 'download'])->name('documentos.download');
    Route::view('/','layouts/main')->name('main');
    Route::get('/documentos', [DocumentoController::class, 'index'])->name('documentos.index');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    #Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    // Ruta para mostrar el perfil del usuario
    Route::get('/perfil', [UserProfileController::class, 'show'])->name('profile.show');
    // Ruta para actualizar el perfil del usuario
    Route::post('/perfil/update', [UserProfileController::class, 'update'])->name('profile.update');
    Route::resource('documentos', DocumentoController::class);
    Route::resource('categorias', CategoriaController::class);
    
    Route::get('/invitations/create', [InvitationController::class, 'create'])
        ->middleware('can:create-invitations')
        ->name('invitations.create');

    Route::post('/invitations', [InvitationController::class, 'store'])
        ->middleware('can:create-invitations')
        ->name('invitations.store');

    Route::post('/invitations/{invitation}/resend', [InvitationController::class, 'resend'])
        ->middleware('can:create-invitations')
        ->name('invitations.resend');

    Route::post('/invitations/{invitation}/revoke', [InvitationController::class, 'revoke'])
        ->middleware('can:create-invitations')
        ->name('invitations.revoke');
    
    // Gestión de usuarios (solo admin)
    Route::get('/usuarios', [UserController::class, 'index'])->name('usuarios.index');
    Route::post('/usuarios/{id}/toggle-habilitado', [UserController::class, 'toggleHabilitado'])->name('usuarios.toggleHabilitado');


});

require __DIR__.'/auth.php';
