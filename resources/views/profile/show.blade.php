@extends('layouts.main')

@section('heading', 'Mi perfil')

@section('contenidoPrincipal')
@php
    $iconosAvatar = [
        'brujula' => ['fa-compass','avatar-blue','Brújula'], 'hoja' => ['fa-leaf','avatar-green','Hoja'],
        'montana' => ['fa-mountain-sun','avatar-violet','Montaña'], 'sol' => ['fa-sun','avatar-amber','Sol'],
        'estrella' => ['fa-star','avatar-rose','Estrella'], 'ondas' => ['fa-water','avatar-cyan','Ondas'],
        'nube' => ['fa-cloud','avatar-slate','Nube'], 'cohete' => ['fa-rocket','avatar-indigo','Cohete'],
    ];
    $avatarTipo = old('avatar_tipo', $user->avatar_tipo ?: 'iniciales');
    $avatarPreset = old('avatar_preset', $user->avatar_tipo === 'predefinido' ? $user->avatar_valor : 'brujula');
@endphp
<div class="profile-page">
    <header class="profile-hero">
        <x-user-avatar :user="$user" :size="88" />
        <div><span>Configuración personal</span><h1>Mi perfil</h1><p>Administrá tu identidad y adaptá el sistema a tu forma de trabajo.</p></div>
    </header>

    @if ($errors->any())
        <div class="alert alert-danger"><strong>No pudimos guardar algunos datos.</strong><ul class="mb-0 mt-2">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" id="profileForm">
        @csrf
        <div class="profile-grid">
            <section class="profile-card identity-card">
                <header><span class="card-icon"><i class="fa-solid fa-id-card"></i></span><div><h2>Datos personales</h2><p>Información utilizada para identificarte en el sistema.</p></div></header>
                <div class="profile-fields">
                    <div class="form-group"><label for="name">Nombre y apellido</label><input type="text" id="name" name="name" class="form-control" value="{{ old('name',$user->name) }}" required></div>
                    <div class="form-group"><label for="email">Correo electrónico</label><input type="email" id="email" name="email" class="form-control" value="{{ old('email',$user->email) }}" required></div>
                    <div class="form-row"><div class="form-group col-md-6"><label for="password">Nueva contraseña</label><input type="password" id="password" name="password" class="form-control" autocomplete="new-password"><small>Dejala vacía para conservar la actual.</small></div><div class="form-group col-md-6"><label for="password_confirmation">Confirmar contraseña</label><input type="password" id="password_confirmation" name="password_confirmation" class="form-control" autocomplete="new-password"></div></div>
                </div>
            </section>

            <section class="profile-card avatar-card">
                <header><span class="card-icon"><i class="fa-solid fa-user-circle"></i></span><div><h2>Imagen de perfil</h2><p>Visible para otros usuarios habilitados del sistema.</p></div></header>
                <div class="avatar-current"><x-user-avatar :user="$user" :size="74" /><div><strong>Imagen actual</strong><small>Se muestra en el menú, tu perfil y asignaciones relevantes.</small></div></div>
                <div class="avatar-options">
                    <label class="avatar-type-option"><input type="radio" name="avatar_tipo" value="iniciales" @checked($avatarTipo==='iniciales')><span class="avatar-choice-preview avatar-initials">{{ $user->iniciales() }}</span><span><strong>Usar mis iniciales</strong><small>Se genera automáticamente con tu nombre.</small></span></label>
                    <label class="avatar-type-option"><input type="radio" name="avatar_tipo" value="predefinido" @checked($avatarTipo==='predefinido')><span class="avatar-choice-preview avatar-blue"><i class="fa-solid fa-shapes"></i></span><span><strong>Elegir un avatar</strong><small>Seleccioná una imagen de la galería.</small></span></label>
                    <div class="preset-gallery" id="presetGallery">
                        @foreach($avatares as $avatar)
                            <label title="{{ $iconosAvatar[$avatar][2] }}"><input type="radio" name="avatar_preset" value="{{ $avatar }}" @checked($avatarPreset===$avatar)><span class="user-avatar {{ $iconosAvatar[$avatar][1] }}" style="--avatar-size:48px"><i class="fa-solid {{ $iconosAvatar[$avatar][0] }}"></i></span></label>
                        @endforeach
                    </div>
                    <label class="avatar-type-option"><input type="radio" name="avatar_tipo" value="personalizado" @checked($avatarTipo==='personalizado')><span class="avatar-choice-preview photo-preview"><img id="photoPreview" @if($user->avatar_foto_path) src="{{ route('profile.avatar',$user) }}" @endif alt="Vista previa"><i class="fa-solid fa-camera"></i></span><span><strong>{{ $user->avatar_foto_path ? 'Usar mi foto cargada' : 'Subir una foto' }}</strong><small>{{ $user->avatar_foto_path ? 'Podés volver a seleccionarla o reemplazarla.' : 'JPEG, PNG o WebP · máximo 5 MB.' }}</small></span></label>
                    <div class="custom-upload" id="customUpload"><label for="avatar_archivo" class="btn btn-outline-primary btn-sm"><i class="fa-solid fa-upload"></i> {{ $user->avatar_foto_path ? 'Reemplazar foto' : 'Seleccionar foto' }}</label><input id="avatar_archivo" name="avatar_archivo" type="file" accept="image/jpeg,image/png,image/webp" class="sr-only"><span id="fileName">{{ $user->avatar_foto_path ? 'Se conservará la foto actual' : 'Ningún archivo seleccionado' }}</span></div>
                </div>
            </section>

            <section class="profile-card preferences-card">
                <header><span class="card-icon"><i class="fa-solid fa-sliders"></i></span><div><h2>Preferencias de trabajo</h2><p>Elegí cómo querés comenzar y visualizar tus tareas.</p></div></header>
                <div class="preference-grid">
                    <div class="form-group"><label for="inicio">Página después del login</label><select class="form-control" id="inicio" name="inicio"><option value="dashboard" @selected(old('inicio',$preferencias['inicio'])==='dashboard')>Dashboard</option><option value="pendientes" @selected(old('inicio',$preferencias['inicio'])==='pendientes')>Mis pendientes</option>@if($user->puedeVerPlanificacion())<option value="planificacion" @selected(old('inicio',$preferencias['inicio'])==='planificacion')>Planificación ISO</option>@endif<option value="documentos" @selected(old('inicio',$preferencias['inicio'])==='documentos')>Documentos</option><option value="recordatorios" @selected(old('inicio',$preferencias['inicio'])==='recordatorios')>Recordatorios — lista</option><option value="calendario" @selected(old('inicio',$preferencias['inicio'])==='calendario')>Recordatorios — calendario</option></select><small>Si perdés acceso a un destino, volverás al dashboard.</small></div>
                    <div class="form-group"><label for="pendientes_filtro">Vista inicial de Mis pendientes</label><select class="form-control" id="pendientes_filtro" name="pendientes_filtro"><option value="todos" @selected(old('pendientes_filtro',$preferencias['pendientes_filtro'])==='todos')>Todos los pendientes</option><option value="urgentes" @selected(old('pendientes_filtro',$preferencias['pendientes_filtro'])==='urgentes')>Vencidos y próximos</option>@if($user->puedeGestionarPlanificacion())<option value="iso" @selected(old('pendientes_filtro',$preferencias['pendientes_filtro'])==='iso')>Sólo Planificación ISO</option>@endif<option value="documentos" @selected(old('pendientes_filtro',$preferencias['pendientes_filtro'])==='documentos')>Sólo documentos</option></select></div>
                    <div class="form-group"><label for="horizonte_dias">Horizonte de próximos vencimientos</label><select class="form-control" id="horizonte_dias" name="horizonte_dias">@foreach([7,15,30] as $dias)<option value="{{ $dias }}" @selected((int)old('horizonte_dias',$preferencias['horizonte_dias'])===$dias)>{{ $dias }} días</option>@endforeach</select><small>Se aplica al dashboard y a la bandeja.</small></div>
                    <div class="form-group"><label>Densidad de la bandeja</label><div class="density-options"><label><input type="radio" name="densidad" value="comoda" @checked(old('densidad',$preferencias['densidad'])==='comoda')><span><i class="fa-solid fa-grip-lines"></i> Cómoda</span></label><label><input type="radio" name="densidad" value="compacta" @checked(old('densidad',$preferencias['densidad'])==='compacta')><span><i class="fa-solid fa-bars"></i> Compacta</span></label></div></div>
                </div>
            </section>
        </div>
        <div class="profile-actions"><a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">Cancelar</a><button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Guardar perfil y preferencias</button></div>
    </form>
