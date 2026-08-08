<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class UserProfileController extends Controller
{
    public const AVATARES_PREDEFINIDOS = ['brujula', 'hoja', 'montana', 'sol', 'estrella', 'ondas', 'nube', 'cohete'];

    public function show(Request $request)
    {
        return view('profile.show', [
            'user' => $request->user(),
            'preferencias' => $request->user()->preferenciasConDefaults(),
            'avatares' => self::AVATARES_PREDEFINIDOS,
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', 'min:8'],
            'inicio' => ['required', Rule::in(['dashboard', 'pendientes', 'planificacion', 'documentos', 'recordatorios', 'calendario'])],
            'pendientes_filtro' => ['required', Rule::in(['todos', 'urgentes', 'iso', 'documentos'])],
            'horizonte_dias' => ['required', Rule::in([7, 15, 30])],
            'densidad' => ['required', Rule::in(['comoda', 'compacta'])],
            'avatar_tipo' => ['required', Rule::in(['iniciales', 'predefinido', 'personalizado'])],
            'avatar_preset' => ['nullable', Rule::in(self::AVATARES_PREDEFINIDOS)],
            'avatar_archivo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120', 'dimensions:min_width=128,min_height=128,max_width=5000,max_height=5000'],
        ], [
            'avatar_archivo.image' => 'El archivo debe ser una imagen válida.',
            'avatar_archivo.mimes' => 'La foto debe estar en formato JPEG, PNG o WebP.',
            'avatar_archivo.max' => 'La foto no puede superar los 5 MB.',
            'avatar_archivo.dimensions' => 'La foto debe medir entre 128 y 5000 píxeles por lado.',
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
        ]);

        if ($data['inicio'] === 'planificacion' && ! $user->puedeVerPlanificacion()) {
            throw ValidationException::withMessages(['inicio' => 'No tenés permiso para usar Planificación ISO como página de inicio.']);
        }

        if ($data['pendientes_filtro'] === 'iso' && ! $user->puedeGestionarPlanificacion()) {
            throw ValidationException::withMessages(['pendientes_filtro' => 'No tenés permiso para usar acciones ISO como filtro inicial.']);
        }

        if ($data['avatar_tipo'] === 'predefinido' && empty($data['avatar_preset'])) {
            throw ValidationException::withMessages(['avatar_preset' => 'Elegí uno de los avatares disponibles.']);
        }

        if ($data['avatar_tipo'] === 'personalizado' && ! $request->hasFile('avatar_archivo') && ! $user->avatar_foto_path) {
            throw ValidationException::withMessages(['avatar_archivo' => 'Seleccioná una foto para usar el avatar personalizado.']);
        }

        $fotoAnterior = $user->avatar_foto_path;
        $fotoNueva = null;
        $avatarValor = match ($data['avatar_tipo']) {
            'predefinido' => $data['avatar_preset'],
            default => null,
        };

        if ($request->hasFile('avatar_archivo')) {
            $archivo = $request->file('avatar_archivo');
            $nombre = Str::uuid().'.'.$archivo->extension();
            $fotoNueva = $archivo->storeAs("usuarios/{$user->id}/avatar", $nombre, 's3');

            if (! $fotoNueva) {
                throw ValidationException::withMessages(['avatar_archivo' => 'No se pudo almacenar la foto. Intentá nuevamente.']);
            }
        }

        $user->forceFill([
            'name' => $data['name'],
            'email' => $data['email'],
            'preferencias' => [
                'inicio' => $data['inicio'],
                'pendientes_filtro' => $data['pendientes_filtro'],
                'horizonte_dias' => (int) $data['horizonte_dias'],
                'densidad' => $data['densidad'],
            ],
            'avatar_tipo' => $data['avatar_tipo'],
            'avatar_valor' => $avatarValor,
            'avatar_foto_path' => $fotoNueva ?: $fotoAnterior,
        ]);

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        try {
            $user->save();
        } catch (Throwable $exception) {
            if ($fotoNueva) {
                Storage::disk('s3')->delete($fotoNueva);
            }

            throw $exception;
        }

        if ($fotoNueva && $fotoAnterior && $fotoAnterior !== $fotoNueva) {
            Storage::disk('s3')->delete($fotoAnterior);
        }

        return redirect()->route('profile.show')->with('success', 'Perfil y preferencias actualizados correctamente.');
    }

    public function avatar(User $user): Response
    {
        abort_unless($user->avatar_foto_path, 404);
        $disk = Storage::disk('s3');
        abort_unless($disk->exists($user->avatar_foto_path), 404);

        return response($disk->get($user->avatar_foto_path), 200, [
            'Content-Type' => $disk->mimeType($user->avatar_foto_path) ?: 'image/jpeg',
            'Cache-Control' => 'private, max-age=3600',
            'Content-Disposition' => 'inline',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
