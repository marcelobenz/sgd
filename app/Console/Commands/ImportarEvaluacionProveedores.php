<?php

namespace App\Console\Commands;

use App\Models\Iso\Periodo;
use App\Models\Iso\Proveedor;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

class ImportarEvaluacionProveedores extends Command
{
    protected $signature = 'iso:importar-proveedores {archivo : Ruta al archivo .xlsx} {--usuario= : ID del usuario que quedará como autor} {--dry-run : Analiza sin guardar cambios}';
    protected $description = 'Importa el historial de selección y evaluación de proveedores desde la planilla histórica';

    public function handle(): int
    {
        $archivo = realpath($this->argument('archivo'));
        if (!$archivo || !is_file($archivo)) {
            $this->error('No se encontró el archivo indicado.');
            return self::FAILURE;
        }
        $usuario = $this->option('usuario') ? User::find($this->option('usuario')) : (
            User::where('role', 'admin')->orderBy('id')->first()
            ?? User::whereHas('permisoIso', fn ($q) => $q->where('puede_administrar', true))->orderBy('id')->first()
            ?? User::where('habilitado', true)->orderBy('id')->first()
        );
        if (!$usuario) {
            $this->error('No existe un usuario administrador para atribuir la importación.');
            return self::FAILURE;
        }

        try {
            $hojas = $this->leerLibro($archivo);
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $totales = ['proveedores' => 0, 'selecciones' => 0, 'evaluaciones' => 0, 'fechas_ajustadas' => 0, 'omitidos' => 0];
        DB::beginTransaction();
        try {
            foreach ($hojas as $hoja) {
                if (!preg_match('/(20\d{2})/', $hoja['nombre'], $coincidencia)) continue;
                $anio = (int) $coincidencia[1];
                $periodo = Periodo::where('anio', $anio)->first();
                foreach ($hoja['filas'] as $fila) {
                    if (blank($fila['B'] ?? null) || blank($fila['E'] ?? null) || !$this->esFechaExcel($fila['A'] ?? null)) continue;
                    $proveedor = Proveedor::whereRaw('LOWER(nombre) = ?', [mb_strtolower(trim($fila['B']))])
                        ->whereRaw('LOWER(producto_servicio) = ?', [mb_strtolower(trim($fila['E']))])->first();
                    if (!$proveedor) {
                        $proveedor = Proveedor::create([
                            'codigo' => 'PR-' . str_pad((string) ((int) Proveedor::max('id') + 1), 4, '0', STR_PAD_LEFT),
                            'nombre' => trim($fila['B']), 'producto_servicio' => trim($fila['E']),
                            'area_responsable' => 'Compras y proveedores', 'fecha_alta' => $this->fecha($fila['A']),
                            'criticidad' => 'no_critico', 'periodicidad_meses' => 12, 'estado' => 'activo',
                            'observaciones' => 'Registro migrado desde Evaluacion Proveedores.xlsx. Criticidad y área responsable pendientes de revisión.',
                            'creado_por' => $usuario->id, 'actualizado_por' => $usuario->id,
                        ]);
                        $totales['proveedores']++;
                    }

                    if (!$proveedor->selecciones()->exists() && is_numeric($fila['L'] ?? null)) {
                        $puntaje = round((float) $fila['L'], 2);
                        $proveedor->selecciones()->create([
                            'fecha' => $this->fecha($fila['A']),
                            'calificaciones' => ['caracteristicas' => $this->numero($fila['G'] ?? null), 'recomendaciones' => $this->numero($fila['I'] ?? null), 'precio_condiciones' => $this->numero($fila['K'] ?? null)],
                            'puntaje' => $puntaje, 'resultado' => $this->clasificacion($puntaje),
                            'conclusion' => 'Selección histórica migrada desde la planilla de proveedores.', 'evaluado_por' => $usuario->id,
                        ]);
                        $totales['selecciones']++;
                    }

                    if (!is_numeric($fila['W'] ?? null) || !$this->esFechaExcel($fila['N'] ?? null)) {
                        $totales['omitidos']++;
                        continue;
                    }
                    $fechaOrigen = $this->fecha($fila['N']);
                    $fecha = $this->fechaEvaluacion($fila['N'], $anio);
                    if ($fecha !== $fechaOrigen) {
                        $historica = $proveedor->evaluaciones()->whereDate('fecha_evaluacion', $fechaOrigen)
                            ->where('conclusion', 'like', 'Evaluación histórica migrada%')->first();
                        if ($historica) {
                            $historica->update(['fecha_evaluacion' => $fecha]);
                            $totales['fechas_ajustadas']++;
                        }
                    }
                    if ($proveedor->evaluaciones()->whereDate('fecha_evaluacion', $fecha)->exists()) {
                        $totales['omitidos']++;
                        continue;
                    }
                    $puntaje = round((float) $fila['W'], 2);
                    $resultado = $this->clasificacion($puntaje);
                    $seguimiento = trim((string) ($fila['Y'] ?? ''));
                    $proveedor->evaluaciones()->create([
                        'periodo_id' => $periodo?->id, 'fecha_evaluacion' => $fecha,
                        'calificaciones' => ['precio_calidad' => $this->numero($fila['P'] ?? null), 'resolucion_imprevistos' => $this->numero($fila['R'] ?? null), 'calidad_producto' => $this->numero($fila['T'] ?? null), 'calidad_atencion' => $this->numero($fila['V'] ?? null)],
                        'puntaje' => $puntaje, 'resultado' => $resultado,
                        'decision' => $resultado === 'aprobado' ? 'continuar' : 'continuar_con_acciones',
                        'conclusion' => 'Evaluación histórica migrada desde la planilla correspondiente a ' . $anio . '.',
                        'justificacion' => $seguimiento ?: null,
                        'proxima_evaluacion' => $this->esFechaExcel($fila['Z'] ?? null) ? $this->fecha($fila['Z']) : null,
                        'requiere_analisis_riesgo' => false, 'evaluado_por' => $usuario->id,
                    ]);
                    $totales['evaluaciones']++;
                }
            }
            if ($this->option('dry-run')) DB::rollBack(); else DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        $this->table(['Resultado', 'Cantidad'], collect($totales)->map(fn ($cantidad, $nombre) => [$nombre, $cantidad]));
        $this->info($this->option('dry-run') ? 'Simulación finalizada: no se guardaron cambios.' : 'Importación finalizada. La planilla de origen no fue modificada.');
        return self::SUCCESS;
    }

    private function leerLibro(string $archivo): array
    {
        $zip = new ZipArchive();
        if ($zip->open($archivo) !== true) throw new RuntimeException('No se pudo abrir el archivo XLSX.');
        $compartidas = [];
        if (($xml = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
            $doc = simplexml_load_string($xml);
            $doc->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            foreach ($doc->xpath('//m:si') as $item) {
                $item->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
                $compartidas[] = trim(implode('', array_map(fn ($texto) => (string) $texto, $item->xpath('.//m:t'))));
            }
        }
        $libro = simplexml_load_string($zip->getFromName('xl/workbook.xml'));
        $libro->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $relaciones = simplexml_load_string($zip->getFromName('xl/_rels/workbook.xml.rels'));
        $relaciones->registerXPathNamespace('p', 'http://schemas.openxmlformats.org/package/2006/relationships');
        $mapa = [];
        foreach ($relaciones->xpath('//p:Relationship') as $rel) $mapa[(string) $rel['Id']] = (string) $rel['Target'];
        $hojas = [];
        foreach ($libro->xpath('//m:sheets/m:sheet') as $sheet) {
            $atributos = $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
            $destino = $mapa[(string) $atributos['id']] ?? null;
            if (!$destino) continue;
            $rutaHoja = str_starts_with($destino, '/') ? ltrim($destino, '/') : 'xl/' . ltrim(str_replace('worksheets/../', '', $destino), '/');
            $contenido = $zip->getFromName($rutaHoja);
            if ($contenido === false) continue;
            $xmlHoja = simplexml_load_string($contenido);
            $xmlHoja->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $filas = [];
            foreach ($xmlHoja->xpath('//m:sheetData/m:row') as $row) {
                $valores = [];
                $row->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
                foreach ($row->xpath('./m:c') as $cell) {
                    preg_match('/([A-Z]+)/', (string) $cell['r'], $columna);
                    $tipo = (string) $cell['t'];
                    $cell->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
                    $nodosValor = $cell->xpath('./m:v');
                    $valor = isset($nodosValor[0]) ? (string) $nodosValor[0] : null;
                    if ($tipo === 's') $valor = $compartidas[(int) $valor] ?? null;
                    elseif ($tipo === 'inlineStr') $valor = trim(implode('', array_map(fn ($texto) => (string) $texto, $cell->xpath('.//m:t'))));
                    $valores[$columna[1]] = $valor;
                }
                if ($valores) $filas[] = $valores;
            }
            $hojas[] = ['nombre' => (string) $sheet['name'], 'filas' => $filas];
        }
        $zip->close();
        return $hojas;
    }

    private function numero(mixed $valor): ?float { return is_numeric($valor) ? round((float) $valor, 2) : null; }
    private function clasificacion(float $puntaje): string { return $puntaje >= 7 ? 'aprobado' : ($puntaje >= 5 ? 'condicional' : 'no_aprobado'); }
    private function esFechaExcel(mixed $valor): bool { return is_numeric($valor) || (is_string($valor) && strtotime($valor) !== false); }
    private function fecha(mixed $valor): string
    {
        return is_numeric($valor) ? Carbon::create(1899, 12, 30)->addDays((int) floor((float) $valor))->toDateString() : Carbon::parse($valor)->toDateString();
    }
    private function fechaEvaluacion(mixed $valor, int $anioHoja): string
    {
        $fecha = Carbon::parse($this->fecha($valor));
        return $fecha->year > $anioHoja ? $fecha->subYear()->toDateString() : $fecha->toDateString();
    }
}
