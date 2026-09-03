<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'habilitado',
        'preferencias',
        'avatar_tipo',
        'avatar_valor',
        'avatar_foto_path',
        'fecha_ingreso',
        'area',
        'jefe_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'habilitado' => 'boolean',
        'preferencias' => 'array',
        'fecha_ingreso' => 'date',
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

    public function jefe()
    {
        return $this->belongsTo(self::class, 'jefe_id');
    }

    public function colaboradores()
    {
        return $this->hasMany(self::class, 'jefe_id');
    }

    public function solicitudesVacaciones()
    {
        return $this->hasMany(VacacionesSolicitud::class);
    }

    public function puedeGestionarVacaciones(): bool
    {
        return $this->isAdmin() || $this->colaboradores()->where('habilitado', true)->exists();
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

    public function preferenciasConDefaults(): array
    {
        $preferencias = array_merge([
            'inicio' => 'dashboard',
            'pendientes_filtro' => 'todos',
            'horizonte_dias' => 7,
            'densidad' => 'comoda',
        ], $this->preferencias ?? []);

        if ($preferencias['inicio'] === 'planificacion' && ! $this->puedeVerPlanificacion()) {
            $preferencias['inicio'] = 'dashboard';
        }

        if ($preferencias['pendientes_filtro'] === 'iso' && ! $this->puedeGestionarPlanificacion()) {
            $preferencias['pendientes_filtro'] = 'todos';
        }

        return $preferencias;
    }

    public function rutaInicioPreferida(): string
    {
        $inicio = $this->preferenciasConDefaults()['inicio'];

        return match ($inicio) {
            'pendientes' => '/mis-pendientes',
            'planificacion' => $this->puedeVerPlanificacion() ? '/planificacion' : '/dashboard',
            'documentos' => '/documentos',
            'recordatorios' => '/mis-recordatorios',
            'calendario' => '/recordatorios/calendario',
            default => '/dashboard',
        };
    }

    public function iniciales(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $parte) => Str::upper(Str::substr($parte, 0, 1)))
            ->implode('');
    }
}
