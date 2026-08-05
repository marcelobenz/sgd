<?php

use App\Http\Controllers\Iso\AccionController;
use App\Http\Controllers\Iso\ContextoController;
use App\Http\Controllers\Iso\InformeController;
use App\Http\Controllers\Iso\ObjetivoController;
use App\Http\Controllers\Iso\ParteInteresadaController;
use App\Http\Controllers\Iso\PermisoController;
use App\Http\Controllers\Iso\PeriodoController;
use App\Http\Controllers\Iso\PlanificacionController;
use App\Http\Controllers\Iso\ProveedorController;
use App\Http\Controllers\Iso\RiesgoController;
use Illuminate\Support\Facades\Route;

Route::prefix('planificacion')->name('planificacion.')->middleware(['auth', 'usuario.habilitado', 'iso:view'])->group(function () {
    Route::get('/', [PlanificacionController::class, 'index'])->name('index');
    Route::get('/foda', [ContextoController::class, 'index'])->name('foda.index');
    Route::get('/foda/{contexto}', [ContextoController::class, 'show'])->name('foda.show');
    Route::get('/riesgos', [RiesgoController::class, 'index'])->name('riesgos.index');
    Route::get('/riesgos/nuevo', [RiesgoController::class, 'create'])->name('riesgos.create');
    Route::get('/riesgos/{riesgo}', [RiesgoController::class, 'show'])->name('riesgos.show');
    Route::get('/acciones', [AccionController::class, 'index'])->name('acciones.index');
    Route::get('/objetivos', [ObjetivoController::class, 'index'])->name('objetivos.index');
    Route::get('/objetivos/nuevo', [ObjetivoController::class, 'create'])->middleware('iso:manage')->name('objetivos.create');
    Route::get('/objetivos/{objetivo}', [ObjetivoController::class, 'show'])->name('objetivos.show');
    Route::get('/partes-interesadas', [ParteInteresadaController::class, 'index'])->name('partes.index');
    Route::get('/partes-interesadas/{parte}', [ParteInteresadaController::class, 'show'])->name('partes.show');
    Route::get('/proveedores', [ProveedorController::class, 'index'])->name('proveedores.index');
    Route::get('/proveedores/{proveedor}', [ProveedorController::class, 'show'])->name('proveedores.show');
    Route::get('/informes', [InformeController::class, 'index'])->name('informes.index');
    Route::get('/periodos', [PeriodoController::class, 'index'])->middleware('iso:admin')->name('periodos.index');
    Route::get('/permisos', [PermisoController::class, 'index'])->middleware('iso:admin')->name('permisos.index');

    Route::middleware('iso:manage')->group(function () {
        Route::post('/foda', [ContextoController::class, 'store'])->name('foda.store');
        Route::patch('/foda/{contexto}/evaluar', [ContextoController::class, 'evaluar'])->name('foda.evaluar');
        Route::post('/riesgos', [RiesgoController::class, 'store'])->name('riesgos.store');
        Route::patch('/riesgos/{riesgo}/fecha-verificacion', [RiesgoController::class, 'actualizarFechaVerificacion'])->name('riesgos.fecha-verificacion.update');
        Route::patch('/riesgos/{riesgo}/verificar', [RiesgoController::class, 'verificar'])->name('riesgos.verificar');
        Route::post('/riesgos/{riesgo}/acciones', [AccionController::class, 'store'])->name('acciones.store');
        Route::post('/objetivos', [ObjetivoController::class, 'store'])->name('objetivos.store');
        Route::patch('/objetivos/{objetivo}', [ObjetivoController::class, 'update'])->name('objetivos.update');
        Route::patch('/objetivos/{objetivo}/revisar', [ObjetivoController::class, 'revisar'])->name('objetivos.revisar');
        Route::delete('/objetivos/{objetivo}', [ObjetivoController::class, 'destroy'])->name('objetivos.destroy');
        Route::post('/objetivos/{objetivo}/indicadores/{indicador}/mediciones', [ObjetivoController::class, 'medir'])->name('objetivos.mediciones.store');
        Route::post('/objetivos/{objetivo}/acciones', [ObjetivoController::class, 'storeAccion'])->name('objetivos.acciones.store');
        Route::patch('/objetivo-acciones/{accion}', [ObjetivoController::class, 'updateAccion'])->name('objetivos.acciones.update');
        Route::patch('/objetivo-acciones/{accion}/reabrir', [ObjetivoController::class, 'reabrirAccion'])->name('objetivos.acciones.reabrir');
        Route::post('/objetivo-acciones/{accion}/seguimientos', [ObjetivoController::class, 'seguimientoAccion'])->name('objetivos.acciones.seguimientos.store');
        Route::post('/objetivos/{objetivo}/evaluaciones', [ObjetivoController::class, 'evaluar'])->name('objetivos.evaluaciones.store');
        Route::patch('/acciones/{accion}', [AccionController::class, 'actualizar'])->name('acciones.actualizar');
        Route::patch('/acciones/{accion}/reabrir', [AccionController::class, 'reabrir'])->name('acciones.reabrir');
        Route::post('/acciones/{accion}/seguimientos', [AccionController::class, 'seguimiento'])->name('acciones.seguimientos.store');
        Route::post('/partes-interesadas', [ParteInteresadaController::class, 'store'])->name('partes.store');
        Route::patch('/partes-interesadas/{parte}', [ParteInteresadaController::class, 'update'])->name('partes.update');
        Route::post('/partes-interesadas/{parte}/evaluaciones', [ParteInteresadaController::class, 'evaluar'])->name('partes.evaluaciones.store');
        Route::post('/proveedores', [ProveedorController::class, 'store'])->name('proveedores.store');
        Route::patch('/proveedores/{proveedor}', [ProveedorController::class, 'update'])->name('proveedores.update');
        Route::patch('/proveedores/{proveedor}/baja', [ProveedorController::class, 'darDeBaja'])->name('proveedores.baja');
        Route::patch('/proveedores/{proveedor}/reactivar', [ProveedorController::class, 'reactivar'])->name('proveedores.reactivar');
        Route::delete('/proveedores/{proveedor}', [ProveedorController::class, 'destroy'])->name('proveedores.destroy');
        Route::post('/proveedores/{proveedor}/selecciones', [ProveedorController::class, 'seleccionar'])->name('proveedores.selecciones.store');
        Route::post('/proveedores/{proveedor}/evaluaciones', [ProveedorController::class, 'evaluar'])->name('proveedores.evaluaciones.store');
        Route::post('/proveedor-evaluaciones/{evaluacion}/acciones', [ProveedorController::class, 'storeAccion'])->name('proveedores.acciones.store');
        Route::patch('/proveedor-acciones/{accion}', [ProveedorController::class, 'updateAccion'])->name('proveedores.acciones.update');
    });

    Route::middleware('iso:admin')->group(function () {
        Route::post('/periodos', [PeriodoController::class, 'store'])->name('periodos.store');
        Route::patch('/periodos/{periodo}/activar', [PeriodoController::class, 'activar'])->name('periodos.activar');
        Route::patch('/periodos/{periodo}/clima', [PeriodoController::class, 'clima'])->name('periodos.clima');
        Route::patch('/periodos/{periodo}/cerrar', [PeriodoController::class, 'cerrar'])->name('periodos.cerrar');
        Route::patch('/periodos/{periodo}/reabrir', [PeriodoController::class, 'reabrir'])->name('periodos.reabrir');
        Route::put('/permisos/{user}', [PermisoController::class, 'update'])->name('permisos.update');
    });
});
