@extends('layout')

@section('content')

<h1>{{ $record?'Editar':'Llenar' }} expediente</h1>
<form method="post" class="card card-body">@csrf<input type="hidden" name="version" value="{{ \App\Http\Controllers\DossierController::fingerprint($record) }}">
@foreach($sections as $section=>$fields)<fieldset class="mb-4"><legend>{{ config('dossier.section_labels.'.$section,ucfirst($section)) }}</legend>
@foreach($fields as $field)
@php($value=old('datos.'.$field,$record?->$field))
<div class="mb-3"><label for="{{ $field }}" class="form-label">{{ config('dossier.labels.'.$field,$field) }}</label>
@if(preg_match('/^q[1-7]_.*(?<!detalle)$/',$field)||$field==='q9_acomp_psicologico')
<select class="form-select" name="datos[{{ $field }}]" id="{{ $field }}" required><option value="0" @selected($value==0)>No</option><option value="1" @selected($value==1)>Sí</option></select>
@elseif($field==='q11_necesita_apoyo')
@php($supports=is_array($value)?$value:(json_decode($value?:'[]',true)?:[]))
@foreach(['Psicológico','De aprendizaje','Otro','Ninguno'] as $choice)<label class="choice-label"><input type="checkbox" name="datos[{{ $field }}][]" value="{{ $choice }}" @checked(in_array($choice,$supports,true))> {{ $choice }}</label>@endforeach
@elseif($field==='licenciatura_id')<select class="form-select" name="datos[{{ $field }}]" id="{{ $field }}" required>@foreach($licenciaturas as $l)<option value="{{ $l->id }}" @selected($value==$l->id)>{{ $l->nombre }}</option>@endforeach</select>
@elseif(in_array($field,['genero','estado_civil','apnp_tipo_sangre','apnp_factor_rh','q10_estado_emocional']))
@php($options=['genero'=>['Masculino','Femenino','Otro'],'estado_civil'=>['Soltero(a)','Casado(a)','Unión Libre','Otro'],'apnp_tipo_sangre'=>['O','A','B','AB'],'apnp_factor_rh'=>['Positivo (+)','Negativo (-)'],'q10_estado_emocional'=>['Muy desfavorable','Desfavorable','Favorable','Muy favorable']][$field])
<select class="form-select" name="datos[{{ $field }}]" id="{{ $field }}" required><option value="">Seleccionar</option>@foreach($options as $option)<option @selected($value===$option)>{{ $option }}</option>@endforeach</select>
@elseif($field==='fecha_nacimiento')<input class="form-control" name="datos[{{ $field }}]" id="{{ $field }}" type="date" value="{{ $value }}" required max="{{ date('Y-m-d') }}">
@else<textarea class="form-control" name="datos[{{ $field }}]" id="{{ $field }}">{{ $value }}</textarea>@endif


