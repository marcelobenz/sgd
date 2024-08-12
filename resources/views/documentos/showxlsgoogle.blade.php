@extends('main')

@section('heading')
@endsection

@section('contenidoPrincipal')
<body>
    <div>
        <h3>{{ $documento->titulo }}</h3>
        <iframe src="https://docs.google.com/viewer?url={{ urlencode($fileUrl) }}&embedded=true" width="100%" height="500px" frameborder="0"></iframe>
    </div>
</body>

@endsection