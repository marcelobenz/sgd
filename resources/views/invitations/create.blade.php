@extends('layouts.main')

@section('heading')
    <style>
        .invitation-wrapper {
            margin-top: 100px;
            padding: 20px;
        }

        .invitation-card {
            max-width: 850px;
            margin: 0 auto;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
            background: #fff;
        }

        .invitation-card .card-header {
            background-color: #f8f9fa;
            font-weight: 600;
            font-size: 1.2rem;
            border-bottom: 1px solid #dee2e6;
        }

        .invitation-link-box {
            background-color: #f8f9fa;
            border: 1px dashed #adb5bd;
            border-radius: 6px;
            padding: 10px;
            font-size: 0.95rem;
            word-break: break-all;
        }

        .btn-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
    </style>
@endsection

@section('contenidoPrincipal')
    <div class="container invitation-wrapper">
        <div class="invitation-card card">
            <div class="card-header">
                Gestión de invitaciones
            </div>

            <div class="card-body">
                <p class="text-muted">
                    Ingresá el correo electrónico de la persona que querés habilitar para registrarse en el sistema.
                    La invitación tendrá vigencia limitada y podrá utilizarse una sola vez.
                </p>

                @if (session('success'))
                    <div class="alert alert-success">
                        <strong>{{ session('success') }}</strong>
                    </div>
                @endif

                @if (session('invitation_link'))
                    <div class="form-group">
                        <label><strong>Enlace de invitación generado</strong></label>
                        <div class="invitation-link-box" onclick="seleccionarLink(this)">
                            {{ session('invitation_link') }}
                        </div>
                        <small class="form-text text-muted">
                            Hacé clic sobre el enlace para seleccionarlo completo y copiarlo.
                        </small>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <strong>Se encontraron errores:</strong>
                        <ul class="mb-0 mt-2">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('invitations.store') }}">
                    @csrf

                    <div class="form-group">
                        <label for="email">Email a invitar</label>
                        <input type="email" name="email" id="email" class="form-control" value="{{ old('email') }}"
                            placeholder="usuario@empresa.com" required>
                    </div>
                    <div class="form-group form-check">
                        <input type="checkbox" class="form-check-input" id="send_email" name="send_email" value="1"
                            {{ old('send_email') ? 'checked' : '' }}>
                        <label class="form-check-label" for="send_email">Enviar invitación por correo electrónico</label>
                    </div>
                    <div class="btn-actions mt-4">
                        <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                            Volver
                        </a>

                        <button type="submit" class="btn btn-primary">
                            Generar invitación
                        </button>
                    </div>
                </form>
                <hr class="my-4">

                <h5 class="mb-3">Invitaciones generadas</h5>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="thead-light">
                            <tr>
                                <th>Email</th>
                                <th>Creada</th>
                                <th>Vence</th>
                                <th>Estado</th>
                                <th>Link</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($invitations as $inv)
                                @php
                                    $estado = 'Pendiente';
                                    $badge = 'warning';

                                    if ($inv->used_at) {
                                        $estado = 'Usada';
                                        $badge = 'success';
                                    } elseif ($inv->revoked_at) {
                                        $estado = 'Revocada';
                                        $badge = 'danger';
                                    } elseif ($inv->expires_at && $inv->expires_at->isPast()) {
                                        $estado = 'Vencida';
                                        $badge = 'secondary';
                                    }
                                @endphp

                                <tr>
                                    <td>{{ $inv->email }}</td>
                                    <td>{{ $inv->created_at ? $inv->created_at->format('d/m/Y H:i') : '-' }}</td>
                                    <td>{{ $inv->expires_at ? $inv->expires_at->format('d/m/Y H:i') : '-' }}</td>
                                    <td>
                                        <span class="badge badge-{{ $badge }}">{{ $estado }}</span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                            onclick="copiarTexto('{{ route('register', ['token' => $inv->token]) }}')">
                                            Copiar link
                                        </button>
                                    </td>
                                    <td>
                                        @if ($inv->isPending())
                                            <div class="d-flex flex-column flex-md-row" style="gap: 6px;">
                                                <form method="POST" action="{{ route('invitations.resend', $inv->id) }}">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-success">
                                                        Reenviar
                                                    </button>
                                                </form>

                                                <form method="POST" action="{{ route('invitations.revoke', $inv->id) }}"
                                                    onsubmit="return confirm('¿Seguro que querés revocar esta invitación?');">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        Revocar
                                                    </button>
                                                </form>
                                            </div>
                                        @else
                                            <span class="text-muted">Sin acciones</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No hay invitaciones registradas.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-center mt-3">
                    {{ $invitations->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripting')
    <script>
        function seleccionarLink(elemento) {
            const range = document.createRange();
            range.selectNodeContents(elemento);

            const selection = window.getSelection();
            selection.removeAllRanges();
            selection.addRange(range);
        }
    </script>
    <script>
        function seleccionarLink(elemento) {
            const range = document.createRange();
            range.selectNodeContents(elemento);

            const selection = window.getSelection();
            selection.removeAllRanges();
            selection.addRange(range);
        }

        function copiarTexto(texto) {
            navigator.clipboard.writeText(texto).then(function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Link copiado',
                    text: 'El enlace de invitación se copió al portapapeles.'
                });
            }).catch(function() {
                Swal.fire({
                    icon: 'error',
                    title: 'No se pudo copiar',
                    text: 'Copialo manualmente.'
                });
            });
        }
    </script>
@endsection
