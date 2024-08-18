<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Documento extends Model
{
    protected $fillable = ['titulo', 'path', 'contenido', 'estado', 'id_categoria', 'id_usr_creador', 'id_usr_ultima_modif', 'fecha_aprobacion', 'updated_at'];

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'id_categoria');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'id_usr_creador');
    }

    public function ultimaModificacion()
    {
        return $this->belongsTo(User::class, 'id_usr_ultima_modif');
    }

    public function historial()
    {
        return $this->hasMany(HistorialDocumento::class, 'id_documento');
    }

    public function versiones()
    {
        return $this->hasMany(DocumentoVersiones::class);
    }

    public function activeVersion()
    {
        return $this->hasOne(DocumentoVersiones::class)->where('is_active', true);
    }

    public function permisos()
    {
        return $this->hasMany(DocumentoPermiso::class);
    }

    public function puedeLeer(User $user)
    {
        return $this->permisos()->where('user_id', $user->id)->where(function ($query) {
            $query->where('puede_leer', true)
                ->orWhere('puede_escribir', true)
                ->orWhere('puede_aprobar', true)
                ->orWhere('puede_eliminar', true);
        })->exists();
    }

    public function puedeEscribir(User $user)
    {
        return $this->permisos()->where('user_id', $user->id)->where('puede_escribir', true)->exists();
    }

    public function puedeAprobar(User $user)
    {
        return $this->permisos()->where('user_id', $user->id)->where('puede_aprobar', true)->exists();
    }

    public function puedeEliminar(User $user)
    {
        return $this->permisos()->where('user_id', $user->id)->where('puede_eliminar', true)->exists();
    }

}
