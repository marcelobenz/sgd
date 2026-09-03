<?php

namespace App\Services;

use App\Models\User;
use App\Models\VacacionesSolicitud;
use Carbon\Carbon;

class VacacionesService
{
    public function diasPorLey(User $user, int $anio): ?int
    {
        if (! $user->fecha_ingreso) {
            return null;
        }

        $fechaCorte = Carbon::create($anio, 12, 31);
        if ($user->fecha_ingreso->greaterThan($fechaCorte)) {
            return 0;
        }
        $antiguedad = $user->fecha_ingreso->diffInYears($fechaCorte);

        foreach (config('vacaciones.dias_por_antiguedad') as $tramo) {
            if ($tramo['hasta'] === null || $antiguedad <= $tramo['hasta']) {
                return $tramo['dias'];
            }
        }

        return 0;
    }

    public function resumen(User $user, int $anio): array
    {
        $total = $this->diasPorLey($user, $anio);
        $base = VacacionesSolicitud::query()
            ->where('user_id', $user->id)
            ->whereYear('fecha_desde', $anio);

        $usados = (clone $base)->where('estado', 'aprobada')->sum('dias');
        $reservados = (clone $base)->where('estado', 'pendiente')->sum('dias');

        return [
            'total' => $total,
            'usados' => (int) $usados,
            'reservados' => (int) $reservados,
            'pendientes' => $total === null ? null : max(0, $total - $usados),
        ];
    }

    public function diasEntre(Carbon $desde, Carbon $hasta): int
    {
        return $desde->diffInDays($hasta) + 1;
    }
}
