<?php

namespace App\Models\Iso;

use App\Models\Documento;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ProveedorSeleccion extends Model
{
    protected $table = 'iso_proveedor_selecciones';
    protected $fillable = ['proveedor_id', 'fecha', 'calificaciones', 'puntaje', 'resultado', 'conclusion', 'documento_id', 'enlace_externo', 'evaluado_por'];
    protected $casts = ['fecha' => 'date', 'calificaciones' => 'array', 'puntaje' => 'decimal:2'];
    public function proveedor() { return $this->belongsTo(Proveedor::class); }
    public function documento() { return $this->belongsTo(Documento::class); }
    public function evaluador() { return $this->belongsTo(User::class, 'evaluado_por'); }
}
