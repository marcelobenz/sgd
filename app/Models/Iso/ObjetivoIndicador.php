<?php

namespace App\Models\Iso;

use App\Models\Iso\Concerns\RegistraCambiosIso;
use Illuminate\Database\Eloquent\Model;

class ObjetivoIndicador extends Model
{
    use RegistraCambiosIso;

    protected $table = 'iso_objetivo_indicadores';
    protected $fillable = ['objetivo_id', 'nombre', 'metodo_calculo', 'unidad', 'fuente', 'frecuencia', 'agregacion', 'comparador', 'meta', 'meta_hasta', 'tolerancia', 'linea_base', 'principal', 'activo', 'creado_por', 'actualizado_por'];
    protected $casts = ['meta' => 'decimal:4', 'meta_hasta' => 'decimal:4', 'tolerancia' => 'decimal:4', 'linea_base' => 'decimal:4', 'principal' => 'boolean', 'activo' => 'boolean'];

    public function objetivo() { return $this->belongsTo(Objetivo::class); }
    public function mediciones() { return $this->hasMany(ObjetivoMedicion::class, 'indicador_id'); }
}
