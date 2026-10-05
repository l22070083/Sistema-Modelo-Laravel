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
            $data = $request->validate(RegistrationController::profileRules());
            $student->fill($data)->save();

            return redirect()->route('alumno.ver', $id)->with('success', 'Perfil de alumno actualizado.');
        }

        return view('alumno-editar', array_merge(RegistrationController::catalogs(), ['user' => $student]));
    }
}
