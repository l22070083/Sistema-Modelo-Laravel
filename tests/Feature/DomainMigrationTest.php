<?php

namespace Tests\Feature;

use App\Http\Controllers\DossierController;
use App\Mail\AccountLink;
use App\Models\User;
use App\Services\DossierRules;
use App\Services\MicrosoftIdentity;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class DomainMigrationTest extends TestCase
{
    use RefreshDatabase;

    private int $degree;

    private int $gender;

    protected function setUp(): void
    {
        parent::setUp();
        $this->degree = DB::table('licenciatura')->insertGetId(['nombre' => 'Licenciatura de prueba', 'estado' => 1]);
        $this->gender = DB::table('genero')->insertGetId(['nombre' => 'Otro']);
    }

    private function student(): User
    {
        return User::factory()->create(['licenciatura_id' => $this->degree, 'genero_id' => $this->gender]);
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
        foreach (array_merge(...array_values(array_intersect_key(config('dossier.sections'), array_flip(['personales', 'antecedentes', 'cuestionario'])))) as $field) {
            $data[$field] = 'Dato de prueba';
        }
        foreach (array_keys($data) as $field) {
            if (preg_match('/^q[1-7]_.*(?<!detalle)$/', $field) || $field === 'q9_acomp_psicologico') {
                $data[$field] = 0;
            }
        }

        return array_merge($data, ['nombres' => $student->nombre, 'apellidos' => $student->apellidos, 'fecha_nacimiento' => '2000-01-02', 'genero' => 'Otro', 'estado_civil' => 'Soltero(a)',
            'licenciatura_id' => $this->degree, 'apnp_tipo_sangre' => 'O', 'apnp_factor_rh' => 'Positivo (+)', 'q10_estado_emocional' => 'Favorable', 'q11_necesita_apoyo' => ['Ninguno']]);
    }

    private function createDossier(User $student): int
    {
        $this->actingAs($student)->post('/mi-expediente/editar', ['datos' => $this->dossier($student)])->assertRedirect('/mi-expediente');

        return DB::table('expediente_alumno')->where('user_id', $student->id)->value('id');
    }

    public function test_registration_email_verification_and_reset_preserve_pending_approval(): void
    {
        Mail::fake();
        $data = ['nombre' => 'Registro', 'apellidos' => 'Prueba', 'username' => 'registro', 'email' => 'registro@example.com', 'matricula' => '0000987', 'licenciatura_id' => $this->degree, 'genero_id' => $this->gender, 'password' => 'ClaveModelo!2026', 'password_confirmation' => 'ClaveModelo!2026', 'rol_id' => 1, 'status' => 10];
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

    public function test_survey_rejects_chaside_saves_answers_and_requires_completion(): void
    {
        $survey = DB::table('encuesta')->insertGetId(['titulo' => 'Salud', 'tipo_test' => 'salud', 'estado' => 1, 'created_at' => time(), 'updated_at' => time()]);
        $first = DB::table('pregunta')->insertGetId(['encuesta_id' => $survey, 'planteamiento' => 'Pregunta A', 'tipo_riesgo' => 'alto', 'status' => 1]);
        $second = DB::table('pregunta')->insertGetId(['encuesta_id' => $survey, 'planteamiento' => 'Pregunta B', 'tipo_riesgo' => 'bajo', 'status' => 1]);
        $old = DB::table('encuesta')->insertGetId(['titulo' => 'Histórico', 'tipo_test' => 'chaside', 'estado' => 1, 'created_at' => time(), 'updated_at' => time()]);
        $oldQuestion = DB::table('pregunta')->insertGetId(['encuesta_id' => $old, 'planteamiento' => 'Fuera del alcance', 'status' => 1]);
        $student = $this->student();
        $this->actingAs($student)->get('/encuestas/responder/'.$survey)->assertOk()->assertSee('Pregunta A');
        $this->postJson('/encuestas/autoguardado', ['pregunta_id' => $oldQuestion, 'respuesta' => 'Si'])->assertStatus(422);
        $this->postJson('/encuestas/autoguardado', ['pregunta_id' => $first, 'respuesta' => 'Otro'])->assertStatus(422);
        $this->postJson('/encuestas/autoguardado', ['pregunta_id' => $first, 'respuesta' => 'Si'])->assertOk();
        $this->postJson('/encuestas/autoguardado', ['pregunta_id' => $first, 'respuesta' => 'No'])->assertOk();
        $this->assertSame(1, DB::table('respuesta_alumno')->where('pregunta_id', $first)->count());
        $this->get('/encuestas/'.$survey.'/finalizar')->assertRedirect('/encuestas/responder/'.$survey);
        $this->post('/encuestas/responder/'.$survey, ['respuestas' => [$first => 'Si', $second => 'No']])->assertRedirect('/encuestas/'.$survey.'/finalizar');
        $this->get('/encuestas/'.$survey.'/finalizar')->assertOk()->assertSee('Encuesta completada');
        $this->assertSame(0, DB::table('resultados_salud')->count());
    }

    public function test_dossier_sections_privacy_lock_history_and_classification(): void
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
        $classified = DossierRules::classify(['q1_cond_fisica' => 1, 'q2_cond_mental' => 1, 'q11_necesita_apoyo' => '["De aprendizaje"]']);
        $this->assertSame(['Salud Física', 'Atención Psicopedagógica', 'Atención Emocional'], json_decode($classified['categoria_atencion'], true));
        $this->assertSame(1, $classified['atencion_prioritaria']);
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

    public function test_institutional_risk_overrides_automatic_categories_and_survives_student_changes(): void
    {
        foreach (['q1_cond_fisica', 'q3_tratamiento', 'q4_crisis_medica', 'q5_alergia'] as $field) {
            $this->assertSame(['Salud Física'], json_decode(DossierRules::classify([$field => 1])['categoria_atencion'], true));
        }
        foreach (['q2_cond_mental', 'q9_acomp_psicologico'] as $field) {
            $this->assertSame(['Atención Emocional'], json_decode(DossierRules::classify([$field => 1])['categoria_atencion'], true));
        }
        foreach (['q6_diag_aprendizaje', 'q7_dictamen_psico'] as $field) {
            $this->assertSame(['Atención Psicopedagógica'], json_decode(DossierRules::classify([$field => 1])['categoria_atencion'], true));
        }
        $this->assertSame(['Atención Emocional'], json_decode(DossierRules::classify(['q10_estado_emocional' => 'Desfavorable'])['categoria_atencion'], true));
        $this->assertSame(['Atención Emocional'], json_decode(DossierRules::classify(['q11_necesita_apoyo' => '["Psicológico"]'])['categoria_atencion'], true));
        $this->assertSame(['Atención Psicopedagógica'], json_decode(DossierRules::classify(['q11_necesita_apoyo' => '["De aprendizaje"]'])['categoria_atencion'], true));
        $student = $this->student();
        $id = $this->createDossier($student);
        $coord = $this->coordinator();
        $assessment = ['categorias' => ['Atención Emocional'], 'prioritaria' => 1, 'motivo' => 'Valoración del coordinador'];
        $this->actingAs($coord)->post('/expedientes/'.$id.'/clasificar', $assessment)->assertForbidden();
        $admin = $this->admin();
        foreach (['personales', 'clasificacion'] as $section) {
            DB::table('coordinador_permiso')->insert(['coordinador_id' => $coord->id, 'seccion' => $section, 'puede_ver' => 1, 'puede_editar' => 1, 'otorgado_por' => $admin->id, 'updated_at' => now()]);
        }
        $this->post('/expedientes/'.$id.'/clasificar', array_diff_key($assessment, ['motivo' => true]))->assertSessionHasErrors('motivo');
        $this->post('/expedientes/'.$id.'/clasificar', $assessment)->assertRedirect();
        $record = DB::table('expediente_alumno')->find($id);
        $this->assertSame($coord->id, $record->clasificado_por);
        $this->assertSame(1, $record->atencion_prioritaria);
        $this->assertDatabaseHas('auditoria_sistema', ['evento' => 'CLASIFICAR', 'actor_id' => $coord->id, 'motivo' => $assessment['motivo']]);
        $this->get('/resultados/'.$student->id)->assertOk()->assertSee('Valoración institucional')->assertSee('Atención Emocional');
        $this->actingAs($student)->post('/expedientes/'.$id.'/clasificar', $assessment)->assertForbidden();
        $this->post('/mi-expediente/editar', ['datos' => $this->dossier($student), 'version' => DossierController::fingerprint($record)])->assertRedirect();
        $current = DB::table('expediente_alumno')->find($id);
        $this->assertSame(['Sin dato de alarma'], json_decode($current->categoria_atencion, true));
        $this->assertSame(['Atención Emocional'], json_decode($current->categoria_manual, true));
        $this->assertSame(1, $current->atencion_prioritaria);
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
        $this->get('/backend/web/index.php?r=chaside/index')->assertStatus(410);
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
        $tenant = '11111111-1111-1111-1111-111111111111';
        $keys = ['keys' => [['kty' => 'RSA', 'alg' => 'RS256', 'kid' => 'test', 'n' => $encode($details['rsa']['n']), 'e' => $encode($details['rsa']['e']), 'issuer' => 'https://login.microsoftonline.com/{tenantid}/v2.0']]];
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
