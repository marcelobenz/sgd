<?php

namespace App\Models;

//use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable 
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // Para evitar que se eliminen usuarios que existan en documento o historial
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

}
