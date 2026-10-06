@extends('layout')

@section('title', 'Notificaciones')

@section('content')

<h1>Notificaciones</h1>
<div class="card"><div class="card-body">
<h2 class="h5">Solicitudes de alta de alumnos ({{ $alumnos->total() }})</h2>
<p>Puedes dar de alta a un alumno, varios seleccionados o todos los pendientes de todas las páginas.</p>
<form id="seleccionados" action="{{ route('notificaciones.alta') }}" method="post">@csrf<input type="hidden" name="modo" value="seleccionados"></form>
<x-table-scroll label="Solicitudes de alta"><table class="table"><thead><tr><th><label><input type="checkbox" id="marcar-pagina"> Seleccionar página</label></th><th>Alumno</th><th>Correo</th><th>Matrícula</th><th>Acción</th></tr></thead><tbody>
@forelse($alumnos as $alumno)
<tr>
<td><input form="seleccionados" type="checkbox" class="seleccion-alumno" name="seleccion[]" value="{{ $alumno->id }}" aria-label="Seleccionar a {{ $alumno->nombre }}"></td>
<td>{{ $alumno->nombre }} {{ $alumno->apellidos }}</td><td>{{ $alumno->email }}</td><td>{{ $alumno->matricula }}</td>
<td><form action="{{ route('notificaciones.alta') }}" method="post" onsubmit="return confirm('¿Dar de alta a este alumno y avisarle por correo?')">@csrf<input type="hidden" name="modo" value="individual"><input type="hidden" name="seleccion[]" value="{{ $alumno->id }}"><button class="btn btn-success btn-sm">Dar de alta</button></form></td>
</tr>
@empty<tr><td colspan="5">No hay solicitudes de alta pendientes.</td></tr>@endforelse
</tbody></table></x-table-scroll>
{{ $alumnos->links() }}
<div class="approval-actions"><button form="seleccionados" class="btn btn-primary" @disabled(!$alumnos->total()) onclick="return confirm('¿Dar de alta a los seleccionados?')">Dar de alta a los seleccionados</button>
<form action="{{ route('notificaciones.alta') }}" method="post" onsubmit="return confirm('¿Dar de alta a TODOS los pendientes, incluyendo todas las páginas?')">@csrf<input type="hidden" name="modo" value="todos"><button class="btn btn-success" @disabled(!$alumnos->total())>Dar de alta a todos ({{ $alumnos->total() }})</button></form></div>
</div></div>
<script>document.getElementById('marcar-pagina').addEventListener('change', function () { document.querySelectorAll('.seleccion-alumno').forEach(input => input.checked = this.checked); });</script>


