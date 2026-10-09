<?php

namespace App\Http\Controllers;

use App\Mail\AccountLink;
use App\Models\User;
use App\Rules\PasswordPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    public const NOTICE = 'Gracias por tu registro. Espera a que tu administrador o coordinador te dé de alta. Mantente al pendiente de tu correo.';

    public function form(): View
    {
        return view('registro', self::catalogs());
    }

    public static function catalogs(): array
    {
        return ['licenciaturas' => DB::table('licenciatura')->where('estado', 1)->get(), 'grupos' => DB::table('grupo')->where('estado', 1)->get()];
    }

    public static function profileRules(): array
    {
        return ['matricula' => ['required', 'string', 'max:50', 'regex:/^[0-9]+$/D'],
            'licenciatura_id' => ['required', 'integer', Rule::exists('licenciatura', 'id')->where('estado', 1)],
            'grupo_id' => ['nullable', 'integer', Rule::exists('grupo', 'id')->where('estado', 1)->where('licenciatura_id', request('licenciatura_id'))]];
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate(array_merge(self::profileRules(), ['username' => ['required', 'string', 'max:255', 'unique:user,username'],
            'email' => ['required', 'email', 'max:255', 'unique:user,email'], 'nombre' => ['required', 'string', 'max:255'], 'apellidos' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', new PasswordPolicy]]));
        DB::transaction(function () use ($data): void {
            $user = new User;
            $user->fill(collect($data)->except('password')->all());
            $user->rol_id = User::ALUMNO;
            $user->status = User::PENDING;
            $user->password_hash = Hash::make($data['password']);
            $user->auth_key = bin2hex(random_bytes(16));
            $user->verification_token = bin2hex(random_bytes(24)).'_'.time();
            $user->save();
            self::sendVerification($user);
        });

        return redirect()->route('login')->with('success', self::NOTICE);
    }

    public static function sendVerification(User $user): void
    {
        Mail::to($user->email)->send(new AccountLink('Verifica tu correo', route('email.verify', ['token' => $user->verification_token])));
    }

    public function verify(string $token): RedirectResponse
    {
        DB::transaction(function () use ($token): void {
            $user = User::where('verification_token', $token)->where('rol_id', User::ALUMNO)->whereIn('status', [0, 5])->lockForUpdate()->firstOrFail();
            $user->verification_token = null;
            $user->status = User::PENDING;
            $user->save();
        });

        return redirect()->route('login')->with('success', 'Correo verificado. '.self::NOTICE);
    }

    public function resend(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);
        $user = User::where('email', $request->email)->whereIn('status', [0, 5])->whereNotNull('verification_token')->first();
        if ($user) {
            self::sendVerification($user);
        }

        return back()->with('success', 'Si la cuenta está pendiente de verificación, recibirás un correo.');
    }

    public function recovery(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);
        DB::transaction(function () use ($request): void {
            $user = User::where('email', $request->email)->where('status', 10)->lockForUpdate()->first();
            if (! $user) {
                return;
            }
            $user->password_reset_token = bin2hex(random_bytes(24)).'_'.time();
            $user->save();
            Mail::to($user->email)->send(new AccountLink('Recupera tu contraseña', route('password.reset', ['token' => $user->password_reset_token])));
        });

        return back()->with('success', 'Si la cuenta está activa, recibirás un enlace de recuperación.');
    }

    public function reset(Request $request, string $token): View|RedirectResponse
    {
        abort_unless(preg_match('/_(\d+)$/D', $token, $matches) && (int) $matches[1] <= time() && (int) $matches[1] + 3600 >= time(), 400, 'Enlace vencido.');
        if ($request->isMethod('get')) {
            return view('recuperar', ['token' => $token]);
        }
        $data = $request->validate(['password' => ['required', 'confirmed', new PasswordPolicy]]);
        DB::transaction(function () use ($token, $data): void {
            $user = User::where('password_reset_token', $token)->where('status', 10)->lockForUpdate()->firstOrFail();
            $user->password_hash = Hash::make($data['password']);
            $user->password_reset_token = null;
            $user->auth_key = bin2hex(random_bytes(16));
            $user->save();
        });

        return redirect()->route('login')->with('success', 'Contraseña actualizada.');
    }

    public function profile(Request $request): View|RedirectResponse
    {
        if ($request->isMethod('post')) {
            $data = $request->validate(self::profileRules());
            $request->user()->fill($data)->save();

            return redirect()->route('home')->with('success', 'Perfil actualizado.');
        }

        return view('perfil', array_merge(self::catalogs(), ['user' => $request->user()]));
    }
}
