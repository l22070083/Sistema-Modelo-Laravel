@extends('layout')

@section('title', 'Resultados y Valoración de Salud')

@section('content')

<style>
    :root {
        --results-blue: #174a88;
        --results-blue-dark: #10345f;
        --results-blue-light: #eaf2ff;
        --results-border: #dbe5f1;
        --results-bg: #f4f7fb;
        --results-text: #26384e;
        --results-muted: #718096;
        --results-success: #198754;
        --results-warning: #b77900;
        --results-danger: #dc3545;
    }

    .results-page {
        background: var(--results-bg);
        min-height: calc(100vh - 100px);
        padding: 30px 0 45px;
    }

    .results-container {
        max-width: 1250px;
        margin: 0 auto;
        padding: 0 20px;
    }

    .results-header {
        background: linear-gradient(
            135deg,
            var(--results-blue-dark),
            var(--results-blue)
        );
        color: white;
        border-radius: 18px;
        padding: 30px;
        margin-bottom: 24px;
        box-shadow: 0 10px 30px rgba(23, 74, 136, .16);
    }

    .results-header-content {
        display: flex;
        align-items: center;
        gap: 18px;
    }

    .results-icon {
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

    .results-header h1 {
        margin: 0 0 7px;
        font-size: 28px;
        font-weight: 700;
        letter-spacing: -.3px;
    }

    .results-header p {
        margin: 0;
        color: rgba(255, 255, 255, .88);
        font-size: 14px;
        line-height: 1.6;
    }

    .information-card {
        background: white;
        border: 1px solid var(--results-border);
        border-left: 5px solid var(--results-blue);
        border-radius: 14px;
        padding: 19px 21px;
        margin-bottom: 25px;
        box-shadow: 0 5px 18px rgba(30, 55, 90, .05);
    }

    .information-title {
        display: flex;
        align-items: center;
        gap: 9px;
        color: var(--results-blue);
        font-weight: 700;
        margin-bottom: 7px;
        font-size: 14px;
    }

    .information-card p {
        margin: 0;
        color: var(--results-muted);
        font-size: 14px;
        line-height: 1.7;
    }

    .section-heading {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
        margin: 0 0 16px;
    }

    .section-heading h2 {
        margin: 0;
        color: var(--results-text);
        font-size: 20px;
        font-weight: 700;
    }

    .section-heading span {
        color: var(--results-muted);
        font-size: 13px;
    }

    .assessment-card {
        background: white;
        border: 1px solid var(--results-border);
        border-radius: 17px;
        overflow: hidden;
        margin-bottom: 17px;
        box-shadow: 0 7px 22px rgba(30, 55, 90, .06);
        transition: box-shadow .18s ease, transform .18s ease;
    }

    .assessment-card:hover {
        box-shadow: 0 10px 28px rgba(30, 55, 90, .09);
        transform: translateY(-1px);
    }

    .assessment-top {
        padding: 20px 23px;
        border-bottom: 1px solid var(--results-border);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 18px;
    }

    .student-info h3 {
        margin: 0;
        color: var(--results-text);
        font-size: 17px;
        font-weight: 700;
    }

    .student-label {
        display: block;
        color: var(--results-muted);
        font-size: 12px;
        margin-top: 4px;
    }

    .priority-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
    }

    .priority-badge.priority {
        background: #fff1f2;
        color: var(--results-danger);
        border: 1px solid #fecdd3;
    }

    .priority-badge.normal {
        background: #f1f8f4;
        color: var(--results-success);
        border: 1px solid #cce8d7;
    }

    .assessment-body {
        padding: 21px 23px;
    }

    .classification-box {
        background: #f8faff;
        border: 1px solid var(--results-border);
        border-radius: 11px;
        padding: 15px 16px;
        margin-bottom: 16px;
    }

    .classification-label {
        display: block;
        color: var(--results-muted);
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .4px;
        margin-bottom: 6px;
    }

    .classification-value {
        color: var(--results-blue);
        font-weight: 700;
        font-size: 14px;
        line-height: 1.5;
    }

    .institutional-note {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        background: #fff8e8;
        border: 1px solid #f7df9e;
        border-radius: 10px;
        padding: 13px 15px;
        margin-bottom: 17px;
        color: #765a16;
        font-size: 13px;
        line-height: 1.5;
    }

    .institutional-note strong {
        color: #624a10;
    }

    .assessment-action {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 9px 15px;
        border: 1px solid var(--results-blue);
        border-radius: 9px;
        background: white;
        color: var(--results-blue);
        text-decoration: none;
        font-size: 13px;
        font-weight: 700;
        transition: all .18s ease;
    }

    .assessment-action:hover {
        background: var(--results-blue);
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 5px 13px rgba(23, 74, 136, .15);
    }

    .history-section {
        margin-top: 35px;
    }

    .history-card {
        background: white;
        border: 1px solid var(--results-border);
        border-radius: 17px;
        overflow: hidden;
        box-shadow: 0 7px 22px rgba(30, 55, 90, .06);
    }

    .history-header {
        padding: 20px 23px;
        border-bottom: 1px solid var(--results-border);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
    }

    .history-header h2 {
        margin: 0;
        color: var(--results-text);
        font-size: 18px;
        font-weight: 700;
    }

    .history-badge {
        background: var(--results-blue-light);
        color: var(--results-blue);
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
    }

    .history-table-wrapper {
        overflow-x: auto;
    }

    .history-table {
        width: 100%;
        min-width: 650px;
        margin: 0;
        border-collapse: collapse;
    }

    .history-table thead th {
        background: #f7f9fc;
        color: #53657a;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: .4px;
        font-weight: 700;
        padding: 15px 19px;
        border-bottom: 1px solid var(--results-border);
        white-space: nowrap;
    }

    .history-table tbody td {
        padding: 16px 19px;
        color: var(--results-text);
        font-size: 14px;
        border-bottom: 1px solid #edf1f6;
        vertical-align: middle;
    }

    .history-table tbody tr:last-child td {
        border-bottom: none;
    }

    .history-table tbody tr:hover {
        background: #f8fbff;
    }

    .level-value {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 38px;
        padding: 6px 10px;
        background: var(--results-blue-light);
        color: var(--results-blue);
        border-radius: 7px;
        font-weight: 800;
    }

    .risk-status {
        display: inline-flex;
        align-items: center;
        padding: 6px 11px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
    }

    .risk-stable {
        background: #f1f8f4;
        color: var(--results-success);
        border: 1px solid #cce8d7;
    }

    .risk-followup {
        background: #fff8e8;
        color: var(--results-warning);
        border: 1px solid #f7df9e;
    }

    .risk-urgent {
        background: #fff1f2;
        color: var(--results-danger);
        border: 1px solid #fecdd3;
    }

    .date-value {
        color: var(--results-muted);
        white-space: nowrap;
    }

    .empty-assessments {
        background: white;
        border: 1px solid var(--results-border);
        border-radius: 17px;
        padding: 45px 25px;
        text-align: center;
        color: var(--results-muted);
        box-shadow: 0 7px 22px rgba(30, 55, 90, .05);
    }

    .empty-icon {
        font-size: 38px;
        margin-bottom: 10px;
        opacity: .7;
    }

    @media (max-width: 768px) {
        .results-page {
            padding: 20px 0 30px;
        }

        .results-container {
            padding: 0 12px;
        }

        .results-header {
            padding: 22px;
            border-radius: 14px;
        }

        .results-header-content {
            align-items: flex-start;
        }

        .results-icon {
            width: 48px;
            height: 48px;
            font-size: 22px;
        }

        .results-header h1 {
            font-size: 23px;
        }

        .assessment-top {
            align-items: flex-start;
            flex-direction: column;
        }

        .history-header {
            padding: 17px;
        }
    }
