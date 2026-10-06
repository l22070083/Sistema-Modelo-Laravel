@extends('layout')

@section('title', 'Alertas de Salud')

@section('content')

<style>
    :root {
        --health-blue: #174a88;
        --health-blue-dark: #10345f;
        --health-blue-light: #eaf2ff;
        --health-border: #dbe5f1;
        --health-bg: #f4f7fb;
        --health-text: #26384e;
        --health-muted: #718096;
        --health-success: #198754;
        --health-warning: #f59e0b;
        --health-danger: #dc3545;
    }

    .health-page {
        background: var(--health-bg);
        min-height: calc(100vh - 100px);
        padding: 30px 0 45px;
    }

    .health-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 20px;
    }

    .health-header {
        background: linear-gradient(135deg, var(--health-blue-dark), var(--health-blue));
        color: white;
        border-radius: 18px;
        padding: 30px;
        margin-bottom: 24px;
        box-shadow: 0 10px 30px rgba(23, 74, 136, .16);
    }

    .health-header-content {
        display: flex;
        align-items: center;
        gap: 18px;
    }

    .health-icon {
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

    .health-header h1 {
        margin: 0 0 7px;
        font-size: 28px;
        font-weight: 700;
        letter-spacing: -.3px;
    }

    .health-header p {
        margin: 0;
        color: rgba(255, 255, 255, .88);
        line-height: 1.6;
        max-width: 950px;
        font-size: 14px;
    }

    .health-info {
        background: white;
        border: 1px solid var(--health-border);
        border-left: 5px solid var(--health-blue);
        border-radius: 14px;
        padding: 18px 20px;
        margin-bottom: 22px;
        color: var(--health-text);
        box-shadow: 0 5px 18px rgba(30, 55, 90, .05);
    }

    .health-info-title {
        display: flex;
        align-items: center;
        gap: 9px;
        color: var(--health-blue);
        font-weight: 700;
        margin-bottom: 5px;
    }

    .health-info p {
        margin: 0;
        color: var(--health-muted);
        font-size: 14px;
        line-height: 1.6;
    }

    .health-table-card {
        background: white;
        border: 1px solid var(--health-border);
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 8px 25px rgba(30, 55, 90, .07);
    }

    .health-table-header {
        padding: 20px 24px;
        border-bottom: 1px solid var(--health-border);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
    }

    .health-table-header h2 {
        margin: 0;
        font-size: 18px;
        color: var(--health-text);
        font-weight: 700;
    }

    .health-count {
        background: var(--health-blue-light);
        color: var(--health-blue);
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
    }

    .health-table-wrapper {
        overflow-x: auto;
    }

    .health-table {
        width: 100%;
        margin: 0;
        border-collapse: collapse;
        min-width: 950px;
    }

    .health-table thead th {
        background: #f7f9fc;
        color: #53657a;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: .4px;
        font-weight: 700;
        padding: 15px 18px;
        border-bottom: 1px solid var(--health-border);
        white-space: nowrap;
    }

    .health-table tbody td {
        padding: 17px 18px;
        color: var(--health-text);
        border-bottom: 1px solid #edf1f6;
        vertical-align: middle;
        font-size: 14px;
    }

    .health-table tbody tr:last-child td {
        border-bottom: none;
    }

    .health-table tbody tr {
        transition: background .18s ease;
    }

    .health-table tbody tr:hover {
        background: #f8fbff;
    }

    .student-link {
        color: var(--health-blue);
        font-weight: 700;
        text-decoration: none;
    }

    .student-link:hover {
        color: var(--health-blue-dark);
        text-decoration: underline;
    }

    .career {
        color: #53657a;
    }

    .alert-count {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 38px;
        height: 30px;
        padding: 0 10px;
        border-radius: 8px;
        background: var(--health-blue-light);
        color: var(--health-blue);
        font-weight: 800;
    }

    .classification {
        color: #53657a;
        line-height: 1.5;
    }

    .priority-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 11px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
    }

    .priority-badge.priority {
        background: #fff1f2;
        color: var(--health-danger);
        border: 1px solid #fecdd3;
    }

    .priority-badge.normal {
        background: #f1f8f4;
        color: var(--health-success);
        border: 1px solid #cce8d7;
    }

    .date-value {
        color: #53657a;
        white-space: nowrap;
    }

    .empty-state {
        text-align: center;
        padding: 55px 25px !important;
        color: var(--health-muted) !important;
    }

    .empty-icon {
        font-size: 35px;
        margin-bottom: 10px;
        opacity: .7;
    }

    @media (max-width: 768px) {
        .health-page {
            padding: 20px 0 30px;
        }

        .health-container {
            padding: 0 12px;
        }

        .health-header {
            padding: 22px;
            border-radius: 14px;
        }

        .health-header-content {
            align-items: flex-start;
        }

        .health-icon {
            width: 48px;
            height: 48px;
            font-size: 22px;
        }

        .health-header h1 {
            font-size: 23px;
        }

        .health-info {
            padding: 16px;
        }

        .health-table-header {
            padding: 17px;
        }
    }
