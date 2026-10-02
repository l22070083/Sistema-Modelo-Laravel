<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'], 'apellidos' => ['nullable', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:user,username'],
            'email' => ['required', 'email', 'max:255', 'unique:user,email'],
            'password' => ['required', 'string', 'min:8', function ($attribute, $value, $fail) {
                if (strlen($value) > 72 || str_contains($value, "\0") || ! preg_match('/\p{Lu}/u', $value) || ! preg_match('/[\p{P}\p{S}]/u', $value)) {
                    $fail('Usa una mayúscula, un carácter especial y un máximo de 72 bytes.');
                }
            }],
        ]);
        $user = new User;
        $user->fill(collect($data)->except('password')->all());
        $user->rol_id = User::COORDINADOR;
        $user->status = User::ACTIVE;
        $user->password_hash = Hash::make($data['password']);
        $user->auth_key = bin2hex(random_bytes(16));
        $user->save();

        return redirect()->route('coordinadores')->with('success', 'Coordinador dado de alta.');
    }
}
