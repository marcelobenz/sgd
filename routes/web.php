<?php

use App\Http\Controllers\DocumentoController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

// Route::get('/', function () {
//     return view('login');
// });

// Rutas para pruebas
Route::get('/test-s3-credentials', function () {
    dd(env('AWS_ACCESS_KEY_ID'), env('AWS_SECRET_ACCESS_KEY'));
});

Route::get('/upload-test', function () {
    $filePath = 'C:/laragon/www/sgd/storage/logs/laravel.log';

    try {
        // Leer el contenido del archivo
        $contents = file_get_contents($filePath);
        
        // Comprobar si el archivo se ha leído correctamente
        if ($contents === false) {
            $errorMsg = 'Failed to read the file contents.';
            Log::error($errorMsg);
            return $errorMsg;
        }

        // Intentar cargar el archivo en S3
        $result = Storage::disk('s3')->put('prueba/laravel.log', $contents);

        // Verificar si la carga fue exitosa
        if ($result) {
            return 'File uploaded successfully!';
        } else {
            $errorMsg = 'Failed to upload the file.';
            Log::error($errorMsg);
            return $errorMsg;
        }
    } catch (\Exception $e) {
        // Registrar el error en el archivo de logs
        Log::error('File upload error: ' . $e->getMessage(), ['exception' => $e]);

        // Mostrar el mensaje de error al usuario
        return 'Error: ' . $e->getMessage();
    }
});

Route::middleware('auth')->group(function () {
    Route::put('/documentos/{id}', [DocumentoController::class, 'update'])->name('documentos.update');
    Route::post('documentos/{documento}/revert/{version}', [DocumentoController::class, 'revertToVersion'])->name('documentos.revert');
    Route::get('/documentos/download/{id}', [DocumentoController::class, 'download'])->name('documentos.download');
    Route::resource('documentos', DocumentoController::class);
    Route::view('/','main')->name('main');
    Route::get('/documentos', [DocumentoController::class, 'index'])->name('documentos.index');
    Route::view('/dashboard','dashboard')->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    //Route::view('/main','main')->name('main');
});

require __DIR__.'/auth.php';
