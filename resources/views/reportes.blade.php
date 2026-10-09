@extends('layout')

@section('title', 'Exportación de reportes')

@section('content')

<style>
    :root {
        --report-blue: #174a88;
        --report-blue-dark: #10345f;
        --report-blue-light: #eaf2ff;
        --report-border: #dbe5f1;
        --report-text: #26384e;
        --report-muted: #718096;
    }

    .reports-page {
        color: var(--report-text);
    }

    .reports-header {
        background: linear-gradient(135deg, var(--report-blue-dark), var(--report-blue));
        border-radius: 18px;
        padding: 28px 30px;
        color: #fff;
        margin-bottom: 22px;
        box-shadow: 0 10px 25px rgba(16, 52, 95, .15);
    }

    .reports-header h1 {
        margin: 0 0 7px;
        font-size: 28px;
        font-weight: 700;
    }

    .reports-header p {
        margin: 0;
        opacity: .9;
        font-size: 14px;
        line-height: 1.6;
    }

    .info-box {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        background: var(--report-blue-light);
        border: 1px solid #cbdcf5;
        border-radius: 12px;
        padding: 15px 17px;
        margin-bottom: 22px;
        color: #35516f;
        font-size: 13px;
        line-height: 1.6;
    }

    .info-icon {
        width: 28px;
        height: 28px;
        min-width: 28px;
        border-radius: 50%;
        background: #fff;
        color: var(--report-blue);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
    }

    .info-box strong {
        color: var(--report-blue-dark);
    }

    .report-card {
        background: #fff;
        border: 1px solid var(--report-border);
        border-radius: 17px;
        overflow: hidden;
        box-shadow: 0 7px 22px rgba(31, 55, 86, .07);
    }

    .report-card-header {
        background: #f8fbff;
        border-bottom: 1px solid var(--report-border);
        padding: 18px 22px;
        display: flex;
        align-items: center;
        gap: 13px;
    }

    .report-icon {
        width: 42px;
        height: 42px;
        border-radius: 11px;
        background: var(--report-blue-light);
        color: var(--report-blue);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        font-weight: 700;
    }

    .report-card-header h2 {
        margin: 0;
        color: var(--report-blue-dark);
        font-size: 18px;
        font-weight: 700;
    }

    .report-card-header span {
        display: block;
        margin-top: 3px;
        color: var(--report-muted);
        font-size: 12px;
    }

    .report-card-body {
        padding: 24px 22px;
    }

    .form-section {
        margin-bottom: 25px;
    }

    .section-heading {
        display: flex;
        align-items: center;
        gap: 9px;
        color: var(--report-blue-dark);
        font-size: 15px;
        font-weight: 700;
        margin-bottom: 16px;
        padding-bottom: 9px;
        border-bottom: 1px solid #edf2f7;
    }

    .section-number {
        width: 25px;
        height: 25px;
        border-radius: 7px;
        background: var(--report-blue-light);
        color: var(--report-blue);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        font-weight: 700;
    }

    .report-label {
        display: block;
        color: #425466;
        font-size: 13px;
        font-weight: 600;
        margin-bottom: 7px;
    }

    .reports-page .form-control,
    .reports-page .form-select {
        border: 1px solid var(--report-border);
        border-radius: 9px;
        padding: 10px 12px;
        color: var(--report-text);
        transition: border-color .2s ease, box-shadow .2s ease;
    }

    .reports-page .form-control:focus,
    .reports-page .form-select:focus {
        border-color: var(--report-blue);
        box-shadow: 0 0 0 3px rgba(23, 74, 136, .12);
    }

    .reports-page textarea.form-control {
        min-height: 110px;
        resize: vertical;
    }

    .field-help {
        display: block;
        margin-top: 5px;
        color: var(--report-muted);
        font-size: 11px;
    }

    .actions {
        border-top: 1px solid var(--report-border);
        margin-top: 25px;
        padding-top: 20px;
        display: flex;
        justify-content: flex-end;
    }

    .btn-report {
        background: linear-gradient(135deg, var(--report-blue), #2468b5);
        color: #fff;
        border: none;
        border-radius: 9px;
        padding: 11px 21px;
        font-weight: 600;
        box-shadow: 0 4px 11px rgba(23, 74, 136, .18);
        transition: .2s ease;
    }

    .btn-report:hover {
        background: linear-gradient(135deg, var(--report-blue-dark), var(--report-blue));
        color: #fff;
        transform: translateY(-1px);
    }

    @media (max-width: 768px) {
        .reports-header {
            padding: 22px;
        }

        .reports-header h1 {
            font-size: 23px;
        }

        .report-card-body {
            padding: 19px;
        }

        .actions {
            justify-content: stretch;
        }

        .btn-report {
            width: 100%;
        }
    }
</style>

<div class="reports-page">


{{-- Encabezado --}}
<div class="reports-header">
    <h1>Exportación de reportes</h1>

    <p>
        Genera reportes institucionales de alumnos en diferentes formatos.
    </p>
</div>

{{-- Aviso --}}
<div class="info-box">
    <div class="info-icon">i</div>

    <div>
        <strong>Importante:</strong>
        las exportaciones pueden contener información institucional.
        Cada reporte generado queda registrado para fines de auditoría
        y control de acceso.
    </div>
</div>

{{-- Formulario --}}
<div class="report-card">

    <div class="report-card-header">
        <div class="report-icon">
            ⇩
        </div>

        <div>
            <h2>Generar nuevo reporte</h2>
            <span>
                Selecciona los criterios de información que deseas exportar.
            </span>
        </div>
    </div>

    <div class="report-card-body">

        <form
            method="post"
            action="{{ route('reportes.exportar') }}"
        >
            @csrf

            {{-- Tipo de reporte --}}
            <div class="form-section">

                <div class="section-heading">
                    <span class="section-number">1</span>
                    Tipo de reporte
                </div>

                <label
                    class="report-label"
                    for="tipo"
                >
                    Selecciona la información
                </label>

                <select
                    id="tipo"
                    class="form-select"
                    name="tipo"
                >
                    <option value="alumnos">
                        Alumnos
                    </option>

                </select>

                <small class="field-help">
                    El reporte incluye los datos generales de los alumnos.
                </small>

            </div>

            {{-- Filtros --}}
            <div class="form-section">

                <div class="section-heading">
                    <span class="section-number">2</span>
                    Filtros del reporte
                </div>

                <div class="row g-3">

                    <div class="col-md-6">

                        <label
                            class="report-label"
                            for="q"
                        >
                            Nombre del alumno
                        </label>

                        <input
                            id="q"
                            class="form-control"
                            name="q"
                            maxlength="255"
                            placeholder="Buscar por nombre o apellidos"
                        >

                    </div>

                    <div class="col-md-6">

                        <label
                            class="report-label"
                            for="licenciatura_id"
                        >
                            Licenciatura
                        </label>

                        <select
                            id="licenciatura_id"
                            class="form-select"
                            name="licenciatura_id"
                        >
                            <option value="">
                                Todas las licenciaturas
                            </option>

                            @foreach($licenciaturas as $l)
                                <option value="{{ $l->id }}">
                                    {{ $l->nombre }}
                                </option>
                            @endforeach
                        </select>

                    </div>

                </div>

            </div>

            {{-- Motivo y formato --}}
            <div class="form-section">

                <div class="section-heading">
                    <span class="section-number">3</span>
                    Motivo y formato
                </div>

                <div class="row g-3">

                    <div class="col-12">

                        <label
                            class="report-label"
                            for="motivo"
                        >
                            Motivo del reporte
                        </label>

                        <textarea
                            id="motivo"
                            class="form-control"
                            name="motivo"
                            required
                            maxlength="2000"
                            placeholder="Indica el motivo por el cual necesitas generar este reporte..."
                        ></textarea>

                        <small class="field-help">
                            Este motivo será almacenado como parte del registro de auditoría.
                        </small>

                    </div>

                    <div class="col-md-6">

                        <label
                            class="report-label"
                            for="formato"
                        >
                            Formato de exportación
                        </label>

                        <select
                            id="formato"
                            class="form-select"
                            name="formato"
                        >
                            <option value="xlsx">
                                Excel (.xlsx)
                            </option>

                            <option value="pdf">
                                PDF
                            </option>
                        </select>

                    </div>

                </div>

            </div>

            {{-- Botón --}}
            <div class="actions">

                <button
                    class="btn btn-report"
                    type="submit"
                >
                    Generar y registrar auditoría
                </button>

            </div>

        </form>

    </div>
</div>


</div>

@endsection
