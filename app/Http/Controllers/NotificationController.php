<?php

namespace App\Http\Controllers;

use App\Mail\StudentActivated;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class NotificationController extends Controller
{
    public function index()
    {
        return view('notificaciones', ['alumnos' => User::pendientes()->orderByDesc('id')->paginate(20)]);
    }

    public function approve(Request $request)
    {
        $data = $request->validate([
            'modo' => ['required', 'in:individual,seleccionados,todos'],
            'seleccion' => ['required_unless:modo,todos', 'array', 'min:1', 'max:1000'],
            'seleccion.*' => ['required', 'integer', 'min:1', 'distinct'],
        ]);
        $query = User::pendientes();
        if ($data['modo'] !== 'todos') {
            $query->whereIn('id', $data['seleccion']);
        }
        $ids = $query->pluck('id');
        $approved = 0;
        $failed = 0;
        foreach ($ids as $id) {
            try {
                $changed = DB::transaction(function () use ($id) {
                    $user = User::pendientes()->whereKey($id)->lockForUpdate()->first();
                    if (! $user) {
                        return false;
                    }
                    $user->status = User::ACTIVE;
                    $user->verification_token = null;
                    $user->save();
                    Mail::to($user->email)->send(new StudentActivated($user->nombre));

                    return true;
                });
                if ($changed) {
                    $approved++;
                }
            } catch (\Throwable $error) {
                report($error);
                $failed++;
            }
        }
        $response = redirect()->route('notificaciones')->with('success', "Se dieron de alta {$approved} alumnos.");
        if ($failed) {
            $response->with('error', "{$failed} solicitudes continúan pendientes porque no se pudo completar su alta y aviso.");
        }

        return $response;
    }
}
