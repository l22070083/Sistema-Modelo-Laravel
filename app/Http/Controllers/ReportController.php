<?php

namespace App\Http\Controllers;

use App\Services\Audit;
use App\Services\SectionAccess;
use Dompdf\Dompdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    public function index(): View
    {
        SectionAccess::require('personales');

        return view('reportes', ['licenciaturas' => DB::table('licenciatura')->get()]);
    }

    public function data(Request $request): Collection
    {
        SectionAccess::require('personales');
        $data = $request->validate(['tipo' => 'required|in:alumnos,salud,atencion', 'licenciatura_id' => 'nullable|integer|exists:licenciatura,id', 'q' => 'nullable|string|max:255']);
        if ($data['tipo'] !== 'alumnos') {
            SectionAccess::require('clasificacion');
        }
        $query = DB::table('user as u')->leftJoin('licenciatura as l', 'l.id', '=', 'u.licenciatura_id')->where('u.rol_id', 3)->select('u.id', 'u.nombre', 'u.apellidos', 'u.matricula', 'l.nombre as licenciatura');
        if (! empty($data['licenciatura_id'])) {
            $query->where('u.licenciatura_id', $data['licenciatura_id']);
        }
        if (! empty($data['q'])) {
            $query->where(function ($q) use ($data): void {
                $q->where('u.nombre', 'like', '%'.$data['q'].'%')->orWhere('u.apellidos', 'like', '%'.$data['q'].'%');
            });
        }
        $rows = $query->orderBy('u.apellidos')->get();
        if ($data['tipo'] === 'salud') {
            $health = DB::table('respuesta_alumno as ra')->join('pregunta as p', 'p.id', '=', 'ra.pregunta_id')->join('encuesta as e', 'e.id', '=', 'p.encuesta_id')->where('e.tipo_test', 'salud')->whereIn('ra.user_id', $rows->pluck('id'))->groupBy('ra.user_id')->selectRaw("ra.user_id, COUNT(*) as respuestas, SUM(CASE WHEN ra.respuesta='Si' AND p.tipo_riesgo IN ('medio','alto') THEN 1 ELSE 0 END) as alertas, MAX(ra.fecha_registro) as ultima")->get()->keyBy('user_id');
            foreach ($rows as $row) {
                $row->respuestas = $health[$row->id]->respuestas ?? 0;
                $row->alertas = $health[$row->id]->alertas ?? 0;
                $row->ultima = $health[$row->id]->ultima ?? '';
            }
        }
        if (in_array($data['tipo'], ['atencion', 'salud'], true)) {
            $files = DB::table('expediente_alumno')->whereIn('user_id', $rows->pluck('id'))->whereNull('archivado_at')->get()->keyBy('user_id');
            foreach ($rows as $row) {
                $row->categoria = isset($files[$row->id]) ? ($files[$row->id]->categoria_manual ?: $files[$row->id]->categoria_atencion) : '';
                $row->prioritaria = $files[$row->id]->atencion_prioritaria ?? 0;
            }
        }

        return $rows;
    }

    public function export(Request $request): Response
    {
        $request->validate(['motivo' => 'required|string|max:2000', 'formato' => 'required|in:xlsx,pdf']);
        $rows = $this->data($request);
        Audit::record('EXPORTACION_REPORTE', $request->motivo, ['tipo' => $request->tipo, 'formato' => $request->formato, 'licenciatura_id' => $request->licenciatura_id, 'busqueda' => $request->q, 'alumnos' => $rows->pluck('id')->all()]);
        if ($request->formato === 'pdf') {
            $html = view('reporte-pdf', ['rows' => $rows, 'tipo' => $request->tipo, 'motivo' => $request->motivo])->render();
            $pdf = new Dompdf(['isRemoteEnabled' => false]);
            $pdf->loadHtml($html);
            $pdf->setPaper('A4', 'landscape');
            $pdf->render();

            return response($pdf->output(), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="reporte.pdf"']);
        }

        return response()->streamDownload(function () use ($rows, $request): void {
            $book = new Spreadsheet;
            $sheet = $book->getActiveSheet();
            $values = [['Reporte', $request->tipo], ['Motivo', $request->motivo], ['Fecha', now()->format('Y-m-d H:i:s')]];
            if ($rows->isNotEmpty()) {
                $values[] = array_keys((array) $rows->first());
                foreach ($rows as $row) {
                    $values[] = array_values((array) $row);
                }
            }
            foreach ($values as $r => $cells) {
                foreach ($cells as $c => $value) {
                    $sheet->setCellValueExplicit([$c + 1, $r + 1], (string) $value, DataType::TYPE_STRING);
                }
            }
            (new Xlsx($book))->save('php://output');
        }, 'reporte.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function alerts(Request $request): View
    {
        $request->merge(['tipo' => 'salud']);

        return view('alertas', ['rows' => $this->data($request)]);
    }
}
