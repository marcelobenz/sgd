<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Editar Documento</title>
    <!-- Incluir CKEditor -->
    <script src="https://cdn.ckeditor.com/ckeditor5/35.0.1/classic/ckeditor.js"></script>
</head>
<body>
    <form action="{{ route('documentos.update', $documento->id) }}" method="POST">
        @csrf
        @method('PUT')
        <textarea name="content" id="editor">{!! $htmlContent !!}</textarea>
        <button type="submit">Guardar Cambios</button>
    </form>

    <script>
        ClassicEditor
            .create(document.querySelector('#editor'))
            .catch(error => {
                console.error(error);
            });
    </script>
</body>
</html>
