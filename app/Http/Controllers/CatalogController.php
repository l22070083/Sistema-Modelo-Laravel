<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function status(Request $request, string $catalog, int $id): RedirectResponse
    {
        $table = $this->table($catalog);
        abort_unless(DB::table($table)->find($id), 404);
        $data = $request->validate(['estado' => ['required', 'boolean']]);
        if ($table === 'grupo') {
            $data['updated_at'] = now();
        }
        DB::table($table)->where('id', $id)->update($data);

        return redirect()->route('catalogo', $catalog)->with('success', 'Estado del catálogo actualizado.');
    }

    private function table(string $catalog): string
    {
        abort_unless(in_array($catalog, ['licenciatura', 'grupo'], true), 404);

        return $catalog;
    }

    public function index(string $catalog): View
    {
        return view('catalogo', ['catalog' => $this->table($catalog), 'rows' => DB::table($catalog)->orderBy('id')->paginate(20),
            'licenciaturas' => DB::table('licenciatura')->where('estado', 1)->get(), 'editing' => null]);
    }

    public function edit(string $catalog, int $id): View
    {
        $table = $this->table($catalog);
        $record = DB::table($table)->find($id);
        abort_unless($record, 404);

        return view('catalogo', ['catalog' => $table, 'rows' => DB::table($table)->paginate(20), 'editing' => $record, 'licenciaturas' => DB::table('licenciatura')->where('estado', 1)->get()]);
    }

    public function save(Request $request, string $catalog, ?int $id = null): RedirectResponse
    {
        $table = $this->table($catalog);
        if ($id) {
            abort_unless(DB::table($table)->find($id), 404);
        }
        $rules = ['nombre' => ['required', 'string', 'max:100'], 'estado' => ['required', 'boolean']];
        if ($table === 'grupo') {
            $rules += ['licenciatura_id' => ['required', 'integer', Rule::exists('licenciatura', 'id')->where('estado', 1)], 'periodo' => ['nullable', 'string', 'max:20']];
        }
        $data = $request->validate($rules);
        if ($table === 'grupo') {
            if ($id && DB::table('user')->where('grupo_id', $id)->where('licenciatura_id', '!=', $data['licenciatura_id'])->exists()) {
                throw ValidationException::withMessages(['licenciatura_id' => 'No puedes cambiar la licenciatura de un grupo con alumnos de otra licenciatura.']);
            }
            $data['updated_at'] = now();
            if (! $id) {
                $data['created_at'] = now();
            }
        }
        if ($id) {
            DB::table($table)->where('id', $id)->update($data);
        } else {
            DB::table($table)->insert($data);
        }

        return redirect()->route('catalogo', $catalog)->with('success', 'Catálogo actualizado.');
    }
}
