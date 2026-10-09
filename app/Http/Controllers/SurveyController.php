<?php

namespace App\Http\Controllers;

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
        $data = $request->validate(['encuesta_id' => 'required|integer|exists:encuesta,id', 'planteamiento' => 'required|string|max:10000', 'status' => 'required|boolean']);
        abort_unless(DB::table('encuesta')->where('id', $data['encuesta_id'])->where('tipo_test', 'salud')->exists(), 422);
        if ($id) {
            abort_unless(DB::table('pregunta')->join('encuesta', 'pregunta.encuesta_id', '=', 'encuesta.id')->where('pregunta.id', $id)->where('tipo_test', 'salud')->exists(), 404);
            DB::table('pregunta')->where('id', $id)->update($data);
        } else {
            DB::table('pregunta')->insert($data);
        }

        return back()->with('success', 'Pregunta guardada.');
    }

}
