<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AccountCreator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AccessController extends Controller
{
    public function login(Request $request)
    {
        $request->merge(['username' => is_string($request->username) ? trim($request->username) : $request->username]);
        $data = $request->validate(['username' => ['required', 'string', 'max:255'], 'password' => ['required', 'string', 'max:255']]);
        $key = 'login:'.hash('sha256', mb_strtolower($data['username']).'|'.$request->ip());
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['username' => 'Demasiados intentos. Intenta de nuevo en un minuto.']);
        }
        // No reescribir hashes Yii: password_verify admite sus hashes bcrypt existentes.
        $user = User::where('username', $data['username'])->where('status', User::ACTIVE)->first();
        if (! $user || ! password_verify($data['password'], $user->password_hash)) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['username' => 'Usuario o contraseña incorrectos, o cuenta pendiente de alta.']);
        }
        RateLimiter::clear($key);
        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('auth_key', $user->auth_key);

        return redirect()->route($user->rol_id === User::ALUMNO ? 'inicio' : 'panel');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function coordinadores()
    {
        return view('coordinadores', ['coordinadores' => User::where('rol_id', User::COORDINADOR)->orderBy('nombre')->paginate(20),
            'candidatos' => User::where('rol_id', User::ALUMNO)->where(function ($query): void {
                $query->where('status', User::ACTIVE)->orWhere('status', User::PENDING)->orWhere(function ($query): void {
                    $query->where('status', User::INACTIVE)->whereNotNull('verification_token');
                });
            })->orderBy('nombre')->get(['id', 'nombre', 'apellidos', 'email'])]);
    }

    public function crearCoordinador(Request $request)
    {
        $user = AccountCreator::create(AccountCreator::validate($request), User::COORDINADOR);

        return redirect()->route('coordinador.edit', $user->id)->with('success', 'Coordinador creado y activo. Asigna sus grupos y permisos por sección.');
    }

    public function administradores()
    {
        return view('administradores', ['administradores' => User::where('rol_id', User::ADMIN)->orderBy('nombre')->paginate(20)]);
    }

    public function crearAdministrador(Request $request)
    {
        AccountCreator::create(AccountCreator::validate($request), User::ADMIN);

        return redirect()->route('administradores')->with('success', 'Administrador creado y activo.');
    }
}
