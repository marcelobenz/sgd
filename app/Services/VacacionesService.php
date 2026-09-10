<?php

namespace App\Services;

use App\Models\User;
use App\Models\VacacionesSaldo;
use App\Models\VacacionesSolicitud;
use Carbon\Carbon;
use Illuminate\Support\Collection;

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
        $saldos = $this->saldosAnteriores($user, $anio);
        $saldoAnterior = $saldos->sum('dias_disponibles');
        $saldoAnteriorAsignado = $saldos->sum('dias_pendientes');
        $usados = $this->diasImputados($user, $anio, null, 'aprobada');
        $reservados = $this->diasImputados($user, $anio, null, 'pendiente');
        $totalAsignado = $total === null ? null : $total + $saldoAnteriorAsignado;

        return [
            'total' => $total,
            'saldo_anterior' => (int) $saldoAnterior,
            'total_disponible' => $totalAsignado,
            'usados' => $usados,
            'reservados' => $reservados,
            'pendientes' => $totalAsignado === null ? null : max(0, $totalAsignado - $usados),
            'desglose' => $saldos,
        ];
    }

    public function saldosAnteriores(User $user, int $anio): Collection
    {
        return VacacionesSaldo::query()
            ->where('user_id', $user->id)
            ->where('anio', '<', $anio)
            ->orderBy('anio')
            ->get()
            ->map(function (VacacionesSaldo $saldo) use ($user, $anio) {
                $consumidos = $this->diasImputados($user, $anio, $saldo->anio);
                $saldo->dias_consumidos = $consumidos;
                $saldo->dias_disponibles = max(0, $saldo->dias_pendientes - $consumidos);

                return $saldo;
            });
    }

    public function asignarPeriodos(VacacionesSolicitud $solicitud): void
    {
        $user = $solicitud->usuario;
        $anio = $solicitud->fecha_desde->year;
        $restantes = $solicitud->dias;

        foreach ($this->saldosAnteriores($user, $anio)->where('dias_disponibles', '>', 0) as $saldo) {
            if ($restantes <= 0) {
                break;
            }

            $dias = min($restantes, $saldo->dias_disponibles);
            $solicitud->periodos()->create(['anio' => $saldo->anio, 'dias' => $dias]);
            $restantes -= $dias;
        }

        if ($restantes > 0) {
            $solicitud->periodos()->create(['anio' => $anio, 'dias' => $restantes]);
        }
    }

    private function diasImputados(User $user, int $anio, ?int $periodo, ?string $estado = null): int
    {
        $solicitudes = VacacionesSolicitud::query()
            ->where('user_id', $user->id)
            ->whereYear('fecha_desde', '<=', $anio)
            ->whereIn('estado', $estado ? [$estado] : ['pendiente', 'aprobada'])
            ->with('periodos')
            ->get();
        $total = 0;

        foreach ($solicitudes as $solicitud) {
            if ($solicitud->periodos->isNotEmpty()) {
                $total += (int) ($periodo === null
                    ? ($solicitud->fecha_desde->year === $anio ? $solicitud->periodos->sum('dias') : 0)
                    : $solicitud->periodos->where('anio', $periodo)->sum('dias'));
            } elseif ($periodo === null && $solicitud->fecha_desde->year === $anio) {
                // Compatibilidad con solicitudes creadas antes del desglose por períodos.
                $total += (int) $solicitud->dias;
            } elseif ($periodo !== null && $solicitud->fecha_desde->year === $periodo && $solicitud->fecha_desde->year === $anio) {
                $total += (int) $solicitud->dias;
            }
        }

        return $total;
    }

    public function diasEntre(Carbon $desde, Carbon $hasta): int
    {
        return $desde->diffInDays($hasta) + 1;
    }
}
