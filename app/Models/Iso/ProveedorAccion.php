<?php

namespace App\Models\Iso;

use App\Models\Documento;
use App\Models\Iso\Concerns\RegistraCambiosIso;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ProveedorAccion extends Model
{
    use RegistraCambiosIso;
    protected $table = 'iso_proveedor_acciones';
    protected $fillable = ['evaluacion_id', 'obligatoria', 'descripcion', 'area_responsable', 'responsable_id', 'fecha_objetivo', 'estado', 'resultado', 'documento_id', 'enlace_externo', 'creado_por', 'actualizado_por', 'cerrada_en'];
    protected $casts = ['obligatoria' => 'boolean', 'fecha_objetivo' => 'date', 'cerrada_en' => 'datetime'];
    public function evaluacion() { return $this->belongsTo(ProveedorEvaluacion::class, 'evaluacion_id'); }
    public function responsable() { return $this->belongsTo(User::class, 'responsable_id'); }
    public function documento() { return $this->belongsTo(Documento::class); }
}
