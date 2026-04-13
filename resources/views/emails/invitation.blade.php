<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Invitación al sistema</title>
</head>

<body style="font-family: Arial, sans-serif; color: #333;">
    <h2>Invitación al Docnisys</h2>
    <p>Hola,</p>

    <p>Se generó una invitación para que puedas registrarte en Docnisys.</p>
    <p>
        Para completar tu registro, hacé clic en el siguiente enlace:
    </p>
    <p>
        <a href="{{ $link }}">{{ $link }}</a>
    </p>
    <p>
        Esta invitación puede usarse una sola vez
        @if ($invitation->expires_at)
            y vence el {{ $invitation->expires_at->format('d/m/Y H:i') }}.
        @endif
    </p>
    <p>Saludos.</p>
</body>

</html>
