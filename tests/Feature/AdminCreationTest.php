<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminCreationTest extends TestCase
{
    use RefreshDatabase;

    private int $degree;

    private int $gender;

    protected function setUp(): void
    {
        parent::setUp();
        $this->degree = DB::table('licenciatura')->insertGetId(['nombre' => 'Ingeniería de prueba', 'estado' => 1]);
        $this->gender = DB::table('genero')->insertGetId(['nombre' => 'Otro']);
    }

    private function account(string $name = 'nueva-cuenta'): array
    {
        return ['nombre' => 'Cuenta de prueba', 'apellidos' => 'Prueba', 'username' => $name, 'email' => $name.'@example.test',
            'password' => 'ClaveModelo!2026', 'password_confirmation' => 'ClaveModelo!2026',
            'matricula' => '000123', 'licenciatura_id' => $this->degree, 'genero_id' => $this->gender];
    }

    public function test_admin_creates_active_accounts_with_fixed_roles_and_audited_logins(): void
    {
        $admin = User::factory()->create(['rol_id' => User::ADMIN]);
        foreach ([User::ALUMNO => ['/alumnos/crear', '/inicio', 'CREACION_ALUMNO'], User::COORDINADOR => ['/coordinadores', '/panel', 'CREACION_COORDINADOR'], User::ADMIN => ['/administradores', '/panel', 'CREACION_ADMINISTRADOR']] as $role => [$path, $destination, $event]) {
            $data = array_merge($this->account('cuenta-'.$role), ['rol_id' => 99, 'status' => 0, 'password_hash' => 'hash-inyectado', 'auth_key' => 'clave-inyectada', 'verification_token' => 'token-inyectado', 'password_reset_token' => 'reset-inyectado']);
            $this->actingAs($admin)->post($path, $data)->assertRedirect()->assertSessionHas('success');
            $created = User::where('username', $data['username'])->firstOrFail();
            $this->assertSame($role, $created->rol_id);
            $this->assertSame(User::ACTIVE, $created->status);
            $this->assertTrue(password_verify($data['password'], $created->password_hash));
            $this->assertSame(32, strlen($created->auth_key));
            $this->assertNotSame($data['auth_key'], $created->auth_key);
            $this->assertNull($created->verification_token);
            $this->assertNull($created->password_reset_token);
            $audit = DB::table('auditoria_sistema')->where('evento', $event)->first();
            $this->assertEquals($admin->id, $audit->actor_id);
            $this->assertSame(['user_id' => $created->id, 'rol_id' => $role], json_decode($audit->datos, true));
            $this->assertStringNotContainsString($data['password'], $audit->datos);
            if ($role === User::ALUMNO) {
                $this->assertSame('000123', $created->matricula);
                $this->assertEquals($created->id, $audit->alumno_id);
            }
            $this->post('/logout')->assertRedirect('/login');
            $this->post('/login', ['username' => $data['username'], 'password' => $data['password']])->assertRedirect($destination);
            $this->get($destination)->assertOk();
            $this->post('/logout')->assertRedirect('/login');
        }
    }

    public function test_new_coordinator_has_no_section_access_until_admin_grants_it(): void
    {
        $admin = User::factory()->create(['rol_id' => User::ADMIN]);
        $this->actingAs($admin)->post('/coordinadores', $this->account('coordinacion'))->assertRedirect();
        $created = User::where('username', 'coordinacion')->firstOrFail();
        $this->assertSame(5, DB::table('coordinador_permiso')->where('coordinador_id', $created->id)->count());
        $this->assertSame(0, DB::table('coordinador_permiso')->where('coordinador_id', $created->id)->sum('puede_ver'));
        $this->assertSame(0, DB::table('coordinador_permiso')->where('coordinador_id', $created->id)->sum('puede_editar'));
        $this->actingAs($created)->get('/panel')->assertOk()->assertDontSee('Crear registros')->assertDontSee('Listado de Alumnos');
        $this->get('/alumnos')->assertForbidden();
        $this->get('/administradores')->assertForbidden();
        $this->actingAs($admin)->post('/coordinadores/'.$created->id.'/permisos', ['motivo' => 'Consulta de alumnos autorizada', 'permisos' => ['personales' => ['ver' => 1]]])->assertRedirect();
        $this->actingAs($created)->get('/alumnos')->assertOk()->assertDontSee('Crear alumno');
        $this->get('/alumnos/crear')->assertForbidden();
    }

    public function test_creation_pages_and_posts_are_admin_only_even_with_coordinator_permissions(): void
    {
        foreach (['/alumnos/crear', '/administradores', '/coordinadores'] as $path) {
            $this->get($path)->assertRedirect('/login');
            $this->post($path, $this->account())->assertRedirect('/login');
        }
        $admin = User::factory()->create(['rol_id' => User::ADMIN]);
        foreach ([User::COORDINADOR, User::ALUMNO] as $role) {
            $actor = User::factory()->create(['rol_id' => $role]);
            if ($role === User::COORDINADOR) {
                foreach (array_keys(config('dossier.sections')) as $section) {
                    DB::table('coordinador_permiso')->insert(['coordinador_id' => $actor->id, 'seccion' => $section, 'puede_ver' => 1, 'puede_editar' => 1, 'otorgado_por' => $admin->id, 'updated_at' => now()]);
                }
            }
            foreach (['/alumnos/crear', '/administradores', '/coordinadores'] as $path) {
                $this->actingAs($actor)->get($path)->assertForbidden();
                $this->post($path, $this->account())->assertForbidden();
            }
        }
        $this->assertDatabaseMissing('user', ['username' => 'nueva-cuenta']);
        $this->assertSame(0, DB::table('auditoria_sistema')->count());
        $admin->forceFill(['status' => User::INACTIVE])->save();
        $this->actingAs($admin)->post('/administradores', $this->account())->assertRedirect('/login');
        $this->assertDatabaseMissing('user', ['username' => 'nueva-cuenta']);
    }

    public function test_account_validation_rejects_duplicates_and_invalid_credentials(): void
    {
        $admin = User::factory()->create(['rol_id' => User::ADMIN]);
        $this->actingAs($admin);
        foreach ([['username', $admin->username], ['email', $admin->email], ['nombre', '   '], ['password_confirmation', 'OtraClave!2026'], ['password', 'debil'], ['password', str_repeat('É', 36).'!']] as [$field, $value]) {
            $data = array_replace($this->account(), [$field => $value]);
            if ($field === 'password') {
                $data['password_confirmation'] = $value;
            }
            $this->post('/administradores', $data)->assertSessionHasErrors($field === 'password_confirmation' ? 'password' : $field);
        }
        $this->assertSame(1, User::count());
        $this->assertSame(0, DB::table('auditoria_sistema')->count());
        $data = $this->account('con-espacios');
        $data['username'] = '  con-espacios  ';
        $data['email'] = '  con-espacios@example.test  ';
        $this->post('/administradores', $data)->assertRedirect('/administradores');
        $this->assertDatabaseHas('user', ['username' => 'con-espacios', 'email' => 'con-espacios@example.test']);
    }

    public function test_student_profile_requires_active_catalogs_and_a_group_from_the_same_degree(): void
    {
        $this->actingAs(User::factory()->create(['rol_id' => User::ADMIN]));
        $inactive = DB::table('licenciatura')->insertGetId(['nombre' => 'Inactiva', 'estado' => 0]);
        $otherDegree = DB::table('licenciatura')->insertGetId(['nombre' => 'Otra carrera', 'estado' => 1]);
        $otherGroup = DB::table('grupo')->insertGetId(['nombre' => 'Otro grupo', 'estado' => 1, 'licenciatura_id' => $otherDegree]);
        $inactiveGroup = DB::table('grupo')->insertGetId(['nombre' => 'Grupo inactivo', 'estado' => 0, 'licenciatura_id' => $this->degree]);
        foreach ([['apellidos', ''], ['matricula', 'ABC123'], ['licenciatura_id', $inactive], ['genero_id', 999999], ['grupo_id', $otherGroup], ['grupo_id', $inactiveGroup]] as [$field, $value]) {
            $this->post('/alumnos/crear', array_replace($this->account(), [$field => $value]))->assertSessionHasErrors($field);
        }
        $this->assertSame(1, User::count());
        $group = DB::table('grupo')->insertGetId(['nombre' => 'Grupo autorizado', 'estado' => 1, 'licenciatura_id' => $this->degree]);
        $this->post('/alumnos/crear', array_merge($this->account(), ['grupo_id' => $group]))->assertRedirect();
        $this->assertDatabaseHas('user', ['username' => 'nueva-cuenta', 'grupo_id' => $group, 'licenciatura_id' => $this->degree]);
    }

    public function test_audit_failure_rolls_back_account_and_coordinator_permissions(): void
    {
        $this->actingAs(User::factory()->create(['rol_id' => User::ADMIN]));
        Schema::drop('auditoria_sistema');
        $this->withoutExceptionHandling();
        try {
            $this->post('/coordinadores', $this->account());
            $this->fail('El alta debió fallar junto con la auditoría.');
        } catch (QueryException $error) {
            $this->assertDatabaseMissing('user', ['username' => 'nueva-cuenta']);
            $this->assertSame(0, DB::table('coordinador_permiso')->count());
        }
    }

    public function test_admin_can_create_catalogs_surveys_and_questions_and_others_cannot(): void
    {
        $this->actingAs(User::factory()->create(['rol_id' => User::ADMIN]));
        $this->get('/panel')->assertOk()->assertSee('Crear registros')->assertSee('Crear administrador');
        $this->get('/alumnos/crear')->assertOk()->assertSee('password_confirmation');
        $this->get('/administradores')->assertOk()->assertSee('Listado de administradores');
        $this->post('/catalogos/licenciatura', ['nombre' => 'Nueva licenciatura', 'estado' => 1])->assertRedirect();
        $this->post('/catalogos/genero', ['nombre' => 'Género de prueba'])->assertRedirect();
        $this->post('/catalogos/grupo', ['nombre' => 'Nuevo grupo', 'estado' => 1, 'licenciatura_id' => $this->degree, 'periodo' => '2026'])->assertRedirect();
        $this->post('/encuestas', ['titulo' => 'Nueva encuesta', 'descripcion' => 'Salud', 'estado' => 1])->assertRedirect();
        $survey = DB::table('encuesta')->where('titulo', 'Nueva encuesta')->value('id');
        $this->post('/preguntas', ['encuesta_id' => $survey, 'planteamiento' => 'Pregunta de prueba', 'tipo_riesgo' => 'medio', 'status' => 1])->assertRedirect();
        $this->assertDatabaseHas('licenciatura', ['nombre' => 'Nueva licenciatura']);
        $this->assertDatabaseHas('genero', ['nombre' => 'Género de prueba']);
        $this->assertDatabaseHas('grupo', ['nombre' => 'Nuevo grupo']);
        $this->assertDatabaseHas('pregunta', ['planteamiento' => 'Pregunta de prueba', 'encuesta_id' => $survey]);
        foreach ([User::COORDINADOR, User::ALUMNO] as $role) {
            $this->actingAs(User::factory()->create(['rol_id' => $role]));
            foreach (['/catalogos/licenciatura', '/catalogos/genero', '/catalogos/grupo', '/encuestas', '/preguntas'] as $path) {
                $this->post($path, [])->assertForbidden();
            }
        }
    }

    public function test_yii_create_urls_keep_admin_authorization_and_fixed_roles(): void
    {
        $this->actingAs(User::factory()->create(['rol_id' => User::ADMIN]));
        foreach (['alumno' => User::ALUMNO, 'coordinador' => User::COORDINADOR] as $kind => $role) {
            $path = '/backend/web/index.php?r='.$kind.'/create';
            $this->get($path)->assertOk();
            $data = $this->account('legacy-'.$kind);
            unset($data['password_confirmation']);
            $this->post($path, ['User' => array_merge($data, ['rol_id' => User::ADMIN])])->assertRedirect();
            $this->assertDatabaseHas('user', ['username' => $data['username'], 'rol_id' => $role, 'status' => User::ACTIVE]);
        }
        $this->actingAs(User::factory()->create(['rol_id' => User::COORDINADOR]));
        $this->post('/backend/web/index.php?r=alumno/create', ['User' => $this->account('rechazada')])->assertForbidden();
        $this->assertDatabaseMissing('user', ['username' => 'rechazada']);
    }
}
