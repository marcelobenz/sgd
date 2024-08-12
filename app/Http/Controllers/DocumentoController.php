<?php

namespace App\Http\Controllers;

use App\Models\HistorialDocumento; // Importa el modelo HistorialDocumento
use PhpOffice\PhpWord\IOFactory;
use App\Models\Documento;
use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage; 
use PhpOffice\PhpSpreadsheet\IOFactory as ExcelIOFactory;

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
        $categorias = Categoria::all(); // Obtén todas las categorías para el formulario
        return view('documentos.create', compact('categorias'));
    }
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'archivo' => 'required|file',
            'id_categoria' => 'required|exists:categorias,id'
        ]);

        // Guarda el archivo en S3 o local
        $file = $request->file('archivo');
        $path = $file->store('documentos', 's3'); // usar 's3' en lugar de local para guardar en bucket s3

        // Crea un nuevo documento
        Documento::create([
            'titulo' => $validated['titulo'],
            'path' => $path,
            'contenido' => $request->input('contenido'),
            'estado' => 'en curso',
            'id_categoria' => $validated['id_categoria'],
            'id_usr_creador' => auth()->id(),
            'id_usr_ultima_modif' => auth()->id(),
        ]);

        return redirect()->route('documentos.index')->with('success', 'Documento creado exitosamente.');
    }

    
    // public function show_x_extension($id)
    // {
    //     $documento = Documento::findOrFail($id);
        
    //     // Obtener el contenido del archivo desde S3
    //     $filePath = $documento->path;
    //     $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);
    //     $fileContent = '';
    //     $htmlContent = '';
        
    //     try {
    //         $s3Client = Storage::disk('s3');
    //         $fileStream = $s3Client->getDriver()->readStream($filePath);
    
    //         if ($fileStream === false) {
    //             throw new \Exception('El archivo no se encuentra en S3.');
    //         }
    
    //         switch (strtolower($fileExtension)) {
    //             case 'pdf':
    //                 $fileContent = base64_encode(stream_get_contents($fileStream)); // Para mostrar como embed PDF
    //                 break;
    //             case 'doc':
    //             case 'docx':
    //                 $phpWord = IOFactory::load($fileStream);
    //                 $htmlWriter = IOFactory::createWriter($phpWord, 'HTML');
    //                 ob_start();
    //                 $htmlWriter->save('php://output');
    //                 $htmlContent = ob_get_clean();
    //                 break;
    //             case 'xls':
    //             case 'xlsx':
    //                 $spreadsheet = ExcelIOFactory::load($fileStream);
    //                 $sheetData = $spreadsheet->getActiveSheet()->toArray();
    //                 $fileContent = $sheetData;
    //                 break;
    //         }
            
    //         // Asegúrate de cerrar el stream después de usarlo
    //         fclose($fileStream);
    
    //     } catch (\Exception $e) {
    //         // Manejar excepciones, tal vez registrar el error o mostrar un mensaje
    //         return response()->view('errors.404', [], 404);
    //     }
    
    //     return view('documentos.show', compact('documento', 'fileContent', 'htmlContent', 'fileExtension'));
    // }
    
    /**
     * Display the specified resource.
     */
    // public function show($id)
    // {
    //     $documento = Documento::findOrFail($id);

    //     // Obtener el contenido del archivo si es necesario para el preview
    //     $filePath = storage_path('app/' . $documento->path);
    //     $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);
    //     $fileContent = '';
    
    //     switch (strtolower($fileExtension)) {
    //         case 'pdf':
    //             $fileContent = base64_encode(file_get_contents($filePath)); // Para mostrar como embed PDF
    //             break;
    //         case 'doc':
    //         case 'docx':
    //             $phpWord = IOFactory::load($filePath);
    //             $htmlWriter = IOFactory::createWriter($phpWord, 'HTML');
    //             $htmlContent = '';
    //             ob_start();
    //             $htmlWriter->save('php://output');
    //             $htmlContent = ob_get_clean();
    //             break;
    //         case 'xls':
    //         case 'xlsx':
    //             $spreadsheet = ExcelIOFactory::load($filePath);
    //             $sheetData = $spreadsheet->getActiveSheet()->toArray();
    //             $fileContent = $sheetData;
    //             break;
    //     }
    //     return view('documentos.showlocal', compact('documento', 'fileContent', 'htmlContent', 'fileExtension'));
    // }


    public function show($id)
    {
        $documento = Documento::findOrFail($id);

        // Obtener el contenido del archivo si es necesario para el preview
        $fileUrl = "https://repositorio-sgd.s3.us-west-2.amazonaws.com/" . $documento->path;
        $fileExtension = pathinfo($documento->path, PATHINFO_EXTENSION);
    
        return view('documentos.showlocal', compact('documento', 'fileUrl', 'fileExtension'));
    }

    // public function show($id)
    // {
    //     $documento = Documento::findOrFail($id);

    //     // Generar una URL temporal para acceder al archivo
    //     $fileUrl = Storage::disk('s3')->temporaryUrl($documento->path, now()->addMinutes(10));

    //     $fileExtension = pathinfo($documento->path, PATHINFO_EXTENSION);
        
    //     return view('documentos.showlocal', compact('documento', 'fileUrl', 'fileExtension'));
    // }


    public function download($id)
    {
        $documento = Documento::findOrFail($id);
        $filePath = storage_path('app/' . $documento->path);
    
        if (!Storage::disk('local')->exists($documento->path)) {
            abort(404, 'File not found.');
        }
    
        return response()->download($filePath, $documento->titulo);
    }

    //Versionado
    public function update(Request $request, $id)
    {
        // Encuentra el documento actual
        $documento = Documento::findOrFail($id);
    
        // Verifica si la versión ya existe en el historial
        $existeEnHistorial = HistorialDocumento::where('id_documento', $documento->id)
                                            ->where('version', $documento->version)
                                            ->exists();

        // Solo archiva la versión actual si no existe en el historial
        if (!$existeEnHistorial) {
            $this->archiveCurrentVersion($documento);
        }
    
        // Subir y almacenar la nueva versión del documento en local u otro almacenamiento
        if ($request->hasFile('nuevoArchivo')) {
            $path = $request->file('nuevoArchivo')->store('documentos', 's3');
            $documento->path = $path;
        } 
    
        // Guardar la nueva versión
        $documento->fill($request->except('nuevoArchivo'));
        // Obtiene la versión máxima actual de los documentos
        $maxVersion = HistorialDocumento::where('id_documento', $documento->id)
                       ->max('version');
        // Establece la versión del documento actual como la máxima + 1
        $documento->version = $maxVersion + 1;
        // Cambia la versión a Pendiente de Aprobacion
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

    public function show_googledocs(string $id)
    {
        //Para ver los documentos con googledocs
        $documento = Documento::findOrFail($id);
        $baseUrl="https://repositorio-sgd.s3.amazonaws.com/";
        //dd($documento->path);
        //dd(env('AWS_ACCESS_KEY_ID'), env('AWS_SECRET_ACCESS_KEY'), env('AWS_DEFAULT_REGION'), env('AWS_BUCKET'), Storage::disk('s3')->exists('documentos/OIVWqbtyMNDZdZXSnUtyvHleA9lNxxOtbS48O7pr.xlsx'));

        // Obtener la URL del archivo
        if (Storage::disk('s3')->exists($documento->path)) {
            //$fileUrl = Storage::disk('s3')->get($documento->url);

            //$fileUrl = Storage::disk('s3')->temporaryUrl($documento->path, now()->addMinutes(5));
            $fileUrl = $baseUrl . $documento->path;

            //$fileUrl = 'https://repositorio-sgd.s3.amazonaws.com/documentos/OIVWqbtyMNDZdZXSnUtyvHleA9lNxxOtbS48O7pr.xlsx';
            return view('documentos.showxlsgoogle', compact('documento', 'fileUrl'));
        } else {
            return "Archivo no encontrado.";
        }

        // Obtener la URL pública del archivo en S3
        // $fileUrl = Storage::disk('s3')->url($documento->path);
        // return view('documentos.show', compact('documento', 'fileUrl'));

    }

    // Editar
    public function edit(string $id)
    {
        $documento = Documento::findOrFail($id);
        $tempFilePath = sys_get_temp_dir() . '/' . basename($documento->path);

        // Descargar el archivo desde S3
        try {
            $content = Storage::disk('s3')->get($documento->path);
            file_put_contents($tempFilePath, $content);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al leer el archivo desde S3: ' . $e->getMessage()], 500);
        }

        // Convertir el archivo DOCX a HTML
        try {
            $phpWord = IOFactory::load($tempFilePath);
            $htmlWriter = IOFactory::createWriter($phpWord, 'HTML');
            ob_start();
            $htmlWriter->save('php://output');
            $fullHtml = ob_get_clean();
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al convertir el archivo con PHPWord: ' . $e->getMessage()], 500);
        }

        // Eliminar el archivo temporal
        unlink($tempFilePath);

        // Extraer solo el contenido dentro de <body> ... </body>
        preg_match('/<body>(.*?)<\/body>/is', $fullHtml, $matches);
        $htmlContent = $matches[1] ?? 'No se pudo extraer el contenido';

        return view('documentos.edit', ['htmlContent' => $htmlContent, 'documento' => $documento]);
    }

    /**
     * Show the form for editing the specified resource.
     */
     public function edit2(string $id)
    {
        $documento = Documento::findOrFail($id);
        $tempFilePath = sys_get_temp_dir() . '/' . basename($documento->path);
        //dd("id:" . $id, "PATH:" . $documento->path, "tempFilePath" . $tempFilePath);
    
        // Descargar el archivo desde S3
        try {
            $content = Storage::disk('s3')->get($documento->path);
            file_put_contents($tempFilePath, $content);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al leer el archivo desde S3: ' . $e->getMessage()], 500);
        }

        if (!file_exists($tempFilePath)) {
            return response()->json(['error' => 'Archivo temporal no encontrado.'], 404);
        }
        
        // Cargar el archivo en PHPWord
        $phpWord = IOFactory::load($tempFilePath);
        
        // Convertir el documento a HTML
        $htmlWriter = IOFactory::createWriter($phpWord, 'HTML');
        $htmlContent = '';
        ob_start();
        $htmlWriter->save('php://output');
        $htmlContent = ob_get_clean();
        
        // Eliminar el archivo temporal
        unlink($tempFilePath);
    
        return view('documentos.edit', compact('htmlContent', 'documento'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // Encuentra el documento por su ID
        $documento = Documento::findOrFail($id);
        // Elimina el archivo del fillesystem local o s3 (reemplazar local por s3)
        if (Storage::disk('local')->exists($documento->path)) {
            Storage::disk('local')->delete($documento->path);
        }
        // Elimina el documento
        $documento->delete();
        // Redirige con un mensaje de éxito
        return redirect()->route('documentos.index')->with('success', 'Documento eliminado exitosamente.');        
    }

}
