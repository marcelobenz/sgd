<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\DocumentoRecordatorio;

class User extends Authenticatable 
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'habilitado',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'habilitado' => 'boolean',
    ];

    public static function boot()
    {
        parent::boot();
    
        static::deleting(function ($user) {
            if ($user->documentosCreados()->exists() || $user->documentosModificados()->exists() || $user->historialDocumentos()->exists()) {
                throw new \Exception('No se puede eliminar este usuario porque tiene documentos o registros de historial asociados.');
            }
        });
    }

    public function permisosDocumentos()
    {
        return $this->hasMany(DocumentoPermiso::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function scopeHabilitados($query)
    {
        return $query->where('habilitado', true);
    }

    public function scopeDeshabilitados($query)
    {
        return $query->where('habilitado', false);
    }

    public function recordatoriosDocumento()
    {
        return $this->belongsToMany(
            DocumentoRecordatorio::class,
            'documento_recordatorio_user',
            'user_id',
            'documento_recordatorio_id'
        )->withTimestamps();
    }

    public function permisoIso()
    {
        return $this->hasOne(\App\Models\Iso\UsuarioPermiso::class, 'user_id');
    }

    public function puedeVerPlanificacion(): bool
    {
        $permiso = $this->relationLoaded('permisoIso') ? $this->permisoIso : $this->permisoIso()->first();
        return $this->isAdmin() || (bool) ($permiso?->puede_ver || $permiso?->puede_gestionar || $permiso?->puede_administrar);
    }

    public function puedeGestionarPlanificacion(): bool
    {
        $permiso = $this->relationLoaded('permisoIso') ? $this->permisoIso : $this->permisoIso()->first();
        return $this->isAdmin() || (bool) ($permiso?->puede_gestionar || $permiso?->puede_administrar);
    }

    public function puedeAdministrarPlanificacion(): bool
    {
        $permiso = $this->relationLoaded('permisoIso') ? $this->permisoIso : $this->permisoIso()->first();
        return $this->isAdmin() || (bool) $permiso?->puede_administrar;
    }

}
