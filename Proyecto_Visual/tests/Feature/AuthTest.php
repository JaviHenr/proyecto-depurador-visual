<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_login_page(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_root_redirects_to_login_when_guest(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/login');
    }

    public function test_profesor_can_authenticate_and_access_dashboard(): void
    {
        $user = User::factory()->create([
            'email' => 'profesor@depurador.test',
            'password' => bcrypt('profesor123'),
            'role' => 'profesor',
        ]);

        $response = $this->post('/login', [
            'email' => 'profesor@depurador.test',
            'password' => 'profesor123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/dashboard');

        $this->assertTrue($user->isProfesor());
        $this->assertFalse($user->isEstudiante());
    }

    public function test_estudiante_can_authenticate_and_access_dashboard(): void
    {
        $user = User::factory()->create([
            'email' => 'estudiante@depurador.test',
            'password' => bcrypt('estudiante123'),
            'role' => 'estudiante',
        ]);

        $response = $this->post('/login', [
            'email' => 'estudiante@depurador.test',
            'password' => 'estudiante123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/dashboard');

        $this->assertTrue($user->isEstudiante());
        $this->assertFalse($user->isProfesor());
    }

    public function test_user_cannot_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create([
            'email' => 'profesor@depurador.test',
            'password' => bcrypt('profesor123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'profesor@depurador.test',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/login');
    }

    public function test_guest_cannot_access_dashboard(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }
}
