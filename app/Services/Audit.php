<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class Audit
{
    public static function record(string $event, string $reason, array $data = [], ?int $student = null): void
    {
        DB::table('auditoria_sistema')->insert(['actor_id' => auth()->id(), 'alumno_id' => $student,
            'evento' => $event, 'motivo' => $reason, 'datos' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), 'fecha' => now()]);
    }
}
