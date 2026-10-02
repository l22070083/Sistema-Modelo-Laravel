<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class LegacyController extends Controller
{
    public function landing(Request $request): Response
    {
        if ($request->query('r')) {
            return $this->dispatch($request);
        }

        return auth()->check() ? redirect()->route('inicio') : response()->view('portal');
    }

    public function dispatch(Request $request, ?string $area = null): Response
    {
        $action = $request->query('r');
        abort_unless(is_string($action), 404);
        if (preg_match('~^(test-chaside|chaside|resultado)/~', $action) || $action === 'encuesta/index-chaside') {
            abort(410, 'CHASIDE está retirado de esta aplicación.');
        }
        $map = [
            'site/index' => ['home'], 'site/login' => [$area === 'backend' && $request->isMethod('get') ? 'login.administrativo' : 'login'], 'site/logout' => ['logout'],
            'site/signup' => ['registro'], 'site/completar-perfil' => ['perfil'],
            'site/microsoft-login' => ['microsoft.login'], 'site/microsoft-callback' => ['microsoft.callback'], 'site/microsoft-vincular' => ['microsoft.link'],
            'site/verify-email' => ['email.verify', 'token'], 'site/resend-verification-email' => ['email.resend'], 'site/request-password-reset' => ['password.request'], 'site/reset-password' => ['password.reset', 'token'],
            'alumno/index' => ['alumnos'], 'alumno/view' => ['alumno.ver', 'id'], 'alumno/update' => ['alumno.editar', 'id'],
            'alumno/aprobar' => ['notificaciones.alta'], 'notificacion/index' => ['notificaciones'],
            'coordinador/index' => ['coordinadores'], 'coordinador/create' => ['coordinadores'],
            'coordinador/view' => ['coordinador.edit', 'id'], 'coordinador/update' => ['coordinador.edit', 'id'], 'coordinador/permisos' => ['coordinador.edit', 'id'], 'coordinador/asignar-grupo' => ['coordinador.edit', 'id'],
            'coordinador/delete' => ['coordinador.status', 'id'], 'coordinador/reactivar' => ['coordinador.status', 'id'],
            'encuesta/index' => [$area === 'frontend' ? 'encuesta.responder' : 'encuestas'],
            'encuesta/create' => ['encuestas'], 'pregunta/index' => ['encuestas'], 'pregunta/create' => ['encuestas'],
            'encuesta/index-salud' => ['encuestas'], 'encuesta/lista-alumno' => ['encuesta.responder'],
            'encuesta/view' => ['encuestas'], 'encuesta/update' => ['encuestas'], 'pregunta/view' => ['encuestas'], 'pregunta/update' => ['encuestas'],
            'encuesta/finalizar' => ['encuesta.finalizar', 'id_encuesta'], 'encuesta/guardar-respuesta-ajax' => ['encuesta.autoguardado'],
            'expediente/index' => [$area === 'frontend' ? 'mi-expediente' : 'expedientes'],
            'expediente/ver' => [$area === 'frontend' ? 'mi-expediente' : 'expediente.ver', $area === 'frontend' ? null : 'id'],
            'expediente/editar' => [$area === 'frontend' ? 'mi-expediente.editar' : 'expediente.editar', $area === 'frontend' ? null : 'id'],
            'expediente/llenar' => ['mi-expediente.editar'], 'expediente/constancia' => [$area === 'frontend' ? 'mi-constancia' : 'expediente.constancia', $area === 'frontend' ? null : 'id'],
            'expediente/crear' => ['expediente.crear', 'user_id'],
            'atencion/index' => ['atencion'], 'reporte/index' => ['alertas'], 'salud/index' => ['resultados'], 'salud/resultado' => ['resultados', 'alumno_id'],
            'coordinador-panel/index' => ['panel'],
        ];
        foreach (['licenciatura', 'genero', 'grupo'] as $catalog) {
            $map[$catalog.'/index'] = ['catalogo', null, ['catalog' => $catalog]];
            $map[$catalog.'/create'] = ['catalogo', null, ['catalog' => $catalog]];
            $map[$catalog.'/update'] = ['catalogo.edit', 'id', ['catalog' => $catalog]];
            $map[$catalog.'/view'] = ['catalogo.edit', 'id', ['catalog' => $catalog]];
            if ($catalog !== 'genero') {
                $map[$catalog.'/delete'] = ['catalogo.status', 'id', ['catalog' => $catalog]];
                $map[$catalog.'/reactivar'] = ['catalogo.status', 'id', ['catalog' => $catalog]];
            }
        }
        if (in_array($action, ['expediente/delete', 'expediente/archivar', 'expediente/restaurar', 'expediente/agregar-nota', 'expediente/clasificar', 'expediente/bloqueo'], true)) {
            $kind = ['delete' => 'archivar', 'agregar-nota' => 'nota'][basename($action)] ?? basename($action);
            $map[$action] = ['expediente.accion', 'id', ['action' => $kind]];
        }
        abort_unless(isset($map[$action]), 404, 'Esta URL Yii no tiene equivalencia habilitada.');
        $definition = $map[$action];
        $name = $definition[0];
        $parameters = $definition[2] ?? [];
        if (! empty($definition[1])) {
            $value = $request->query($definition[1]);
            abort_unless(is_scalar($value) && (string) $value !== '', 400);
            $key = $definition[1] === 'token' ? 'token' : (in_array($definition[1], ['alumno_id', 'user_id'], true) ? 'student' : 'id');
            $parameters[$key] = $value;
        }
        if ($name === 'encuesta.responder' && $request->filled('id_encuesta')) {
            $parameters['id'] = $request->query('id_encuesta');
        }
        $body = $request->request->all();
        foreach (['User', 'SignupForm', 'LoginForm', 'CompletarPerfilForm', 'PasswordResetRequestForm', 'ResetPasswordForm', 'ResendVerificationEmailForm', 'Grupo', 'Genero', 'Licenciatura', 'Encuesta', 'Pregunta'] as $model) {
            if (isset($body[$model]) && is_array($body[$model])) {
                $body = array_merge($body, $body[$model]);
                unset($body[$model]);
            }
        }
        if (isset($body['nombres'])) {
            $body['nombre'] = $body['nombres'];
        }
        if (isset($body['ExpedienteAlumno'])) {
            $body['datos'] = $body['ExpedienteAlumno'];
            unset($body['ExpedienteAlumno']);
        }
        if (isset($body['Respuesta'])) {
            $body['respuestas'] = $body['Respuesta'];
        }
        if (isset($body['Permisos'])) {
            $body['permisos'] = $body['Permisos'];
        }
        $body['motivo'] = $body['motivo'] ?? $body['motivo_cambio'] ?? null;
        if (isset($body['password']) && in_array($action, ['site/signup', 'site/reset-password'], true)) {
            $body['password_confirmation'] ??= $body['password'];
        }
        if (isset($body['nota_seguimiento'])) {
            $body['nota'] = $body['nota_seguimiento'];
        }
        if (isset($body['categoria_manual'])) {
            $body['categorias'] = $body['categoria_manual'];
        }
        if ($action === 'coordinador/delete') {
            $body['status'] = 0;
        }
        if ($action === 'coordinador/reactivar') {
            $body['status'] = 10;
        }
        if ($name === 'catalogo.status') {
            $body['estado'] = str_ends_with($action, '/delete') ? 0 : 1;
        }
        if ($action === 'alumno/aprobar') {
            $body['modo'] = 'individual';
            $body['seleccion'] = [$request->query('id')];
        }
        if ($request->isMethod('post')) {
            $name = ['login' => 'login.submit', 'registro' => 'registro.submit', 'coordinadores' => 'coordinadores.create', 'coordinador.edit' => match ($action) {
                'coordinador/permisos' => 'coordinador.permisos', 'coordinador/asignar-grupo' => 'coordinador.grupos', default => 'coordinador.save'
            }, 'catalogo' => 'catalogo.save', 'catalogo.edit' => 'catalogo.save'][$name] ?? $name;
            if ($action === 'encuesta/create') {
                $name = 'encuesta.save';
            }
            if ($action === 'pregunta/create') {
                $name = 'pregunta.save';
            }
            if (in_array($action, ['encuesta/update', 'pregunta/update'], true)) {
                $name = $action === 'encuesta/update' ? 'encuesta.save' : 'pregunta.save';
                $parameters['id'] = $request->query('id');
                abort_unless(is_scalar($parameters['id']) && ctype_digit((string) $parameters['id']), 400);
            }
        } elseif ($name === 'email.resend') {
            $name = 'email.resend.form';
        }
        $query = $request->query();
        unset($query['r'], $query['id'], $query['token'], $query['alumno_id'], $query['user_id'], $query['id_encuesta']);
        $url = route($name, $parameters, false).($query ? '?'.http_build_query($query) : '');
        $forward = Request::create($url, $request->method(), $request->isMethod('get') ? $query : $body, $request->cookies->all(), [], $request->server->all());
        $forward->headers->replace($request->headers->all());
        $forward->setLaravelSession($request->session());
        app()->instance('request', $forward);
        try {
            return Route::dispatch($forward);
        } finally {
            app()->instance('request', $request);
        }
    }
}