<style>
    :root {
        --notif-blue: #174a88;
        --notif-blue-dark: #10345f;
        --notif-blue-light: #eaf2ff;
        --notif-border: #dbe5f1;
        --notif-bg: #f4f7fb;
        --notif-text: #26384e;
        --notif-muted: #718096;
        --notif-success: #198754;
        --notif-success-dark: #146c43;
        --notif-warning: #b77900;
    }

    .notifications-page {
        background: var(--notif-bg);
        min-height: calc(100vh - 100px);
        padding: 30px 0 45px;
    }

    .notifications-container {
        max-width: 1300px;
        margin: 0 auto;
        padding: 0 20px;
    }

    .notifications-header {
        background: linear-gradient(
            135deg,
            var(--notif-blue-dark),
            var(--notif-blue)
        );
        color: white;
        border-radius: 18px;
        padding: 30px;
        margin-bottom: 24px;
        box-shadow: 0 10px 30px rgba(23, 74, 136, .16);
    }

    .notifications-header-content {
        display: flex;
        align-items: center;
        gap: 18px;
    }

    .notifications-icon {
        width: 58px;
        height: 58px;
        border-radius: 15px;
        background: rgba(255, 255, 255, .15);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        flex-shrink: 0;
    }

    .notifications-header h1 {
        margin: 0 0 7px;
        font-size: 28px;
        font-weight: 700;
        letter-spacing: -.3px;
    }

    .notifications-header p {
        margin: 0;
        color: rgba(255, 255, 255, .88);
        font-size: 14px;
        line-height: 1.6;
    }

    .requests-card {
        background: white;
        border: 1px solid var(--notif-border);
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 8px 25px rgba(30, 55, 90, .07);
    }

    .requests-header {
        padding: 22px 24px;
        border-bottom: 1px solid var(--notif-border);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 18px;
    }

    .requests-title {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .requests-title-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: var(--notif-blue-light);
        color: var(--notif-blue);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 19px;
    }

    .requests-header h2 {
        margin: 0;
        color: var(--notif-text);
        font-size: 18px;
        font-weight: 700;
    }

    .requests-count {
        background: var(--notif-blue-light);
        color: var(--notif-blue);
        padding: 7px 13px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
    }

    .requests-body {
        padding: 22px 24px;
    }

    .requests-description {
        color: var(--notif-muted);
        font-size: 14px;
        line-height: 1.6;
        margin: 0 0 20px;
    }

    .selection-info {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        background: #f8faff;
        border: 1px solid var(--notif-border);
        border-radius: 10px;
        padding: 12px 14px;
        margin-bottom: 20px;
        color: var(--notif-muted);
        font-size: 13px;
        line-height: 1.5;
    }

    .selection-info strong {
        color: var(--notif-blue);
    }

    .table-wrapper {
        overflow-x: auto;
        border: 1px solid var(--notif-border);
        border-radius: 12px;
    }

    .notifications-table {
        width: 100%;
        min-width: 900px;
        margin: 0;
        border-collapse: collapse;
    }

    .notifications-table thead th {
        background: #f7f9fc;
        color: #53657a;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: .4px;
        font-weight: 700;
        padding: 15px 16px;
        border-bottom: 1px solid var(--notif-border);
        white-space: nowrap;
    }

    .notifications-table tbody td {
        padding: 16px;
        color: var(--notif-text);
        font-size: 14px;
        border-bottom: 1px solid #edf1f6;
        vertical-align: middle;
    }

    .notifications-table tbody tr:last-child td {
        border-bottom: none;
    }

    .notifications-table tbody tr {
        transition: background .18s ease;
    }

    .notifications-table tbody tr:hover {
        background: #f8fbff;
    }

    .page-checkbox {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        cursor: pointer;
        color: #53657a;
        font-size: 12px;
        font-weight: 700;
    }

    .selection-checkbox {
        width: 17px;
        height: 17px;
        accent-color: var(--notif-blue);
        cursor: pointer;
    }

    .student-name {
        font-weight: 700;
        color: var(--notif-text);
    }

    .student-email {
        color: var(--notif-muted);
        font-size: 13px;
    }

    .student-matricula {
        display: inline-flex;
        align-items: center;
        padding: 5px 9px;
        border-radius: 7px;
        background: #f3f6fa;
        border: 1px solid var(--notif-border);
        color: #53657a;
        font-size: 12px;
        font-weight: 700;
    }

    .individual-button {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border: none;
        border-radius: 8px;
        padding: 8px 13px;
        background: var(--notif-success);
        color: white;
        font-size: 12px;
        font-weight: 700;
        transition: all .18s ease;
    }

    .individual-button:hover {
        background: var(--notif-success-dark);
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 4px 11px rgba(25, 135, 84, .18);
    }

    .empty-state {
        text-align: center;
        padding: 55px 25px !important;
        color: var(--notif-muted) !important;
    }

    .empty-icon {
        font-size: 38px;
        margin-bottom: 10px;
        opacity: .7;
    }

    .pagination-container {
        padding: 20px 0;
        display: flex;
        justify-content: center;
    }

    .actions-panel {
        border-top: 1px solid var(--notif-border);
        margin-top: 5px;
        padding-top: 22px;
    }

    .actions-title {
        color: var(--notif-text);
        font-size: 15px;
        font-weight: 700;
        margin-bottom: 14px;
    }

    .actions-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
    }

    .bulk-button {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border: none;
        border-radius: 9px;
        padding: 10px 17px;
        font-size: 13px;
        font-weight: 700;
        transition: all .18s ease;
    }

    .bulk-button.primary {
        background: var(--notif-blue);
        color: white;
    }

    .bulk-button.primary:hover:not(:disabled) {
        background: var(--notif-blue-dark);
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 5px 13px rgba(23, 74, 136, .2);
    }

    .bulk-button.success {
        background: var(--notif-success);
        color: white;
    }

    .bulk-button.success:hover:not(:disabled) {
        background: var(--notif-success-dark);
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 5px 13px rgba(25, 135, 84, .2);
    }

    .bulk-button:disabled {
        opacity: .45;
        cursor: not-allowed;
    }

    .all-note {
        display: block;
        margin-top: 9px;
        color: var(--notif-muted);
        font-size: 12px;
    }

    @media (max-width: 768px) {
        .notifications-page {
            padding: 20px 0 30px;
        }

        .notifications-container {
            padding: 0 12px;
        }

        .notifications-header {
            padding: 22px;
            border-radius: 14px;
        }

        .notifications-header-content {
            align-items: flex-start;
        }

        .notifications-icon {
            width: 48px;
            height: 48px;
            font-size: 22px;
        }

        .notifications-header h1 {
            font-size: 23px;
        }

        .requests-header {
            padding: 17px;
            align-items: flex-start;
        }

        .requests-body {
            padding: 18px;
        }

        .actions-grid {
            flex-direction: column;
        }

        .bulk-button {
            justify-content: center;
            width: 100%;
        }
    }
</style>

<div class="notifications-page">


