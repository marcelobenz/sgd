<?php

namespace App\Services\Iso;

use App\Models\Iso\Periodo;

class PeriodoAbiertoService
{
    public function validar(Periodo $periodo, string $operacion = 'modificar esta planificación'): void
    {
        abort_if(
            $periodo->estado === 'cerrado',
            422,
            "El período {$periodo->anio} está cerrado. Reabrilo como borrador antes de {$operacion}."
        );
    }
}
