@props(['user', 'size' => 40, 'label' => true])
@php
    $presets = [
        'brujula' => ['fa-compass', 'avatar-blue'],
        'hoja' => ['fa-leaf', 'avatar-green'],
        'montana' => ['fa-mountain-sun', 'avatar-violet'],
        'sol' => ['fa-sun', 'avatar-amber'],
        'estrella' => ['fa-star', 'avatar-rose'],
        'ondas' => ['fa-water', 'avatar-cyan'],
        'nube' => ['fa-cloud', 'avatar-slate'],
        'cohete' => ['fa-rocket', 'avatar-indigo'],
    ];
    $preset = $presets[$user->avatar_valor] ?? $presets['brujula'];
@endphp

<span class="user-avatar {{ $user->avatar_tipo === 'predefinido' ? $preset[1] : 'avatar-initials' }}"
    style="--avatar-size: {{ (int) $size }}px"
    @if($label) role="img" aria-label="Avatar de {{ $user->name }}" @else aria-hidden="true" @endif>
    @if($user->avatar_tipo === 'personalizado' && $user->avatar_foto_path)
        <img src="{{ route('profile.avatar', $user) }}" alt="{{ $label ? 'Foto de '.$user->name : '' }}">
    @elseif($user->avatar_tipo === 'predefinido')
        <i class="fa-solid {{ $preset[0] }}"></i>
    @else
        <span>{{ $user->iniciales() }}</span>
    @endif
</span>