</div>
@endsection

@push('styles')
<style>
body{background:#f3f6fa}.profile-page{max-width:1250px;margin:0 auto;padding:10px 5px 35px;color:#172033}.profile-hero{display:flex;align-items:center;gap:18px;margin-bottom:20px;padding:22px 25px;background:linear-gradient(115deg,#122a49,#285d7d);border-radius:16px;color:#fff;box-shadow:0 12px 30px rgba(20,43,74,.16)}.profile-hero>div>span{text-transform:uppercase;letter-spacing:.12em;font-size:.68rem;font-weight:800;opacity:.72}.profile-hero h1{font-size:1.8rem;font-weight:800;margin:2px 0}.profile-hero p{margin:0;opacity:.76}.profile-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px}.profile-card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;box-shadow:0 5px 16px rgba(28,46,70,.04);overflow:hidden}.profile-card>header{display:flex;gap:12px;padding:20px 22px 16px;border-bottom:1px solid #edf1f5}.card-icon{width:39px;height:39px;display:grid;place-items:center;border-radius:10px;background:#eaf2fa;color:#2563a6}.profile-card h2{font-size:1.05rem;font-weight:800;margin:0}.profile-card header p{font-size:.78rem;color:#657286;margin:3px 0 0}.profile-fields,.avatar-options{padding:20px 22px}.profile-card label{font-size:.79rem;font-weight:700}.profile-card .form-control{border-color:#d7dee8;border-radius:8px}.profile-card small{display:block;color:#718096;font-size:.72rem;margin-top:4px}.avatar-current{display:flex;align-items:center;gap:14px;padding:17px 22px;background:#f8fafc;border-bottom:1px solid #edf1f5}.avatar-current>div{display:flex;flex-direction:column}.avatar-type-option{display:flex;align-items:center;gap:12px;border:1px solid #e2e8f0;border-radius:11px;padding:10px 12px;margin-bottom:9px;cursor:pointer}.avatar-type-option:has(input:checked){border-color:#3c79b5;background:#f2f7fc;box-shadow:0 0 0 1px #3c79b5}.avatar-type-option>input{position:absolute;opacity:0}.avatar-type-option>span:last-child{display:flex;flex-direction:column}.avatar-choice-preview{width:44px;height:44px;flex:0 0 44px;border-radius:50%;display:grid;place-items:center;color:#fff;font-weight:800;overflow:hidden}.photo-preview{background:#e8edf3;color:#758399;position:relative}.photo-preview img{width:100%;height:100%;object-fit:cover;position:absolute}.photo-preview img:not([src]){display:none}.preset-gallery{display:grid;grid-template-columns:repeat(8,1fr);gap:8px;padding:5px 8px 15px 38px}.preset-gallery label{cursor:pointer;margin:0}.preset-gallery input{position:absolute;opacity:0}.preset-gallery input:checked+span{outline:3px solid #fff;box-shadow:0 0 0 3px #2563a6}.custom-upload{display:flex;align-items:center;gap:10px;padding:3px 0 14px 56px;color:#718096;font-size:.76rem}.preferences-card{grid-column:1/-1}.preference-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:5px 22px;padding:20px 22px}.density-options{display:grid;grid-template-columns:1fr 1fr;gap:8px}.density-options input{position:absolute;opacity:0}.density-options span{display:block;text-align:center;border:1px solid #d7dee8;border-radius:8px;padding:9px;cursor:pointer;color:#5b6879}.density-options input:checked+span{border-color:#2563a6;background:#edf4fb;color:#2563a6}.profile-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:18px}.profile-actions .btn{border-radius:8px;padding:9px 16px;font-weight:700}
@media(max-width:900px){.profile-grid{grid-template-columns:1fr}.preferences-card{grid-column:auto}.preference-grid{grid-template-columns:1fr}}@media(max-width:575px){.profile-hero{padding:19px}.profile-hero .user-avatar{--avatar-size:64px!important}.profile-hero h1{font-size:1.5rem}.preset-gallery{grid-template-columns:repeat(4,1fr);padding-left:0}.custom-upload{padding-left:0}.profile-actions{flex-direction:column-reverse}.profile-actions .btn{width:100%}}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded',()=>{
    const types=[...document.querySelectorAll('input[name="avatar_tipo"]')];
    const gallery=document.getElementById('presetGallery');
    const upload=document.getElementById('customUpload');
    const file=document.getElementById('avatar_archivo');
    const preview=document.getElementById('photoPreview');
    const fileName=document.getElementById('fileName');
    const refresh=()=>{const value=types.find(item=>item.checked)?.value;gallery.style.display=value==='predefinido'?'grid':'none';upload.style.display=value==='personalizado'?'flex':'none'};
    types.forEach(item=>item.addEventListener('change',refresh));refresh();
    file.addEventListener('change',()=>{const selected=file.files[0];fileName.textContent=selected?selected.name:'Ningún archivo seleccionado';if(selected){preview.src=URL.createObjectURL(selected);types.find(item=>item.value==='personalizado').checked=true;refresh()}});
});
</script>
@endpush