</style>

<div class="health-page">
    <div class="health-container">


    {{-- Encabezado --}}
    <div class="health-header">
        <div class="health-header-content">
            <div class="health-icon">
                🩺
            </div>

            <div>
                <h1>Alertas de Salud</h1>
                <p>
                    Consulta y seguimiento de los indicadores de salud detectados
                    en los expedientes de los alumnos.
                </p>
            </div>
        </div>
    </div>

    {{-- Información --}}
    <div class="health-info">
        <div class="health-info-title">
            <span>ⓘ</span>
            <span>Información de los indicadores</span>
        </div>

        <p>
            Los indicadores cuentan respuestas afirmativas de riesgo medio o alto.
            La clasificación y la prioridad del expediente incluyen la valoración
            institucional cuando existe.
        </p>
    </div>

    {{-- Tabla --}}
    <div class="health-table-card">

        <div class="health-table-header">
            <h2>Indicadores de salud de los alumnos</h2>

            <span class="health-count">
                {{ count($rows) }} registros
            </span>
        </div>

        <div class="health-table-wrapper">
            <table class="health-table">

                <thead>
                    <tr>
                        <th>Alumno</th>
                        <th>Licenciatura</th>
                        <th>Indicadores</th>
                        <th>Clasificación</th>
                        <th>Prioridad</th>
                        <th>Última respuesta</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse($rows as $row)

                        <tr>

                            <td>
                                <a
                                    href="{{ route('resultados', $row->id) }}"
                                    class="student-link"
                                >
                                    {{ $row->nombre }} {{ $row->apellidos }}
                                </a>
                            </td>

                            <td>
                                <span class="career">
                                    {{ $row->licenciatura }}
                                </span>
                            </td>

                            <td>
                                <span class="alert-count">
                                    {{ $row->alertas }}
                                </span>
                            </td>

                            <td>
                                <div class="classification">
                                    {{ implode(', ', json_decode($row->categoria, true) ?: []) }}
                                </div>
                            </td>

                            <td>
                                @if($row->prioritaria)
                                    <span class="priority-badge priority">
                                        ⚠ Prioritaria
                                    </span>
                                @else
                                    <span class="priority-badge normal">
                                        ✓ Sin prioridad marcada
                                    </span>
                                @endif
                            </td>

                            <td>
                                <span class="date-value">
                                    {{ $row->ultima }}
                                </span>
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="6" class="empty-state">
                                <div class="empty-icon">🩺</div>
                                <strong>No hay alertas de salud registradas.</strong>
                                <br>
                                <small>
                                    Cuando existan respuestas que generen indicadores,
                                    aparecerán en esta sección.
                                </small>
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>
        </div>

    </div>

</div>


</div>

@endsection
