<?php
namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class AccountRemoval
{
    // El estado eliminado conserva las referencias históricas y no admite reactivación ordinaria.
    public static function remove(int $id, int $role): void
    {
        abort_unless(auth()->user()?->rol_id === User::ADMIN, 403);
        DB::transaction(function () use ($id, $role): void {
            $user = User::where('rol_id', $role)->lockForUpdate()->findOrFail($id);
            $user->status = User::DELETED;
            $user->auth_key = bin2hex(random_bytes(16));
            $user->verification_token = null;
            $user->password_reset_token = null;
            $user->save();
            if ($role === User::COORDINADOR) {
                DB::table('grupo')->where('coordinador_id', $id)->update(['coordinador_id' => null]);
                DB::table('coordinador_permiso')->where('coordinador_id', $id)->update(['puede_ver' => 0, 'puede_editar' => 0]);
            }
            Audit::record('ELIMINACION_CUENTA', 'Eliminación administrativa de cuenta; se conserva el historial institucional.', ['user_id' => $id, 'rol_id' => $role], $role === User::ALUMNO ? $id : null);
        });
    }
}