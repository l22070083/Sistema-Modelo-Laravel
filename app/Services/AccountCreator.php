<?php

namespace App\Services;

use App\Models\User;
use App\Rules\PasswordPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AccountCreator
{
    public static function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'apellidos' => ['nullable', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:user,username'],
            'email' => ['required', 'email', 'max:255', 'unique:user,email'],
            'password' => ['required', 'confirmed', new PasswordPolicy],
        ];
    }

    public static function validate(Request $request, array $extraRules = []): array
    {
        foreach (['nombre', 'apellidos', 'username', 'email'] as $field) {
            if (is_string($request->input($field))) {
                $request->merge([$field => trim($request->input($field))]);
            }
        }

        return $request->validate(array_merge(self::rules(), $extraRules));
    }

    public static function create(array $data, int $role): User
    {
        abort_unless(auth()->user()?->rol_id === User::ADMIN && auth()->user()->status === User::ACTIVE, 403);
        $event = match ($role) {
            User::ADMIN => 'CREACION_ADMINISTRADOR',
            User::COORDINADOR => 'CREACION_COORDINADOR',
            User::ALUMNO => 'CREACION_ALUMNO',
        };

        return DB::transaction(function () use ($data, $role, $event): User {
            $user = new User;
            $fields = ['nombre', 'apellidos', 'username', 'email'];
            if ($role === User::ALUMNO) {
                $fields = array_merge($fields, ['matricula', 'licenciatura_id', 'genero_id', 'grupo_id']);
            }
            $user->fill(collect($data)->only($fields)->all());
            $user->rol_id = $role;
            $user->status = User::ACTIVE;
            $user->password_hash = Hash::make($data['password']);
            $user->auth_key = bin2hex(random_bytes(16));
            $user->save();

            if ($role === User::COORDINADOR) {
                foreach (array_keys(config('dossier.sections')) as $section) {
                    DB::table('coordinador_permiso')->insert([
                        'coordinador_id' => $user->id, 'seccion' => $section,
                        'puede_ver' => 0, 'puede_editar' => 0,
                        'otorgado_por' => auth()->id(), 'updated_at' => now(),
                    ]);
                }
            }
            Audit::record($event, 'Alta manual por el administrador.', ['user_id' => $user->id, 'rol_id' => $role], $role === User::ALUMNO ? $user->id : null);

            return $user;
        });
    }
}
