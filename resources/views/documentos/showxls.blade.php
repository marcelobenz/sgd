@extends('main')

@section('heading')
@endsection

@section('contenidoPrincipal')
<body>
    <h1>{{ $documento->titulo }}</h1>
    <table border="1">
        @foreach($data as $row)
            <tr>
                @foreach($row as $cell)
                    <td>{{ $cell }}</td>
                @endforeach
            </tr>
        @endforeach
    </table>

</body>

@endsection