<style>
    .expediente-page {
        --azul: #174a8b;
        --azul-oscuro: #103665;
        --azul-claro: #eaf2fc;
        --borde: #d9e4f1;
        --texto: #24364b;
        --muted: #64748b;
        color: var(--texto);
        padding: 24px 0 40px;
    }

    .expediente-page .exp-header {
        position: relative;
        overflow: hidden;
        background: linear-gradient(120deg, #103665, #2164ac);
        color: #fff;
        padding: 30px;
        border-radius: 18px;
        margin-bottom: 25px;
        box-shadow: 0 8px 24px rgba(23, 74, 139, .14);
    }

    .exp-header h1 {
        font-size: clamp(1.5rem, 3vw, 2rem);
        font-weight: 750;
        margin: 0 0 8px;
        letter-spacing: -.5px;
    }

    .exp-header p {
        margin: 0;
        color: #e0ecfb;
        font-size: .95rem;
    }

    .exp-header .header-icon {
        width: 58px;
        height: 58px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 15px;
        background: rgba(255,255,255,.14);
        border: 1px solid rgba(255,255,255,.25);
        font-size: 28px;
        flex-shrink: 0;
    }

    .exp-badge {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        background: rgba(255,255,255,.13);
        border: 1px solid rgba(255,255,255,.23);
        padding: 8px 12px;
        border-radius: 30px;
        font-size: .8rem;
        margin-top: 18px;
    }

    .exp-card {
        background: #fff;
        border: 1px solid var(--borde);
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 5px 20px rgba(30, 64, 110, .05);
    }

    .exp-card-heading {
        padding: 20px 25px;
        border-bottom: 1px solid var(--borde);
        background: #fbfdff;
    }

    .exp-card-heading h2 {
        color: var(--azul-oscuro);
        font-size: 1.1rem;
        font-weight: 700;
        margin: 0 0 5px;
    }

    .exp-card-heading p {
        color: var(--muted);
        font-size: .87rem;
        margin: 0;
    }

    .exp-card-body {
        padding: 25px;
    }

    .expediente-page fieldset {
        border: 0;
        padding: 0;
        margin: 0;
        min-width: 0;
    }

    .expediente-page fieldset legend {
        width: 100%;
        float: none;
        color: var(--azul);
        font-size: 1.05rem;
        font-weight: 750;
        padding: 0 0 13px;
        margin-bottom: 23px;
        border-bottom: 2px solid var(--azul-claro);
    }

    .expediente-page .form-label {
        display: block;
        font-size: .9rem;
        font-weight: 650;
        color: #334963;
        margin-bottom: 8px;
    }

    .expediente-page .form-control,
    .expediente-page .form-select {
        width: 100%;
        min-height: 45px;
        border: 1px solid #cedbea;
        border-radius: 9px;
        padding: 10px 13px;
        color: #24364b;
        background-color: #fff;
        transition: border-color .2s, box-shadow .2s;
    }

    .expediente-page textarea.form-control {
        min-height: 100px;
        resize: vertical;
    }

    .expediente-page .form-control:focus,
    .expediente-page .form-select:focus {
        border-color: #377ac5;
        box-shadow: 0 0 0 3px rgba(55, 122, 197, .13);
        outline: none;
    }

    .expediente-page .form-check-custom {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 14px;
        margin: 8px 0;
        border: 1px solid var(--borde);
        border-radius: 9px;
        background: #fff;
        cursor: pointer;
        transition: background .2s, border-color .2s;
    }

    .expediente-page .form-check-custom:hover {
        background: #f3f7fd;
        border-color: #8bb2df;
    }

    .expediente-page .form-check-custom input {
        width: 17px;
        height: 17px;
        accent-color: var(--azul);
    }

    .expediente-page .btn-primary {
        background: var(--azul);
        border-color: var(--azul);
    }

    .expediente-page .btn-primary:hover {
        background: var(--azul-oscuro);
        border-color: var(--azul-oscuro);
    }

    .expediente-page .btn {
        min-height: 42px;
        border-radius: 9px;
        font-weight: 650;
        padding: 10px 18px;
    }

    .expediente-page .btn-outline-primary {
        color: var(--azul);
        border-color: #b7cce5;
        background: #fff;
    }

    .expediente-page .btn-outline-primary:hover {
        color: #fff;
        background: var(--azul);
        border-color: var(--azul);
    }

    .exp-progress {
        display: flex;
        gap: 8px;
        margin: 18px 0 0;
    }

    .exp-progress span {
        height: 5px;
        flex: 1;
        background: #dce6f2;
        border-radius: 10px;
        transition: background .2s;
    }

    .exp-progress span.active {
        background: #fff;
    }

    .exp-progress span.completed {
        background: #8fc0ff;
    }

    .exp-step-label {
        color: var(--muted);
        font-size: .84rem;
        margin-top: 10px;
    }

    .exp-footer {
        padding: 20px 25px;
        background: #fbfdff;
        border-top: 1px solid var(--borde);
    }

    .exp-required-note {
        color: var(--muted);
        font-size: .82rem;
    }

    .expediente-page [hidden] {
        display: none !important;
    }

    @media (max-width: 576px) {
        .expediente-page {
            padding-top: 12px;
        }

        .expediente-page .exp-header {
            padding: 22px 18px;
            border-radius: 13px;
        }

        .exp-card-body {
            padding: 20px 17px;
        }

        .exp-card-heading,
        .exp-footer {
            padding: 18px 17px;
        }
    }
</style>

<div class="container expediente-page">

    <div class="exp-header">
        <div class="d-flex align-items-start gap-3">
            <div class="header-icon" aria-hidden="true">🎓</div>

            <div class="flex-grow-1">
                <h1>{{ $record ? 'Editar expediente' : 'Expediente estudiantil' }}</h1>

                <p>
                    {{ $record
                        ? 'Actualiza la información del expediente del estudiante.'
                        : 'Completa la información solicitada para registrar el expediente.' }}
                </p>

                <div class="exp-badge">
                    <span>●</span>
                    Sistema integral de tutorías
                </div>
            </div>
        </div>

        <div class="exp-progress" id="exp-progress" aria-hidden="true">
            @foreach($sections as $section => $fields)
                <span></span>
            @endforeach
        </div>

        <div class="exp-step-label" id="exp-step-label" aria-live="polite">
            Información del expediente
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger mb-4" role="alert">
            <strong>Revisa la información ingresada.</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="post" class="exp-card" id="expediente-form">
        @csrf

        <input
            type="hidden"
            name="version"
            value="{{ \App\Http\Controllers\DossierController::fingerprint($record) }}"
        >

        <div class="exp-card-heading">
            <h2 id="exp-section-title">Información general</h2>
            <p>Ingresa los datos con atención. Los cambios deben guardarse al finalizar.</p>
        </div>

        <div class="exp-card-body">

            @foreach($sections as $section => $fields)
                <fieldset class="exp-section mb-4"
                          data-section="{{ ucfirst(str_replace('_', ' ', $section)) }}">

                    <legend>{{ ucfirst(str_replace('_', ' ', $section)) }}</legend>

                    @foreach($fields as $field)
                        @php($value = old('datos.'.$field, $record?->$field))

                        <div class="mb-4">
                            <label for="{{ $field }}" class="form-label">
                                {{ config('dossier.labels.'.$field, ucfirst(str_replace('_', ' ', $field))) }}
                            </label>

                            @if(preg_match('/^q[1-7]_.*(?<!detalle)$/', $field) || $field === 'q9_acomp_psicologico')

                                <select class="form-select"
                                        name="datos[{{ $field }}]"
                                        id="{{ $field }}"
                                        required>
                                    <option value="0" @selected((string)$value === '0')>No</option>
                                    <option value="1" @selected((string)$value === '1')>Sí</option>
                                </select>

                            @elseif($field === 'q11_necesita_apoyo')

                                @php($supports = is_array($value) ? $value : (json_decode($value ?: '[]', true) ?: []))

                                @foreach(['Psicológico', 'De aprendizaje', 'Otro', 'Ninguno'] as $choice)
                                    <label class="form-check-custom">
                                        <input
                                            type="checkbox"
                                            name="datos[{{ $field }}][]"
                                            value="{{ $choice }}"
                                            @checked(in_array($choice, $supports, true))
                                        >
                                        <span>{{ $choice }}</span>
                                    </label>
                                @endforeach

                            @elseif($field === 'licenciatura_id')

                                <select class="form-select"
                                        name="datos[{{ $field }}]"
                                        id="{{ $field }}"
                                        required>
                                    <option value="">Selecciona una licenciatura</option>

                                    @foreach($licenciaturas as $l)
                                        <option value="{{ $l->id }}"
                                                @selected((string)$value === (string)$l->id)>
                                            {{ $l->nombre }}
                                        </option>
                                    @endforeach
                                </select>

                            @elseif(in_array($field, [
                                'genero',
                                'estado_civil',
                                'apnp_tipo_sangre',
                                'apnp_factor_rh',
                                'q10_estado_emocional'
                            ]))

                                @php($options = [
                                    'genero' => ['Masculino', 'Femenino', 'Otro'],
                                    'estado_civil' => ['Soltero(a)', 'Casado(a)', 'Unión Libre', 'Otro'],
                                    'apnp_tipo_sangre' => ['O', 'A', 'B', 'AB'],
                                    'apnp_factor_rh' => ['Positivo (+)', 'Negativo (-)'],
                                    'q10_estado_emocional' => ['Muy desfavorable', 'Desfavorable', 'Favorable', 'Muy favorable']
                                ][$field])

                                <select class="form-select"
                                        name="datos[{{ $field }}]"
                                        id="{{ $field }}"
                                        required>
                                    <option value="">Selecciona una opción</option>

                                    @foreach($options as $option)
                                        <option value="{{ $option }}"
                                                @selected((string)$value === (string)$option)>
                                            {{ $option }}
                                        </option>
                                    @endforeach
                                </select>

                            @elseif($field === 'fecha_nacimiento')

                                <input
                                    class="form-control"
                                    name="datos[{{ $field }}]"
                                    id="{{ $field }}"
                                    type="date"
                                    value="{{ $value }}"
                                    required
                                    max="{{ date('Y-m-d') }}"
                                >

                            @else

                                <textarea
                                    class="form-control"
                                    name="datos[{{ $field }}]"
                                    id="{{ $field }}"
                                >{{ $value }}</textarea>

                            @endif
                        </div>
                    @endforeach
                </fieldset>
            @endforeach

            @if(auth()->user()->rol_id !== 3)
                <div class="mb-4">
                    <label for="motivo" class="form-label">
                        Motivo de la actualización
                    </label>

                    <textarea
                        name="motivo"
                        id="motivo"
                        class="form-control"
                        required
                        maxlength="2000"
                        placeholder="Describe brevemente el motivo de la actualización..."
                    >{{ old('motivo') }}</textarea>
                </div>
            @endif

        </div>

        <div class="exp-footer">

            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">

                <div class="exp-required-note">
                    Verifica los datos antes de guardar.
                </div>

                <div class="d-flex flex-wrap gap-2"
                     id="pasos-expediente"
                     hidden>

                    <button class="btn btn-outline-primary"
                            type="button"
                            id="paso-anterior">
                        ← Anterior
                    </button>

                    <button class="btn btn-primary"
                            type="button"
                            id="paso-siguiente">
                        Siguiente →
                    </button>

                    <span id="paso-actual"
                          class="visually-hidden"
                          role="status"
                          aria-live="polite"></span>
                </div>

                <button class="btn btn-primary"
                        id="guardar-expediente"
                        type="submit">
                    ✓ Guardar expediente
                </button>

            </div>
        </div>

    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('expediente-form');

    if (!form) return;

    const steps = Array.from(form.querySelectorAll('.exp-section'));
    const progress = Array.from(document.querySelectorAll('#exp-progress span'));
    const progressLabel = document.getElementById('exp-step-label');
    const sectionTitle = document.getElementById('exp-section-title');

    const navigation = document.getElementById('pasos-expediente');
    const previousButton = document.getElementById('paso-anterior');
    const nextButton = document.getElementById('paso-siguiente');
    const saveButton = document.getElementById('guardar-expediente');
    const currentLabel = document.getElementById('paso-actual');

    let activeStep = 0;

    function showStep(index) {
        if (!steps.length) return;

        activeStep = Math.max(0, Math.min(index, steps.length - 1));

        steps.forEach((step, i) => {
            step.hidden = i !== activeStep;
        });

        progress.forEach((bar, i) => {
            bar.classList.toggle('active', i === activeStep);
            bar.classList.toggle('completed', i < activeStep);
        });

        previousButton.disabled = activeStep === 0;
        nextButton.hidden = activeStep === steps.length - 1;
        saveButton.hidden = activeStep !== steps.length - 1;

        const title = steps[activeStep].dataset.section || 'Información general';

        sectionTitle.textContent = title;
        progressLabel.textContent =
            'Sección ' + (activeStep + 1) + ' de ' + steps.length;

        currentLabel.textContent =
            'Paso ' + (activeStep + 1) + ' de ' + steps.length;
    }

    function validateCurrentStep() {
        const controls = Array.from(
            steps[activeStep].querySelectorAll('input, select, textarea')
        ).filter(control =>
            !control.disabled &&
            control.type !== 'hidden' &&
            control.name !== 'motivo'
        );

        for (const control of controls) {
            if (!control.checkValidity()) {
                control.reportValidity();
                control.focus();
                return false;
            }
        }

        return true;
    }

    if (steps.length > 1) {
        navigation.hidden = false;

        previousButton.addEventListener('click', function () {
            showStep(activeStep - 1);
        });

        nextButton.addEventListener('click', function () {
            if (validateCurrentStep()) {
                showStep(activeStep + 1);
                sectionTitle.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
            }
        });

        form.addEventListener('invalid', function (event) {
            const index = steps.findIndex(step =>
                step.contains(event.target)
            );

            if (index >= 0 && index !== activeStep) {
                showStep(index);
            }
        }, true);

        form.addEventListener('submit', function (event) {
            if (!validateCurrentStep()) {
                event.preventDefault();
            }
        });

        showStep(0);
    } else if (steps.length === 1) {
        showStep(0);
    } else {
        navigation.hidden = true;
    }
});
</script>

@endsection

