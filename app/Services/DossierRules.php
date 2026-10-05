<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DossierRules
{
    public static function categories(?string $value): array
    {
        if (! $value) {
            return [];
        }
        $decoded = json_decode($value, true);
        $items = is_array($decoded) ? $decoded : [$value];
        $legacy = ['Sin indicadores de atención prioritaria' => 'Sin dato de alarma', 'Seguimiento de salud física' => 'Salud Física', 'Atención socioemocional' => 'Atención Emocional', 'Atención psicopedagógica' => 'Atención Psicopedagógica'];

        return array_values(array_unique(array_map(fn (string $category): string => $legacy[$category] ?? $category, $items)));
    }

    public static function validate(array $data, array $fields): array
    {
        $required = ['nombres', 'apellidos', 'fecha_nacimiento', 'genero', 'estado_civil', 'licenciatura_id', 'domicilio', 'telefono', 'contacto_emergencia_nombre', 'contacto_emergencia_parentesco', 'contacto_emergencia_telefono', 'religion', 'apnp_tipo_sangre', 'apnp_factor_rh', 'q8_red_apoyo', 'q10_estado_emocional'];
        $rules = [];
        foreach ($fields as $field) {
            $rules[$field] = [in_array($field, $required, true) ? 'required' : 'nullable', 'string', 'max:10000'];
            if (in_array($field, ['nombres', 'apellidos', 'contacto_emergencia_nombre'], true)) {
                $rules[$field] = ['required', 'string', 'max:255'];
            }
            if (in_array($field, ['telefono', 'contacto_emergencia_telefono'], true)) {
                $rules[$field] = ['required', 'string', 'max:50'];
            }
            if (in_array($field, ['contacto_emergencia_parentesco', 'religion', 'ocupacion'], true)) {
                $rules[$field] = [in_array($field, $required, true) ? 'required' : 'nullable', 'string', 'max:100'];
            }
            if (preg_match('/^q[1-7]_.*(?<!detalle)$/', $field) || $field === 'q9_acomp_psicologico') {
                $rules[$field] = ['required', 'boolean'];
            }
        }
        $specific = ['fecha_nacimiento' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'], 'licenciatura_id' => ['required', 'integer', 'exists:licenciatura,id'],
            'genero' => ['required', Rule::in(['Masculino', 'Femenino', 'Otro'])], 'estado_civil' => ['required', Rule::in(['Soltero(a)', 'Casado(a)', 'Unión Libre', 'Otro'])],
            'apnp_tipo_sangre' => ['required', Rule::in(['O', 'A', 'B', 'AB'])], 'apnp_factor_rh' => ['required', Rule::in(['Positivo (+)', 'Negativo (-)'])],
            'q10_estado_emocional' => ['required', Rule::in(['Muy desfavorable', 'Desfavorable', 'Favorable', 'Muy favorable'])],
            'q11_necesita_apoyo' => ['required', 'array', 'min:1'], 'q11_necesita_apoyo.*' => [Rule::in(['Psicológico', 'De aprendizaje', 'Otro', 'Ninguno'])]];
        foreach ($specific as $field => $rule) {
            if (in_array(explode('.', $field)[0], $fields, true)) {
                $rules[$field] = $rule;
            }
        }
        $validator = Validator::make($data, $rules);
        $validator->after(function ($validation) use ($data, $fields): void {
            foreach ($fields as $field) {
                if (str_ends_with($field, '_detalle') && ($data[substr($field, 0, -8)] ?? 0) == 1 && trim((string) ($data[$field] ?? '')) === '') {
                    $validation->errors()->add($field, 'Describe la respuesta afirmativa.');
                }
            }
            $support = $data['q11_necesita_apoyo'] ?? [];
            if (is_array($support) && in_array('Ninguno', $support, true) && count($support) > 1) {
                $validation->errors()->add('q11_necesita_apoyo', 'Ninguno no puede combinarse.');
            }
            if (is_array($support) && in_array('Otro', $support, true) && empty($data['q11_necesita_apoyo_otro'])) {
                $validation->errors()->add('q11_necesita_apoyo_otro', 'Describe el otro apoyo.');
            }
        });

        return $validator->validate();
    }

    public static function classify(array $data): array
    {
        $categories = [];
        $supports = json_decode($data['q11_necesita_apoyo'] ?? '[]', true) ?: [];
        if (! empty($data['q1_cond_fisica']) || ! empty($data['q3_tratamiento']) || ! empty($data['q4_crisis_medica']) || ! empty($data['q5_alergia'])) {
            $categories[] = 'Salud Física';
        }
        if (! empty($data['q6_diag_aprendizaje']) || ! empty($data['q7_dictamen_psico']) || in_array('De aprendizaje', $supports, true)) {
            $categories[] = 'Atención Psicopedagógica';
        }
        if (! empty($data['q2_cond_mental']) || ! empty($data['q9_acomp_psicologico']) || in_array($data['q10_estado_emocional'] ?? '', ['Muy desfavorable', 'Desfavorable'], true) || in_array('Psicológico', $supports, true)) {
            $categories[] = 'Atención Emocional';
        }
        if (! $categories) {
            $categories[] = 'Sin dato de alarma';
        }

        return ['categoria_atencion' => json_encode($categories, JSON_UNESCAPED_UNICODE), 'atencion_prioritaria' => (int) (! empty($data['q4_crisis_medica']) || ! empty($data['q2_cond_mental']) || ($data['q10_estado_emocional'] ?? '') === 'Muy desfavorable')];
    }
}
