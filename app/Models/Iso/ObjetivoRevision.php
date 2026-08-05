<?php

namespace App\Models\Iso;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ObjetivoRevision extends Model
{
    protected $table = 'iso_objetivo_revisiones';
    protected $fillable = ['objetivo_id', 'tipo', 'fecha_vigencia', 'motivo', 'valores_anteriores', 'valores_nuevos', 'realizada_por'];
    protected $casts = ['fecha_vigencia' => 'date', 'valores_anteriores' => 'array', 'valores_nuevos' => 'array'];

    public function objetivo() { return $this->belongsTo(Objetivo::class); }
    public function realizadaPor() { return $this->belongsTo(User::class, 'realizada_por'); }
}
