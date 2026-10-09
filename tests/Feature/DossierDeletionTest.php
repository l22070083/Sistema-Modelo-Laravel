<?php

namespace Tests\Feature;

use App\Http\Controllers\DossierController;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DossierDeletionTest extends TestCase
{
    use RefreshDatabase;

    private function dossier(User $student, array $values = []): int
    {
        $id = DB::table('expediente_alumno')->insertGetId($values + ['user_id' => $student->id, 'nombres' => 'Alumno', 'apellidos' => 'Prueba', 'created_at' => now(), 'updated_at' => now(), 'bloqueado' => 0]);
        DB::table('expediente_historial')->insert(['expediente_id' => $id, 'user_id' => $student->id, 'accion' => 'REGISTRO_INICIAL', 'detalles' => 'Registro de prueba', 'fecha' => now()]);
        return $id;
    }

    private function payload(int $id): array
    {
        return ['motivo' => 'Solicitud de reiniciar el expediente del alumno.', 'version' => DossierController::fingerprint(DB::table('expediente_alumno')->find($id))];
    }

    public function test_admin_can_delete_even_protected_or_archived_dossiers_and_preserve_students_and_answers(): void
    {
        $admin = User::factory()->create(['rol_id' => User::ADMIN]);
        $other = User::factory()->create();
        $otherId = $this->dossier($other);
        $degree = DB::table('licenciatura')->insertGetId(['nombre' => 'Licenciatura de prueba', 'estado' => 1]);
        $survey = DB::table('encuesta')->insertGetId(['titulo' => 'Encuesta', 'tipo_test' => 'salud', 'estado' => 1]);
        $question = DB::table('pregunta')->insertGetId(['encuesta_id' => $survey, 'planteamiento' => 'Pregunta', 'status' => 1]);
        foreach ([[], ['bloqueado' => 1], ['bloqueado' => 1, 'archivado_at' => now(), 'archivado_por' => $admin->id]] as $state) {
            $student = User::factory()->create(['matricula' => '000123', 'licenciatura_id' => $degree]);
            $id = $this->dossier($student, $state);
            DB::table('respuesta_alumno')->insert(['user_id' => $student->id, 'pregunta_id' => $question, 'respuesta' => 'Si', 'fecha_registro' => now()]);
            $beforeUser = (array) DB::table('user')->find($student->id);
            $this->actingAs($admin)->get('/expedientes/'.$id)->assertOk()->assertSee('Eliminar expediente definitivamente');
            $this->delete('/expedientes/'.$id, $this->payload($id))->assertRedirect('/expedientes')->assertSessionHas('success');
            $this->assertDatabaseMissing('expediente_alumno', ['id' => $id]);
            $this->assertDatabaseMissing('expediente_historial', ['expediente_id' => $id]);
            $this->assertSame($beforeUser, (array) DB::table('user')->find($student->id));
            $this->assertDatabaseHas('respuesta_alumno', ['user_id' => $student->id, 'pregunta_id' => $question, 'respuesta' => 'Si']);
            $this->assertDatabaseHas('auditoria_sistema', ['actor_id' => $admin->id, 'alumno_id' => $student->id, 'evento' => 'ELIMINACION_EXPEDIENTE', 'motivo' => $this->payload($otherId)['motivo']]);
            $this->actingAs($student)->get('/mi-expediente')->assertOk()->assertSee('Aún no has llenado tu expediente.');
            $this->get('/mi-expediente/editar')->assertOk();
        }
        $this->assertDatabaseHas('expediente_alumno', ['id' => $otherId]);
        $this->assertDatabaseHas('expediente_historial', ['expediente_id' => $otherId]);
        $this->actingAs($admin)->get('/expedientes')->assertOk()->assertSee('#eliminar-expediente');
    }

    public function test_students_and_coordinators_cannot_delete_or_see_the_delete_control(): void
    {
        $student = User::factory()->create();
        $id = $this->dossier($student);
        $admin = User::factory()->create(['rol_id' => User::ADMIN]);
        $coordinator = User::factory()->create(['rol_id' => User::COORDINADOR]);
        DB::table('coordinador_permiso')->insert(['coordinador_id' => $coordinator->id, 'seccion' => 'personales', 'puede_ver' => 1, 'puede_editar' => 1, 'otorgado_por' => $admin->id, 'updated_at' => now()]);
        $this->actingAs($coordinator)->get('/expedientes/'.$id)->assertOk()->assertDontSee('Eliminar expediente definitivamente');
        $this->get('/expedientes')->assertOk()->assertDontSee('#eliminar-expediente');
        foreach ([$student, $coordinator] as $actor) {
            $this->actingAs($actor)->delete('/expedientes/'.$id, $this->payload($id))->assertForbidden();
        }
        $this->actingAs($student)->get('/mi-expediente')->assertOk()->assertDontSee('Eliminar expediente definitivamente');
        $this->assertDatabaseHas('expediente_alumno', ['id' => $id]);
        $this->assertDatabaseMissing('auditoria_sistema', ['evento' => 'ELIMINACION_EXPEDIENTE']);
    }

    public function test_deletion_requires_a_reason_and_current_version_and_rejects_missing_dossiers(): void
    {
        $id = $this->dossier(User::factory()->create());
        $payload = $this->payload($id);
        $this->actingAs(User::factory()->create(['rol_id' => User::ADMIN]));
        $this->deleteJson('/expedientes/'.$id, ['version' => $payload['version']])->assertUnprocessable()->assertJsonValidationErrors('motivo');
        $this->deleteJson('/expedientes/'.$id, ['motivo' => $payload['motivo']])->assertUnprocessable()->assertJsonValidationErrors('version');
        DB::table('expediente_alumno')->where('id', $id)->update(['nombres' => 'Actualizado por el alumno']);
        $this->delete('/expedientes/'.$id, $payload)->assertStatus(409);
        $this->delete('/expedientes/999999', $payload)->assertNotFound();
        $this->assertDatabaseHas('expediente_alumno', ['id' => $id, 'nombres' => 'Actualizado por el alumno']);
        $this->assertDatabaseHas('expediente_historial', ['expediente_id' => $id]);
        $this->assertDatabaseMissing('auditoria_sistema', ['evento' => 'ELIMINACION_EXPEDIENTE']);
    }

    public function test_failed_audit_keeps_the_dossier_and_its_history(): void
    {
        $id = $this->dossier(User::factory()->create());
        $payload = $this->payload($id);
        $this->actingAs(User::factory()->create(['rol_id' => User::ADMIN]));
        Schema::drop('auditoria_sistema');
        $this->withoutExceptionHandling();
        try {
            $this->delete('/expedientes/'.$id, $payload);
            $this->fail('La eliminación debe fallar si no se registra su auditoría.');
        } catch (QueryException $expected) {
            $this->assertDatabaseHas('expediente_alumno', ['id' => $id]);
            $this->assertDatabaseHas('expediente_historial', ['expediente_id' => $id]);
        }
    }
}
