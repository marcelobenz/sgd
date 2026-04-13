<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentoPermiso extends Model
{
    protected $fillable = ['documento_id', 'user_id', 'puede_leer', 'puede_escribir', 'puede_aprobar','puede_eliminar'];

    public function documento()
    {
        return $this->belongsTo(Documento::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
