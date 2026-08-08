<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfilePreferencesTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_choose_start_page_preferences_and_preset_avatar(): void
    {
        $user = User::factory()->create(['habilitado' => true]);

        $this->actingAs($user)->post(route('profile.update'), $this->payload([
            'inicio' => 'pendientes',
            'pendientes_filtro' => 'documentos',
            'horizonte_dias' => 15,
            'densidad' => 'compacta',
            'avatar_tipo' => 'predefinido',
            'avatar_preset' => 'hoja',
        ]))->assertSessionHasNoErrors()->assertRedirect(route('profile.show'));

        $user->refresh();
        $this->assertSame('predefinido', $user->avatar_tipo);
        $this->assertSame('hoja', $user->avatar_valor);
        $this->assertSame('pendientes', $user->preferencias['inicio']);
        $this->assertSame(15, $user->preferencias['horizonte_dias']);
        $this->assertSame('compacta', $user->preferencias['densidad']);

        $this->post('/logout');
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/mis-pendientes');
    }

    public function test_start_page_preference_overrides_a_previous_intended_url(): void
    {
        $user = User::factory()->create([
            'habilitado' => true,
            'preferencias' => [
                'inicio' => 'pendientes',
                'pendientes_filtro' => 'todos',
                'horizonte_dias' => 7,
                'densidad' => 'comoda',
            ],
        ]);

        $this->withSession(['url.intended' => '/dashboard'])
            ->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/mis-pendientes');
    }

    public function test_user_without_iso_permission_cannot_choose_iso_start_page(): void
    {
        $user = User::factory()->create(['habilitado' => true]);

        $this->actingAs($user)->post(route('profile.update'), $this->payload([
            'inicio' => 'planificacion',
        ]))->assertSessionHasErrors('inicio');

        $this->assertNull($user->fresh()->preferencias);
    }

    public function test_user_can_upload_and_view_private_profile_photo(): void
    {
        Storage::fake('s3');
        $user = User::factory()->create(['habilitado' => true]);

        $this->actingAs($user)->post(route('profile.update'), $this->payload([
            'avatar_tipo' => 'personalizado',
            'avatar_archivo' => UploadedFile::fake()->image('perfil.jpg', 256, 256),
        ]))->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame('personalizado', $user->avatar_tipo);
        Storage::disk('s3')->assertExists($user->avatar_foto_path);

        $this->actingAs($user)->get(route('profile.avatar', $user))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_uploaded_photo_can_be_reselected_and_a_new_upload_replaces_the_only_photo(): void
    {
        Storage::fake('s3');
        $user = User::factory()->create(['habilitado' => true]);

        $this->actingAs($user)->post(route('profile.update'), $this->payload([
            'avatar_tipo' => 'personalizado',
            'avatar_archivo' => UploadedFile::fake()->image('primera.jpg', 256, 256),
        ]))->assertSessionHasNoErrors();

        $primeraFoto = $user->fresh()->avatar_foto_path;
        Storage::disk('s3')->assertExists($primeraFoto);

        $this->actingAs($user)->post(route('profile.update'), $this->payload([
            'avatar_tipo' => 'iniciales',
        ]))->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame('iniciales', $user->avatar_tipo);
        $this->assertSame($primeraFoto, $user->avatar_foto_path);
        Storage::disk('s3')->assertExists($primeraFoto);

        $this->actingAs($user)->post(route('profile.update'), $this->payload([
            'avatar_tipo' => 'personalizado',
        ]))->assertSessionHasNoErrors();

        $this->assertSame('personalizado', $user->fresh()->avatar_tipo);
        $this->assertSame($primeraFoto, $user->fresh()->avatar_foto_path);

        $this->actingAs($user)->post(route('profile.update'), $this->payload([
            'avatar_tipo' => 'personalizado',
            'avatar_archivo' => UploadedFile::fake()->image('segunda.png', 300, 300),
        ]))->assertSessionHasNoErrors();

        $segundaFoto = $user->fresh()->avatar_foto_path;
        $this->assertNotSame($primeraFoto, $segundaFoto);
        Storage::disk('s3')->assertMissing($primeraFoto);
        Storage::disk('s3')->assertExists($segundaFoto);
        $this->assertCount(1, Storage::disk('s3')->allFiles("usuarios/{$user->id}/avatar"));
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Usuario de prueba',
            'email' => 'usuario@example.test',
            'inicio' => 'dashboard',
            'pendientes_filtro' => 'todos',
            'horizonte_dias' => 7,
            'densidad' => 'comoda',
            'avatar_tipo' => 'iniciales',
        ], $overrides);
    }
}
