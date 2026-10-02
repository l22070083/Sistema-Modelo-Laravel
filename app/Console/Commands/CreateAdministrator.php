<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateAdministrator extends Command
{
    protected $signature = 'modelo:crear-administrador';

    protected $description = 'Crear el administrador inicial de una instalación local nueva, sin contraseñas predefinidas';

    public function handle(): int
    {
        if (! app()->environment('local') || ! $this->input->isInteractive()) {
            $this->error('Este comando requiere una terminal interactiva y APP_ENV=local.');

            return self::FAILURE;
        }
        if (User::where('rol_id', User::ADMIN)->exists()) {
            $this->error('Ya existe un administrador. Usa la gestión autenticada cuando esté disponible.');

            return self::FAILURE;
        }
        $data = [
            'nombre' => $this->ask('Nombre'), 'username' => $this->ask('Usuario'), 'email' => $this->ask('Correo'),
            'password' => $this->secret('Contraseña (mínimo 8 caracteres, una mayúscula y un carácter especial)'),
            'password_confirmation' => $this->secret('Confirma la contraseña'),
        ];
        $validation = Validator::make($data, [
            'nombre' => 'required|string|max:255', 'username' => 'required|string|max:255|unique:user,username',
            'email' => 'required|email|max:255|unique:user,email',
            'password' => ['required', 'string', 'min:8', 'confirmed', function ($attribute, $value, $fail) {
                if (strlen($value) > 72 || str_contains($value, "\0") || ! preg_match('/\p{Lu}/u', $value) || ! preg_match('/[\p{P}\p{S}]/u', $value)) {
                    $fail('La contraseña requiere una mayúscula, un carácter especial y máximo 72 bytes.');
                }
            }],
        ]);
        if ($validation->fails()) {
            foreach ($validation->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }
        $admin = new User;
        $admin->forceFill([
            'nombre' => $data['nombre'], 'username' => $data['username'], 'email' => $data['email'],
            'rol_id' => User::ADMIN, 'status' => User::ACTIVE,
            'password_hash' => Hash::make($data['password']), 'auth_key' => bin2hex(random_bytes(16)),
        ])->save();
        $this->info('Administrador inicial creado.');

        return self::SUCCESS;
    }
}
