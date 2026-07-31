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
use setasign\Fpdi\Fpdi as Fpdi;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Notifications\DocumentoRechazado;
use App\Notifications\DocumentoAprobado;
use Illuminate\Http\UploadedFile;


class DocumentoController extends Controller
{
    private const EXTENSIONES_PERMITIDAS = [
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
    ];

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $documentos = Documento::with(['categoria.parent', 'ultimaModificacion'])
            ->get();

        $categoriasDocumentos = $documentos
            ->filter(fn ($documento) => $documento->categoria)
            ->groupBy(function ($documento) {
                return $documento->categoria->parent_id ?? $documento->categoria->id;
            })
            ->map(function ($documentosCategoria) {
                $primero = $documentosCategoria->first();
                $categoriaRaiz = $primero->categoria->parent ?? $primero->categoria;
                $documentosDirectos = $documentosCategoria
                    ->where('id_categoria', $categoriaRaiz->id)
                    ->sortBy(fn ($documento) => mb_strtolower($documento->titulo))
                    ->values();

                $subcategorias = $documentosCategoria
                    ->filter(fn ($documento) => $documento->categoria->parent_id === $categoriaRaiz->id)
                    ->groupBy('id_categoria')
                    ->map(function ($documentosSubcategoria) {
                        return $this->armarGrupoCategoria(
                            $documentosSubcategoria->first()->categoria,
                            $documentosSubcategoria
                        );
                    })
                    ->sortBy(fn ($grupo) => mb_strtolower($grupo['categoria']->nombre_categoria))
                    ->values();

                return array_merge(
                    $this->armarGrupoCategoria($categoriaRaiz, $documentosCategoria),
                    [
                        'documentos_directos' => $documentosDirectos,
                        'subcategorias' => $subcategorias,
                    ]
                );
            })
            ->sortBy(fn ($grupo) => mb_strtolower($grupo['categoria']->nombre_categoria))
            ->values();