<div class="notifications-container">

    {{-- Encabezado --}}
    <div class="notifications-header">

        <div class="notifications-header-content">

            <div class="notifications-icon">
                🔔
            </div>

            <div>
                <h1>Notificaciones</h1>

                <p>
                    Gestiona las solicitudes de alta de alumnos
                    pendientes de aprobación.
                </p>
            </div>

        </div>

    </div>

    {{-- Solicitudes --}}
    <div class="requests-card">

        <div class="requests-header">

            <div class="requests-title">

                <div class="requests-title-icon">
                    👨‍🎓
                </div>

                <h2>
                    Solicitudes de alta de alumnos
                </h2>

            </div>

            <span class="requests-count">
                {{ $alumnos->total() }} pendientes
            </span>

        </div>

        <div class="requests-body">

            <p class="requests-description">
                Puedes dar de alta a un alumno individualmente,
                seleccionar varios alumnos de la página actual
                o aprobar todos los pendientes de todas las páginas.
            </p>

            <div class="selection-info">

                <span>ⓘ</span>

                <span>
                    Utiliza <strong>“Seleccionar página”</strong>
                    para marcar rápidamente los alumnos visibles.
                    Las acciones masivas se ejecutarán según la opción elegida.
                </span>

            </div>

            {{-- Formulario para seleccionados --}}
            <form
                id="seleccionados"
                action="{{ route('notificaciones.alta') }}"
                method="post"
            >
                @csrf

                <input
                    type="hidden"
                    name="modo"
                    value="seleccionados"
                >
            </form>

            <div class="table-wrapper">

                <table class="notifications-table">

                    <thead>

                        <tr>

                            <th>

                                <label
                                    for="marcar-pagina"
                                    class="page-checkbox"
                                >
                                    <input
                                        type="checkbox"
                                        id="marcar-pagina"
                                        class="selection-checkbox"
                                    >

                                    <span>
                                        Seleccionar página
                                    </span>
                                </label>

                            </th>

                            <th>Alumno</th>
                            <th>Correo</th>
                            <th>Matrícula</th>
                            <th>Acción</th>

                        </tr>

                    </thead>

                    <tbody>

                        @forelse($alumnos as $alumno)

                            <tr>

                                <td>

                                    <input
                                        form="seleccionados"
                                        type="checkbox"
                                        class="seleccion-alumno selection-checkbox"
                                        name="seleccion[]"
                                        value="{{ $alumno->id }}"
                                        aria-label="Seleccionar a {{ $alumno->nombre }}"
                                    >

                                </td>

                                <td>
                                    <div class="student-name">
                                        {{ $alumno->nombre }}
                                        {{ $alumno->apellidos }}
                                    </div>
                                </td>

                                <td>
                                    <span class="student-email">
                                        {{ $alumno->email }}
                                    </span>
                                </td>

                                <td>
                                    <span class="student-matricula">
                                        {{ $alumno->matricula }}
                                    </span>
                                </td>

                                <td>

                                    <form
                                        action="{{ route('notificaciones.alta') }}"
                                        method="post"
                                        onsubmit="return confirm('¿Dar de alta a este alumno y avisarle por correo?')"
                                    >

                                        @csrf

                                        <input
                                            type="hidden"
                                            name="modo"
                                            value="individual"
                                        >

                                        <input
                                            type="hidden"
                                            name="seleccion[]"
                                            value="{{ $alumno->id }}"
                                        >

                                        <button
                                            type="submit"
                                            class="individual-button"
                                        >
                                            ✓ Dar de alta
                                        </button>

                                    </form>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td
                                    colspan="5"
                                    class="empty-state"
                                >

                                    <div class="empty-icon">
                                        🔔
                                    </div>

                                    <strong>
                                        No hay solicitudes de alta pendientes.
                                    </strong>

                                    <br>

                                    <small>
                                        Cuando un alumno solicite su registro,
                                        aparecerá aquí para su aprobación.
                                    </small>

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

            {{-- Paginación --}}
            @if($alumnos->hasPages())

                <div class="pagination-container">
                    {{ $alumnos->links() }}
                </div>

            @endif

            {{-- Acciones masivas --}}
            <div class="actions-panel">

                <div class="actions-title">
                    Acciones masivas
                </div>

                <div class="actions-grid">

                    <button
                        form="seleccionados"
                        type="submit"
                        class="bulk-button primary"
                        @disabled(!$alumnos->total())
                        onclick="return confirm('¿Dar de alta a los seleccionados?')"
                    >
                        ✓ Dar de alta a los seleccionados
                    </button>

                    <form
                        action="{{ route('notificaciones.alta') }}"
                        method="post"
                        onsubmit="return confirm('¿Dar de alta a TODOS los pendientes, incluyendo todas las páginas?')"
                    >

                        @csrf

                        <input
                            type="hidden"
                            name="modo"
                            value="todos"
                        >

                        <button
                            type="submit"
                            class="bulk-button success"
                            @disabled(!$alumnos->total())
                        >
                            ✓ Dar de alta a todos
                            ({{ $alumnos->total() }})
                        </button>

                    </form>

                </div>

                <small class="all-note">
                    La opción “Dar de alta a todos” incluye las solicitudes
                    pendientes que se encuentren en todas las páginas.
                </small>

            </div>

        </div>

    </div>

</div>


</div>

<script>
    const marcarPagina = document.getElementById('marcar-pagina');

    if (marcarPagina) {
        marcarPagina.addEventListener('change', function () {
            document
                .querySelectorAll('.seleccion-alumno')
                .forEach(input => {
                    input.checked = this.checked;
                });
        });
    }
</script>

>>>>>>> c285146 (agregando nuevas vistas de acuerdo con los colores de la escuela dentro del panel administrativo)
@endsection
