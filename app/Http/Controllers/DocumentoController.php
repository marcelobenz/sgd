<?php

namespace App\Http\Controllers;

use App\Models\HistorialDocumento; // Importa el modelo HistorialDocumento
use App\Models\Documento;
use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage; 
use Symfony\Component\HttpFoundation\StreamedResponse;
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

    public function show($id)
    {
        $documento = Documento::findOrFail($id);

        // Obtener el contenido del archivo si es necesario para el preview
        $fileUrl = "https://repositorio-sgd.s3.us-west-2.amazonaws.com/" . $documento->path;
        $fileExtension = pathinfo($documento->path, PATHINFO_EXTENSION);
    
        return view('documentos.showlocal', compact('documento', 'fileUrl', 'fileExtension'));
    }

    public function aprobar($id)
    {
        $documento = Documento::findOrFail($id);
        $documento->estado = 'aprobado';
        $documento->fecha_aprobacion = now();
        $documento->save();
    
        return redirect()->back()->with('success', 'El documento ha sido aprobado.');
    }
    

    public function download($id)
    {
        // Obtén el documento desde la base de datos
        $documento = Documento::findOrFail($id);

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

    // Editar
    public function edit(string $id)
    {
        //Aca va el codigo para eliminar los metadatos del documento
        // Mensaje de alerta
        session()->flash('alert_message', 'Esta funcionalidad aún no está desarrollada.');

        // Redirige de vuelta a la página anterior
        return redirect()->back();        
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
