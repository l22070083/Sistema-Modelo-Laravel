<?php
namespace Tests\Feature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminAccountManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_removes_accounts_preserving_history_and_preventing_reactivation(): void
    {
        $admin = User::factory()->create(['rol_id' => 1]);
        foreach ([3 => 'alumnos', 2 => 'coordinadores'] as $role => $path) {
            $user = User::factory()->create(['rol_id' => $role]);
            $this->actingAs($admin)->get('/'.$path)->assertOk()->assertSee('Eliminar')->assertSee($role === 3 ? 'Crear alumno' : 'Crear coordinador')->assertSee('Actualizar');
            $this->delete('/'.$path.'/'.$user->id)->assertRedirect('/'.$path);
            $this->assertDatabaseHas('user', ['id' => $user->id, 'status' => -1]);
            $this->assertNull(User::find($user->id));
            $this->assertDatabaseHas('auditoria_sistema', ['evento' => 'ELIMINACION_CUENTA', 'actor_id' => $admin->id]);
            $this->get('/'.$path)->assertDontSee($user->username);
            $this->post('/'.$path.'/'.$user->id.'/estado', ['status' => 10])->assertNotFound();
            $this->post('/logout');
            $this->post('/login', ['username' => $user->username, 'password' => 'ClaveModelo!2026'])->assertSessionHasErrors('username');
        }
    }

    public function test_only_admin_can_delete_and_routes_enforce_role_of_target(): void
    {
        $admin = User::factory()->create(['rol_id' => 1]);
        $student = User::factory()->create();
        $coord = User::factory()->create(['rol_id' => 2]);
        foreach ([$student, $coord] as $actor) {
            foreach (['alumnos' => $student, 'coordinadores' => $coord] as $path => $target) {
                $this->actingAs($actor)->delete('/'.$path.'/'.$target->id)->assertForbidden();
            }
        }
        $this->actingAs($admin)->delete('/alumnos/'.$coord->id)->assertNotFound();
        $this->delete('/coordinadores/'.$student->id)->assertNotFound();
        $this->delete('/alumnos/'.$admin->id)->assertNotFound();
    }

    public function test_removing_coordinator_releases_groups_and_revokes_permissions(): void
    {
        $admin = User::factory()->create(['rol_id' => 1]);
        $coord = User::factory()->create(['rol_id' => 2]);
        $group = DB::table('grupo')->insertGetId(['nombre' => 'Grupo de prueba', 'estado' => 1, 'coordinador_id' => $coord->id]);
        DB::table('coordinador_permiso')->insert(['coordinador_id' => $coord->id, 'seccion' => 'personales', 'puede_ver' => 1, 'puede_editar' => 1, 'otorgado_por' => $admin->id, 'updated_at' => now()]);
        $this->actingAs($admin)->delete('/coordinadores/'.$coord->id)->assertRedirect('/coordinadores');
        $this->assertDatabaseHas('grupo', ['id' => $group, 'coordinador_id' => null]);
        $this->assertDatabaseHas('coordinador_permiso', ['coordinador_id' => $coord->id, 'puede_ver' => 0, 'puede_editar' => 0]);
    }

    public function test_admin_updates_student_account_fields_and_password(): void
    {
        $admin = User::factory()->create(['rol_id' => 1]);
        $student = User::factory()->create();
        $degree = DB::table('licenciatura')->insertGetId(['nombre' => 'Prueba', 'estado' => 1]);
        $this->actingAs($admin)->get('/alumnos/'.$student->id.'/editar')->assertOk()->assertSee('Nueva contraseña');
        $this->post('/alumnos/'.$student->id.'/editar', ['nombre' => 'Nombre actualizado', 'apellidos' => 'Apellido actualizado', 'username' => 'actualizado', 'email' => 'actualizado@example.test', 'matricula' => '123', 'licenciatura_id' => $degree, 'password' => 'ClaveNueva!2026', 'password_confirmation' => 'ClaveNueva!2026', 'rol_id' => 1, 'status' => -1])->assertRedirect('/alumnos/'.$student->id);
        $student->refresh();
        $this->assertSame('actualizado', $student->username);
        $this->assertSame('Nombre actualizado', $student->nombre);
        $this->assertSame(3, $student->rol_id);
        $this->assertSame(10, $student->status);
        $this->assertTrue(password_verify('ClaveNueva!2026', $student->password_hash));
        $this->assertDatabaseHas('auditoria_sistema', ['evento' => 'ACTUALIZACION_ALUMNO', 'alumno_id' => $student->id]);
    }
}