@extends('layout')
<<<<<<< HEAD
@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
    <h1 class="mb-0">Alumnos</h1>
    @if(auth()->user()->rol_id === \App\Models\User::ADMIN)<a class="btn btn-primary" href="{{ route('alumno.create') }}">Crear alumno</a>@endif
</div>
<x-table-scroll label="Listado de alumnos"><table class="table"><thead><tr><th>Nombre</th><th>Usuario</th><th>Matrícula</th><th>Acción</th></tr></thead><tbody>@forelse($rows as $student)<tr><td>{{ $student->nombre }} {{ $student->apellidos }}</td><td>{{ $student->username }}</td><td>{{ $student->matricula }}</td><td><a href="{{ route('alumno.ver',$student->id) }}">Ver</a></td></tr>@empty<tr><td colspan="4">No hay alumnos registrados.</td></tr>@endforelse</tbody></table></x-table-scroll>{{ $rows->links() }}
=======

@section('title', 'Alumnos')

@section('content')

<style>
    :root {
        --students-blue: #174a88;
        --students-blue-dark: #10345f;
        --students-blue-light: #eaf2ff;
        --students-border: #dbe5f1;
        --students-bg: #f4f7fb;
        --students-text: #26384e;
        --students-muted: #718096;
    }

    .students-page {
        background: var(--students-bg);
        min-height: calc(100vh - 100px);
        padding: 30px 0 45px;
    }

    .students-container {
        max-width: 1250px;
        margin: 0 auto;
        padding: 0 20px;
    }

    .students-header {
        background: linear-gradient(
            135deg,
            var(--students-blue-dark),
            var(--students-blue)
        );
        color: white;
        border-radius: 18px;
        padding: 30px;
        margin-bottom: 24px;
        box-shadow: 0 10px 30px rgba(23, 74, 136, .16);
    }

    .students-header-content {
        display: flex;
        align-items: center;
        gap: 18px;
    }

    .students-icon {
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

    .students-header h1 {
        margin: 0 0 7px;
        font-size: 28px;
        font-weight: 700;
        letter-spacing: -.3px;
    }

    .students-header p {
        margin: 0;
        color: rgba(255, 255, 255, .88);
        font-size: 14px;
        line-height: 1.6;
    }

    .students-card {
        background: white;
        border: 1px solid var(--students-border);
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 8px 25px rgba(30, 55, 90, .07);
    }

    .students-card-header {
        padding: 20px 24px;
        border-bottom: 1px solid var(--students-border);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
    }

    .students-card-header h2 {
        margin: 0;
        color: var(--students-text);
        font-size: 18px;
        font-weight: 700;
    }

    .students-count {
        background: var(--students-blue-light);
        color: var(--students-blue);
        padding: 6px 13px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
    }

    .students-table-wrapper {
        overflow-x: auto;
    }

    .students-table {
        width: 100%;
        margin: 0;
        border-collapse: collapse;
        min-width: 700px;
    }

    .students-table thead th {
        background: #f7f9fc;
        color: #53657a;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: .4px;
        font-weight: 700;
        padding: 15px 20px;
        border-bottom: 1px solid var(--students-border);
        white-space: nowrap;
    }

    .students-table tbody td {
        padding: 17px 20px;
        color: var(--students-text);
        font-size: 14px;
        border-bottom: 1px solid #edf1f6;
        vertical-align: middle;
    }

    .students-table tbody tr:last-child td {
        border-bottom: none;
    }

    .students-table tbody tr {
        transition: background .18s ease;
    }

    .students-table tbody tr:hover {
        background: #f8fbff;
    }

    .student-name {
        font-weight: 700;
        color: var(--students-text);
    }

    .student-username {
        color: var(--students-muted);
        font-family: monospace;
        font-size: 13px;
    }

    .student-id {
        display: inline-flex;
        align-items: center;
        padding: 6px 10px;
        background: #f3f6fa;
        border: 1px solid var(--students-border);
        border-radius: 7px;
        color: #53657a;
        font-size: 13px;
        font-weight: 600;
    }

    .student-action {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 8px 14px;
        border-radius: 8px;
        background: var(--students-blue);
        color: white;
        text-decoration: none;
        font-size: 13px;
        font-weight: 700;
        transition: all .18s ease;
    }

    .student-action:hover {
        background: var(--students-blue-dark);
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 5px 12px rgba(23, 74, 136, .2);
    }

    .empty-state {
        text-align: center;
        padding: 55px 25px !important;
        color: var(--students-muted) !important;
    }

    .empty-icon {
        font-size: 36px;
        margin-bottom: 10px;
        opacity: .7;
    }

    .students-pagination {
        padding: 20px 24px;
        border-top: 1px solid var(--students-border);
        background: #fbfcfe;
    }

    .students-pagination nav {
        display: flex;
        justify-content: center;
    }

    @media (max-width: 768px) {
        .students-page {
            padding: 20px 0 30px;
        }

        .students-container {
            padding: 0 12px;
        }

        .students-header {
            padding: 22px;
            border-radius: 14px;
        }

        .students-header-content {
            align-items: flex-start;
        }

        .students-icon {
            width: 48px;
            height: 48px;
            font-size: 22px;
        }

        .students-header h1 {
            font-size: 23px;
        }

        .students-card-header {
            padding: 17px;
        }
    }
</style>

<div class="students-page">


<div class="students-container">

    {{-- Encabezado --}}
    <div class="students-header">
        <div class="students-header-content">

            <div class="students-icon">
                👨‍🎓
            </div>

            <div>
                <h1>Alumnos</h1>

                <p>
                    Consulta y administra la información de los alumnos
                    registrados en el sistema de tutorías.
                </p>
            </div>

        </div>
    </div>

    {{-- Tabla --}}
    <div class="students-card">

        <div class="students-card-header">

            <h2>
                Alumnos registrados
            </h2>

            <span class="students-count">
                {{ $rows->total() }} registros
            </span>

        </div>

        <div class="students-table-wrapper">

            <table class="students-table">

                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Usuario</th>
                        <th>Matrícula</th>
                        <th>Acción</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse($rows as $student)

                        <tr>

                            <td>
                                <div class="student-name">
                                    {{ $student->nombre }}
                                    {{ $student->apellidos }}
                                </div>
                            </td>

                            <td>
                                <span class="student-username">
                                    {{ $student->username }}
                                </span>
                            </td>

                            <td>
                                <span class="student-id">
                                    {{ $student->matricula }}
                                </span>
                            </td>

                            <td>
                                <a
                                    href="{{ route('alumno.ver', $student->id) }}"
                                    class="student-action"
                                >
                                    <span>👁</span>
                                    Ver alumno
                                </a>
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="4" class="empty-state">

                                <div class="empty-icon">
                                    👨‍🎓
                                </div>

                                <strong>
                                    No hay alumnos registrados.
                                </strong>

                                <br>

                                <small>
                                    Los alumnos registrados aparecerán
                                    en esta sección.
                                </small>

                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        {{-- Paginación --}}
        @if($rows->hasPages())
            <div class="students-pagination">
                {{ $rows->links() }}
            </div>
        @endif

    </div>

</div>


</div>

>>>>>>> c285146 (agregando nuevas vistas de acuerdo con los colores de la escuela dentro del panel administrativo)
@endsection
