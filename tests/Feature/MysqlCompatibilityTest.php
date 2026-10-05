<?php

namespace Tests\Feature;

use App\Models\User;
use Dotenv\Dotenv;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MysqlCompatibilityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $values = Dotenv::parse(file_get_contents(base_path('.env')));
        if (($values['DB_CONNECTION'] ?? null) !== 'mysql' || ($values['DB_DATABASE'] ?? null) !== 'tutoriasmv_laravel_migracion_20261002') {
            $this->markTestSkipped('Se requiere la copia MySQL prevista, nunca la base original.');
        }
        config(['database.default' => 'mysql', 'database.connections.mysql.database' => $values['DB_DATABASE'],
            'database.connections.mysql.username' => $values['DB_USERNAME'], 'database.connections.mysql.password' => $values['DB_PASSWORD'] ?? '', 'database.connections.mysql.host' => $values['DB_HOST']]);
        DB::purge('mysql');
        $this->assertSame('tutoriasmv_laravel_migracion_20261002', DB::selectOne('SELECT DATABASE() AS name')->name);
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        if (DB::connection()->transactionLevel()) {
            DB::rollBack();
        }
        parent::tearDown();
    }

    public function test_imported_schema_supports_laravel_views_and_coordinator_creation(): void
    {
        $admin = User::factory()->create(['rol_id' => 1]);
        $this->actingAs($admin)->get('/inicio')->assertRedirect('/panel');
        foreach (['panel', 'notificaciones', 'coordinadores', 'administradores', 'alumnos/crear', 'encuestas', 'expedientes', 'atencion', 'alertas', 'resultados', 'reportes', 'catalogos/grupo', 'catalogos/licenciatura', 'catalogos/genero'] as $path) {
            $this->get('/'.$path)->assertOk();
        }
        $email = 'mysql_'.bin2hex(random_bytes(5)).'@example.com';
        $this->post('/coordinadores', ['nombre' => 'Prueba MySQL', 'username' => 'mysql_'.bin2hex(random_bytes(5)), 'email' => $email, 'password' => 'ClaveModelo!2026', 'password_confirmation' => 'ClaveModelo!2026'])->assertRedirect();
        $this->assertDatabaseHas('user', ['email' => $email, 'rol_id' => 2, 'status' => 10]);
        $coordinator = User::where('email', $email)->firstOrFail();
        $this->assertSame(5, DB::table('coordinador_permiso')->where('coordinador_id', $coordinator->id)->count());
        $this->assertDatabaseHas('auditoria_sistema', ['actor_id' => $admin->id, 'evento' => 'CREACION_COORDINADOR']);
        $studentEmail = 'mysql_alumno_'.bin2hex(random_bytes(5)).'@example.com';
        $degree = DB::table('licenciatura')->where('estado', 1)->value('id');
        $gender = DB::table('genero')->value('id');
        $this->post('/alumnos/crear', ['nombre' => 'Alumno MySQL', 'apellidos' => 'Prueba temporal', 'username' => 'mysql_alumno_'.bin2hex(random_bytes(5)), 'email' => $studentEmail,
            'password' => 'ClaveModelo!2026', 'password_confirmation' => 'ClaveModelo!2026', 'matricula' => '000123', 'licenciatura_id' => $degree, 'genero_id' => $gender])->assertRedirect();
        $this->assertDatabaseHas('user', ['email' => $studentEmail, 'rol_id' => 3, 'status' => 10]);
        $administratorEmail = 'mysql_admin_'.bin2hex(random_bytes(5)).'@example.com';
        $this->post('/administradores', ['nombre' => 'Administrador MySQL', 'username' => 'mysql_admin_'.bin2hex(random_bytes(5)), 'email' => $administratorEmail,
            'password' => 'ClaveModelo!2026', 'password_confirmation' => 'ClaveModelo!2026'])->assertRedirect('/administradores');
        $this->assertDatabaseHas('user', ['email' => $administratorEmail, 'rol_id' => 1, 'status' => 10]);
    }

    public function test_application_database_account_cannot_access_original_database(): void
    {
        $this->expectException(QueryException::class);
        DB::select('SELECT COUNT(*) FROM tutoriasmv.user');
    }

    public function test_imported_schema_creates_dossier_with_blood_without_retired_antecedents(): void
    {
        $admin = User::factory()->create(['rol_id' => User::ADMIN]);
        $degree = DB::table('licenciatura')->where('estado', 1)->value('id');
        $gender = DB::table('genero')->value('id');
        $student = User::factory()->create(['licenciatura_id' => $degree, 'genero_id' => $gender]);
        $data = [];
        foreach (array_merge(config('dossier.sections.personales'), config('dossier.sections.cuestionario')) as $field) {
            $data[$field] = preg_match('/^q[1-7]_.*(?<!detalle)$/', $field) || $field === 'q9_acomp_psicologico' ? 0 : 'Dato temporal de prueba';
        }
        $data = array_replace($data, ['nombres' => $student->nombre, 'apellidos' => $student->apellidos, 'fecha_nacimiento' => '2000-01-02', 'genero' => 'Otro', 'estado_civil' => 'Soltero(a)',
            'licenciatura_id' => $degree, 'apnp_tipo_sangre' => 'AB', 'apnp_factor_rh' => 'Negativo (-)', 'q10_estado_emocional' => 'Favorable', 'q11_necesita_apoyo' => ['Ninguno']]);
        $this->actingAs($admin)->post('/alumnos/'.$student->id.'/expediente', ['datos' => $data, 'motivo' => 'Prueba de formulario sin antecedentes'])->assertRedirect();
        $this->assertDatabaseHas('expediente_alumno', ['user_id' => $student->id, 'apnp_tipo_sangre' => 'AB', 'apnp_factor_rh' => 'Negativo (-)', 'app_alergias' => null, 'app_cirugias_previas' => null, 'apnp_habitos_toxicos' => null]);
        $dossier = DB::table('expediente_alumno')->where('user_id', $student->id)->value('id');
        $this->get('/expedientes/'.$dossier)->assertOk()->assertSee('Tipo de Sangre')->assertSee('Factor RH')->assertDontSee('Antecedentes Personales');
    }
}
