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
        $data = $request->validate(['tipo' => 'required|in:alumnos', 'licenciatura_id' => 'nullable|integer|exists:licenciatura,id', 'q' => 'nullable|string|max:255']);
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

}
