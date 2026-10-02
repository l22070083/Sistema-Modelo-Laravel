<?php

namespace App\Http\Controllers;

use App\Services\SectionAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SurveyController extends Controller
{
    public function manage(): View
    {
        $surveys = DB::table('encuesta')->where('tipo_test', 'salud')->get();
        $questions = DB::table('pregunta')->join('encuesta', 'encuesta.id', '=', 'pregunta.encuesta_id')->where('encuesta.tipo_test', 'salud')->select('pregunta.*')->get();

        return view('encuestas-admin', compact('surveys', 'questions'));
    }

    public function saveSurvey(Request $request, ?int $id = null): RedirectResponse
    {
        $data = $request->validate(['titulo' => 'required|string|max:255', 'descripcion' => 'nullable|string', 'estado' => 'required|boolean']);
        $data['tipo_test'] = 'salud';
        $data['updated_at'] = time();
        if ($id) {
            abort_unless(DB::table('encuesta')->where('tipo_test', 'salud')->find($id), 404);
            DB::table('encuesta')->where('id', $id)->update($data);
        } else {
            $data['created_at'] = time();
            DB::table('encuesta')->insert($data);
        }

        return back()->with('success', 'Encuesta guardada.');
    }

    public function saveQuestion(Request $request, ?int $id = null): RedirectResponse
    {
        $data = $request->validate(['encuesta_id' => 'required|integer|exists:encuesta,id', 'planteamiento' => 'required|string|max:10000', 'tipo_riesgo' => 'required|in:bajo,medio,alto', 'status' => 'required|boolean']);
        abort_unless(DB::table('encuesta')->where('id', $data['encuesta_id'])->where('tipo_test', 'salud')->exists(), 422);
        if ($id) {
            abort_unless(DB::table('pregunta')->join('encuesta', 'pregunta.encuesta_id', '=', 'encuesta.id')->where('pregunta.id', $id)->where('tipo_test', 'salud')->exists(), 404);
            DB::table('pregunta')->where('id', $id)->update($data);
        } else {
            DB::table('pregunta')->insert($data);
        }

        return back()->with('success', 'Pregunta guardada.');
    }

    private function active(?int $id): object
    {
        $query = DB::table('encuesta')->where('tipo_test', 'salud')->where('estado', 1);
        if ($id) {
            $query->where('id', $id);
        }
        $survey = $query->orderByDesc('id')->first();
        abort_unless($survey, 404, 'No hay encuesta de salud activa.');

        return $survey;
    }

    private function profile(Request $request): void
    {
        abort_unless($request->user()->matricula && $request->user()->genero_id && $request->user()->licenciatura_id, 422, 'Completa tu perfil antes de responder.');
    }

    public function answer(Request $request, ?int $id = null): View|RedirectResponse
    {
        $this->profile($request);
        $survey = $this->active($id);
        $questions = DB::table('pregunta')->where('encuesta_id', $survey->id)->where('status', 1)->orderBy('id')->paginate(10);
        if ($request->isMethod('post')) {
            $data = $request->validate(['respuestas' => 'required|array', 'respuestas.*' => 'required|in:Si,No']);
            abort_if(array_diff(array_keys($data['respuestas']), $questions->pluck('id')->all()), 422);
            DB::transaction(function () use ($request, $data, $survey): void {
                DB::table('user')->where('id', $request->user()->id)->lockForUpdate()->first();
                foreach ($data['respuestas'] as $question => $answer) {
                    $this->persist($request->user()->id, (int) $question, $answer, $survey->id);
                }
            });

            return $questions->hasMorePages() ? redirect()->route('encuesta.responder', ['id' => $survey->id, 'page' => $questions->currentPage() + 1]) : redirect()->route('encuesta.finalizar', $survey->id);
        }
        $previous = DB::table('respuesta_alumno')->where('user_id', $request->user()->id)->whereIn('pregunta_id', $questions->pluck('id'))->pluck('respuesta', 'pregunta_id');
        $answered = DB::table('respuesta_alumno as ra')->join('pregunta as p', 'p.id', '=', 'ra.pregunta_id')->where('ra.user_id', $request->user()->id)->where('p.encuesta_id', $survey->id)->where('p.status', 1)->whereIn('ra.respuesta', ['Si', 'No'])->count();

        return view('encuesta-responder', compact('survey', 'questions', 'previous', 'answered'));
    }

    private function persist(int $user, int $question, string $answer, ?int $survey = null): void
    {
        $query = DB::table('pregunta as p')->join('encuesta as e', 'e.id', '=', 'p.encuesta_id')->where('p.id', $question)->where('p.status', 1)->where('e.estado', 1)->where('e.tipo_test', 'salud');
        if ($survey) {
            $query->where('e.id', $survey);
        }
        abort_unless($query->exists(), 422, 'Pregunta fuera de una encuesta de salud activa.');
        DB::table('respuesta_alumno')->updateOrInsert(['user_id' => $user, 'pregunta_id' => $question], ['respuesta' => $answer, 'fecha_registro' => now()]);
    }

    public function autosave(Request $request): JsonResponse
    {
        $this->profile($request);
        $data = $request->validate(['pregunta_id' => 'required|integer|min:1', 'respuesta' => 'required|in:Si,No']);
        DB::transaction(function () use ($request, $data): void {
            DB::table('user')->where('id', $request->user()->id)->lockForUpdate()->first();
            $this->persist($request->user()->id, $data['pregunta_id'], $data['respuesta']);
        });

        return response()->json(['success' => true]);
    }

    public function finish(Request $request, int $id): View|RedirectResponse
    {
        $this->profile($request);
        $survey = $this->active($id);
        $questions = DB::table('pregunta')->where('encuesta_id', $survey->id)->where('status', 1)->pluck('id');
        $count = DB::table('respuesta_alumno')->where('user_id', $request->user()->id)->whereIn('pregunta_id', $questions)->whereIn('respuesta', ['Si', 'No'])->count();
        if (! $questions->count() || $count !== $questions->count()) {
            return redirect()->route('encuesta.responder', $id)->with('error', 'Responde todas las preguntas activas antes de finalizar.');
        }

        return view('encuesta-finalizada');
    }

    public function results(Request $request, ?int $student = null): View
    {
        if ($request->user()->rol_id === 3) {
            abort_if($student && $student !== $request->user()->id, 403);
            $student = $request->user()->id;
        } else {
            SectionAccess::require('personales');
            SectionAccess::require('clasificacion');
        }
        $query = DB::table('resultados_salud')->join('user', 'user.id', '=', 'resultados_salud.alumno_id')->select('resultados_salud.*', 'user.nombre', 'user.apellidos');
        if ($student) {
            $query->where('alumno_id', $student);
        }
        $assessments = DB::table('expediente_alumno as e')->join('user as u', 'u.id', '=', 'e.user_id')
            ->leftJoin('user as reviewer', 'reviewer.id', '=', 'e.clasificado_por')
            ->where('u.rol_id', 3)->whereNull('e.archivado_at')
            ->select('e.id', 'e.user_id', 'e.categoria_atencion', 'e.categoria_manual', 'e.atencion_prioritaria', 'e.motivo_clasificacion',
                'u.nombre', 'u.apellidos', 'reviewer.nombre as valorado_por');
        if ($student) {
            $assessments->where('e.user_id', $student);
        }

        return view('resultados', ['results' => $query->orderByDesc('fecha')->get(), 'assessments' => $assessments->get()]);
    }
}
