<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Audit;
use App\Services\DossierRules;
use App\Services\SectionAccess;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DossierController extends Controller
{
    public static function fingerprint(?object $record): string
    {
        return $record ? hash('sha256', json_encode((array) $record, JSON_THROW_ON_ERROR)) : '';
    }

    public function index(Request $request): View
    {
        if ($request->user()->rol_id === 3) {
            return $this->show($request, null);
        }
        SectionAccess::require('personales');
        $query = DB::table('expediente_alumno as e')->leftJoin('licenciatura as l', 'e.licenciatura_id', '=', 'l.id')->select('e.id', 'e.user_id', 'e.nombres', 'e.apellidos', 'e.archivado_at', 'l.nombre as licenciatura');
        if (SectionAccess::can('clasificacion')) {
            $query->addSelect('e.categoria_atencion', 'e.categoria_manual', 'e.atencion_prioritaria');
        }
        if ($request->filled('q')) {
            $query->where(function ($q) use ($request): void {
                $q->where('e.nombres', 'like', '%'.$request->query('q').'%')->orWhere('e.apellidos', 'like', '%'.$request->query('q').'%');
            });
        }
        if ($request->filled('licenciatura_id')) {
            $query->where('e.licenciatura_id', $request->integer('licenciatura_id'));
        }
        if ($request->filled('grupo_id')) {
            $query->whereIn('e.user_id', User::where('grupo_id', $request->integer('grupo_id'))->select('id'));
        }
        if (! $request->boolean('archivados')) {
            $query->whereNull('e.archivado_at');
        }

        return view('expedientes', ['rows' => $query->paginate(20)->withQueryString(), 'licenciaturas' => DB::table('licenciatura')->get()]);
    }

    public function record(Request $request, ?int $id): ?object
    {
        if ($request->user()->rol_id === 3) {
            $record = DB::table('expediente_alumno')->where('user_id', $request->user()->id)->first();
            abort_if($id && (! $record || $record->id !== $id), 403);

            return $record;
        }
        $record = DB::table('expediente_alumno')->find($id);
        abort_unless($record, 404);

        return $record;
    }

    public function sections(Request $request, bool $edit = false): array
    {
        $sections = [];
        foreach (config('dossier.sections') as $name => $fields) {
            if ($request->user()->rol_id === 3 ? in_array($name, ['personales', 'cuestionario'], true) : SectionAccess::can($name, $edit)) {
                $sections[$name] = $fields;
            }
        }
        abort_unless($sections, 403, 'No tienes secciones autorizadas.');

        return $sections;
    }

    public function show(Request $request, ?int $id = null): View
    {
        $record = $this->record($request, $id);
        $sections = $this->sections($request);
        $history = $record && isset($sections['bitacora']) ? DB::table('expediente_historial')->where('expediente_id', $record->id)->orderByDesc('fecha')->get() : collect();
        $own = $request->user()->rol_id === User::ALUMNO;
        $revisions = $own && $record ? DB::table('expediente_historial')->where('expediente_id', $record->id)->orderByDesc('fecha')->limit(5)->get(['accion', 'fecha']) : $history;
        $student = $record && isset($sections['personales']) ? User::find($record->user_id) : null;
        $degree = $student ? DB::table('licenciatura')->where('id', $record->licenciatura_id)->value('nombre') : null;
        $categories = $record && ($own || isset($sections['clasificacion'])) ? DossierRules::categories($record->categoria_manual ?: $record->categoria_atencion) : [];

        return view('expediente', compact('record', 'sections', 'history', 'revisions', 'student', 'degree', 'categories', 'own'));
    }

    public function edit(Request $request, ?int $id = null): View|RedirectResponse
    {
        if ($request->user()->rol_id === 3 && (! $request->user()->matricula || ! $request->user()->licenciatura_id || ! $request->user()->genero_id)) {
            return redirect()->route('perfil')->with('error', 'Completa tu perfil antes de llenar el expediente.');
        }
        $creatingFor = $request->attributes->get('dossier_student');
        $record = $creatingFor ? DB::table('expediente_alumno')->where('user_id', $creatingFor)->first() : $this->record($request, $id);
        if ($record) {
            abort_if($record->archivado_at || ($request->user()->rol_id === 3 && $record->bloqueado), 403, 'El expediente está protegido o archivado.');
        } else {
            abort_unless($request->user()->rol_id === 3 || $creatingFor, 404);
        }
        $sections = array_intersect_key($this->sections($request, true), array_flip(['personales', 'cuestionario']));
        abort_unless($sections, 403);
        if ($request->isMethod('post')) {
            $request->validate(['datos' => 'required|array', 'motivo' => ($request->user()->rol_id === 3 ? 'nullable' : 'required').'|string|max:2000', 'version' => 'nullable|string|max:128']);
            $fields = array_merge(...array_values($sections));
            abort_if(array_diff(array_keys($request->input('datos')), $fields), 403, 'Campos fuera de las secciones autorizadas.');
            $data = DossierRules::validate($request->input('datos'), $fields);
            $student = $record ? $record->user_id : ($creatingFor ?: $request->user()->id);
            $savedId = DB::transaction(function () use ($request, $data, $student): int {
                User::where('rol_id', 3)->lockForUpdate()->findOrFail($student);
                $current = DB::table('expediente_alumno')->where('user_id', $student)->lockForUpdate()->first();
                if ($current) {
                    abort_if($current->archivado_at || ($request->user()->rol_id === 3 && $current->bloqueado), 403);
                    abort_if(self::fingerprint($current) !== (string) $request->input('version'), 409, 'El expediente cambió; recarga antes de guardar.');
                }
                $values = $data;
                if (isset($values['q11_necesita_apoyo'])) {
                    $values['q11_necesita_apoyo'] = json_encode($values['q11_necesita_apoyo'], JSON_UNESCAPED_UNICODE);
                }
                if (isset($values['fecha_nacimiento'])) {
                    $values['edad'] = (int) Carbon::parse($values['fecha_nacimiento'])->diffInYears(now());
                }
                if (array_intersect(array_keys($values), config('dossier.sections.cuestionario'))) {
                    $classification = DossierRules::classify(array_merge((array) $current, $values));
                    if ($current && $current->categoria_manual) {
                        unset($classification['atencion_prioritaria']);
                    }
                    $values = array_merge($values, $classification);
                }
                $values['updated_at'] = now();
                $values['ultimo_editor_id'] = $request->user()->id;
                if ($current) {
                    DB::table('expediente_alumno')->where('id', $current->id)->update($values);
                    $fileId = $current->id;
                } else {
                    $fileId = DB::table('expediente_alumno')->insertGetId($values + ['user_id' => $student, 'created_at' => now(), 'bloqueado' => 0]);
                }
                $this->history($fileId, ! $current ? 'REGISTRO_INICIAL' : ($request->user()->rol_id === 3 ? 'EDICION_ALUMNO' : 'EDICION_INSTITUCIONAL'), $request->input('motivo') ?: 'Actualización por el alumno.');

                return $fileId;
            });

            return redirect()->route($request->user()->rol_id === 3 ? 'mi-expediente' : 'expediente.ver', $request->user()->rol_id === 3 ? [] : $savedId)->with('success', 'Expediente e historial guardados.');
        }

        return view('expediente-form', ['record' => $record, 'sections' => $sections, 'licenciaturas' => DB::table('licenciatura')->get()]);
    }

    public function create(Request $request, int $student): View|RedirectResponse
    {
        User::where('rol_id', 3)->findOrFail($student);
        foreach (['personales', 'cuestionario'] as $section) {
            SectionAccess::require($section, true);
        }
        $record = DB::table('expediente_alumno')->where('user_id', $student)->first();
        if ($record) {
            return redirect()->route('expediente.editar', $record->id);
        }
        $request->attributes->set('dossier_student', $student);

        return $this->edit($request);
    }

    public function exportPdf(Request $request, ?int $id = null): Response
    {
        $record = $this->record($request, $id);
        abort_unless($record, 404);
        $sections = $this->sections($request);
        $reason = $request->user()->rol_id === 3 ? 'Exportación de expediente propio.' : $request->validate(['motivo' => 'required|string|max:2000'])['motivo'];
        Audit::record('EXPORTACION_EXPEDIENTE', $reason, ['expediente_id' => $record->id, 'secciones' => array_keys($sections)], $record->user_id);
        $history = isset($sections['bitacora']) ? DB::table('expediente_historial')->where('expediente_id', $record->id)->orderBy('fecha')->get() : collect();
        $html = view('expediente-pdf', compact('record', 'sections', 'history'))->render();
        $pdf = new Dompdf(['isRemoteEnabled' => false]);
        $pdf->loadHtml($html);
        $pdf->setPaper('A4');
        $pdf->render();

        return response($pdf->output(), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="expediente-'.$record->id.'.pdf"']);
    }

    private function history(int $id, string $action, string $reason): void
    {
        DB::table('expediente_historial')->insert(['expediente_id' => $id, 'user_id' => auth()->id(), 'accion' => $action, 'detalles' => $reason, 'fecha' => now()]);
    }

    public function action(Request $request, int $id, string $action): RedirectResponse
    {
        $record = $this->record($request, $id);
        abort_if($request->user()->rol_id === 3, 403);
        $request->validate(['motivo' => 'required|string|max:2000']);
        if (in_array($action, ['archivar', 'restaurar', 'bloqueo'], true)) {
            abort_unless($request->user()->rol_id === 1, 403);
        } elseif ($action === 'nota') {
            SectionAccess::require('notas', true);
        } elseif ($action === 'clasificar') {
            SectionAccess::require('clasificacion', true);
        } else {
            abort(404);
        }
        if ($action === 'nota') {
            $request->validate(['nota' => 'required|string|max:10000']);
        }
        if ($action === 'bloqueo') {
            $request->validate(['bloqueado' => 'required|boolean']);
        }
        if ($action === 'clasificar') {
            $request->validate(['categorias' => 'required|array|min:1', 'categorias.*' => 'required|distinct|in:Sin dato de alarma,Atención Psicopedagógica,Salud Física,Atención Emocional', 'prioritaria' => 'sometimes|boolean']);
            abort_if(in_array('Sin dato de alarma', $request->categorias, true) && count($request->categorias) > 1, 422);
        }
        DB::transaction(function () use ($request, $id, $action): void {
            $record = DB::table('expediente_alumno')->where('id', $id)->lockForUpdate()->first();
            abort_if($record->archivado_at && ! in_array($action, ['restaurar', 'archivar'], true), 403);
            $data = ['updated_at' => now(), 'ultimo_editor_id' => auth()->id()];
            if ($action === 'archivar') {
                $data += ['archivado_at' => now(), 'archivado_por' => auth()->id(), 'motivo_archivo' => $request->motivo];
            }
            if ($action === 'restaurar') {
                $data += ['archivado_at' => null, 'archivado_por' => null, 'motivo_archivo' => null];
            }
            if ($action === 'bloqueo') {
                $data['bloqueado'] = (int) $request->bloqueado;
            }
            if ($action === 'clasificar') {
                $data += ['categoria_manual' => json_encode($request->categorias, JSON_UNESCAPED_UNICODE), 'clasificado_por' => auth()->id(), 'motivo_clasificacion' => $request->motivo];
                if ($request->has('prioritaria')) {
                    $data['atencion_prioritaria'] = (int) $request->boolean('prioritaria');
                }
            }
            if ($action === 'nota') {
                $data['notas_coordinador'] = ($record->notas_coordinador ? $record->notas_coordinador."\n---\n" : '').'['.now()->format('d/m/Y H:i').' - '.auth()->user()->nombre."]:\n".$request->nota;
            }
            DB::table('expediente_alumno')->where('id', $id)->update($data);
            $this->history($id, strtoupper($action), $request->motivo);
            Audit::record(strtoupper($action), $request->motivo, ['expediente_id' => $id, 'cambios' => array_diff_key($data, array_flip(['updated_at', 'ultimo_editor_id', 'notas_coordinador']))], $record->user_id);
        });

        return back()->with('success', 'Acción registrada en la auditoría.');
    }

    public function attention(): View
    {
        SectionAccess::require('personales');
        SectionAccess::require('clasificacion');

        return view('atencion', ['rows' => DB::table('expediente_alumno')->whereNull('archivado_at')->whereNotNull('categoria_atencion')->orderByDesc('atencion_prioritaria')->get()]);
    }

    public function certificate(Request $request, ?int $id = null): Response
    {
        $record = $this->record($request, $id);
        abort_unless($record, 404);
        if ($request->user()->rol_id !== 3) {
            SectionAccess::require('personales');
        }
        $initial = DB::table('expediente_historial')->where('expediente_id', $record->id)->where('accion', 'REGISTRO_INICIAL')->orderBy('fecha')->value('fecha') ?: $record->created_at;
        $last = DB::table('expediente_historial')->where('expediente_id', $record->id)->where('accion', '!=', 'REGISTRO_INICIAL')->orderByDesc('fecha')->value('fecha');
        $folio = 'UMV-EXP-'.str_pad((string) $record->id, 5, '0', STR_PAD_LEFT).'-'.date('Y', strtotime($initial));
        $html = view('constancia', compact('record', 'initial', 'last', 'folio'))->render();
        if (! $request->boolean('pdf')) {
            return response($html);
        }
        $pdf = new Dompdf(['isRemoteEnabled' => false]);
        $pdf->loadHtml($html);
        $pdf->setPaper('A4');
        $pdf->render();

        return response($pdf->output(), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="constancia-'.$record->id.'.pdf"']);
    }
}
