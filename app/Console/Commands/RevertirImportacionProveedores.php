<?php

namespace App\Console\Commands;

use App\Models\Iso\HistorialCambio;
use App\Models\Iso\Proveedor;
use App\Models\Iso\ProveedorEvaluacion;
use App\Models\Iso\ProveedorSeleccion;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RevertirImportacionProveedores extends Command
{
    protected $signature = 'iso:revertir-proveedores {lote : UUID informado por la importación} {--usuario= : ID del usuario que ejecuta la reversión} {--dry-run : Informa sin eliminar} {--confirmar= : Debe ser REVERTIR}';
    protected $description = 'Revierte exclusivamente los registros creados por un lote de importación de proveedores';

    public function handle(): int
    {
        $lote = $this->argument('lote');
        $importacion = DB::table('iso_proveedor_importaciones')->where('lote', $lote)->first();
        if (!$importacion || $importacion->estado !== 'importado') {
            $this->error('El lote no existe o ya fue revertido.');
            return self::FAILURE;
        }
        $usuario = $this->option('usuario') ? User::find($this->option('usuario')) : User::where('role', 'admin')->orderBy('id')->first();
        if (!$usuario) {
            $this->error('No existe un usuario válido para atribuir la reversión.');
            return self::FAILURE;
        }
        $evaluaciones = ProveedorEvaluacion::where('importacion_lote', $lote)->get();
        $selecciones = ProveedorSeleccion::where('importacion_lote', $lote)->get();
        $proveedores = Proveedor::where('importacion_lote', $lote)->get();

        $bloqueos = [];
        foreach ($evaluaciones as $evaluacion) {
            if ($evaluacion->acciones()->exists() || $evaluacion->riesgos()->exists() || $evaluacion->reevaluaciones()->where('importacion_lote', '!=', $lote)->exists()) {
                $bloqueos[] = "Evaluación {$evaluacion->id} tiene actividad posterior.";
            }
        }
        foreach ($proveedores as $proveedor) {
            if ($proveedor->selecciones()->where(fn ($q) => $q->whereNull('importacion_lote')->orWhere('importacion_lote', '!=', $lote))->exists()
                || $proveedor->evaluaciones()->where(fn ($q) => $q->whereNull('importacion_lote')->orWhere('importacion_lote', '!=', $lote))->exists()) {
                $bloqueos[] = "{$proveedor->codigo} tiene registros posteriores o ajenos al lote.";
            }
        }
        if ($bloqueos) {
            $this->error('La reversión fue bloqueada para evitar pérdida de información:');
            foreach ($bloqueos as $bloqueo) $this->line("- {$bloqueo}");
            return self::FAILURE;
        }

        $resumen = ['proveedores' => $proveedores->count(), 'selecciones' => $selecciones->count(), 'evaluaciones' => $evaluaciones->count()];
        $this->table(['Registro', 'Cantidad'], collect($resumen)->map(fn ($cantidad, $nombre) => [$nombre, $cantidad]));
        if ($this->option('dry-run')) {
            $this->info('Simulación finalizada: no se eliminaron registros.');
            return self::SUCCESS;
        }
        if ($this->option('confirmar') !== 'REVERTIR') {
            $this->error('Para ejecutar la reversión indicá --confirmar=REVERTIR.');
            return self::FAILURE;
        }

        DB::transaction(function () use ($lote, $usuario, $evaluaciones, $selecciones, $proveedores) {
            $evaluacionIds = $evaluaciones->pluck('id');
            $seleccionIds = $selecciones->pluck('id');
            $proveedorIds = $proveedores->pluck('id');
            ProveedorEvaluacion::whereIn('id', $evaluacionIds)->delete();
            ProveedorSeleccion::whereIn('id', $seleccionIds)->delete();
            Proveedor::whereIn('id', $proveedorIds)->delete();
            HistorialCambio::where(fn ($q) => $q
                ->where(fn ($s) => $s->where('entidad_tipo', Proveedor::class)->whereIn('entidad_id', $proveedorIds))
                ->orWhere(fn ($s) => $s->where('entidad_tipo', ProveedorEvaluacion::class)->whereIn('entidad_id', $evaluacionIds))
                ->orWhere(fn ($s) => $s->where('entidad_tipo', ProveedorSeleccion::class)->whereIn('entidad_id', $seleccionIds))
            )->delete();
            DB::table('iso_proveedor_importaciones')->where('lote', $lote)->update([
                'estado' => 'revertido', 'revertido_en' => now(), 'revertido_por' => $usuario->id, 'updated_at' => now(),
            ]);
        });
        $this->info("Lote {$lote} revertido correctamente.");
        return self::SUCCESS;
    }
}
