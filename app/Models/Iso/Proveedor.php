<?php

namespace App\Models\Iso;

use App\Models\Documento;
use App\Models\Iso\Concerns\RegistraCambiosIso;
use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model
{
    use RegistraCambiosIso;

    protected $table = 'iso_proveedores';
    protected $fillable = ['codigo', 'nombre', 'producto_servicio', 'area_responsable', 'fecha_alta', 'criticidad', 'periodicidad_meses', 'estado', 'fecha_baja', 'motivo_baja', 'fecha_reactivacion', 'motivo_reactivacion', 'observaciones', 'documento_id', 'enlace_externo', 'creado_por', 'actualizado_por', 'importacion_lote'];
    protected $casts = ['fecha_alta' => 'date', 'fecha_baja' => 'datetime', 'fecha_reactivacion' => 'datetime'];

    public function documento() { return $this->belongsTo(Documento::class); }
    public function selecciones() { return $this->hasMany(ProveedorSeleccion::class); }
    public function evaluaciones() { return $this->hasMany(ProveedorEvaluacion::class); }
    public function ultimaEvaluacion() { return $this->hasOne(ProveedorEvaluacion::class)->ofMany(['fecha_evaluacion' => 'max', 'id' => 'max']); }
    public function cicloActivo() { return $this->hasOne(ProveedorEvaluacion::class)->where('estado_ciclo', '!=', 'cerrada')->ofMany(['fecha_evaluacion' => 'max', 'id' => 'max']); }

    public function tieneActividad(): bool
    {
        return $this->selecciones()->exists() || $this->evaluaciones()->exists();
    }
}
