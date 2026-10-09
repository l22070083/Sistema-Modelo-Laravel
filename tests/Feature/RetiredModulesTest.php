<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\RetiredModulesSchema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RetiredModulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_retired_catalog_is_unavailable_without_login(): void
    {
        $this->get('/catalogos/genero')->assertNotFound();
        $this->get('/catalogos/genero/1')->assertNotFound();
        $this->post('/catalogos/genero')->assertNotFound();
        $this->post('/catalogos/genero/1/estado')->assertNotFound();
    }

    public function test_retired_modules_are_absent_from_navigation_and_schema(): void
    {
        $admin = User::factory()->create(['rol_id' => 1]);
        $this->actingAs($admin)->get('/panel')->assertOk()
            ->assertDontSee('Géneros')->assertDontSee('Alertas de Salud')
            ->assertDontSee('Atención Estudiantil')->assertDontSee('Resultados')
            ->assertSee('Encuestas y preguntas')->assertSee('Expedientes Clínicos');
        foreach (['genero', 'resultados_salud', 'resultados_chaside'] as $table) {
            $this->assertFalse(Schema::hasTable($table));
        }
        foreach (RetiredModulesSchema::COLUMNS as $table => $columns) {
            foreach ($columns as $column) {
                $this->assertFalse(Schema::hasColumn($table, $column));
            }
        }
        foreach (['/alertas', '/atencion', '/resultados', '/resultados/123', '/catalogos/genero'] as $path) {
            $this->get($path)->assertNotFound();
        }
        $this->get('/index.php?r=salud%2Findex')->assertStatus(410);
        $this->get('/index.php?r=atencion%2Findex')->assertStatus(410);
    }

    public function test_upgrading_an_existing_schema_preserves_accounts_and_answers(): void
    {
        $student = User::factory()->create();
        $survey = DB::table('encuesta')->insertGetId(['titulo' => 'Encuesta conservada', 'tipo_test' => 'salud', 'estado' => 1]);
        $question = DB::table('pregunta')->insertGetId(['encuesta_id' => $survey, 'planteamiento' => 'Pregunta conservada', 'status' => 1]);
        DB::table('respuesta_alumno')->insert(['user_id' => $student->id, 'pregunta_id' => $question, 'respuesta' => 'Si', 'fecha_registro' => now()]);
        $beforeUser = (array) DB::table('user')->find($student->id);
        $beforeAnswers = DB::table('respuesta_alumno')->get()->map(fn ($row) => (array) $row)->all();
        foreach (['genero', 'resultados_salud', 'resultados_chaside'] as $table) {
            Schema::create($table, function (Blueprint $table) { $table->increments('id'); });
            DB::table($table)->insert(['id' => 1]);
        }
        foreach (RetiredModulesSchema::COLUMNS as $table => $columns) {
            Schema::table($table, function (Blueprint $table) use ($columns) {
                foreach ($columns as $column) { $table->string($column)->nullable(); }
            });
        }
        Schema::table('user', fn (Blueprint $table) => $table->index('genero_id'));
        DB::table('user')->where('id', $student->id)->update(['genero_id' => '1']);
        DB::table('pregunta')->where('id', $question)->update(['tipo_riesgo' => 'alto']);
        DB::table('coordinador_permiso')->insert([
            ['coordinador_id' => $student->id, 'seccion' => 'clasificacion', 'puede_ver' => 1, 'puede_editar' => 1, 'otorgado_por' => $student->id, 'updated_at' => now()],
            ['coordinador_id' => $student->id, 'seccion' => 'personales', 'puede_ver' => 1, 'puede_editar' => 0, 'otorgado_por' => $student->id, 'updated_at' => now()],
        ]);
        DB::table('auditoria_sistema')->insert(['evento' => 'CLASIFICAR', 'motivo' => 'Dato retirado', 'fecha' => now(), 'datos' => '{}']);
        RetiredModulesSchema::apply(DB::connection()->getPdo());
        RetiredModulesSchema::apply(DB::connection()->getPdo());
        $this->assertSame($beforeUser, (array) DB::table('user')->find($student->id));
        $this->assertSame($beforeAnswers, DB::table('respuesta_alumno')->get()->map(fn ($row) => (array) $row)->all());
        $this->assertDatabaseHas('pregunta', ['id' => $question, 'planteamiento' => 'Pregunta conservada']);
        $this->assertDatabaseHas('coordinador_permiso', ['seccion' => 'personales', 'puede_ver' => 1, 'puede_editar' => 0]);
        $this->assertDatabaseMissing('coordinador_permiso', ['seccion' => 'clasificacion']);
        $this->assertDatabaseMissing('auditoria_sistema', ['evento' => 'CLASIFICAR']);
        $this->assertSame(['tables' => [], 'columns' => []], RetiredModulesSchema::plan(DB::connection()->getPdo()));
    }
}
