<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\SectionAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PanelController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        return $request->user()->rol_id === User::ALUMNO ? view('portal') : redirect()->route('panel');
    }

    public function dashboard(Request $request): View
    {
        $canSeeStudents = SectionAccess::can('personales');
        $metrics = ['alumnos' => 0, 'expedientes' => 0, 'pendientes' => 0];
        $students = null;
        if ($canSeeStudents) {
            $query = User::where('rol_id', User::ALUMNO)->where('status', User::ACTIVE)
                ->select('user.id', 'user.nombre', 'user.apellidos', 'user.email', 'user.matricula');
            if ($request->filled('q')) {
                $request->validate(['q' => 'string|max:255']);
                $query->where(function ($query) use ($request): void {
                    $query->where('user.nombre', 'like', '%'.$request->query('q').'%')->orWhere('user.apellidos', 'like', '%'.$request->query('q').'%');
                });
            }
            $query->leftJoin('expediente_alumno as e', function ($join): void {
                $join->on('e.user_id', '=', 'user.id')->whereNull('e.archivado_at');
            })->addSelect('e.id as expediente_id');
            $summary = DB::table('user as u')->leftJoin('expediente_alumno as e', function ($join): void {
                $join->on('e.user_id', '=', 'u.id')->whereNull('e.archivado_at');
            })->where('u.rol_id', User::ALUMNO)->where('u.status', User::ACTIVE)
                ->selectRaw('COUNT(u.id) as alumnos, COUNT(e.id) as expedientes')->first();
            $metrics = ['alumnos' => (int) $summary->alumnos, 'expedientes' => (int) $summary->expedientes,
                'pendientes' => (int) $summary->alumnos - (int) $summary->expedientes];
            $students = $query->orderBy('user.apellidos')->paginate(20)->withQueryString();
        }

        return view('panel', compact('canSeeStudents', 'metrics', 'students'));
    }
}