        return view('documentos.index', compact('documentos', 'categoriasDocumentos'));
    }

    private function armarGrupoCategoria(Categoria $categoria, $documentos): array
    {
        return [
            'categoria' => $categoria,
            'documentos' => $documentos
                ->sortBy(fn ($documento) => mb_strtolower($documento->titulo))
                ->values(),
            'total' => $documentos->count(),
            'aprobados' => $documentos->where('estado', 'aprobado')->count(),
            'pendientes' => $documentos->where('estado', 'pendiente de aprobación')->count(),
            'registros' => $documentos->where('estado', 'registro')->count(),
            'ultima_modificacion' => $documentos->max('updated_at'),
        ];
    }

    public function create()
    {
        //$categorias = Categoria::all();
        $categorias = Categoria::orderBy('nombre_categoria', 'asc')->get();

        //$usuarios = User::all(); // Obtener todos los usuarios
        $usuarios = User::habilitados()
            ->where('id', '!=', auth()->id())
            ->orderBy('email', 'asc')
            ->get();

        return view('documentos.create', compact('categorias', 'usuarios'));
    }

    public function store(Request $request)
    {

        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'archivo' => $this->reglasArchivoDocumento(),
            'id_categoria' => 'required|exists:categorias,id',
            'permisos' => 'array'
        ]);

        $file = $request->file('archivo');
        $path = $this->guardarArchivoDocumento($file);
        $estado = $request->has('sin_aprobacion') ? 'registro' : 'pendiente de aprobación';
    
        $documento = Documento::create([
            'titulo' => $validated['titulo'],
            'path' => $path,
            'contenido' => $request->input('contenido'),
            'estado' => $estado,
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
            foreach ($validated['permisos'] as $userId => $permisos) {
                if ((int) $userId === (int) auth()->id()) {
                    continue;
                }

                // Evitar permisos a usuarios deshabilitados
                $user = User::habilitados()->find($userId);

                if (!$user) {
                    continue;
                }

                DocumentoPermiso::updateOrCreate(
                    ['documento_id' => $documento->id, 'user_id' => $userId],
                    [
                        'puede_leer' => isset($permisos['puede_leer']),
                        'puede_escribir' => isset($permisos['puede_escribir']),
                        'puede_aprobar' => isset($permisos['puede_aprobar']),
                        'puede_eliminar' => isset($permisos['puede_eliminar']),
                    ]
                );

                if (isset($permisos['puede_aprobar']) && $permisos['puede_aprobar']) {
                    $user->notify(new DocumentoPendienteAprobacion($documento));
                }
            }
        }

        // Envia correo a el/los aprobador/es para avisar que tienen un doc pendiente de aprobar
            
        return redirect()->route('documentos.index')->with('success', 'Documento creado exitosamente.');
    }
        
    public function show($id)
    {
        // 👉 Eager loading para evitar N+1 al usar relaciones en la vista
        $documento = Documento::with([
            'categoria',
            'creador',
            'ultimaModificacion',
            'aprobador',
            'historial.categoria',
            'historial.creador',
            'historial.ultimaModificacion',
            'historial.aprobador',
            'ejecucionesRecordatorio.recordatorio',
            'ejecucionesRecordatorio.resueltoPor',
        ])->findOrFail($id);

        if (!$documento->puedeLeer(auth()->user())) {
            return redirect()
                ->to(url()->previous() ?: route('documentos.index'))
                ->with('swal', [
                    'icon'  => 'error',
                    'title' => 'Acceso denegado',
                    'text'  => 'No tienes permiso para leer este documento.'
                ]);
        }

        $bucket = config('filesystems.disks.s3.bucket');
        $region = config('filesystems.disks.s3.region');
        $baseUrl = "https://{$bucket}.s3.{$region}.amazonaws.com/";
        $fileUrl = $baseUrl . $documento->path;
        $fileExtension = pathinfo($documento->path, PATHINFO_EXTENSION);
        $revisionesCumplidas = $documento->ejecucionesRecordatorio
            ->where('estado', 'resuelto')
            ->sortByDesc('fecha_resolucion');

        // Una versión restaurada continúa existiendo en el historial, pero no debe
        // mostrarse como anterior mientras su número sea el de la versión activa.
        $documento->setRelation(
            'historial',
            $documento->historial
                ->reject(function ($versionHistorial) use ($documento) {
                    return (int) $versionHistorial->version === (int) $documento->version;
                })
                ->unique(function ($versionHistorial) {
                    return (int) $versionHistorial->version;
                })
                ->values()
        );

        return view('documentos.showlocal', compact('documento', 'fileUrl', 'fileExtension', 'revisionesCumplidas'));
    }

    public function aprobar(Request $request, $id)
    {
        $documento = Documento::with('ultimaModificacion')->findOrFail($id);

        if (!$documento->puedeAprobar(auth()->user())) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'No tienes permiso para aprobar este documento.',
                ], 403);
            }

            return redirect()
                ->to(url()->previous() ?: route('documentos.index'))
                ->with('swal', [
                    'icon'  => 'error',
                    'title' => 'Acceso denegado',
                    'text'  => 'No tienes permiso para aprobar este documento.'
                ]);
        }

        // Aprobar
        $documento->estado = 'aprobado';
        $documento->fecha_aprobacion = now();
        $documento->id_usr_aprobador = auth()->id();
        $documento->ultima_version_aprobada = $documento->version; // si usás este campo
        $documento->save();

        Log::info('Documento aprobado', [
            'doc_id'         => $documento->id,
            'aprobador_id'   => auth()->id(),
            'ultimo_editor'  => $documento->ultimaModificacion?->id, // asumimos presente
            'notificar_flag' => $request->boolean('notificar_autor'),
        ]);

        // Notificar al último modificador (simple)
        if ($request->boolean('notificar_autor')) {
            $ultimoEditor = $documento->ultimaModificacion; // asumimos que existe

            if ($ultimoEditor->id !== auth()->id()) {
                $ultimoEditor->notify(new DocumentoAprobado($documento, auth()->user()));
                Log::info('Notificación enviada al último editor', [
                    'doc_id' => $documento->id,
                    'user_id'=> $ultimoEditor->id,
                ]);
            } else {
                Log::info('No se envía notificación (aprobador == último editor)', [
                    'doc_id' => $documento->id,
                    'user_id'=> $ultimoEditor->id,
                ]);
            }
        } else {
            Log::info('No se envía notificación (checkbox no tildado)', [
                'doc_id' => $documento->id,
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'El documento ha sido aprobado.',
                'estado' => $documento->estado,
                'aprobador' => auth()->user()->name,
                'fecha_aprobacion' => (string) $documento->fecha_aprobacion,
            ]);
        }

        return back()->with('success', 'El documento ha sido aprobado.');
    }
    

    public function download($id)
    {
        // Obtén el documento desde la base de datos
        $documento = Documento::findOrFail($id);

        if (!$documento->puedeEscribir(auth()->user())) {
            return redirect()
                ->to(url()->previous() ?: route('documentos.index'))
                ->with('swal', [
                    'icon'  => 'error',
                    'title' => 'Acceso denegado',
                    'text'  => 'No tienes permiso para descargar/modificar este documento.'
                ]);
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
            return redirect()
                ->to(url()->previous() ?: route('documentos.index'))
                ->with('swal', [
                    'icon'  => 'error',
                    'title' => 'Acceso denegado',
                    'text'  => 'No tienes permiso para modificar o revertir este documento.'
                ]);
        }

        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'id_categoria' => 'required|exists:categorias,id',
            'permisos' => 'array'
        ]);

        // === Manejo de "No requiere aprobación" ===
        $sinAprobacion = $request->has('sin_aprobacion');

        // Determinar el nuevo estado en base al checkbox
        // - Si NO requiere aprobación => 'registro'
        // - Si requiere aprobación:
        //     * si ya estaba 'pendiente de aprobación', lo dejamos como está
        $nuevoEstado = $documento->estado; // default: mantener

        if ($sinAprobacion) {
            $nuevoEstado = 'registro';
        } else {
            if ($documento->estado === 'registro') {
                $nuevoEstado = 'pendiente de aprobación';
            }
            // si ya estaba en 'pendiente de aprobación' o 'aprobado', se mantiene
        }

        // Actualiza cabecera
        $documento->titulo = $validated['titulo'];
        $documento->id_categoria = $validated['id_categoria'];
        $documento->id_usr_ultima_modif = auth()->id();
        $documento->estado = $nuevoEstado;

        // Si ahora NO requiere aprobación, limpiamos datos de aprobación previos
        if ($nuevoEstado === 'registro') {
            $documento->id_usr_aprobador = null;   // <-- ajusta nombre si difiere
            $documento->fecha_aprobacion = null;   // <-- ajusta nombre si difiere
        }

        $documento->save();

        // === Gestionar permisos ===
        DocumentoPermiso::where('documento_id', $documento->id)->delete();

        // Dueño siempre con todos los permisos
        DocumentoPermiso::updateOrCreate(
            ['documento_id' => $documento->id, 'user_id' => $documento->id_usr_creador],
            [
                'puede_leer' => true,
                'puede_escribir' => true,
                'puede_aprobar' => true,
                'puede_eliminar' => true,
            ]
        );

        // Asignación para el resto
        foreach ($request->input('permisos', []) as $userId => $permisos) {
            if ((int) $userId === (int) $documento->id_usr_creador) {
                continue;
            }

            $user = User::habilitados()->find($userId);

            if (!$user) {
                continue;
            }
            
            DocumentoPermiso::updateOrCreate(
                ['documento_id' => $documento->id, 'user_id' => $userId],
                [
                    'puede_leer' => isset($permisos['puede_leer']),
                    'puede_escribir' => isset($permisos['puede_escribir']),
                    'puede_aprobar' => isset($permisos['puede_aprobar']),
                    'puede_eliminar' => isset($permisos['puede_eliminar']),
                ]
            );

            // Avisamos solo si efectivamente está pendiente de aprobación
            if (
                isset($permisos['puede_aprobar']) &&
                $permisos['puede_aprobar'] &&
                $documento->estado === 'pendiente de aprobación'
            ) {
                $user = User::find($userId);
                if ($user) {
                    $user->notify(new DocumentoPendienteAprobacion($documento));
                }
            }
        }

        return redirect()->route('documentos.edit', $documento->id)->with('success', 'Documento actualizado exitosamente.');

    }

    
    //Versionado
    public function AddVersion(Request $request, $id)
    {
        $documento = Documento::findOrFail($id);
        
        if (!$documento->puedeEscribir(auth()->user())) {
            return redirect()
                ->to(url()->previous() ?: route('documentos.index'))
                ->with('swal', [
                    'icon'  => 'error',
                    'title' => 'Acceso denegado',
                    'text'  => 'No tienes permiso para modificar este documento.'
                ]);
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
            'nuevoArchivo' => $this->reglasArchivoDocumento(),
            'id_categoria' => 'required|exists:categorias,id',
            'contenidoActualizado' => 'required|string', // Agregar validación para el contenido actualizado
        ]);

        // Subir el nuevo archivo a S3 si se proporciona
        if ($request->hasFile('nuevoArchivo')) {
            $path = $this->guardarArchivoDocumento($request->file('nuevoArchivo'));
            $documento->path = $path;
        }
        //dd($request->file('nuevoArchivo'));

        // Asignar todos los campos excepto el archivo y el contenido
        $documento->fill($request->except('nuevoArchivo','contenidoActualizado'));

        // Actualizar el contenido del documento con el valor de 'contenidoActualizado'
        $documento->contenido = $request->input('contenidoActualizado');

        $maxVersion = HistorialDocumento::where('id_documento', $documento->id)
                       ->max('version');
        $documento->version = $maxVersion + 1;
        $documento->estado = "pendiente de aprobación";
        $documento->id_usr_ultima_modif = auth()->id();
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
        $historial->id_categoria = $documento->id_categoria;
        $historial->id_usr_creador = $documento->id_usr_creador;
        $historial->id_usr_ultima_modif = $documento->id_usr_ultima_modif;
        $historial->id_usr_aprobador = $documento->id_usr_aprobador;
        $historial->fecha_aprobacion = $documento->fecha_aprobacion;
        $historial->version = $documento->version;
        //$historial->created_at = now();
        $historial->save();
    }
    
    public function revertToVersion($documentoId, $versionId)
    {
        try {
            return DB::transaction(function () use ($documentoId, $versionId) {
                Log::info('Revertir versión', ['doc' => $documentoId, 'hist' => $versionId, 'user' => auth()->id()]);

                $documento = Documento::findOrFail($documentoId);

                if (!$documento->puedeEscribir(auth()->user())) {
                    return redirect()
                        ->to(url()->previous() ?: route('documentos.index'))
                        ->with('swal', [
                            'icon'  => 'error',
                            'title' => 'Acceso denegado',
                            'text'  => 'No tienes permiso para modificar o revertir este documento.'
                        ]);
                }


                $historialDocumento = HistorialDocumento::where('id', $versionId)
                    ->where('id_documento', $documento->id)
                    ->firstOrFail();

                // Si la versión actual no está ya en historial, archivarla
                $existeEnHistorial = HistorialDocumento::where('id_documento', $documento->id)
                    ->where('version', $documento->version)
                    ->exists();
                if (!$existeEnHistorial) {
                    $this->archiveCurrentVersion($documento);
                }

                // ¿El documento actual está configurado como "sin aprobación"?
                $sinAprobacionActual = ($documento->estado === 'registro');

                // Restaurar campos principales desde el historial
                $documento->path                = $historialDocumento->path;
                $documento->titulo              = $historialDocumento->titulo;
                $documento->contenido           = $historialDocumento->contenido;
                $documento->id_categoria        = $historialDocumento->id_categoria ?? $documento->id_categoria;

                // Mantener el creador original del documento
                // (no lo sobreescribas con el del historial)
                // $documento->id_usr_creador   = $documento->id_usr_creador;

                $documento->id_usr_ultima_modif = auth()->id();
                $documento->id_usr_aprobador    = null;
                $documento->fecha_aprobacion    = null;
                $documento->version             = $historialDocumento->version;

                // Estado según “requiere/no requiere aprobación”
                $documento->estado = $sinAprobacionActual
                    ? 'registro'
                    : 'pendiente de aprobación';

                $documento->save();

                return redirect()
                    ->route('documentos.show', $documento->id)
                    ->with('success', 'Se revirtió el documento a la versión seleccionada.');
            });
        } catch (\Throwable $e) {
            Log::error('RevertToVersion ERROR', [
                'doc' => $documentoId,
                'hist' => $versionId,
                'user' => optional(auth()->user())->id,
                'msg' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'No se pudo revertir: ' . $e->getMessage());
        }
    }


    // Editar
    public function edit(Request $request, $id)
    {
        $documento = Documento::findOrFail($id);
        $categorias = Categoria::all();
        //$usuarios = User::orderBy('email', 'asc')->get();
        $usuarios = User::habilitados()
            ->where('id', '!=', $documento->id_usr_creador)
            ->orderBy('email', 'asc')
            ->get();
        $usuariosRecordatorio = User::habilitados()
            ->orderBy('email', 'asc')
            ->get();

        $recordatorioEnEdicion = null;

        if ($request->filled('edit_recordatorio')) {
            $recordatorioEnEdicion = $documento->recordatorios
                ->where('id', (int) $request->edit_recordatorio)
                ->first();
        }

        return view('documentos.edit', compact(
            'documento',
            'categorias',
            'usuarios',
            'usuariosRecordatorio',
            'recordatorioEnEdicion'
        ));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // Encuentra el documento por su ID
        $documento = Documento::findOrFail($id); 

        if (!$documento->puedeEliminar(auth()->user())) {
            return redirect()
                ->to(url()->previous() ?: route('documentos.index'))
                ->with('swal', [
                    'icon'  => 'error',
                    'title' => 'Acceso denegado',
                    'text'  => 'No tienes permiso para eliminar este documento.'
                ]);
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
            return redirect()
                ->to(url()->previous() ?: route('documentos.index'))
                ->with('swal', [
                    'icon'  => 'error',
                    'title' => 'Acceso denegado',
                    'text'  => 'No tienes permiso para realizar esta acción.'
                ]);
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
                return redirect()
                    ->route('documentos.show', $documentId)
                    ->with('swal', [
                        'icon' => 'warning',
                        'title' => 'Exportación no disponible',
                        'text' => 'No existe ninguna versión aprobada para el documento que intenta descargar.',
                    ]);
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
        $pdf->Cell(0, 10, iconv('UTF-8', 'windows-1252', 'Ultima Versión Aprobada'), 0, 1, 'C');

        $pdf->Cell(80, 10, 'Documento:', 1);
        $pdf->Cell(0, 10, $documento->titulo, 1, 1); // Nuevo línea después del título

        $pdf->Cell(80, 10, iconv('UTF-8', 'windows-1252','Versión:'), 1);
        $pdf->Cell(0, 10, $documento->version, 1, 1); // Versión

        $pdf->Cell(80, 10, iconv('UTF-8', 'windows-1252','Detalles de la versión:'), 1);
        $pdf->MultiCell(0, 10, iconv('UTF-8', 'windows-1252', $documento->contenido), 1);

        $pdf->Cell(80, 10, iconv('UTF-8', 'windows-1252','Categoría:'), 1);
        $pdf->Cell(0, 10, $documento->categoria->nombre_categoria, 1, 1); // Categoría

        $pdf->Cell(80, 10, 'Estado:', 1);
        $pdf->Cell(0, 10, iconv('UTF-8', 'windows-1252', $documento->estado), 1, 1); // Estado

        $pdf->Cell(80, 10, 'Aprobador:', 1);
        $pdf->Cell(0, 10, iconv('UTF-8', 'windows-1252', $documento->aprobador->name), 1, 1); // Aprobador

        $pdf->Cell(80, 10, iconv('UTF-8', 'windows-1252','Fecha aprobación:'), 1);
        $pdf->Cell(0, 10, iconv('UTF-8', 'windows-1252', $documento->fecha_aprobacion), 1, 1); // Fecha aprobacion

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

    /**
     * Valida la extension informada por el archivo sin depender de finfo.
     * Algunos DOCX validos se detectan como application/octet-stream y
     * Laravel les asigna erroneamente la extension .bin.
     */
    private function reglasArchivoDocumento(): array
    {
        return [
            'required',
            'file',
            'max:10240',
            function (string $attribute, $archivo, \Closure $fail): void {
                if (!$archivo instanceof UploadedFile) {
                    return;
                }

                $extension = strtolower($archivo->getClientOriginalExtension());

                if (!in_array($extension, self::EXTENSIONES_PERMITIDAS, true)) {
                    $fail('Solo se permiten archivos PDF, Word, Excel o PowerPoint.');
                }
            },
        ];
    }

    /**
     * Genera un nombre aleatorio y conserva la extension original validada.
     */
    private function guardarArchivoDocumento(UploadedFile $archivo): string
    {
        $extension = strtolower($archivo->getClientOriginalExtension());
        $nombreBase = pathinfo($archivo->hashName(), PATHINFO_FILENAME);

        return $archivo->storeAs(
            'documentos',
            $nombreBase . '.' . $extension,
            's3'
        );
    }


    public function rechazar(Request $request, $id)
    {
        $documento = Documento::findOrFail($id);

        if (!$documento->puedeAprobar(auth()->user())) {
            return redirect()
                ->to(url()->previous() ?: route('documentos.index'))
                ->with('swal', [
                    'icon'  => 'error',
                    'title' => 'Acceso denegado',
                    'text'  => 'No tienes permiso para rechazar este documento.'
                ]);
        }

        $request->validate([
            'comentarios' => 'required|string|max:1000',
        ]);

        // El documento sigue requiriendo aprobación hasta nueva versión
        $documento->estado = 'pendiente de aprobación';
        $documento->save();

        // === Destinatario: último editor (id_usr_ultima_modif) ===
        // Intentamos por relación; si no está cargada, buscamos por ID.
        $destinatario = $documento->ultimaModificacion ?? User::find($documento->id_usr_ultima_modif);

        // Fallback: si por alguna razón no existe, notificamos al creador
        if (!$destinatario) {
            $destinatario = $documento->creador ?? User::find($documento->id_usr_creador);
        }

        if ($destinatario) {
            $destinatario->notify(new DocumentoRechazado($documento, $request->comentarios));
        }

        return redirect()->back()->with('success', 'Documento rechazado y notificación enviada al último editor.');
    }

}
