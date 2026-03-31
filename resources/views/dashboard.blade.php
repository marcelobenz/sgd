@extends('layouts.main')

@section('heading')
    Dashboard
@endsection

@section('contenidoPrincipal')
    <div class="container-fluid dashboard-wrapper">

        <div class="dashboard-header d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-0">Panel de control</h4>
                <small class="text-muted">Bienvenido, {{ auth()->user()->name }}</small>
            </div>

            <div>
                <a href="{{ route('documentos.index') }}" class="btn btn-outline-primary btn-sm mr-2">
                    <i class="fas fa-folder-open mr-1"></i> Documentos
                </a>
                <a href="{{ route('recordatorios.mis') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-bell mr-1"></i> Recordatorios
                </a>
            </div>
        </div>

        <div class="row no-gutters dashboard-main-row">

            {{-- LATERAL IZQUIERDO --}}
            <div class="col-lg-2 col-md-3 mb-3 mb-md-0 d-flex justify-content-start">
                <div class="stats-sidebar">
                    <div class="stat-card">
                        <div class="stat-label">Total</div>
                        <div class="stat-value">{{ $totalDocumentos }}</div>
                        <div class="stat-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-label">Aprobados</div>
                        <div class="stat-value">{{ $documentosAprobados }}</div>
                        <div class="stat-icon">
                            <i class="fas fa-check"></i>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-label">Pendientes</div>
                        <div class="stat-value">{{ $documentosPendientes }}</div>
                        <div class="stat-icon">
                            <i class="fas fa-hourglass-half"></i>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-label">Vencidos</div>
                        <div class="stat-value">{{ $recordatoriosVencidos }}</div>
                        <div class="stat-icon">
                            <i class="fas fa-exclamation"></i>
                        </div>
                    </div>
                </div>
            </div>

            {{-- CONTENIDO DERECHO --}}
            <div class="col-lg-10 col-md-9 dashboard-right-content">
                <div class="row align-items-stretch">

                    {{-- ACCIONES --}}
                    <div class="col-lg-3 col-md-6 mb-3 d-flex">
                        <div class="card dashboard-card top-card flex-fill">
                            <div class="card-header dashboard-card-header">
                                <i class="fas fa-bolt text-warning mr-2"></i> Acciones
                            </div>
                            <div class="card-body top-card-body">
                                @if ($documentosPendientesUsuario > 0)
                                    <a href="{{ route('documentos.index') }}" class="action-link">
                                        <span>Documentos a aprobar</span>
                                        <span class="badge badge-danger">{{ $documentosPendientesUsuario }}</span>
                                    </a>
                                @endif

                                @if ($recordatoriosVencidos > 0)
                                    <a href="{{ route('recordatorios.mis') }}" class="action-link">
                                        <span>Recordatorios vencidos</span>
                                        <span class="badge badge-warning">{{ $recordatoriosVencidos }}</span>
                                    </a>
                                @endif

                                @if ($documentosPendientesUsuario == 0 && $recordatoriosVencidos == 0)
                                    <div class="empty-card-message">
                                        Sin pendientes
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- GRAFICO --}}
                    <div class="col-lg-3 col-md-6 mb-3 d-flex">
                        <div class="card dashboard-card top-card flex-fill">
                            <div class="card-header dashboard-card-header">
                                <i class="fas fa-chart-pie text-info mr-2"></i> Documentos
                            </div>
                            <div class="card-body top-card-body chart-card-body">
                                <div class="chart-box">
                                    <canvas id="grafico"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ACTIVIDAD --}}
                    <div class="col-lg-3 col-md-6 mb-3 d-flex">
                        <div class="card dashboard-card top-card flex-fill">
                            <div class="card-header dashboard-card-header">
                                <i class="fas fa-clock text-secondary mr-2"></i> Actividad
                            </div>
                            <div class="card-body p-0 top-card-body list-card-body">
                                @forelse ($actividadReciente as $item)
                                    <div class="mini-list-item">
                                        <div class="mini-list-title">{{ $item['titulo'] }}</div>
                                        <div class="mini-list-subtitle">{{ $item['descripcion'] }}</div>
                                    </div>
                                @empty
                                    <div class="empty-card-message">
                                        Sin actividad
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    {{-- RECORDATORIOS --}}
                    <div class="col-lg-3 col-md-6 mb-3 d-flex">
                        <div class="card dashboard-card top-card flex-fill">
                            <div class="card-header dashboard-card-header">
                                <i class="fas fa-calendar-alt text-primary mr-2"></i> Recordatorios
                            </div>
                            <div class="card-body p-0 top-card-body list-card-body">
                                @forelse ($proximosRecordatorios as $r)
                                    <div class="mini-list-item">
                                        <div class="mini-list-title">{{ $r->nombre }}</div>
                                        <div class="mini-list-subtitle">{{ $r->documento->titulo ?? '' }}</div>
                                    </div>
                                @empty
                                    <div class="empty-card-message">
                                        Sin recordatorios
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    {{-- TABLA --}}
                    <div class="col-12">
                        <div class="card dashboard-card">
                            <div class="card-header dashboard-card-header">
                                <i class="fas fa-file-alt text-muted mr-2"></i> Últimos documentos
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm mb-0 dashboard-table">
                                        <thead>
                                            <tr>
                                                <th>Título</th>
                                                <th>Estado</th>
                                                <th class="text-center"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($ultimosDocumentos as $doc)
                                                <tr>
                                                    <td>{{ $doc->titulo }}</td>
                                                    <td>
                                                        @php
                                                            $estado = strtolower($doc->estado);
                                                            $estadoClass = 'secondary';

                                                            if ($estado === 'aprobado') {
                                                                $estadoClass = 'success';
                                                            } elseif ($estado === 'pendiente de aprobación') {
                                                                $estadoClass = 'warning';
                                                            }
                                                        @endphp

                                                        <span class="badge badge-{{ $estadoClass }}">
                                                            {{ $doc->estado }}
                                                        </span>
                                                    </td>
                                                    <td class="text-center">
                                                        <a href="{{ route('documentos.validaPermiso', [
                                                            'id' => $doc->id,
                                                            'ruta' => 'documentos.show',
                                                            'permiso' => 'puedeLeer',
                                                        ]) }}"
                                                            class="btn btn-sm btn-outline-primary" title="Ver">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="3" class="text-center text-muted py-3">
                                                        No hay documentos recientes
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
@endsection

