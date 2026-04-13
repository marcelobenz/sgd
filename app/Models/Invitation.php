<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Invitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'token',
        'expires_at',
        'used_at',
        'revoked_at',
        'created_by',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function isValid(): bool
    {
        if ($this->used_at !== null) {
            return false;
        }

        if ($this->revoked_at !== null) {
            return false;
        }

        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    public function isPending(): bool
    {
        return $this->used_at === null
            && $this->revoked_at === null
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function status(): string
    {
        if ($this->used_at) {
            return 'Usada';
        }

        if ($this->revoked_at) {
            return 'Revocada';
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return 'Vencida';
        }

        return 'Pendiente';
    }
}