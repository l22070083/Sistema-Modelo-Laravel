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
        foreach (['panel', 'notificaciones', 'coordinadores', 'encuestas', 'expedientes', 'atencion', 'alertas', 'resultados', 'reportes', 'catalogos/grupo', 'catalogos/licenciatura', 'catalogos/genero'] as $path) {
            $this->get('/'.$path)->assertOk();
        }
        $email = 'mysql_'.bin2hex(random_bytes(5)).'@example.com';
        $this->post('/coordinadores', ['nombre' => 'Prueba MySQL', 'username' => 'mysql_'.bin2hex(random_bytes(5)), 'email' => $email, 'password' => 'ClaveModelo!2026'])->assertRedirect();
        $this->assertDatabaseHas('user', ['email' => $email, 'rol_id' => 2, 'status' => 10]);
    }

    public function test_application_database_account_cannot_access_original_database(): void
    {
        $this->expectException(QueryException::class);
        DB::select('SELECT COUNT(*) FROM tutoriasmv.user');
    }
}
