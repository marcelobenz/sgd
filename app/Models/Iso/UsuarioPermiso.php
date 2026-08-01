<?php

namespace App\Models\Iso;

use Illuminate\Database\Eloquent\Model;

class UsuarioPermiso extends Model
{
    protected $table = 'iso_usuario_permisos';
    protected $fillable = ['user_id', 'puede_ver', 'puede_gestionar', 'puede_administrar'];
    protected $casts = ['puede_ver' => 'boolean', 'puede_gestionar' => 'boolean', 'puede_administrar' => 'boolean'];
}