</style>

<div class="results-page">
    <div class="results-container">

        {{-- Encabezado --}}
        <div class="results-header">
            <div class="results-header-content">
                <div class="results-icon">🩺</div>

                <div>
                    <h1>Resultados y valoración de salud</h1>
                    <p>
                        Consulta la clasificación, valoración institucional
                        y nivel de prioridad de los expedientes.
                    </p>
                </div>
            </div>
        </div>

        {{-- Información --}}
        <div class="information-card">
            <div class="information-title">
                <span>ⓘ</span>
                <span>Criterios de clasificación</span>
            </div>

            <p>
                La clasificación se obtiene del expediente:
                <strong>salud física</strong> (1, 3, 4, 5),
                <strong>socioemocional</strong> (2, 9, 10 y apoyo psicológico en 11)
                y <strong>psicopedagógica</strong> (6, 7 y apoyo de aprendizaje en 11).
                El administrador o coordinador autorizado puede determinar la
                clasificación y la prioridad.
            </p>
        </div>

        {{-- Valoraciones --}}
        <div class="section-heading">
            <h2>Valoración actual</h2>
            <span>Expedientes con clasificación disponible</span>
        </div>

        @forelse($assessments as $row)

            @php
                $categorias = json_decode(
                    $row->categoria_manual ?: $row->categoria_atencion,
                    true
                ) ?: [];
            @endphp

            <article class="assessment-card">

                <div class="assessment-top">
                    <div class="student-info">
                        <h3>{{ $row->nombre }} {{ $row->apellidos }}</h3>
                        <span class="student-label">
                            Resultado de valoración del expediente
                        </span>
                    </div>

                    @if($row->atencion_prioritaria)
                        <span class="priority-badge priority">
                            ⚠ Atención prioritaria
                        </span>
                    @else
                        <span class="priority-badge normal">
                            ✓ Sin prioridad marcada
                        </span>
                    @endif
                </div>

                <div class="assessment-body">

                    <div class="classification-box">
                        <span class="classification-label">
                            Clasificación registrada
                        </span>

                        <div class="classification-value">
                            {{ implode(', ', $categorias) }}
                        </div>
                    </div>

                    @if($row->categoria_manual)
                        <div class="institutional-note">
                            <span>⚠</span>

                            <div>
                                <strong>Valoración institucional</strong><br>
                                Esta valoración fue realizada por
                                <strong>{{ $row->valorado_por }}</strong>
                                y prevalece sobre la clasificación automática.
                            </div>
                        </div>
                    @endif

                    @if(
                        auth()->user()->rol_id !== 3 &&
                        \App\Services\SectionAccess::can('clasificacion', true)
                    )
                        <a
                            href="{{ route('expediente.ver', $row->id) }}"
                            class="assessment-action"
                        >
                            ✎ Valorar o modificar clasificación y riesgo
                        </a>
                    @endif

                </div>

            </article>

        @empty

            <div class="empty-assessments">
                <div class="empty-icon">🩺</div>

                <strong>No hay expedientes con clasificación disponible.</strong>
                <br>
                <small>
                    Los expedientes que tengan una valoración registrada
                    aparecerán en esta sección.
                </small>
            </div>

        @endforelse

        {{-- Historial --}}
        @if($results->isNotEmpty())

            <div class="history-section">
                <div class="history-card">

                    <div class="history-header">
                        <h2>Puntajes históricos conservados</h2>
                        <span class="history-badge">Historial</span>
                    </div>

                    <div class="history-table-wrapper">
                        <table class="history-table">

                            <thead>
                                <tr>
                                    <th>Alumno</th>
                                    <th>Nivel registrado</th>
                                    <th>Estado</th>
                                    <th>Fecha</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach($results as $row)
                                    <tr>
                                        <td>
                                            <strong>
                                                {{ $row->nombre }}
                                                {{ $row->apellidos }}
                                            </strong>
                                        </td>

                                        <td>
                                            <span class="level-value">
                                                {{ $row->nivel_riesgo }}
                                            </span>
                                        </td>

                                        <td>
                                            @if($row->nivel_riesgo <= 3)
                                                <span class="risk-status risk-stable">
                                                    ✓ Estable
                                                </span>
                                            @elseif($row->nivel_riesgo <= 6)
                                                <span class="risk-status risk-followup">
                                                    ◷ Seguimiento
                                                </span>
                                            @else
                                                <span class="risk-status risk-urgent">
                                                    ⚠ Urgente
                                                </span>
                                            @endif
                                        </td>

                                        <td>
                                            <span class="date-value">
                                                {{ $row->fecha }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>

                        </table>
                    </div>

                </div>
            </div>

        @endif

    </div>
</div>

@endsection