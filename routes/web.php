<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\DocumentoController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\GoController;
use App\Http\Controllers\InvitationController;

Route::get('/go', GoController::class)->name('go');

Route::middleware('auth')->group(function () {
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

});

require __DIR__.'/auth.php';
