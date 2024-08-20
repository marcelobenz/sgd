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
    
        // Asignar permisos
        foreach ($validated['permisos'] as $userId => $permisos) {
            DocumentoPermiso::updateOrCreate(
                ['documento_id' => $documento->id, 'user_id' => $userId],
                [
                    'puede_leer' => isset($permisos['puede_leer']) ? $permisos['puede_leer'] : false,
                    'puede_escribir' => isset($permisos['puede_escribir']) ? $permisos['puede_escribir'] : false,
                    'puede_aprobar' => isset($permisos['puede_aprobar']) ? $permisos['puede_aprobar'] : false,
                    'puede_eliminar' => isset($permisos['puede_eliminar']) ? $permisos['puede_eliminar'] : false,
                ]
            );
        }
    
        return redirect()->route('documentos.index')->with('success', 'Documento creado exitosamente.');
    }
    
    public function show($id)
    {
        $documento = Documento::findOrFail($id); 

        if (!$documento->puedeLeer(auth()->user())) {
            return redirect()->route('documentos.index')->with('error', 'No tienes permiso para leer este documento.');
        }

        // Obtener el contenido del archivo si es necesario para el preview
        $fileUrl = "https://repositorio-sgd.s3.us-west-2.amazonaws.com/" . $documento->path;
        $fileExtension = pathinfo($documento->path, PATHINFO_EXTENSION);
    
        return view('documentos.showlocal', compact('documento', 'fileUrl', 'fileExtension'));
    }

    public function aprobar($id)
    {

        $documento = Documento::findOrFail($id);

        if (!$documento->puedeAprobar(auth()->user())) {
            return redirect()->route('documentos.index')->with('error', 'No tienes permiso para Aprobar este documento');
        }

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

    //Versionado, cuando se sube una nueva version del documento
    public function update(Request $request, $id)
    {
        $documento = Documento::findOrFail($id);
        //dd($documento, $request->all());
        
        if (!$documento->puedeEscribir(auth()->user())) {
            return redirect()->route('documentos.index')->with('error', 'No tienes permiso para modificar este documento');
        }
        //Asignar el título y la categoría desde el documento existente
        // $request->merge([
        //     'titulo' => $documento->titulo,
        //     'id_categoria' => $documento->id_categoria,
        // ]);
        
        //$validated = $request->validate([
            //'titulo' => 'required|string|max:255',
            //'nuevoArchivo' => 'file'//,
            //'id_categoria' => 'required|exists:categorias,id',
            //'permisos' => 'array'
        //]);

        if ($request->hasFile('nuevoArchivo')) {
            $path = $request->file('nuevoArchivo')->store('documentos', 's3');
            $documento->path = $path;
        }
    
        $documento->fill($request->except('nuevoArchivo'));
        $maxVersion = HistorialDocumento::where('id_documento', $documento->id)
                       ->max('version');
        $documento->version = $maxVersion + 1;
        $documento->estado = "pendiente de aprobación";
        $documento->save();
    
        // Pendiente: Asignar permisos
        // if ($request->has('permisos')) {
        //     foreach ($request->input('permisos') as $userId => $permisos) {
        //         DocumentoPermiso::updateOrCreate(
        //             ['documento_id' => $documento->id, 'user_id' => $userId],
        //             $permisos
        //         );
        //     }
        // }
    
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
    public function edit(string $id)
    {
        // Encuentra el documento actual
        $documento = Documento::findOrFail($id);

        if (!$documento->puedeEscribir(auth()->user())) {
            return redirect()->route('documentos.index')->with('error', 'No tienes permiso para modificar este documento');
        }

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

}
