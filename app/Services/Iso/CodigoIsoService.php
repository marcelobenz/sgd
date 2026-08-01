<?php

namespace App\Services\Iso;

use App\Models\Iso\Contexto;
use App\Models\Iso\Periodo;
use App\Models\Iso\Riesgo;

class CodigoIsoService
{
    public function siguienteContexto(Periodo $periodo, string $tipo): array
    {
        $numero = (int) Contexto::where('periodo_id', $periodo->id)->where('tipo', $tipo)->lockForUpdate()->max('numero') + 1;
        $prefijo = ['fortaleza' => 'F', 'debilidad' => 'D', 'oportunidad' => 'O', 'amenaza' => 'A'][$tipo];

        return [$numero, sprintf('%s-%d-%03d', $prefijo, $periodo->anio, $numero)];
    }

    public function siguienteRiesgo(Periodo $periodo): array
    {
        $numero = (int) Riesgo::where('periodo_id', $periodo->id)->lockForUpdate()->max('numero') + 1;

        return [$numero, sprintf('RO-%d-%03d', $periodo->anio, $numero)];
    }
}
