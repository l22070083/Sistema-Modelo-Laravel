<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return ['username' => fake()->unique()->userName(), 'nombre' => fake()->firstName(), 'apellidos' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(), 'rol_id' => 3, 'status' => 10,
            'password_hash' => password_hash('ClaveModelo!2026', PASSWORD_BCRYPT, ['cost' => 4]), 'auth_key' => bin2hex(random_bytes(16)),
            'matricula' => '000123'];
    }
}
