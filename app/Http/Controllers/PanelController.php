<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\DossierRules;
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
        $canSeeHealth = $canSeeStudents && SectionAccess::can('clasificacion');
        $metrics = ['sin_alarma' => 0, 'seguimiento' => 0, 'prioritaria' => 0, 'sin_expediente' => 0];
        $categories = array_fill_keys(['Sin dato de alarma', 'Atención Psicopedagógica', 'Salud Física', 'Atención Emocional'], 0);
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
            if ($canSeeHealth) {
                $query->leftJoin('expediente_alumno as e', function ($join): void {
                    $join->on('e.user_id', '=', 'user.id')->whereNull('e.archivado_at');
                })->addSelect('e.id as expediente_id', 'e.categoria_atencion', 'e.categoria_manual', 'e.atencion_prioritaria');
                $records = DB::table('user as u')->leftJoin('expediente_alumno as e', function ($join): void {
                    $join->on('e.user_id', '=', 'u.id')->whereNull('e.archivado_at');
                })->where('u.rol_id', User::ALUMNO)->where('u.status', User::ACTIVE)
                    ->select('e.id', 'e.categoria_atencion', 'e.categoria_manual', 'e.atencion_prioritaria')->get();
                foreach ($records as $record) {
                    if (! $record->id) {
                        $metrics['sin_expediente']++;

                        continue;
                    }
                    $effective = DossierRules::categories($record->categoria_manual ?: $record->categoria_atencion);
                    if (! $effective) {
                        $metrics['sin_expediente']++;

                        continue;
                    }
                    $state = $record->atencion_prioritaria ? 'prioritaria' : (in_array('Sin dato de alarma', $effective, true) ? 'sin_alarma' : 'seguimiento');
                    $metrics[$state]++;
                    foreach ($effective as $category) {
                        if (isset($categories[$category])) {
                            $categories[$category]++;
                        }
                    }
                }
            }
            $students = $query->orderBy('user.apellidos')->paginate(20)->withQueryString();
        }

        return view('panel', compact('canSeeStudents', 'canSeeHealth', 'metrics', 'categories', 'students'));
    }
}
