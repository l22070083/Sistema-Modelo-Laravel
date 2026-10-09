<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class SectionAccess
{
    public static function can(string $section, bool $edit = false): bool
    {
        $user = auth()->user();
        if (! $user || $user->status !== User::ACTIVE || ! array_key_exists($section, config('dossier.sections'))) {
            return false;
        }
        if ($user->rol_id === User::ADMIN) {
            return true;
        }
        if ($user->rol_id !== User::COORDINADOR) {
            return false;
        }
        $grant = DB::table('coordinador_permiso')->where('coordinador_id', $user->id)->where('seccion', $section)->first();

        return $grant && $grant->puede_ver && (! $edit || $grant->puede_editar);
    }

    public static function require(string $section, bool $edit = false): void
    {
        abort_unless(self::can($section, $edit), 403, 'El administrador debe autorizar esta sección: '.$section);
    }
}
