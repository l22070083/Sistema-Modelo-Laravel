<?php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_deactivate_and_reactivate_student_without_deleting_data(): void
    {
        $admin = User::factory()->create(['rol_id' => User::ADMIN]);
        $student = User::factory()->create();
        $key = $student->auth_key;
        $this->actingAs($admin)->get('/alumnos')->assertOk()->assertSee('Dar de baja');
        $this->post('/alumnos/'.$student->id.'/estado', ['status' => 0])->assertRedirect('/alumnos');
        $this->assertSame(0, $student->fresh()->status);
        $this->assertNotSame($key, $student->fresh()->auth_key);
        $this->assertDatabaseHas('auditoria_sistema', ['evento' => 'ESTADO_ALUMNO', 'alumno_id' => $student->id, 'actor_id' => $admin->id]);
        $this->actingAs($student->fresh())->get('/inicio')->assertRedirect('/login');
        $this->actingAs($admin)->get('/alumnos')->assertSee('Reactivar')->assertSee($student->nombre);
        $this->post('/alumnos/'.$student->id.'/estado', ['status' => 10])->assertRedirect('/alumnos');
        $this->assertSame(10, $student->fresh()->status);
    }

    public function test_student_status_is_admin_only_and_cannot_change_other_roles_or_pending_accounts(): void
    {
        $admin = User::factory()->create(['rol_id' => 1]);
        $coordinator = User::factory()->create(['rol_id' => 2]);
        $student = User::factory()->create();
        foreach ([$coordinator, $student] as $actor) {
            $this->actingAs($actor)->post('/alumnos/'.$student->id.'/estado', ['status' => 0])->assertForbidden();
        }
        $this->actingAs($admin)->post('/alumnos/'.$coordinator->id.'/estado', ['status' => 0])->assertNotFound();
        $this->post('/alumnos/'.$student->id.'/estado', ['status' => 5])->assertSessionHasErrors('status');
        $pending = User::factory()->create(['status' => 5]);
        $this->post('/alumnos/'.$pending->id.'/estado', ['status' => 10])->assertStatus(422);
        $this->get('/alumnos/'.$student->id.'/estado')->assertStatus(405);
        $this->assertSame(10, $student->fresh()->status);
        $this->get('/coordinadores')->assertOk()->assertSee('Dar de baja');
        $this->post('/coordinadores/'.$coordinator->id.'/estado', ['status' => 0])->assertRedirect();
        $this->assertSame(0, $coordinator->fresh()->status);
        $this->get('/coordinadores')->assertSee('Reactivar');
    }
}