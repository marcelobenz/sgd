<?php

namespace App\Models\Iso\Concerns;

use App\Models\Iso\HistorialCambio;

trait RegistraCambiosIso
{
    protected static function bootRegistraCambiosIso(): void
    {
        static::created(function ($model) {
            HistorialCambio::create([
                'entidad_tipo' => $model->getMorphClass(),
                'entidad_id' => $model->getKey(),
                'evento' => 'creado',
                'valores_nuevos' => $model->getAttributes(),
                'user_id' => auth()->id(),
            ]);
        });

        static::updated(function ($model) {
            HistorialCambio::create([
                'entidad_tipo' => $model->getMorphClass(),
                'entidad_id' => $model->getKey(),
                'evento' => 'actualizado',
                'valores_anteriores' => array_intersect_key($model->getOriginal(), $model->getChanges()),
                'valores_nuevos' => $model->getChanges(),
                'user_id' => auth()->id(),
            ]);
        });
    }
}
