<?php

namespace App\Services\Iso;

use App\Models\Iso\ObjetivoIndicador;

class ResultadoObjetivoService
{
    public function resultado(ObjetivoIndicador $indicador): ?float
    {
        $valores = $indicador->mediciones()->orderBy('fecha_medicion')->orderBy('id')->pluck('valor')->map(fn ($valor) => (float) $valor);
        if ($valores->isEmpty()) return null;
        if (in_array($indicador->agregacion, ['variacion', 'variacion_porcentual'], true) && $valores->count() < 2) return null;

        return match ($indicador->agregacion) {
            'promedio' => round($valores->avg(), 4),
            'suma' => round($valores->sum(), 4),
            'variacion' => round($valores->last() - $valores->first(), 4),
            'variacion_porcentual' => $valores->first() == 0.0 ? null : round((($valores->last() / $valores->first()) - 1) * 100, 4),
            default => round($valores->last(), 4),
        };
    }

    public function cumplimiento(ObjetivoIndicador $indicador, ?float $resultado = null): string
    {
        $resultado ??= $this->resultado($indicador);
        if ($resultado === null || $indicador->meta === null) return 'pendiente';

        $meta = (float) $indicador->meta;
        $metaHasta = $indicador->meta_hasta !== null ? (float) $indicador->meta_hasta : null;
        $cumple = match ($indicador->comparador) {
            'mayor' => $resultado > $meta,
            'menor' => $resultado < $meta,
            'menor_igual' => $resultado <= $meta,
            'igual' => abs($resultado - $meta) < 0.0001,
            'rango' => $metaHasta !== null && $resultado >= $meta && $resultado <= $metaHasta,
            default => $resultado >= $meta,
        };
        if ($cumple) return 'cumplido';

        $tolerancia = (float) ($indicador->tolerancia ?? 0);
        if ($tolerancia <= 0) return 'incumplido';

        $desvio = match ($indicador->comparador) {
            'mayor', 'mayor_igual' => $meta - $resultado,
            'menor', 'menor_igual' => $resultado - $meta,
            'igual' => abs($resultado - $meta),
            'rango' => $resultado < $meta ? $meta - $resultado : $resultado - (float) $metaHasta,
            default => INF,
        };

        return $desvio <= $tolerancia ? 'aceptable' : 'incumplido';
    }
}
