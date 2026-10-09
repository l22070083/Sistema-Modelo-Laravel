@extends('layout')

@section('title', 'Expedientes de Alumnos')

@section('content')

<style>
    :root {
        --records-blue: #174a88;
        --records-blue-dark: #10345f;
        --records-blue-light: #eaf2ff;
        --records-border: #dbe5f1;
        --records-bg: #f4f7fb;
        --records-text: #26384e;
        --records-muted: #718096;
        --records-success: #198754;
        --records-warning: #f59e0b;
    }

    .records-page {
        background: var(--records-bg);
        min-height: calc(100vh - 100px);
        padding: 30px 0 45px;
    }

    .records-container {
        max-width: 1250px;
        margin: 0 auto;
        padding: 0 20px;
    }

    .records-header {
        background: linear-gradient(
            135deg,
            var(--records-blue-dark),
            var(--records-blue)
        );
        color: white;
        border-radius: 18px;
        padding: 30px;
        margin-bottom: 24px;
        box-shadow: 0 10px 30px rgba(23, 74, 136, .16);
    }

    .records-header-content {
        display: flex;
        align-items: center;
        gap: 18px;
    }

    .records-icon {
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

    .records-header h1 {
        margin: 0 0 7px;
        font-size: 28px;
        font-weight: 700;
        letter-spacing: -.3px;
    }

    .records-header p {
        margin: 0;
        color: rgba(255, 255, 255, .88);
        font-size: 14px;
        line-height: 1.6;
    }

    .filter-card {
        background: white;
        border: 1px solid var(--records-border);
        border-radius: 18px;
        padding: 24px;
        margin-bottom: 24px;
        box-shadow: 0 8px 25px rgba(30, 55, 90, .06);
    }

    .filter-title {
        display: flex;
        align-items: center;
        gap: 9px;
        margin-bottom: 20px;
        color: var(--records-text);
        font-size: 17px;
        font-weight: 700;
    }

    .filter-title-icon {
        width: 34px;
        height: 34px;
        border-radius: 9px;
        background: var(--records-blue-light);
        color: var(--records-blue);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .form-label {
        color: #53657a;
        font-size: 13px;
        font-weight: 700;
        margin-bottom: 7px;
    }

    .form-control,
    .form-select {
        min-height: 42px;
        border: 1px solid var(--records-border);
        border-radius: 9px;
        color: var(--records-text);
        box-shadow: none;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: var(--records-blue);
        box-shadow: 0 0 0 3px rgba(23, 74, 136, .10);
    }

    .archive-option {
        min-height: 42px;
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 10px;
        color: #53657a;
        font-size: 13px;
        font-weight: 600;
    }

    .archive-option input {
        width: 17px;
        height: 17px;
        accent-color: var(--records-blue);
    }

    .filter-button {
        border: none;
        border-radius: 9px;
        padding: 10px 20px;
        background: var(--records-blue);
        color: white;
        font-size: 14px;
        font-weight: 700;
        transition: all .18s ease;
    }

    .filter-button:hover {
        background: var(--records-blue-dark);
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 5px 14px rgba(23, 74, 136, .2);
    }

    .records-card {
        background: white;
        border: 1px solid var(--records-border);
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 8px 25px rgba(30, 55, 90, .07);
    }

    .records-card-header {
        padding: 20px 24px;
        border-bottom: 1px solid var(--records-border);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
    }

    .records-card-header h2 {
        margin: 0;
        color: var(--records-text);
        font-size: 18px;
        font-weight: 700;
    }

    .records-count {
        background: var(--records-blue-light);
        color: var(--records-blue);
        padding: 6px 13px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
    }

    .records-table-wrapper {
        overflow-x: auto;
    }

    .records-table {
        width: 100%;
        margin: 0;
        border-collapse: collapse;
        min-width: 750px;
    }

    .records-table thead th {
        background: #f7f9fc;
        color: #53657a;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: .4px;
        font-weight: 700;
        padding: 15px 20px;
        border-bottom: 1px solid var(--records-border);
        white-space: nowrap;
    }

    .records-table tbody td {
        padding: 17px 20px;
        color: var(--records-text);
        font-size: 14px;
        border-bottom: 1px solid #edf1f6;
        vertical-align: middle;
    }

    .records-table tbody tr:last-child td {
        border-bottom: none;
    }

    .records-table tbody tr {
        transition: background .18s ease;
    }

    .records-table tbody tr:hover {
        background: #f8fbff;
    }

    .student-name {
        font-weight: 700;
        color: var(--records-text);
    }

    .career-name {
        color: #53657a;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 11px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
    }

    .status-active {
        background: #f1f8f4;
        color: var(--records-success);
        border: 1px solid #cce8d7;
    }

    .status-archived {
        background: #fff8e8;
        color: #b77900;
        border: 1px solid #f7df9e;
    }

    .view-button {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 8px 14px;
        border-radius: 8px;
        background: var(--records-blue);
        color: white;
        text-decoration: none;
        font-size: 13px;
        font-weight: 700;
        transition: all .18s ease;
    }

    .view-button:hover {
        background: var(--records-blue-dark);
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 5px 12px rgba(23, 74, 136, .2);
    }

    .empty-state {
        text-align: center;
        padding: 55px 25px !important;
        color: var(--records-muted) !important;
    }

    .empty-icon {
        font-size: 36px;
        margin-bottom: 10px;
        opacity: .7;
    }

    .records-pagination {
        padding: 20px 24px;
        border-top: 1px solid var(--records-border);
        background: #fbfcfe;
    }

    .records-pagination nav {
        display: flex;
        justify-content: center;
    }

    @media (max-width: 768px) {
        .records-page {
            padding: 20px 0 30px;
        }

        .records-container {
            padding: 0 12px;
        }

        .records-header {
            padding: 22px;
            border-radius: 14px;
        }

        .records-header-content {
            align-items: flex-start;
        }

        .records-icon {
            width: 48px;
            height: 48px;
            font-size: 22px;
        }

        .records-header h1 {
            font-size: 23px;
        }

        .filter-card {
            padding: 18px;
        }

        .records-card-header {
            padding: 17px;
        }
    }
</style>

<div class="records-page">

    <div class="records-container">

        {{-- Encabezado --}}
        <div class="records-header">
            <div class="records-header-content">

                <div class="records-icon">
                    📁
                </div>

                <div>
                    <h1>Expedientes de alumnos</h1>

                    <p>
                        Consulta, filtra y revisa los expedientes académicos
                        y de seguimiento de los alumnos registrados.
                    </p>
                </div>

            </div>
        </div>

        {{-- Filtros --}}
        <div class="filter-card">

            <div class="filter-title">
                <span class="filter-title-icon">🔎</span>
                <span>Buscar y filtrar expedientes</span>
            </div>

            <form method="get">

                <div class="row g-3">

                    <div class="col-md-4">

                        <label for="q" class="form-label">
                            Alumno
                        </label>

                        <input
                            id="q"
                            class="form-control"
                            name="q"
                            value="{{ request('q') }}"
                            placeholder="Nombre o apellido"
                        >

                    </div>

                    <div class="col-md-4">

                        <label for="licenciatura" class="form-label">
                            Licenciatura
                        </label>

                        <select
                            id="licenciatura"
                            class="form-select"
                            name="licenciatura_id"
                        >

                            <option value="">
                                Todas las licenciaturas
                            </option>

                            @foreach($licenciaturas as $l)

                                <option
                                    value="{{ $l->id }}"
                                    @selected(request('licenciatura_id') == $l->id)
                                >
                                    {{ $l->nombre }}
                                </option>

                            @endforeach

                        </select>

                    </div>

                    <div class="col-md-4">

                        <label class="form-label">
                            Opciones
                        </label>

                        <label class="archive-option">
                            <input
                                name="archivados"
                                type="checkbox"
                                value="1"
                                @checked(request('archivados'))
                            >

                            <span>Incluir expedientes archivados</span>
                        </label>

                        <button
                            type="submit"
                            class="filter-button"
                        >
                            🔎 Filtrar resultados
                        </button>

                    </div>

                </div>

            </form>

        </div>

        {{-- Expedientes --}}
        <div class="records-card">

            <div class="records-card-header">

                <h2>
                    Expedientes registrados
                </h2>

                <span class="records-count">
                    {{ $rows->total() }} registros
                </span>

            </div>

            <div class="records-table-wrapper">

                <table class="records-table">

                    <thead>
                        <tr>
                            <th>Alumno</th>
                            <th>Licenciatura</th>
                            <th>Estado</th>
                            <th>Acción</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse($rows as $row)

                            <tr>

                                <td>
                                    <div class="student-name">
                                        {{ $row->nombres }}
                                        {{ $row->apellidos }}
                                    </div>
                                </td>

                                <td>
                                    <span class="career-name">
                                        {{ $row->licenciatura }}
                                    </span>
                                </td>

                                <td>

                                    @if($row->archivado_at)

                                        <span class="status-badge status-archived">
                                            ▣ Archivado
                                        </span>

                                    @else

                                        <span class="status-badge status-active">
                                            ✓ Activo
                                        </span>

                                    @endif

                                </td>

                                <td>

                                    <a
                                        href="{{ route('expediente.ver', $row->id) }}"
                                        class="view-button"
                                    >
                                        <span>👁</span>
                                        Ver expediente
                                    </a>
                                    @if(auth()->user()->rol_id===\App\Models\User::ADMIN)
                                    <a class="btn btn-outline-danger btn-sm ms-2" href="{{ route('expediente.ver', $row->id) }}#eliminar-expediente">Eliminar</a>
                                    @endif

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="4" class="empty-state">

                                    <div class="empty-icon">
                                        📁
                                    </div>

                                    <strong>
                                        No hay expedientes disponibles.
                                    </strong>

                                    <br>

                                    <small>
                                        Intenta cambiar los filtros de búsqueda
                                        o registra un nuevo expediente.
                                    </small>

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

            {{-- Paginación --}}
            @if($rows->hasPages())

                <div class="records-pagination">
                    {{ $rows->links() }}
                </div>

            @endif

        </div>

    </div>

</div>

@endsection
