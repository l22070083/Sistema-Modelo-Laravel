<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AccountCreator;
use App\Services\SectionAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function destroy(int $id): RedirectResponse
    {
        \App\Services\AccountRemoval::remove($id, User::ALUMNO);

        return redirect()->route('alumnos')->with('success', 'Alumno eliminado del listado. Su historial institucional se conserva.');
    }

    public function create(Request $request): View|RedirectResponse
    {
        abort_unless($request->user()->rol_id === User::ADMIN, 403);
        if ($request->isMethod('post')) {
            $rules = array_merge(RegistrationController::profileRules(), ['apellidos' => ['required', 'string', 'max:255']]);
            $student = AccountCreator::create(AccountCreator::validate($request, $rules), User::ALUMNO);

            return redirect()->route('alumno.ver', $student->id)->with('success', 'Alumno creado y activo. Ya puede iniciar sesión.');
        }

        return view('alumno-crear', RegistrationController::catalogs());
    }

    public function status(Request $request, int $id): RedirectResponse
    {
        abort_unless($request->user()->rol_id === User::ADMIN, 403);
        $data = $request->validate(['status' => ['required', \Illuminate\Validation\Rule::in([0, 10])]]);
        \Illuminate\Support\Facades\DB::transaction(function () use ($data, $id): void {
            $student = User::where('rol_id', User::ALUMNO)->lockForUpdate()->findOrFail($id);
            abort_unless(in_array($student->status, [User::ACTIVE, User::INACTIVE], true) && $student->verification_token === null, 422, 'Las solicitudes pendientes se gestionan en Notificaciones.');
            $student->status = (int) $data['status'];
            $student->auth_key = bin2hex(random_bytes(16));
            $student->password_reset_token = null;
            $student->save();
            \App\Services\Audit::record('ESTADO_ALUMNO', 'Cambio administrativo de estado.', ['alumno_id' => $id, 'status' => $student->status], $id);
        });

        return redirect()->route('alumnos')->with('success', (int) $data['status'] === User::INACTIVE ? 'Alumno dado de baja. Su información y expediente se conservan.' : 'Alumno reactivado.');
    }

    public function index(): View
    {
        SectionAccess::require('personales');

        return view('alumnos', ['rows' => User::where('rol_id', 3)->where('status', '!=', 5)->orderBy('nombre')->paginate(20)]);
    }

    public function show(int $id): View
    {
        SectionAccess::require('personales');
        $student = User::where('rol_id', 3)->findOrFail($id);

        return view('alumno', ['student' => $student]);
    }

    public function edit(Request $request, int $id): View|RedirectResponse
    {
        abort_unless($request->user()->rol_id === 1, 403);
        $student = User::where('rol_id', 3)->findOrFail($id);
        if ($request->isMethod('post')) {
            $data = $request->validate(array_merge(RegistrationController::profileRules(), [
                'nombre' => 'required|string|max:255', 'apellidos' => 'required|string|max:255',
                'username' => ['required', 'string', 'max:255', \Illuminate\Validation\Rule::unique('user')->ignore($id)],
                'email' => ['required', 'email', 'max:255', \Illuminate\Validation\Rule::unique('user')->ignore($id)],
                'password' => ['nullable', 'confirmed', new \App\Rules\PasswordPolicy],
            ]));
            \Illuminate\Support\Facades\DB::transaction(function () use ($student, $data): void {
                $student->fill(collect($data)->except('password')->all());
                if (! empty($data['password'])) {
                    $student->password_hash = \Illuminate\Support\Facades\Hash::make($data['password']);
                    $student->password_reset_token = null;
                }
                $student->auth_key = bin2hex(random_bytes(16));
                $student->save();
                \App\Services\Audit::record('ACTUALIZACION_ALUMNO', 'Actualización administrativa de cuenta.', ['user_id' => $student->id], $student->id);
            });

            return redirect()->route('alumno.ver', $id)->with('success', 'Perfil de alumno actualizado.');
        }

        return view('alumno-editar', array_merge(RegistrationController::catalogs(), ['user' => $student]));
    }
}
