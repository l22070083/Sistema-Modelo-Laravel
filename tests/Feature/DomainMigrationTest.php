<?php

namespace Tests\Feature;

use App\Http\Controllers\DossierController;
use App\Mail\AccountLink;
use App\Models\User;
use App\Services\MicrosoftIdentity;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DomainMigrationTest extends TestCase
{
    use RefreshDatabase;

    private int $degree;

    protected function setUp(): void
    {
        parent::setUp();
        $this->degree = DB::table('licenciatura')->insertGetId(['nombre' => 'Licenciatura de prueba', 'estado' => 1]);
    }

    private function student(): User
    {
        return User::factory()->create(['licenciatura_id' => $this->degree]);
    }

    private function admin(): User
    {
        return User::factory()->create(['rol_id' => 1]);
    }

    private function coordinator(): User
    {
        return User::factory()->create(['rol_id' => 2]);
    }

    private function dossier(User $student): array
    {
        $data = [];
        foreach (array_merge(...array_values(array_intersect_key(config('dossier.sections'), array_flip(['personales', 'cuestionario'])))) as $field) {
            $data[$field] = 'Dato de prueba';
        }
        foreach (array_keys($data) as $field) {
            if (preg_match('/^q[1-7]_.*(?<!detalle)$/', $field) || $field === 'q9_acomp_psicologico') {
                $data[$field] = 0;
            }
        }

        return array_merge($data, ['nombres' => $student->nombre, 'apellidos' => $student->apellidos, 'fecha_nacimiento' => '2000-01-02', 'estado_civil' => 'Soltero(a)',
            'licenciatura_id' => $this->degree, 'telefono' => '0123456789', 'contacto_emergencia_telefono' => '9876543210', 'apnp_tipo_sangre' => 'O', 'apnp_factor_rh' => 'Positivo (+)', 'q10_estado_emocional' => 'Favorable', 'q11_necesita_apoyo' => ['Ninguno']]);
    }

    private function createDossier(User $student): int
    {
        $this->actingAs($student)->post('/mi-expediente/editar', ['datos' => $this->dossier($student)])->assertRedirect('/mi-expediente');

        return DB::table('expediente_alumno')->where('user_id', $student->id)->value('id');
    }

    public function test_dossier_prefills_student_names_and_keeps_saved_or_posted_names(): void
    {
        $student = $this->student();
        $student->update(['nombre' => 'Ana María', 'apellidos' => 'López Pérez']);
        $form = $this->actingAs($student)->get('/mi-expediente/editar')->assertOk()->assertDontSee('datos[ocupacion]', false);
        $document = new \DOMDocument;
        @$document->loadHTML($form->getContent());
        $xpath = new \DOMXPath($document);
        $this->assertSame('Ana María', $xpath->query('//input[@id="nombres"]')->item(0)->getAttribute('value'));
        $this->assertSame('López Pérez', $xpath->query('//input[@id="apellidos"]')->item(0)->getAttribute('value'));
        $this->assertTrue($xpath->query('//input[@id="edad-calculada"]')->item(0)->hasAttribute('readonly'));
        foreach (['telefono', 'contacto_emergencia_telefono'] as $field) {
            $this->assertSame('[0-9]{10}', $xpath->query('//input[@id="'.$field.'"]')->item(0)->getAttribute('pattern'));
        }
        $id = $this->createDossier($student);
        DB::table('expediente_alumno')->where('id', $id)->update(['nombres' => 'Nombre guardado']);
        $this->get('/mi-expediente/editar')->assertOk()->assertSee('value="Nombre guardado"', false);
        $this->withSession(['_old_input' => ['datos' => ['nombres' => 'Nombre corregido']]])->get('/mi-expediente/editar')->assertOk()->assertSee('value="Nombre corregido"', false);
        $other = $this->student();
        $other->update(['nombre' => 'Estudiante destino', 'apellidos' => 'Apellido destino']);
        $this->withSession(['_old_input' => []])->actingAs($this->admin())->get('/alumnos/'.$other->id.'/expediente')->assertOk()->assertSee('value="Estudiante destino"', false)->assertSee('value="Apellido destino"', false);
        $this->assertFalse(Schema::hasColumn('expediente_alumno', 'ocupacion'));
    }

    public function test_dossier_requires_ten_digit_phones_and_calculates_age_from_birth_date(): void
    {
        $student = $this->student();
        $this->actingAs($student);
        foreach (['telefono', 'contacto_emergencia_telefono'] as $field) {
            foreach (['123456789', '12345678901', '+123456789', '12345 7890', 'abcdefghij'] as $phone) {
                $data = array_replace($this->dossier($student), [$field => $phone]);
                $this->post('/mi-expediente/editar', ['datos' => $data])->assertSessionHasErrors($field);
                $this->assertDatabaseMissing('expediente_alumno', ['user_id' => $student->id]);
            }
        }
        $this->travelTo(\Carbon\Carbon::parse('2026-10-08 12:00:00'));
        try {
            $data = array_replace($this->dossier($student), ['fecha_nacimiento' => '2000-10-09']);
            $this->post('/mi-expediente/editar', ['datos' => $data])->assertRedirect('/mi-expediente');
            $this->assertDatabaseHas('expediente_alumno', ['user_id' => $student->id, 'edad' => 25, 'telefono' => '0123456789']);
            $record = DB::table('expediente_alumno')->where('user_id', $student->id)->first();
            $data['fecha_nacimiento'] = '2000-10-08';
            $this->post('/mi-expediente/editar', ['datos' => $data, 'version' => DossierController::fingerprint($record)])->assertRedirect();
            $this->assertDatabaseHas('expediente_alumno', ['user_id' => $student->id, 'edad' => 26]);
            $data['ocupacion'] = 'Campo retirado';
            $this->post('/mi-expediente/editar', ['datos' => $data])->assertForbidden();
        } finally { $this->travelBack(); }
    }

    public function test_wizard_requires_explicit_answers_and_clears_details_that_no_longer_apply(): void
    {
        $student = $this->student();
        $student->update(['nombre' => 'Alumno de prueba', 'apellidos' => 'Vista previa']);
        $form = $this->actingAs($student)->get('/mi-expediente/editar')->assertOk()
            ->assertSee('¿Tienes alguna alergia diagnosticada?')->assertSee('Revisar y guardar')->assertDontSee('Responder encuesta de salud');
        $document = new \DOMDocument;
        @$document->loadHTML($form->getContent());
        $xpath = new \DOMXPath($document);
        $this->assertSame(0, $xpath->query('//input[@type="radio" and @checked]')->length);
        $this->assertSame(2, $xpath->query('//input[@name="datos[q1_cond_fisica]" and @required]')->length);
        if (getenv('DOSSIER_UX_PREVIEW') === '1') {
            file_put_contents(base_path('../dossier-wizard-preview.html'), $form->getContent());
        }
        $data = array_replace($this->dossier($student), ['q1_cond_fisica' => 1, 'q1_cond_fisica_detalle' => 'Detalle anterior', 'q11_necesita_apoyo' => ['Otro'], 'q11_necesita_apoyo_otro' => 'Apoyo anterior']);
        $this->post('/mi-expediente/editar', ['datos' => $data])->assertRedirect();
        $record = DB::table('expediente_alumno')->where('user_id', $student->id)->first();
        $data['q1_cond_fisica'] = 0;
        $data['q11_necesita_apoyo'] = ['Ninguno'];
        unset($data['q1_cond_fisica_detalle'], $data['q11_necesita_apoyo_otro']);
        $this->post('/mi-expediente/editar', ['datos' => $data, 'version' => DossierController::fingerprint($record)])->assertRedirect();
        $this->assertDatabaseHas('expediente_alumno', ['id' => $record->id, 'q1_cond_fisica' => 0, 'q1_cond_fisica_detalle' => null, 'q11_necesita_apoyo_otro' => null]);
    }

    public function test_dossier_form_and_pdf_keep_blood_in_personal_data_and_remove_antecedents(): void
    {
        $student = $this->student();
        $id = $this->createDossier($student);
        DB::table('expediente_alumno')->where('id', $id)->update(['app_alergias' => 'CONTENIDO_APP_RETIRADO', 'apnp_estilo_vida' => 'CONTENIDO_APNP_RETIRADO']);
        $response = $this->get('/mi-expediente')->assertOk()->assertSee('Tipo de Sangre')->assertSee('Factor RH')
            ->assertDontSee('Antecedentes Personales')->assertDontSee('Parámetros Clínicos Críticos')
            ->assertDontSee('CONTENIDO_APP_RETIRADO')->assertDontSee('CONTENIDO_APNP_RETIRADO');
        $form = $this->get('/mi-expediente/editar')->assertOk()->assertDontSee('app_alergias')->assertDontSee('apnp_estilo_vida');
        $document = new \DOMDocument;
        @$document->loadHTML($form->getContent());
        $xpath = new \DOMXPath($document);
        $this->assertSame(2, $xpath->query('//fieldset')->length);
        foreach (['apnp_tipo_sangre', 'apnp_factor_rh'] as $field) {
            $this->assertSame(1, $xpath->query('//fieldset[legend[contains(., "Datos personales")]]//select[@name="datos['.$field.']"]')->length);
        }
        $pdfSource = view('expediente-pdf', ['record' => $response->viewData('record'), 'sections' => $response->viewData('sections'), 'history' => collect()])->render();
        $this->assertStringContainsString('Datos personales y contacto', $pdfSource);
        $this->assertStringContainsString('Tipo de Sangre', $pdfSource);
        $this->assertStringNotContainsString('CONTENIDO_APP_RETIRADO', $pdfSource);
        $this->assertStringNotContainsString('CONTENIDO_APNP_RETIRADO', $pdfSource);
        $this->actingAs($this->admin())->get('/expedientes/'.$id)->assertOk()->assertDontSee('Antecedentes Personales')->assertDontSee('CONTENIDO_APP_RETIRADO');
        $this->get('/expedientes/'.$id.'/editar')->assertOk()->assertDontSee('app_alergias')->assertDontSee('apnp_estilo_vida');
    }

    public function test_dossier_saves_two_sections_preserves_history_and_rejects_retired_fields(): void
    {
        $student = $this->student();
        $id = $this->createDossier($student);
        DB::table('expediente_alumno')->where('id', $id)->update(['app_alergias' => 'HISTORICO_APP', 'apnp_estilo_vida' => 'HISTORICO_APNP']);
        $record = DB::table('expediente_alumno')->find($id);
        $data = array_replace($this->dossier($student), ['apnp_tipo_sangre' => 'AB', 'apnp_factor_rh' => 'Negativo (-)']);
        $this->post('/mi-expediente/editar', ['datos' => $data, 'version' => DossierController::fingerprint($record)])->assertRedirect('/mi-expediente');
        $this->assertDatabaseHas('expediente_alumno', ['id' => $id, 'user_id' => $student->id, 'apnp_tipo_sangre' => 'AB', 'apnp_factor_rh' => 'Negativo (-)', 'app_alergias' => 'HISTORICO_APP', 'apnp_estilo_vida' => 'HISTORICO_APNP']);
        $current = DB::table('expediente_alumno')->find($id);
        foreach (['app_alergias', 'apnp_habitos_toxicos', 'app_cirugias_previas', 'apnp_inmunizaciones', 'app_transfusiones'] as $field) {
            $this->post('/mi-expediente/editar', ['datos' => $data + [$field => 'Cambio no permitido'], 'version' => DossierController::fingerprint($current)])->assertForbidden();
        }
        $this->post('/frontend/web/index.php?r=expediente/editar', ['ExpedienteAlumno' => $data + ['app_alergias' => 'Cambio legado'], 'version' => DossierController::fingerprint($current)])->assertForbidden();
        $this->assertSame(2, DB::table('expediente_historial')->where('expediente_id', $id)->count());
        $this->assertDatabaseHas('expediente_alumno', ['id' => $id, 'app_alergias' => 'HISTORICO_APP']);
    }

    public function test_blood_is_controlled_by_personal_permissions_and_old_grants_are_retired(): void
    {
        $student = $this->student();
        $id = $this->createDossier($student);
        $admin = $this->admin();
        $coordinator = $this->coordinator();
        DB::table('coordinador_permiso')->insert(['coordinador_id' => $coordinator->id, 'seccion' => 'antecedentes', 'puede_ver' => 1, 'puede_editar' => 1, 'otorgado_por' => $admin->id, 'updated_at' => now()]);
        $this->actingAs($coordinator)->get('/expedientes/'.$id)->assertForbidden();
        DB::table('coordinador_permiso')->insert(['coordinador_id' => $coordinator->id, 'seccion' => 'cuestionario', 'puede_ver' => 1, 'puede_editar' => 1, 'otorgado_por' => $admin->id, 'updated_at' => now()]);
        $this->get('/expedientes/'.$id)->assertOk()->assertDontSee('Tipo de Sangre')->assertDontSee('Sangre:');
        DB::table('coordinador_permiso')->insert(['coordinador_id' => $coordinator->id, 'seccion' => 'personales', 'puede_ver' => 1, 'puede_editar' => 1, 'otorgado_por' => $admin->id, 'updated_at' => now()]);
        $this->get('/expedientes/'.$id)->assertOk()->assertSee('Tipo de Sangre');
        $this->get('/expedientes/'.$id.'/editar')->assertOk()->assertSee('apnp_tipo_sangre')->assertDontSee('app_alergias');
        $other = $this->student();
        $this->get('/alumnos/'.$other->id)->assertOk()->assertSee('Crear o editar expediente');
        $this->post('/alumnos/'.$other->id.'/expediente', ['datos' => $this->dossier($other), 'motivo' => 'Registro con las dos secciones vigentes'])->assertRedirect();
        $this->assertDatabaseHas('expediente_alumno', ['user_id' => $other->id]);
        $this->actingAs($admin)->get('/coordinadores/'.$coordinator->id)->assertOk()->assertDontSee('permisos[antecedentes]')->assertSee('Datos personales y contacto');
        $this->post('/coordinadores/'.$coordinator->id.'/permisos', ['motivo' => 'Intento con sección retirada', 'permisos' => ['antecedentes' => ['ver' => 1]]])->assertSessionHasErrors('permisos');
    }

    public function test_personal_section_requires_a_valid_blood_group_and_rh(): void
    {
        $student = $this->student();
        $this->actingAs($student);
        foreach ([['apnp_tipo_sangre', ''], ['apnp_tipo_sangre', 'X'], ['apnp_factor_rh', ''], ['apnp_factor_rh', 'Indefinido']] as [$field, $value]) {
            $this->post('/mi-expediente/editar', ['datos' => array_replace($this->dossier($student), [$field => $value])])->assertSessionHasErrors($field);
        }
        $this->assertSame(0, DB::table('expediente_alumno')->count());
        $this->post('/mi-expediente/editar', ['datos' => $this->dossier($student)])->assertRedirect('/mi-expediente');
        $this->assertDatabaseHas('expediente_alumno', ['user_id' => $student->id, 'apnp_tipo_sangre' => 'O', 'apnp_factor_rh' => 'Positivo (+)', 'app_alergias' => null, 'apnp_estilo_vida' => null]);
    }

    public function test_registration_email_verification_and_reset_preserve_pending_approval(): void
    {
        Mail::fake();
        $data = ['nombre' => 'Registro', 'apellidos' => 'Prueba', 'username' => 'registro', 'email' => 'registro@example.com', 'matricula' => '0000987', 'licenciatura_id' => $this->degree, 'password' => 'ClaveModelo!2026', 'password_confirmation' => 'ClaveModelo!2026', 'rol_id' => 1, 'status' => 10];
        $this->post('/registro', $data)->assertRedirect('/login');
        $user = User::where('username', 'registro')->firstOrFail();
        $this->assertSame('0000987', $user->matricula);
        $this->assertSame(3, $user->rol_id);
        $this->assertSame(5, $user->status);
        Mail::assertSent(AccountLink::class);
        $token = $user->verification_token;
        $this->get('/verificar/'.$token)->assertRedirect('/login');
        $this->assertSame(5, $user->fresh()->status);
        $this->assertNull($user->fresh()->verification_token);
        $this->get('/verificar/'.$token)->assertNotFound();
        $this->post('/login', ['username' => 'registro', 'password' => 'ClaveModelo!2026'])->assertSessionHasErrors();
        $this->assertGuest();
        $coordinator = $this->coordinator();
        $coordinator->forceFill(['status' => 0, 'verification_token' => 'coordinator-token'])->save();
        $this->get('/verificar/coordinator-token')->assertNotFound();
        $this->assertSame(0, $coordinator->fresh()->status);
        $this->actingAs($this->admin())->post('/notificaciones/alta', ['modo' => 'individual', 'seleccion' => [$user->id]])->assertRedirect();
        auth()->logout();
        $this->post('/recuperar', ['email' => $user->email])->assertRedirect();
        $user->refresh();
        $reset = $user->password_reset_token;
        $key = $user->auth_key;
        $this->post('/recuperar/'.$reset, ['password' => 'NuevaClave!2026', 'password_confirmation' => 'NuevaClave!2026'])->assertRedirect('/login');
        $this->assertTrue(password_verify('NuevaClave!2026', $user->fresh()->password_hash));
        $this->assertNotSame($key, $user->fresh()->auth_key);
        $this->post('/recuperar/'.$reset, ['password' => 'NuevaClave!2026', 'password_confirmation' => 'NuevaClave!2026'])->assertNotFound();
    }

    public function test_catalogs_assignment_and_permission_audit_are_admin_only(): void
    {
        $coord = $this->coordinator();
        $this->actingAs($coord)->post('/catalogos/grupo', ['nombre' => 'Sin permiso'])->assertForbidden();
        $this->actingAs($this->admin())->post('/catalogos/grupo', ['nombre' => 'Grupo A', 'estado' => 1, 'licenciatura_id' => $this->degree, 'periodo' => '2026'])->assertRedirect();
        $group = DB::table('grupo')->where('nombre', 'Grupo A')->value('id');
        $this->post('/coordinadores/'.$coord->id.'/grupos', ['grupos' => [$group]])->assertRedirect();
        $this->assertDatabaseHas('grupo', ['id' => $group, 'coordinador_id' => $coord->id]);
        $other = $this->coordinator();
        $this->post('/coordinadores/'.$other->id.'/grupos', ['grupos' => [$group]])->assertStatus(422);
        $this->post('/coordinadores/'.$coord->id.'/permisos', ['motivo' => 'Consulta autorizada', 'permisos' => ['personales' => ['ver' => 1]]])->assertRedirect();
        $this->assertDatabaseHas('auditoria_sistema', ['evento' => 'PERMISOS_COORDINADOR', 'motivo' => 'Consulta autorizada']);
        $this->post('/coordinadores/'.$coord->id.'/estado', ['status' => 0])->assertRedirect();
        $this->assertDatabaseHas('grupo', ['id' => $group, 'coordinador_id' => null]);
        $this->post('/coordinadores/'.$coord->id.'/estado', ['status' => 10])->assertRedirect();
    }

    public function test_student_survey_responses_are_retired_but_administration_and_saved_answers_remain(): void
    {
        $survey = DB::table('encuesta')->insertGetId(['titulo' => 'Encuesta conservada', 'tipo_test' => 'salud', 'estado' => 1]);
        $question = DB::table('pregunta')->insertGetId(['encuesta_id' => $survey, 'planteamiento' => 'Pregunta conservada', 'status' => 1]);
        $student = $this->student();
        DB::table('respuesta_alumno')->insert(['user_id' => $student->id, 'pregunta_id' => $question, 'respuesta' => 'Si', 'fecha_registro' => now()]);
        $before = DB::table('respuesta_alumno')->get()->map(fn($row) => (array) $row)->all();
        $this->actingAs($student)->get('/inicio')->assertOk()->assertSee('Mi expediente')->assertDontSee('Responder encuesta de salud')->assertDontSee('Para comenzar la encuesta');
        foreach ([$student, $this->admin(), $this->coordinator()] as $actor) {
            $this->actingAs($actor)->get('/encuestas/responder/'.$survey)->assertNotFound();
            $this->post('/encuestas/responder/'.$survey, ['respuestas' => [$question => 'No']])->assertNotFound();
            $this->postJson('/encuestas/autoguardado', ['pregunta_id' => $question, 'respuesta' => 'No'])->assertNotFound();
            $this->get('/encuestas/'.$survey.'/finalizar')->assertNotFound();
        }
        foreach (['encuesta/index', 'encuesta/lista-alumno', 'encuesta/finalizar', 'encuesta/guardar-respuesta-ajax'] as $oldAction) {
            $this->get('/frontend/web/index.php?r='.$oldAction.'&id_encuesta='.$survey)->assertStatus(410);
            $this->post('/frontend/web/index.php?r='.$oldAction, ['respuestas' => [$question => 'No']])->assertStatus(410);
        }
        $this->assertSame($before, DB::table('respuesta_alumno')->get()->map(fn($row) => (array) $row)->all());
        $this->actingAs($this->admin())->get('/encuestas')->assertOk()->assertSee('Encuesta conservada')->assertSee('Pregunta conservada');
        $this->get('/backend/web/index.php?r=encuesta/index')->assertOk();
        $this->assertDatabaseHas('encuesta', ['id' => $survey, 'titulo' => 'Encuesta conservada']);
        $this->assertDatabaseHas('pregunta', ['id' => $question, 'planteamiento' => 'Pregunta conservada']);
    }

    public function test_dossier_sections_privacy_lock_and_history(): void
    {
        $student = $this->student();
        $id = $this->createDossier($student);
        $this->assertSame(1, DB::table('expediente_historial')->where('expediente_id', $id)->count());
        $coord = $this->coordinator();
        DB::table('expediente_alumno')->where('id', $id)->update(['notas_coordinador' => 'NOTA PRIVADA']);
        $this->actingAs($coord)->get('/expedientes/'.$id)->assertForbidden();
        DB::table('coordinador_permiso')->insert(['coordinador_id' => $coord->id, 'seccion' => 'personales', 'puede_ver' => 1, 'puede_editar' => 0, 'otorgado_por' => $this->admin()->id, 'updated_at' => now()]);
        $this->get('/expedientes/'.$id)->assertOk()->assertDontSee('NOTA PRIVADA');
        $this->post('/expedientes/'.$id.'/editar', ['datos' => ['nombres' => 'Sin permiso'], 'motivo' => 'Cambio'])->assertForbidden();
        $this->post('/expedientes/'.$id.'/archivar', ['motivo' => 'Sin permiso'])->assertForbidden();
        $this->actingAs($this->admin())->post('/expedientes/'.$id.'/bloqueo', ['motivo' => 'Protección', 'bloqueado' => 1])->assertRedirect();
        $this->actingAs($student)->get('/mi-expediente/editar')->assertForbidden();
        $other = $this->student();
        $this->actingAs($other)->get('/expedientes/'.$id)->assertForbidden();
    }

    public function test_dossier_rejects_stale_edits_and_inconsistent_question_details(): void
    {
        $student = $this->student();
        $id = $this->createDossier($student);
        $record = DB::table('expediente_alumno')->find($id);
        $version = DossierController::fingerprint($record);
        $data = $this->dossier($student);
        $data['q1_cond_fisica'] = 1;
        $data['q1_cond_fisica_detalle'] = '';
        $this->post('/mi-expediente/editar', ['datos' => $data, 'version' => $version])->assertSessionHasErrors('q1_cond_fisica_detalle');
        DB::table('expediente_alumno')->where('id', $id)->update(['q12_info_adicional' => 'Cambio concurrente']);
        $this->post('/mi-expediente/editar', ['datos' => $this->dossier($student), 'version' => $version])->assertStatus(409);
        $this->assertDatabaseHas('expediente_alumno', ['id' => $id, 'q12_info_adicional' => 'Cambio concurrente']);
    }

    public function test_retired_modules_are_absent_and_clinical_answers_remain_editable(): void
    {
        $student = $this->student();
        $id = $this->createDossier($student);
        $record = DB::table('expediente_alumno')->find($id);
        $data = array_replace($this->dossier($student), ['q1_cond_fisica' => 1, 'q1_cond_fisica_detalle' => 'Condición documentada', 'q11_necesita_apoyo' => ['Psicológico']]);
        $this->post('/mi-expediente/editar', ['datos' => $data, 'version' => DossierController::fingerprint($record)])->assertRedirect();
        $this->assertDatabaseHas('expediente_alumno', ['id' => $id, 'q1_cond_fisica' => 1, 'q1_cond_fisica_detalle' => 'Condición documentada']);
        $this->assertSame(['Psicológico'], json_decode(DB::table('expediente_alumno')->where('id', $id)->value('q11_necesita_apoyo'), true));
        $this->get('/mi-expediente')->assertOk()->assertDontSee('Clasificación')->assertDontSee('Atención prioritaria')->assertDontSee('Sexo / Género');
        $admin = $this->admin();
        $this->actingAs($admin)->get('/panel')->assertOk()->assertSee('Encuestas y preguntas')->assertDontSee('Alertas de Salud')->assertDontSee('Atención Estudiantil')->assertDontSee('Géneros')->assertDontSee('Resultados');
        foreach (['/atencion', '/alertas', '/resultados', '/resultados/'.$student->id, '/catalogos/genero'] as $path) {
            $this->get($path)->assertNotFound();
        }
        $this->post('/expedientes/'.$id.'/clasificar', ['motivo' => 'Módulo retirado'])->assertNotFound();
        $coord = $this->coordinator();
        $this->get('/coordinadores/'.$coord->id)->assertOk()->assertDontSee('permisos[clasificacion]');
        $this->post('/coordinadores/'.$coord->id.'/permisos', ['motivo' => 'Sección retirada', 'permisos' => ['clasificacion' => ['ver' => 1]]])->assertSessionHasErrors('permisos');
        foreach (['salud', 'atencion'] as $type) {
            $this->post('/reportes/exportar', ['tipo' => $type, 'formato' => 'pdf', 'motivo' => 'Reporte retirado'])->assertSessionHasErrors('tipo');
        }
        foreach (['genero', 'resultados_salud', 'resultados_chaside'] as $table) {
            $this->assertFalse(Schema::hasTable($table));
        }
        foreach (['genero', 'categoria_atencion', 'categoria_manual', 'atencion_prioritaria', 'motivo_clasificacion', 'clasificado_por'] as $field) {
            $this->assertFalse(Schema::hasColumn('expediente_alumno', $field));
        }
        $this->assertFalse(Schema::hasColumn('user', 'genero_id'));
        $this->assertFalse(Schema::hasColumn('pregunta', 'tipo_riesgo'));
    }

    public function test_post_routes_require_csrf_outside_test_mode(): void
    {
        $this->app['env'] = 'local';
        $this->post('/login', ['username' => 'usuario', 'password' => 'ClaveModelo!2026'])->assertStatus(419);
        $this->app['env'] = 'testing';
    }

    public function test_reports_require_reason_and_certificates_preserve_folio_and_export_pdf(): void
    {
        $student = $this->student();
        $id = $this->createDossier($student);
        $this->get('/mi-constancia')->assertOk()->assertSee('UMV-EXP-'.str_pad((string) $id, 5, '0', STR_PAD_LEFT));
        $pdf = $this->get('/mi-constancia?pdf=1')->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
        $this->post('/mi-expediente/pdf')->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->actingAs($this->admin())->post('/reportes/exportar', ['tipo' => 'alumnos', 'formato' => 'pdf'])->assertSessionHasErrors('motivo');
        $this->post('/reportes/exportar', ['tipo' => 'alumnos', 'formato' => 'pdf', 'motivo' => 'Auditoría de prueba'])->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertDatabaseHas('auditoria_sistema', ['evento' => 'EXPORTACION_REPORTE', 'motivo' => 'Auditoría de prueba']);
        $xlsx = $this->post('/reportes/exportar', ['tipo' => 'alumnos', 'formato' => 'xlsx', 'motivo' => 'Prueba Excel'])->assertOk();
        $this->assertStringStartsWith('PK', $xlsx->streamedContent());
    }

    public function test_institutional_dossier_creation_requires_all_edit_permissions(): void
    {
        $student = $this->student();
        $this->actingAs($this->coordinator())->get('/alumnos/'.$student->id.'/expediente')->assertForbidden();
        $this->actingAs($this->admin())->post('/alumnos/'.$student->id.'/expediente', ['datos' => $this->dossier($student), 'motivo' => 'Registro institucional'])->assertRedirect();
        $this->assertDatabaseHas('expediente_alumno', ['user_id' => $student->id]);
        $this->assertDatabaseHas('expediente_historial', ['accion' => 'REGISTRO_INICIAL', 'detalles' => 'Registro institucional']);
    }

    public function test_legacy_get_and_post_routes_keep_permissions_and_retire_chaside(): void
    {
        $this->get('/frontend/web/index.php?r=site/login')->assertOk();
        $this->get('/frontend/web/index.php?r=site/resend-verification-email')->assertOk();
        config(['services.microsoft.enabled' => false]);
        $this->get('/login')->assertSee('Acceso institucional pendiente de activación.')->assertDontSee('href="'.route('microsoft.login').'"', false);
        foreach (['chaside/index', 'genero/index', 'atencion/index', 'reporte/index', 'salud/index', 'salud/resultado', 'resultado/index', 'expediente/clasificar'] as $retired) {
            $this->get('/backend/web/index.php?r='.$retired)->assertStatus(410);
            $this->post('/backend/web/index.php?r='.$retired)->assertStatus(410);
        }
        $this->get('/backend/web/index.php?r=accion-inventada')->assertNotFound();
        $this->actingAs($this->coordinator())->post('/backend/web/index.php?r=coordinador/create', ['User' => ['nombre' => 'No autorizado']])->assertForbidden();
        $this->actingAs($this->admin())->post('/backend/web/index.php?r=licenciatura/create', ['Licenciatura' => ['nombre' => 'Legado', 'estado' => 1]])->assertRedirect();
        $this->assertDatabaseHas('licenciatura', ['nombre' => 'Legado']);
        $id = DB::table('licenciatura')->where('nombre', 'Legado')->value('id');
        $this->get('/backend/web/index.php?r=licenciatura/delete&id='.$id)->assertStatus(405);
        $this->post('/backend/web/index.php?r=licenciatura/delete&id='.$id)->assertRedirect();
        $this->assertDatabaseHas('licenciatura', ['id' => $id, 'estado' => 0]);
        $this->post('/backend/web/index.php?r=licenciatura/reactivar&id='.$id)->assertRedirect();
        $this->assertDatabaseHas('licenciatura', ['id' => $id, 'estado' => 1]);
        $this->get('/coordinadores/invalid')->assertNotFound();
        $survey = DB::table('encuesta')->insertGetId(['titulo' => 'Salud', 'tipo_test' => 'salud', 'estado' => 1, 'created_at' => time(), 'updated_at' => time()]);
        $this->post('/backend/web/index.php?r=encuesta/update&id='.$survey, ['Encuesta' => ['titulo' => 'Actualizada', 'estado' => 1, 'tipo_test' => 'chaside']])->assertRedirect();
        $this->assertDatabaseHas('encuesta', ['id' => $survey, 'titulo' => 'Actualizada', 'tipo_test' => 'salud']);
        $this->get('/encuestas')->assertOk()->assertDontSee('name="tipo_riesgo"', false);
        $this->post('/preguntas', ['encuesta_id' => $survey, 'planteamiento' => 'Pregunta sin evaluación de riesgo', 'status' => 1])->assertRedirect();
        $this->assertDatabaseHas('pregunta', ['encuesta_id' => $survey, 'planteamiento' => 'Pregunta sin evaluación de riesgo']);
        $student = $this->student();
        $this->post('/backend/web/index.php?r=expediente/crear&user_id='.$student->id, ['ExpedienteAlumno' => $this->dossier($student), 'motivo_cambio' => 'Compatibilidad Yii'])->assertRedirect();
        $this->assertDatabaseHas('expediente_alumno', ['user_id' => $student->id]);
    }

    public function test_microsoft_new_account_is_pending_then_approved_and_email_collision_does_not_link(): void
    {
        Mail::fake();
        $claims = ['tid' => '11111111-1111-1111-1111-111111111111', 'oid' => '22222222-2222-2222-2222-222222222222'];
        $stub = new class($claims) extends MicrosoftIdentity
        {
            public function __construct(private array $claims) {}

            public function exchange(string $code, string $verifier, string $redirectUri): array
            {
                return ['id_token' => 'verified', 'access_token' => 'graph'];
            }

            public function verify(string $token, string $nonce): array
            {
                return $this->claims;
            }

            public function profile(string $accessToken): array
            {
                return ['id' => $this->claims['oid'], 'mail' => 'microsoft@example.com', 'givenName' => 'Microsoft', 'surname' => 'Prueba'];
            }
        };
        $this->app->instance(MicrosoftIdentity::class, $stub);
        $session = ['state' => 'state', 'nonce' => 'nonce', 'verifier' => 'verifier', 'created_at' => time(), 'redirect_uri' => 'http://localhost/microsoft/callback', 'link_user_id' => null];
        $this->withSession(['oauth_state' => $session])->get('/microsoft/callback?code=code&state=bad')->assertRedirect('/login')->assertSessionHas('error');
        $this->withSession(['oauth_state' => $session])->get('/microsoft/callback?code=code&state=state')->assertRedirect('/login');
        $user = User::where('email', 'microsoft@example.com')->firstOrFail();
        $this->assertSame(5, $user->status);
        $this->assertGuest();
        $this->actingAs($this->admin())->post('/notificaciones/alta', ['modo' => 'individual', 'seleccion' => [$user->id]])->assertRedirect();
        auth()->logout();
        $this->withSession(['oauth_state' => $session])->get('/microsoft/callback?code=code&state=state')->assertRedirect('/inicio');
        $this->assertAuthenticatedAs($user);
        auth()->logout();
        DB::table('identidad_externa')->where('user_id', $user->id)->delete();
        $this->withSession(['oauth_state' => $session])->get('/microsoft/callback?code=code&state=state')->assertRedirect('/login');
        $this->assertSame(0, DB::table('identidad_externa')->where('user_id', $user->id)->count());
        $this->assertGuest();
    }

    public function test_actual_jwt_validation_rejects_forged_nonce_and_signature(): void
    {
        config(['services.microsoft.client_id' => 'client', 'services.microsoft.tenant' => 'common']);
        $options = ['private_key_type' => OPENSSL_KEYTYPE_RSA, 'private_key_bits' => 2048];
        $configuration = dirname(PHP_BINARY).'/extras/ssl/openssl.cnf';
        if (is_file($configuration)) {
            $options['config'] = $configuration;
        }
        $private = openssl_pkey_new($options);
        $this->assertNotFalse($private);
        openssl_pkey_export($private, $pem, null, $options);
        $details = openssl_pkey_get_details($private);
        $encode = fn ($value) => rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
        $keys = ['keys' => [['kty' => 'RSA', 'alg' => 'RS256', 'kid' => 'test', 'n' => $encode($details['rsa']['n']), 'e' => $encode($details['rsa']['e']), 'issuer' => 'https://login.microsoftonline.com/{tenantid}/v2.0']]];
        foreach (['11111111-1111-1111-1111-111111111111', MicrosoftIdentity::CONSUMER_TENANT] as $tenant) {
            $claims = ['tid' => $tenant, 'oid' => '22222222-2222-2222-2222-222222222222', 'aud' => 'client', 'iss' => 'https://login.microsoftonline.com/'.$tenant.'/v2.0', 'nonce' => 'nonce', 'iat' => time(), 'exp' => time() + 60];
            $jwt = JWT::encode($claims, $pem, 'RS256', 'test');
            $service = new MicrosoftIdentity;
            $this->assertSame($claims['oid'], $service->verifyWithKeys($jwt, 'nonce', $keys)['oid']);
            $parts = explode('.', $jwt);
            $parts[2] = $encode(str_repeat('x', 256));
            foreach ([[$jwt, 'forged'], [implode('.', $parts), 'nonce']] as [$token,$nonce]) {
                $rejected = false;
                try {
                    $service->verifyWithKeys($token, $nonce, $keys);
                } catch (\UnexpectedValueException $error) {
                    $rejected = true;
                }
                $this->assertTrue($rejected, 'Se aceptó un nonce o una firma falsificada.');
            }
        }
    }
}
