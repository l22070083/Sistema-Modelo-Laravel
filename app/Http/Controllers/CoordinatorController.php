<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Rules\PasswordPolicy;
use App\Services\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CoordinatorController extends Controller
{
    public function destroy(int $id): RedirectResponse
    {
        \App\Services\AccountRemoval::remove($id, User::COORDINADOR);

        return redirect()->route('coordinadores')->with('success', 'Coordinador eliminado del listado. Su historial institucional se conserva.');
    }

    public function designate(Request $request): RedirectResponse
    {
        $data = $request->validate(['user_id' => 'required|integer|exists:user,id', 'motivo' => 'required|string|max:2000']);
        $id = DB::transaction(function () use ($data): int {
            $user = User::where('rol_id', User::ALUMNO)->where(function ($query): void {
                $query->where('status', User::ACTIVE)->orWhere(function ($query): void {
                    $query->where('status', User::PENDING)->orWhere(function ($query): void {
                        $query->where('status', User::INACTIVE)->whereNotNull('verification_token');
                    });
                });
            })->lockForUpdate()->findOrFail($data['user_id']);
            $user->rol_id = User::COORDINADOR;
            $user->status = User::ACTIVE;
            $user->auth_key = bin2hex(random_bytes(16));
            $user->verification_token = null;
            $user->password_reset_token = null;
            $user->save();
            foreach (array_keys(config('dossier.sections')) as $section) {
                DB::table('coordinador_permiso')->updateOrInsert(['coordinador_id' => $user->id, 'seccion' => $section],
                    ['puede_ver' => 0, 'puede_editar' => 0, 'otorgado_por' => auth()->id(), 'updated_at' => now()]);
            }
            Audit::record('DESIGNACION_COORDINADOR', $data['motivo'], ['coordinador_id' => $user->id, 'rol_anterior' => User::ALUMNO]);

            return $user->id;
        });

        return redirect()->route('coordinador.edit', $id)->with('success', 'Coordinador designado. Asigna los permisos de sus secciones y grupos.');
    }

    public function edit(int $id): View
    {
        return view('coordinador-editar', ['coordinador' => User::where('rol_id', 2)->findOrFail($id),
            'grupos' => DB::table('grupo')->where('estado', 1)->get(), 'permisos' => DB::table('coordinador_permiso')->where('coordinador_id', $id)->get()->keyBy('seccion')]);
    }

    public function save(Request $request, int $id): RedirectResponse
    {
        $user = User::where('rol_id', 2)->findOrFail($id);
        $data = $request->validate(['nombre' => 'required|string|max:255', 'apellidos' => 'nullable|string|max:255',
            'username' => ['required', 'string', 'max:255', Rule::unique('user')->ignore($id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('user')->ignore($id)], 'password' => ['nullable', new PasswordPolicy]]);
        $user->fill(collect($data)->except('password')->all());
        if (! empty($data['password'])) {
            $user->password_hash = Hash::make($data['password']);
            $user->auth_key = bin2hex(random_bytes(16));
        }
        $user->save();

        return back()->with('success', 'Coordinador actualizado.');
    }

    public function status(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in([0, 10])]]);
        DB::transaction(function () use ($data, $id): void {
            $user = User::where('rol_id', 2)->lockForUpdate()->findOrFail($id);
            $user->status = (int) $data['status'];
            $user->auth_key = bin2hex(random_bytes(16));
            $user->verification_token = null;
            $user->password_reset_token = null;
            $user->save();
            if ($user->status === 0) {
                DB::table('grupo')->where('coordinador_id', $id)->update(['coordinador_id' => null]);
            }
            Audit::record('ESTADO_COORDINADOR', 'Cambio administrativo de estado.', ['coordinador_id' => $id, 'status' => $user->status]);
        });

        return back()->with('success', 'Estado actualizado.');
    }

    public function groups(Request $request, int $id): RedirectResponse
    {
        $request->validate(['grupos' => 'array', 'grupos.*' => 'integer|distinct|exists:grupo,id']);
        $ids = $request->input('grupos', []);
        DB::transaction(function () use ($ids, $id): void {
            User::where('rol_id', 2)->where('status', 10)->lockForUpdate()->findOrFail($id);
            $groups = DB::table('grupo')->whereIn('id', $ids)->lockForUpdate()->get();
            foreach ($groups as $group) {
                abort_unless($group->estado == 1 && (! $group->coordinador_id || $group->coordinador_id == $id), 422, 'Grupo inactivo o asignado a otro coordinador.');
            }
            DB::table('grupo')->where('coordinador_id', $id)->update(['coordinador_id' => null]);
            DB::table('grupo')->whereIn('id', $ids)->update(['coordinador_id' => $id]);
        });

        return back()->with('success', 'Grupos asignados.');
    }

    public function permissions(Request $request, int $id): RedirectResponse
    {
        User::where('rol_id', 2)->findOrFail($id);
        $rules = ['motivo' => 'required|string|max:2000', 'permisos' => ['array:'.implode(',', array_keys(config('dossier.sections')))]];
        foreach (array_keys(config('dossier.sections')) as $section) {
            $rules['permisos.'.$section] = 'array:ver,editar';
            $rules['permisos.'.$section.'.ver'] = 'boolean';
            $rules['permisos.'.$section.'.editar'] = 'boolean';
        }
        $data = $request->validate($rules);
        DB::transaction(function () use ($data, $id): void {
            User::lockForUpdate()->findOrFail($id);
            foreach (array_keys(config('dossier.sections')) as $section) {
                $grant = $data['permisos'][$section] ?? [];
                abort_if(! empty($grant['editar']) && empty($grant['ver']), 422, 'Editar requiere permiso de consulta.');
                DB::table('coordinador_permiso')->updateOrInsert(['coordinador_id' => $id, 'seccion' => $section], ['puede_ver' => (int) ($grant['ver'] ?? 0), 'puede_editar' => (int) ($grant['editar'] ?? 0), 'otorgado_por' => auth()->id(), 'updated_at' => now()]);
            }
            Audit::record('PERMISOS_COORDINADOR', $data['motivo'], ['coordinador_id' => $id, 'permisos' => $data['permisos'] ?? []]);
        });

        return back()->with('success', 'Permisos actualizados y auditados.');
    }
}
