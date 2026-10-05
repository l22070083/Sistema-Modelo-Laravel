<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MigrationAccessTest extends TestCase
{
    use RefreshDatabase;

    private function user(int $role, int $status = 10): User
    {
        $id = bin2hex(random_bytes(5));
        $user = new User;
        $user->forceFill(['nombre' => 'Prueba', 'username' => $id, 'email' => $id.'@example.com', 'rol_id' => $role, 'status' => $status,
            'password_hash' => password_hash('ClaveModelo!2026', PASSWORD_BCRYPT), 'auth_key' => bin2hex(random_bytes(16))])->save();

        return $user;
    }

    public function test_login_accepts_yii_hash_and_rejects_pending_account(): void
    {
        $pending = $this->user(3, 5);
        $this->post('/login', ['username' => $pending->username, 'password' => 'ClaveModelo!2026'])->assertSessionHasErrors('username');
        $this->assertGuest();
        $active = $this->user(1);
        $this->post('/login', ['username' => ' '.$active->username.' ', 'password' => 'ClaveModelo!2026'])->assertRedirect('/panel');
        $this->assertAuthenticatedAs($active);
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_only_admin_can_create_coordinators_and_role_is_fixed(): void
    {
        $data = ['nombre' => 'Nuevo', 'username' => 'coordinador-nuevo', 'email' => 'nuevo@example.com', 'password' => 'ClaveModelo!2026', 'password_confirmation' => 'ClaveModelo!2026', 'rol_id' => 1, 'status' => 0];
        foreach ([2, 3] as $role) {
            $this->actingAs($this->user($role))->get('/coordinadores')->assertForbidden();
            $this->post('/coordinadores', $data)->assertForbidden();
        }
        $this->actingAs($this->user(1))->post('/coordinadores', $data)->assertRedirect();
        $created = User::where('username', 'coordinador-nuevo')->firstOrFail();
        $this->get('/coordinadores/'.$created->id)->assertOk();
        $this->assertDatabaseHas('user', ['username' => 'coordinador-nuevo', 'rol_id' => 2, 'status' => 10]);
    }

    public function test_notification_approval_individual_selected_and_all_pages(): void
    {
        Mail::fake();
        $actor = $this->user(2);
        $first = $this->user(3, 5);
        $second = $this->user(3, 5);
        $inactive = $this->user(3, 0);
        $coordinator = $this->user(2, 5);
        $this->actingAs($actor)->get('/notificaciones')->assertOk()->assertSee($first->email);
        $this->post('/notificaciones/alta', ['modo' => 'individual', 'seleccion' => [$first->id]])->assertRedirect('/notificaciones');
        $this->assertEquals(10, $first->fresh()->status);
        $this->assertEquals(5, $second->fresh()->status);
        $this->post('/notificaciones/alta', ['modo' => 'seleccionados', 'seleccion' => [$second->id, $inactive->id, $coordinator->id]])->assertRedirect('/notificaciones');
        $this->assertEquals(10, $second->fresh()->status);
        for ($i = 0; $i < 21; $i++) {
            $this->user(3, 5);
        }
        $this->post('/notificaciones/alta', ['modo' => 'todos'])->assertRedirect('/notificaciones');
        $this->assertEquals(0, User::pendientes()->count());
        $this->assertEquals(0, $inactive->fresh()->status);
        $this->assertEquals(5, $coordinator->fresh()->status);
        Mail::assertSentCount(23);
    }

    public function test_revoked_session_and_student_cannot_approve(): void
    {
        $student = $this->user(3);
        $this->actingAs($student)->get('/notificaciones')->assertForbidden();
        $this->post('/notificaciones/alta', ['modo' => 'todos'])->assertForbidden();
        $admin = $this->user(1);
        $this->actingAs($admin);
        $admin->status = 0;
        $admin->save();
        $this->get('/coordinadores')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_malformed_selection_and_failed_email_leave_account_pending(): void
    {
        $this->actingAs($this->user(1));
        $this->post('/notificaciones/alta', ['modo' => 'seleccionados'])->assertSessionHasErrors('seleccion');
        $this->post('/notificaciones/alta', ['modo' => 'seleccionados', 'seleccion' => ['invalid']])->assertSessionHasErrors('seleccion.0');
        $pending = $this->user(3, 5);
        Mail::shouldReceive('to')->once()->andReturnSelf();
        Mail::shouldReceive('send')->once()->andThrow(new \RuntimeException('Fallo SMTP de prueba'));
        $this->post('/notificaciones/alta', ['modo' => 'todos'])->assertSessionHas('error');
        $this->assertEquals(5, $pending->fresh()->status);
    }

    public function test_guest_is_redirected_and_login_page_renders(): void
    {
        $this->get('/login')->assertOk()->assertSee('Iniciar sesión');
        $this->get('/notificaciones')->assertRedirect('/login');
    }
}