@push('styles')
    <style>
        .dashboard-wrapper {
            padding: 12px 14px 20px 14px;
        }

        .dashboard-header {
            margin-top: 8px;
        }

        .dashboard-main-row {
            align-items: flex-start;
        }

        .dashboard-right-content {
            padding-left: 8px;
        }

        .stats-sidebar {
            width: 100%;
            max-width: 170px;
            border: 3px solid #4cae32;
            background: #fff;
        }

        .stat-card {
            min-height: 138px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 14px 10px;
            border-bottom: 3px solid #4cae32;
            text-align: center;
            position: relative;
            background: #fff;
        }

        .stat-card:last-child {
            border-bottom: none;
        }

        .stat-label {
            font-size: 0.95rem;
            color: #1f2d3d;
            margin-bottom: 10px;
            font-weight: 500;
        }

        .stat-value {
            font-size: 3rem;
            font-weight: 700;
            line-height: 1;
            color: #0b2545;
            margin-bottom: 12px;
        }

        .stat-icon {
            font-size: 1.9rem;
            color: #0b2545;
            line-height: 1;
        }

        .dashboard-card {
            border: 1px solid #dfe3e8;
            border-radius: 3px;
            box-shadow: none;
            background: #fff;
        }

        .dashboard-card-header {
            background: #f7f7f7;
            border-bottom: 1px solid #dfe3e8;
            font-weight: 500;
            font-size: 0.95rem;
            padding: 9px 12px;
        }

        .top-card {
            min-height: 295px;
        }

        .top-card-body {
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .chart-card-body {
            justify-content: center;
            align-items: center;
        }

        .list-card-body {
            overflow: hidden;
        }

        .empty-card-message {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            text-align: center;
            min-height: 120px;
        }

        .action-link {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            color: #007bff;
            text-decoration: none;
        }

        .action-link:hover {
            text-decoration: none;
            color: #0056b3;
        }

        .mini-list-item {
            padding: 8px 10px;
            border-bottom: 1px solid #eceff1;
        }

        .mini-list-item:last-child {
            border-bottom: none;
        }

        .mini-list-title {
            font-weight: 700;
            font-size: 0.95rem;
            color: #111;
            line-height: 1.2;
        }

        .mini-list-subtitle {
            font-size: 0.84rem;
            color: #6c757d;
            margin-top: 3px;
            line-height: 1.2;
        }

        .chart-box {
            position: relative;
            width: 100%;
            height: 180px;
        }

        .dashboard-table th,
        .dashboard-table td {
            vertical-align: middle;
            font-size: 0.9rem;
        }

        .dashboard-table thead th {
            background: #f8f9fa;
            border-top: none;
        }

        @media (max-width: 991.98px) {
            .stats-sidebar {
                max-width: 100%;
            }

            .stat-card {
                min-height: 105px;
            }

            .stat-value {
                font-size: 2.3rem;
            }

            .stat-icon {
                font-size: 1.5rem;
            }

            .dashboard-right-content {
                padding-left: 0;
            }

            .top-card {
                min-height: 260px;
            }

            .chart-box {
                height: 200px;
            }
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const canvas = document.getElementById('grafico');

            if (!canvas) {
                return;
            }

            const ctx = canvas.getContext('2d');

            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Aprobados', 'Pendientes', 'Registro'],
                    datasets: [{
                        data: [
                            {{ $documentosAprobados }},
                            {{ $documentosPendientes }},
                            {{ $documentosRegistro }}
                        ],
                        backgroundColor: ['#28a745', '#f0b400', '#6c757d'],
                        borderColor: '#ffffff',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '58%',
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                boxWidth: 12,
                                font: {
                                    size: 9
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
@endpush
