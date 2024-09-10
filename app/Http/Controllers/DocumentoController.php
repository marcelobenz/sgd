<?php

namespace App\Http\Controllers;

use App\Models\DocumentoPermiso;
use App\Models\User;
use App\Models\HistorialDocumento; // Importa el modelo HistorialDocumento
use App\Models\Documento;
use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage; 
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Notifications\DocumentoPendienteAprobacion;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use setasign\Fpdi\Fpdi as Fpdi;
use Illuminate\Support\Facades\Log;
class DocumentoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $documentos = Documento::all();
        return view('documentos.index', compact('documentos'));    
    }

    public function create()
    {
        $categorias = Categoria::all();
        $usuarios = User::all(); // Obtener todos los usuarios
        return view('documentos.create', compact('categorias', 'usuarios'));
    }

    public function store(Request $request)
    {

        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'archivo' => 'required|file',
            'id_categoria' => 'required|exists:categorias,id',
            'permisos' => 'array'
        ]);

        $file = $request->file('archivo');
        $path = $file->store('documentos', 's3');
    
        $documento = Documento::create([
            'titulo' => $validated['titulo'],
            'path' => $path,
            'contenido' => $request->input('contenido'),
            'estado' => 'en curso',
            'id_categoria' => $validated['id_categoria'],
            'id_usr_creador' => auth()->id(),
            'id_usr_ultima_modif' => auth()->id(),
        ]);

        //Asigna todos los permisos por default al dueño del documento
        DocumentoPermiso::updateOrCreate(
            ['documento_id' => $documento->id, 'user_id' => auth()->id()],
            [
                'puede_leer' => true,
                'puede_escribir' => true,
                'puede_aprobar' => true,
                'puede_eliminar' => true,
            ]
        );

        if (isset($validated['permisos'])) {
            //Asigna los permisos seleccionados al resto de los usuarios
            foreach ($validated['permisos'] as $userId => $permisos) {
                DocumentoPermiso::updateOrCreate(
                    ['documento_id' => $documento->id, 'user_id' => $userId],
                    [
                        'puede_leer' => isset($permisos['puede_leer']) ? true : false,
                        'puede_escribir' => isset($permisos['puede_escribir']) ? true : false,
                        'puede_aprobar' => isset($permisos['puede_aprobar']) ? true : false,
                        'puede_eliminar' => isset($permisos['puede_eliminar']) ? true : false,
                    ]
                );
                // Si el usuario tiene permiso de aprobar, envía una notificación
                if (isset($permisos['puede_aprobar']) && $permisos['puede_aprobar']) {
                    $user = User::find($userId);
                    $user->notify(new DocumentoPendienteAprobacion($documento));
                }
            }
        }

        // Envia correo a el/los aprobador/es para avisar que tienen un doc pendiente de aprobar
            
        return redirect()->route('documentos.index')->with('success', 'Documento creado exitosamente.');
    }
        
    public function show($id)
    {
        $documento = Documento::findOrFail($id); 

        if (!$documento->puedeLeer(auth()->user())) {
            return redirect()->route('documentos.index')->with('error', 'No tienes permiso para leer este documento.');
        }

        $bucket = env('AWS_BUCKET');
        $region = env('AWS_DEFAULT_REGION'); // Opcional: si necesitas la región para construir la URL
        $baseUrl = "https://{$bucket}.s3.{$region}.amazonaws.com/";
        //dd($baseUrl);

        // Construye la URL del archivo
        $fileUrl = $baseUrl . $documento->path;
        //dd($fileUrl);

        // Obtener el contenido del archivo si es necesario para el preview
        // $fileUrl = "https://repositorio-sgd.s3.us-west-2.amazonaws.com/" . $documento->path;
        $fileExtension = pathinfo($documento->path, PATHINFO_EXTENSION);
    
        return view('documentos.showlocal', compact('documento', 'fileUrl', 'fileExtension'));
    }

    public function aprobar($id)
    {

        $documento = Documento::findOrFail($id);
        
        if (!$documento->puedeAprobar(auth()->user())) {
            return redirect()->route('documentos.index')->with('error', 'No tienes permiso para Aprobar este documento lpm: '.$documento->puedeAprobar(auth()->user()));
        }

        $documento->estado = 'aprobado';
        $documento->fecha_aprobacion = now();
        $documento->ultima_version_aprobada = $documento->version;
        $documento->save();
    
        return redirect()->back()->with('success', 'El documento ha sido aprobado.');
    }
    

    public function download($id)
    {
        // Obtén el documento desde la base de datos
        $documento = Documento::findOrFail($id);

        if (!$documento->puedeEscribir(auth()->user())) {
            return redirect()->route('documentos.index')->with('error', 'No tienes permiso para modificar este documento (update)');
        }

        // Ruta del archivo en S3
        $filePath = $documento->path;
        // Determinar el tipo MIME manualmente
        $mimeType = $this->getMimeType(pathinfo($filePath, PATHINFO_EXTENSION));
        // Nombre de archivo para la descarga
        $fileName = basename($filePath);
        $disk = Storage::disk('s3');
    
        if (!$disk->exists($filePath)) {
            abort(404, 'File not found');
        }
    
        $file = $disk->get($filePath);
        
        $response = new StreamedResponse(function() use ($file) {
            echo $file;
        });
    
        $response->headers->set('Content-Type', $mimeType);
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$fileName.'"');
        
        return $response;
    }

    private function getMimeType($extension)
    {
        $mimeTypes = [
            'pdf'  => 'application/pdf',
            'doc'  => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls'  => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'jpg'  => 'image/jpeg',
            'png'  => 'image/png',
            // Agrega más tipos MIME según sea necesario
        ];

        return $mimeTypes[$extension] ?? 'application/octet-stream'; // Valor por defecto si el tipo MIME no está en el arreglo
    }

    // Para editar cabecera y permisos documentos
    public function update(Request $request, $id)
    {
        $documento = Documento::findOrFail($id);
        
        if (!$documento->puedeEscribir(auth()->user())) {
            return redirect()->route('documentos.index')->with('error', 'No tienes permiso para modificar este documento (update)');
        }
    
        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'id_categoria' => 'required|exists:categorias,id',
            'permisos' => 'array'
        ]);
    
        // Actualiza el documento
        $documento->update([
            'titulo' => $validated['titulo'],
            'id_categoria' => $validated['id_categoria'],
            'id_usr_ultima_modif' => auth()->id(),
        ]);

        // Gestionar permisos
        DocumentoPermiso::where('documento_id', $documento->id)->delete();

        //Asigna todos los permisos por default al dueño del documento
        DocumentoPermiso::updateOrCreate(
            ['documento_id' => $documento->id, 'user_id' => $documento->id_usr_creador],
            [
                'puede_leer' => true,
                'puede_escribir' => true,
                'puede_aprobar' => true,
                'puede_eliminar' => true,
            ]
        );

        //Asigna los permisos seleccionados al resto de los usuarios
        foreach ($request->input('permisos', []) as $userId => $permisos) {
            DocumentoPermiso::create([
                'documento_id' => $documento->id,
                'user_id' => $userId,
                'puede_leer' => isset($permisos['puede_leer']),
                'puede_escribir' => isset($permisos['puede_escribir']),
                'puede_aprobar' => isset($permisos['puede_aprobar']),
                'puede_eliminar' => isset($permisos['puede_eliminar']),
            ]);
            // Si el usuario tiene permiso de aprobar, envía una notificación
            if (isset($permisos['puede_aprobar']) && $permisos['puede_aprobar'] && $documento->estado === 'pendiente de aprobación') {
                $user = User::find($userId);
                $user->notify(new DocumentoPendienteAprobacion($documento));
            }
            
        }
    
        return redirect()->route('documentos.index')->with('success', 'Documento actualizado exitosamente.');

    }
    
    //Versionado
    public function AddVersion(Request $request, $id)
    {
        $documento = Documento::findOrFail($id);
        
        if (!$documento->puedeEscribir(auth()->user())) {
            return redirect()->route('documentos.index')->with('error', 'No tienes permiso para modificar este documento (addversion)');
        }

        // Registrar la versión actual en el historial antes de realizar cambios
        $this->archiveCurrentVersion($documento);

        //Asignar el título y la categoría desde el documento existente
        $request->merge([
            'titulo' => $documento->titulo,
            'id_categoria' => $documento->id_categoria,
        ]);

        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'nuevoArchivo' => 'file',
            'id_categoria' => 'required|exists:categorias,id'
        ]);

        if ($request->hasFile('nuevoArchivo')) {
            $path = $request->file('nuevoArchivo')->store('documentos', 's3');
            $documento->path = $path;
        }
        //dd($request->file('nuevoArchivo'));

        $documento->fill($request->except('nuevoArchivo'));
        $maxVersion = HistorialDocumento::where('id_documento', $documento->id)
                       ->max('version');
        $documento->version = $maxVersion + 1;
        $documento->estado = "pendiente de aprobación";
        $documento->save();
    
        return redirect()->route('documentos.show', $documento->id)
                         ->with('success', 'Documento actualizado y nueva versión creada');
    }
    
    
    protected function archiveCurrentVersion($documento)
    {
        // Guardar la versión actual del documento en el historial
        $historial = new HistorialDocumento();
        $historial->path = $documento->path;
        $historial->titulo = $documento->titulo;
        $historial->contenido = $documento->contenido;
        $historial->estado = $documento->estado;
        $historial->id_documento = $documento->id;
        $historial->id_Categoria = $documento->id_categoria;
        $historial->id_usr_creador = $documento->id_usr_creador;
        $historial->id_usr_ultima_modif = $documento->id_usr_ultima_modif;
        $historial->fecha_aprobacion = $documento->fecha_aprobacion;
        $historial->version = $documento->version;
        //$historial->created_at = now();
        $historial->save();
    }
    
    public function revertToVersion($documentoId, $versionId)
    {

        // Encuentra el documento actual
        $documento = Documento::findOrFail($documentoId);

        if (!$documento->puedeEscribir(auth()->user())) {
            return redirect()->route('documentos.index')->with('error', 'No tienes permiso para modificar o revertir este documento');
        }

        // Encuentra la version en el historial a restaurar
        $historialDocumento = HistorialDocumento::findOrFail($versionId);

        // Verifica si la versión ya existe en el historial
        $existeEnHistorial = HistorialDocumento::where('id_documento', $documento->id)
                                            ->where('version', $documento->version)
                                            ->exists();

        // Solo archiva la versión actual si no existe en el historial
        if (!$existeEnHistorial) {
            $this->archiveCurrentVersion($documento);
        }

        if ($documento && $historialDocumento){
            $documento->path = $historialDocumento->path;
            $documento->titulo = $historialDocumento->titulo;
            $documento->contenido = $historialDocumento->contenido;
            $documento->estado = $historialDocumento->estado;
            $documento->id_Categoria = $historialDocumento->id_categoria;
            $documento->id_usr_creador = $historialDocumento->id_usr_creador;
            $documento->id_usr_ultima_modif = $historialDocumento->id_usr_ultima_modif;
            $documento->fecha_aprobacion = $historialDocumento->fecha_aprobacion;
            $documento->version = $historialDocumento->version;
            //$documento->version = $historialDocumento;
            $documento->save();

            return redirect()->route('documentos.show', $documento->id)
            ->with('success', 'Documento actualizado y nueva versión creada');
        } else {
            return response()->json(['error' => 'Documento o historial no encontrado.'], 404);
        }

    }

    // Editar
    public function edit($id)
    {
        $documento = Documento::findOrFail($id);
        $categorias = Categoria::all();
        $usuarios = User::all();
        return view('documentos.edit', compact('documento', 'categorias', 'usuarios'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // Encuentra el documento por su ID
        $documento = Documento::findOrFail($id); 

        if (!$documento->puedeEliminar(auth()->user())) {
            return redirect()->route('documentos.index')->with('error', 'No tienes permiso para eliminar este documento');
        }

        // Obtiene el historial de documentos asociados
        $historialDocumentos = $documento->historial;

        // Elimina los archivos asociados en S3 que están en el historial
        foreach ($historialDocumentos as $historial) {
            if (Storage::disk('s3')->exists($historial->path)) {
                Storage::disk('s3')->delete($historial->path);
            }
        }

        // Elimina el archivo del fillesystem local o s3 (reemplazar local por s3)
        if (Storage::disk('s3')->exists($documento->path)) {
            Storage::disk('s3')->delete($documento->path);
        }

        // Elimina el documento 
        $documento->delete();
        // Redirige con un mensaje de éxito
        return redirect()->route('documentos.index')->with('success', 'Documento eliminado exitosamente.');        
    }

    public function validaPermiso($id, Request $request)
    {
        // Encuentra el documento actual
        $documento = Documento::findOrFail($id);
        $ruta = $request->input('ruta');
        $permiso = $request->input('permiso');
        $usuario = auth()->user();

        // Verifica si el usuario tiene el permiso específico
        if (!$documento->{$permiso}($usuario)) {
            return redirect()->route('documentos.index')->with('error', 'No tienes permiso para realizar esta acción');
        } else {
            return redirect()->route($ruta, ['documento' => $id]);
        }
    }
       
    public function exportarPdf($documentId){
        
        $documento = Documento::findOrFail($documentId);

        // Valida que el doc esté aprobado, si no lo está, busca la última versión aprobada en el historial
        if ($documento->estado != "aprobado") {
            $documentoAprobado = $documento->ultimaVersionAprobada();
            if (!$documentoAprobado) {
                // No hay versiones aprobadas
                return redirect()->route('documentos.index')->with('error', 'No existe ninguna versión aprobada para el documento que intenta descargar.');
            }
            $documento = $documentoAprobado; // Cambiamos al historial del documento aprobado
        }
        //dd($documento); 

        $extension = pathinfo($documento->path, PATHINFO_EXTENSION);

        if ($extension != 'pdf') {
            $convertedPdfPath = $this->convertToPdf($documento->path);
        } else {
            $convertedPdfPath = $this->downloadFileToLocal($documento->path);
        }

        // Preparar el archivo para FPDI
        $pdf = new Fpdi();
        $pageCount = $pdf->setSourceFile($convertedPdfPath);

        // iterate through all pages
        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            // import a page
            $templateId = $pdf->importPage($pageNo);
            $pdf->AddPage();
            // use the imported page and adjust the page size
            $pdf->useTemplate($templateId, ['adjustPageSize' => true]);
        }

        //Agrego una paginma al final con un texto
        $pdf->AddFont('Courier', '', 'courier.php');

        // Añadir una nueva página en blanco
        $pdf->AddPage();
        $pdf->SetFont('Courier', '', 12);
        $pdf->Cell(0, 10, iconv('UTF-8', 'windows-1252', 'Versión Actual'), 0, 1, 'C');

        $pdf->Cell(80, 10, 'Documento:', 1);
        $pdf->Cell(0, 10, $documento->titulo, 1, 1); // Nuevo línea después del título

        $pdf->Cell(80, 10, iconv('UTF-8', 'windows-1252','Versión:'), 1);
        $pdf->Cell(0, 10, $documento->version, 1, 1); // Versión

        $pdf->Cell(80, 10, iconv('UTF-8', 'windows-1252','Categoría:'), 1);
        $pdf->Cell(0, 10, $documento->categoria->nombre_categoria, 1, 1); // Categoría

        $pdf->Cell(80, 10, 'Estado:', 1);
        $pdf->Cell(0, 10, iconv('UTF-8', 'windows-1252', $documento->estado), 1, 1); // Estado

        $pdf->Cell(80, 10, 'Creador:', 1);
        $pdf->Cell(0, 10, iconv('UTF-8', 'windows-1252', $documento->creador->name), 1, 1); // Creador

        $pdf->Cell(80, 10, iconv('UTF-8', 'windows-1252','Fecha de Creación:'), 1);
        $pdf->Cell(0, 10, $documento->created_at, 1, 1); // Fecha de creación

        $pdf->Cell(80, 10, iconv('UTF-8', 'windows-1252','Último Editor:'), 1);
        $pdf->Cell(0, 10, iconv('UTF-8', 'windows-1252', $documento->ultimaModificacion->name), 1, 1); // Último editor

        $pdf->Cell(80, 10, iconv('UTF-8', 'windows-1252','Fecha de Última Modificación:'), 1);
        $pdf->Cell(0, 10, $documento->updated_at, 1, 1); // Fecha de última modificación

        // Eliminar el archivo PDF temporal después de procesarlo
        unlink($convertedPdfPath);

        return $pdf->Output($documento->titulo . '-version_' . $documento->version . '-' . $documento->updated_at . '.pdf', 'D');
    }

    /**
     * Convertir un archivo de S3 a PDF y manejarlo localmente.
     *
     * @param string $path Ruta del archivo en S3.
     * @return string Ruta del archivo PDF local.
     */
    private function convertToPdf($path)
    {
        // Descargar el archivo de S3 a un directorio temporal
        $localPath = tempnam(sys_get_temp_dir(), 'doc');
        $content = Storage::disk('s3')->get($path);
        file_put_contents($localPath, $content);
        // Detectar si estamos en Windows o en Linux
        $isWindows = (PHP_OS_FAMILY === 'Windows');
        // Definir la ruta local del PDF
        $localPdfPath = sys_get_temp_dir() . '/' . basename($path, '.' . pathinfo($path, PATHINFO_EXTENSION)) . '.pdf';
        // Reemplazar las barras invertidas en la ruta local del PDF para Windows
        $localPdfPath = str_replace('/', '\\', $localPdfPath);
        // Obtener la ruta de soffice desde las variables de entorno
        $sofficePath = env('SOFFICE_PATH', 'soffice'); // Proporciona un valor por defecto por si no está definido
        // Escapar la ruta completa del comando
        $sofficePath = escapeshellarg($sofficePath);
        //$dirPath = escapeshellarg(dirname($localPdfPath));
        $dirPath = escapeshellarg(sys_get_temp_dir());
        $filePath = escapeshellarg($localPath);

        if (!$isWindows) {
            $command = "{$sofficePath} --headless --convert-to pdf --outdir {$dirPath} {$filePath} -env:UserInstallation=file:///tmp/LibreOfficeConfig 2>&1";
        } else {
            // Para Windows no es necesario el parámetro -env
            $command = "{$sofficePath} --headless --convert-to pdf --outdir {$dirPath} {$filePath} 2>&1";
        }

        //$command = "{$sofficePath} --headless --convert-to pdf --outdir {$dirPath} {$filePath} 2>&1";
        Log::info("Comando antes de ejecutar: " . $command);
        exec($command, $output, $return_var);
        Log::info("Comando ejecutado: " . $command);
        if ($return_var !== 0) {
            Log::error("Error al convertir archivo: " . implode("\n", $output));
        } else {
            Log::info("Success: PDF generado");
        }

        // Limpiar el archivo original descargado
        unlink($localPath);
        //Obtengo el path del archivo resultante de la exportación
        $resultPath = sys_get_temp_dir() . '/' . basename($localPath, '.' . pathinfo($localPath, PATHINFO_EXTENSION)) . '.pdf';
        //dd($resultPath);
        // Devolver la ruta local del archivo PDF
        return $resultPath;
    }

    private function downloadFileToLocal($path)
    {
        $localPath = tempnam(sys_get_temp_dir(), 'doc');
        $content = Storage::disk('s3')->get($path);
        file_put_contents($localPath, $content);
        return $localPath;
    }

}

