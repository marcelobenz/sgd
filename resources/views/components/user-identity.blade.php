@props(['user', 'fallback' => 'Sin asignar', 'size' => 28])

@if($user)
    <span class="user-inline-identity"><x-user-avatar :user="$user" :size="$size" /><span>{{ $user->name }}</span></span>
@else
    <span class="text-muted">{{ $fallback }}</span>
@endif
