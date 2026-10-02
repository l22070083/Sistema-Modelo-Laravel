<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PasswordPolicy implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || mb_strlen($value) < 8 || strlen($value) > 72 || str_contains($value, "\0") || ! preg_match('/\p{Lu}/u', $value) || ! preg_match('/[\p{P}\p{S}]/u', $value)) {
            $fail('Usa al menos 8 caracteres, una mayúscula y un carácter especial (máximo 72 bytes).');
        }
    }
}
