<?php

namespace Tests\Feature;

use App\Http\Controllers\RegistrationController;
use App\Models\User;
use App\Services\MicrosoftIdentity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RolePanelTest extends TestCase
{
    use RefreshDatabase;

    private function grant(User $coordinator, User $admin, string $section, bool $edit = false): void
    {
        DB::table('coordinador_permiso')->updateOrInsert(['coordinador_id' => $coordinator->id, 'seccion' => $section],
            ['puede_ver' => 1, 'puede_editar' => (int) $edit, 'otorgado_por' => $admin->id, 'updated_at' => now()]);
    }

    public function test_local_login_routes_accounts_by_stored_role(): void
    {
        foreach ([1 => '/panel', 2 => '/panel', 3 => '/inicio'] as $role => $destination) {
            $user = User::factory()->create(['rol_id' => $role, 'password_hash' => Hash::make('ClaveModelo!2026')]);
            $this->post('/login', ['username' => $user->username, 'password' => 'ClaveModelo!2026', 'rol_id' => 1])->assertRedirect($destination);
            $this->assertAuthenticatedAs($user);
            $this->get('/')->assertRedirect($destination);
            $this->get('/frontend/web/index.php?r=site/index')->assertRedirect($destination);
            $this->get('/backend/web/index.php?r=site/index')->assertRedirect($destination);
            $this->get($destination)->assertOk()->assertSee('class="modelo-brand" href="'.url($destination).'"', false);
            if ($role === 3) {
                $this->get('/panel')->assertForbidden();
            }
            $this->post('/logout')->assertRedirect('/login');
        }
    }

    public function test_only_admin_designates_existing_accounts_and_permissions_start_denied(): void
    {
        $candidate = User::factory()->create(['status' => 5, 'verification_token' => 'candidate-token']);
        $admin = User::factory()->create(['rol_id' => 1]);
        $coordinator = User::factory()->create(['rol_id' => 2]);
        $key = $candidate->auth_key;
        $data = ['user_id' => $candidate->id, 'motivo' => 'Designación de coordinación', 'rol_id' => 1, 'permisos' => ['personales' => ['ver' => 1]]];
        $this->actingAs($coordinator)->post('/coordinadores/designar', $data)->assertForbidden();
        $this->actingAs($candidate)->post('/coordinadores/designar', $data)->assertRedirect('/login');
        $this->actingAs($admin)->post('/coordinadores/designar', $data)->assertRedirect('/coordinadores/'.$candidate->id);
        $candidate->refresh();
        $this->assertSame(2, $candidate->rol_id);
        $this->assertSame(10, $candidate->status);
        $this->assertNull($candidate->verification_token);
        $this->assertNotSame($key, $candidate->auth_key);
        $this->assertSame(4, DB::table('coordinador_permiso')->where('coordinador_id', $candidate->id)->count());
        $this->assertSame(0, DB::table('coordinador_permiso')->where('coordinador_id', $candidate->id)->sum('puede_ver'));
        $this->assertDatabaseHas('auditoria_sistema', ['actor_id' => $admin->id, 'evento' => 'DESIGNACION_COORDINADOR', 'motivo' => $data['motivo']]);
        $this->post('/coordinadores/designar', ['user_id' => $admin->id, 'motivo' => 'No cambiar administradores'])->assertNotFound();
    }

    public function test_coordinator_dashboard_does_not_leak_ungranted_student_or_clinical_data(): void
    {
        $admin = User::factory()->create(['rol_id' => 1]);
        $coord = User::factory()->create(['rol_id' => 2]);
        $student = User::factory()->create(['nombre' => 'Alumno reservado']);
        DB::table('expediente_alumno')->insert(['user_id' => $student->id, 'nombres' => $student->nombre, 'apellidos' => 'Prueba',
            'notas_coordinador' => 'SEGUIMIENTO PRIVADO', 'bloqueado' => 0, 'created_at' => now(), 'updated_at' => now()]);
        $this->actingAs($coord)->get('/panel')->assertOk()->assertDontSee($student->nombre)->assertDontSee('Distribución Global');
        $this->get('/coordinadores')->assertForbidden();
        $this->grant($coord, $admin, 'personales');
        $response = $this->get('/panel')->assertOk()->assertSee($student->nombre)->assertDontSee('SEGUIMIENTO PRIVADO')->assertDontSee('Clasificación');
        $this->assertSame(['alumnos' => 1, 'expedientes' => 1, 'pendientes' => 0], $response->viewData('metrics'));
        $this->get('/panel?q=Alumno')->assertOk()->assertSee($student->nombre);
        $this->get('/panel?q=Inexistente')->assertOk()->assertDontSee($student->nombre);
    }

    public function test_microsoft_login_uses_pkce_and_never_assigns_roles_from_claims(): void
    {
        Mail::fake();
        config(['services.microsoft.enabled' => true, 'services.microsoft.client_id' => 'application-client', 'services.microsoft.tenant' => 'common', 'services.microsoft.redirect_uri' => 'http://127.0.0.1:8002/microsoft/callback']);
        foreach (['/login', '/acceso-administrativo'] as $page) {
            $this->get($page)->assertOk()->assertSee('Continuar con Microsoft')->assertSee(route('microsoft.login'));
        }
        $begin = $this->get('/microsoft')->assertRedirect();
        parse_str(parse_url($begin->headers->get('Location'), PHP_URL_QUERY), $query);
        $this->assertSame('S256', $query['code_challenge_method']);
        $session = session('oauth_state');
        $this->assertSame(rtrim(strtr(base64_encode(hash('sha256', $session['verifier'], true)), '+/', '-_'), '='), $query['code_challenge']);
        $this->app->instance(MicrosoftIdentity::class, new class extends MicrosoftIdentity
        {
            public function exchange(string $code, string $verifier, string $redirectUri): array
            {
                return ['id_token' => 'verified', 'access_token' => 'graph'];
            }

            public function verify(string $token, string $nonce): array
            {
                return ['tid' => '11111111-1111-1111-1111-111111111111', 'oid' => '22222222-2222-2222-2222-222222222222', 'roles' => ['admin', 'coordinador']];
            }

            public function profile(string $accessToken): array
            {
                return ['id' => '22222222-2222-2222-2222-222222222222', 'mail' => 'coordinacion@example.test', 'givenName' => 'Microsoft'];
            }
        });
        $this->withSession(['oauth_state' => $session])->get('/microsoft/callback?code=code&state='.$session['state'])->assertRedirect('/login');
        $user = User::where('email', 'coordinacion@example.test')->firstOrFail();
        $this->assertSame(3, $user->rol_id);
        $this->assertSame(5, $user->status);
        $this->assertGuest();
        $admin = User::factory()->create(['rol_id' => 1]);
        $this->actingAs($admin)->post('/coordinadores/designar', ['user_id' => $user->id, 'motivo' => 'Responsable de coordinación'])->assertRedirect();
        auth()->logout();
        $this->withSession(['oauth_state' => $session])->get('/microsoft/callback?code=code&state='.$session['state'])->assertRedirect('/panel');
        $this->assertAuthenticatedAs($user->fresh());
        $this->get('/panel')->assertOk()->assertDontSee('Listado de Alumnos');
        $this->get('/catalogos/licenciatura')->assertForbidden();
    }

    private function mockMicrosoftAccount(array $claims, ?array $profile = null): array
    {
        Mail::fake();
        config(['services.microsoft.enabled' => true, 'services.microsoft.client_id' => 'application-client',
            'services.microsoft.tenant' => 'common', 'services.microsoft.redirect_uri' => 'http://127.0.0.1:8000/microsoft/callback']);
        $identity = $this->partialMock(MicrosoftIdentity::class);
        $identity->shouldReceive('exchange')->andReturn(['id_token' => 'verified', 'access_token' => 'graph']);
        $identity->shouldReceive('verify')->andReturn($claims);
        if ($profile === null) {
            $identity->shouldNotReceive('profile');
        } else {
            $identity->shouldReceive('profile')->once()->andReturn($profile);
        }
        $begin = $this->get('/microsoft')->assertRedirect();
        $this->assertStringStartsWith('https://login.microsoftonline.com/common/oauth2/v2.0/authorize?', $begin->headers->get('Location'));

        return session('oauth_state');
    }

    public function test_personal_microsoft_registration_stays_pending_until_approved(): void
    {
        $claims = ['tid' => MicrosoftIdentity::CONSUMER_TENANT, 'oid' => '22222222-2222-2222-2222-222222222222',
            'email' => 'alumno@outlook.com', 'name' => 'Cuenta Personal', 'roles' => ['admin', 'coordinador']];
        $session = $this->mockMicrosoftAccount($claims);
        $callback = '/microsoft/callback?code=code&state='.$session['state'];
        $this->withSession(['oauth_state' => $session])->get($callback)->assertRedirect('/login')
            ->assertSessionHas('success', RegistrationController::NOTICE);
        $user = User::where('email', $claims['email'])->firstOrFail();
        $this->assertSame(User::ALUMNO, $user->rol_id);
        $this->assertSame(User::PENDING, $user->status);
        $this->assertSame('Cuenta Personal', $user->nombre);
        $this->assertNotNull($user->verification_token);
        $this->assertDatabaseHas('identidad_externa', ['user_id' => $user->id, 'proveedor' => 'microsoft',
            'tenant_id' => $claims['tid'], 'subject_id' => $claims['oid']]);
        $this->assertGuest();
        Mail::assertSentCount(1);
        $this->withSession(['oauth_state' => $session])->get($callback)->assertRedirect('/login')->assertSessionHas('success');
        $this->assertDatabaseCount('user', 1);
        $this->assertDatabaseCount('identidad_externa', 1);
        $this->assertGuest();
        $admin = User::factory()->create(['rol_id' => User::ADMIN]);
        $this->actingAs($admin)->post('/notificaciones/alta', ['modo' => 'individual', 'seleccion' => [$user->id]])->assertRedirect();
        auth()->logout();
        $this->withSession(['oauth_state' => $session])->get($callback)->assertRedirect('/inicio');
        $this->assertAuthenticatedAs($user->fresh());
        $this->get('/panel')->assertForbidden();
    }

    public function test_personal_microsoft_email_collision_does_not_link_an_existing_local_account(): void
    {
        $existing = User::factory()->create(['email' => 'alumno@outlook.com', 'rol_id' => User::ADMIN]);
        $session = $this->mockMicrosoftAccount(['tid' => MicrosoftIdentity::CONSUMER_TENANT,
            'oid' => '22222222-2222-2222-2222-222222222222', 'email' => $existing->email]);
        $this->withSession(['oauth_state' => $session])->get('/microsoft/callback?code=code&state='.$session['state'])
            ->assertRedirect('/login')->assertSessionHas('error', 'Inicia sesión con tu cuenta local y vincula Microsoft desde tu perfil.');
        $this->assertDatabaseCount('user', 1);
        $this->assertDatabaseCount('identidad_externa', 0);
        $this->assertGuest();
        Mail::assertNothingSent();
    }

    public function test_personal_microsoft_can_register_with_a_valid_preferred_username_email(): void
    {
        $session = $this->mockMicrosoftAccount(['tid' => MicrosoftIdentity::CONSUMER_TENANT,
            'oid' => '22222222-2222-2222-2222-222222222222', 'email' => '',
            'preferred_username' => 'personal@hotmail.com', 'given_name' => 'Cuenta', 'family_name' => 'Personal']);
        $this->withSession(['oauth_state' => $session])->get('/microsoft/callback?code=code&state='.$session['state'])
            ->assertRedirect('/login')->assertSessionHas('success');
        $this->assertDatabaseHas('user', ['email' => 'personal@hotmail.com', 'nombre' => 'Cuenta', 'apellidos' => 'Personal',
            'rol_id' => User::ALUMNO, 'status' => User::PENDING]);
        $this->assertGuest();
        Mail::assertSentCount(1);
    }

    public function test_personal_microsoft_without_a_contact_email_has_an_actionable_message(): void
    {
        $session = $this->mockMicrosoftAccount(['tid' => MicrosoftIdentity::CONSUMER_TENANT,
            'oid' => '22222222-2222-2222-2222-222222222222', 'preferred_username' => '5551234']);
        $this->withSession(['oauth_state' => $session])->get('/microsoft/callback?code=code&state='.$session['state'])
            ->assertRedirect('/login')->assertSessionHas('error', 'Tu cuenta Microsoft no proporcionó un correo válido. Puedes usar el registro local o una cuenta Microsoft con correo.');
        $this->assertDatabaseCount('user', 0);
        $this->assertDatabaseCount('identidad_externa', 0);
        $this->assertGuest();
        Mail::assertNothingSent();
    }

    public function test_organizational_microsoft_profile_must_match_the_verified_identity(): void
    {
        $session = $this->mockMicrosoftAccount(['tid' => '11111111-1111-1111-1111-111111111111',
            'oid' => '22222222-2222-2222-2222-222222222222'],
            ['id' => '33333333-3333-3333-3333-333333333333', 'mail' => 'otro@example.com']);
        $this->withSession(['oauth_state' => $session])->get('/microsoft/callback?code=code&state='.$session['state'])
            ->assertRedirect('/login')->assertSessionHas('error');
        $this->assertDatabaseCount('user', 0);
        $this->assertDatabaseCount('identidad_externa', 0);
        $this->assertGuest();
        Mail::assertNothingSent();
    }

    public function test_student_dossier_shows_summary_without_institutional_notes_or_reasons(): void
    {
        $student = User::factory()->create();
        $id = DB::table('expediente_alumno')->insertGetId(['user_id' => $student->id, 'nombres' => $student->nombre, 'apellidos' => $student->apellidos,
            'notas_coordinador' => 'NOTA PRIVADA',
            'bloqueado' => 1, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('expediente_historial')->insert(['expediente_id' => $id, 'user_id' => $student->id, 'accion' => 'NOTA', 'detalles' => 'HISTORIAL PRIVADO', 'fecha' => now()]);
        $this->actingAs($student)->get('/mi-expediente')->assertOk()->assertSee('Expediente Protegido por Coordinación')
            ->assertSee('Historial de Revisiones')->assertDontSee('Clasificación')->assertDontSee('NOTA PRIVADA')->assertDontSee('HISTORIAL PRIVADO');
        $this->get('/mi-expediente/editar')->assertRedirect('/perfil');
    }

    public function test_unconfigured_microsoft_button_is_disabled_and_cannot_start_authorization(): void
    {
        config(['services.microsoft.enabled' => false]);
        $this->get('/login')->assertOk()->assertSee('Acceso institucional pendiente de activación.')
            ->assertSee('disabled aria-describedby="microsoft-status"', false)->assertDontSee('href="'.route('microsoft.login').'"', false);
        $this->get('/microsoft')->assertStatus(503);
    }

    public function test_opening_microsoft_callback_directly_returns_to_login_without_consuming_pending_login(): void
    {
        $identity = $this->mock(MicrosoftIdentity::class);
        $identity->shouldNotReceive('exchange', 'verify', 'profile');
        $this->get('/microsoft/callback')->assertRedirect('/login')->assertSessionHas('error');
        $this->assertGuest();
        $this->get('/login')->assertOk()->assertSee('La dirección de retorno no se abre directamente.');
        $session = ['state' => 'pending-state', 'created_at' => time()];
        $this->withSession(['oauth_state' => $session])->get('/microsoft/callback')->assertRedirect('/login')->assertSessionHas('oauth_state', $session);
        $this->assertDatabaseCount('identidad_externa', 0);
    }

    public function test_invalid_or_expired_microsoft_callbacks_reject_authentication_with_a_friendly_message(): void
    {
        $identity = $this->mock(MicrosoftIdentity::class);
        $identity->shouldNotReceive('exchange', 'verify', 'profile');
        foreach ([null, ['state' => 'other-state', 'created_at' => time()], ['state' => 'expected-state', 'created_at' => time() - 601], ['state' => 'expected-state', 'created_at' => time() + 60]] as $session) {
            $this->withSession(['oauth_state' => $session])->get('/microsoft/callback?code=code&state=expected-state')
                ->assertRedirect('/login')->assertSessionHas('error')->assertSessionMissing('oauth_state');
            $this->assertGuest();
        }
        $this->assertDatabaseCount('identidad_externa', 0);
    }

    public function test_microsoft_cancellation_does_not_exchange_tokens_or_display_provider_details(): void
    {
        $identity = $this->mock(MicrosoftIdentity::class);
        $identity->shouldNotReceive('exchange', 'verify', 'profile');
        $this->withSession(['oauth_state' => ['state' => 'expected-state', 'created_at' => time()]])
            ->get('/microsoft/callback?error=access_denied&state=expected-state&error_description=INTERNAL_PROVIDER_DETAIL')
            ->assertRedirect('/login')->assertSessionHas('error', 'El inicio de sesión con Microsoft fue cancelado. Puedes intentarlo de nuevo.')
            ->assertSessionMissing('oauth_state');
        $this->get('/login')->assertOk()->assertSee('El inicio de sesión con Microsoft fue cancelado.')->assertDontSee('INTERNAL_PROVIDER_DETAIL');
        $this->assertGuest();
        $this->assertDatabaseCount('identidad_externa', 0);
    }
}
