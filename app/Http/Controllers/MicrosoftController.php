<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Audit;
use App\Services\MicrosoftIdentity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MicrosoftController extends Controller
{
    public function begin(Request $request): RedirectResponse
    {
        abort_unless(config('services.microsoft.enabled') && config('services.microsoft.client_id'), 503, 'Microsoft todavía no está configurado.');
        $tenant = config('services.microsoft.tenant');
        abort_unless(preg_match('/^(common|organizations|consumers|[a-f0-9-]{36})$/iD', $tenant), 503);
        $link = null;
        if ($request->isMethod('post')) {
            $request->validate(['password' => ['required', 'string']]);
            abort_unless($request->user() && password_verify($request->password, $request->user()->password_hash), 403);
            $link = $request->user()->id;
        }
        $state = Str::random(40);
        $nonce = Str::random(40);
        $verifier = Str::random(64);
        $redirect = config('services.microsoft.redirect_uri') ?: route('microsoft.callback');
        $request->session()->put('oauth_state', ['state' => $state, 'nonce' => $nonce, 'verifier' => $verifier, 'created_at' => time(), 'redirect_uri' => $redirect, 'link_user_id' => $link]);

        return redirect('https://login.microsoftonline.com/'.$tenant.'/oauth2/v2.0/authorize?'.http_build_query([
            'client_id' => config('services.microsoft.client_id'), 'response_type' => 'code', 'redirect_uri' => $redirect,
            'response_mode' => 'query', 'scope' => 'openid profile email User.Read', 'prompt' => 'select_account',
            'state' => $state, 'nonce' => $nonce, 'code_challenge_method' => 'S256',
            'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '='),
        ]));
    }

    public function callback(Request $request, MicrosoftIdentity $identity): RedirectResponse
    {
        $code = $request->query('code');
        $state = $request->query('state');
        $providerError = $request->query('error');
        if (! is_string($state) || $state === '' || ((! is_string($code) || $code === '') && (! is_string($providerError) || $providerError === ''))) {
            return redirect()->route('login')->with('error', 'Para iniciar sesión con Microsoft, utiliza el botón «Continuar con Microsoft». La dirección de retorno no se abre directamente.');
        }
        $session = $request->session()->pull('oauth_state');
        if (! is_array($session) || ! is_string($session['state'] ?? null) || ! is_int($session['created_at'] ?? null)
            || ! hash_equals($session['state'], $state) || time() > $session['created_at'] + 600 || $session['created_at'] > time()) {
            return redirect()->route('login')->with('error', 'Tu solicitud de Microsoft venció o no corresponde a esta sesión. Vuelve a iniciar desde el botón «Continuar con Microsoft».');
        }
        if (is_string($providerError) && $providerError !== '') {
            return redirect()->route('login')->with('error', $providerError === 'access_denied'
                ? 'El inicio de sesión con Microsoft fue cancelado. Puedes intentarlo de nuevo.'
                : 'Microsoft no pudo completar el inicio de sesión. Inténtalo de nuevo desde el botón «Continuar con Microsoft».');
        }
        try {
            $tokens = $identity->exchange($code, $session['verifier'], $session['redirect_uri']);
            $claims = $identity->verify($tokens['id_token'], $session['nonce']);
            $criteria = ['proveedor' => 'microsoft', 'tenant_id' => strtolower($claims['tid']), 'subject_id' => strtolower($claims['oid'])];
            $binding = DB::table('identidad_externa')->where($criteria)->first();
            if ($session['link_user_id'] !== null) {
                abort_unless($request->user() && $request->user()->id === $session['link_user_id'], 403);
                abort_if($binding && $binding->user_id != $session['link_user_id'], 403);
                if (! $binding) {
                    DB::transaction(function () use ($criteria, $session): void {
                        DB::table('identidad_externa')->insert($criteria + ['user_id' => $session['link_user_id'], 'created_at' => now()]);
                        Audit::record('VINCULO_MICROSOFT', 'Contraseña local verificada.');
                    });
                }

                return redirect()->route('perfil')->with('success', 'Cuenta Microsoft vinculada.');
            }
            if (! $binding) {
                $profile = $identity->profile($tokens['access_token']);
                abort_unless(strcasecmp((string) ($profile['id'] ?? ''), $claims['oid']) === 0, 403);
                $email = $profile['mail'] ?? $profile['userPrincipalName'] ?? null;
                if (! is_string($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new \RuntimeException('Correo inválido.');
                }
                if (User::where('email', $email)->exists()) {
                    return redirect()->route('login')->with('error', 'Inicia sesión con tu cuenta local y vincula Microsoft desde tu perfil.');
                }
                DB::transaction(function () use ($criteria, $profile, $email): void {
                    $user = new User;
                    $user->nombre = $profile['givenName'] ?? $profile['displayName'] ?? 'Alumno';
                    $user->apellidos = $profile['surname'] ?? '';
                    $user->username = 'ms_'.Str::lower(Str::random(20));
                    $user->email = $email;
                    $user->rol_id = 3;
                    $user->status = 5;
                    $user->auth_key = bin2hex(random_bytes(16));
                    $user->password_hash = Hash::make('A!'.Str::random(40));
                    $user->verification_token = bin2hex(random_bytes(24)).'_'.time();
                    $user->save();
                    DB::table('identidad_externa')->insert($criteria + ['user_id' => $user->id, 'created_at' => now()]);
                    RegistrationController::sendVerification($user);
                });

                return redirect()->route('login')->with('success', RegistrationController::NOTICE);
            }
            $user = User::findOrFail($binding->user_id);
            if ($user->status === 5 || ($user->status === 0 && $user->verification_token)) {
                return redirect()->route('login')->with('success', RegistrationController::NOTICE);
            }
            abort_unless($user->status === 10, 403);
            Auth::login($user);
            $request->session()->regenerate();
            $request->session()->put('auth_key', $user->auth_key);

            return redirect()->route($user->rol_id === User::ALUMNO ? 'inicio' : 'panel');
        } catch (\Throwable $error) {
            logger()->error('Falló Microsoft', ['tipo' => get_class($error)]);

            return redirect()->route('login')->with('error', 'No se pudo verificar tu cuenta Microsoft. Revisa su configuración y estado.');
        }
    }
}
