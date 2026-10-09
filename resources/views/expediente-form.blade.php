@extends('layout')
@section('title', 'Completar mi expediente · Escuela Modelo')
@section('content')
<link rel="stylesheet" href="{{ asset('css/dossier-wizard.css') }}">
<div class="dossier-wizard">
    <header class="wizard-intro">
        <span class="wizard-eyebrow">EXPEDIENTE ESTUDIANTIL</span>
        <h1>{{ $record ? 'Actualiza tu información' : 'Vamos a completar tu expediente' }}</h1>
        <p>Responde con calma. Primero revisa tus datos, después cuéntanos sobre tu salud y bienestar.</p>
        <span class="wizard-privacy"><x-icon name="folder"/>Tu información está protegida según la política de privacidad.</span>
    </header>
    <form method="post" id="expediente-form" class="wizard-form">
        @csrf
        <input type="hidden" name="version" value="{{ \App\Http\Controllers\DossierController::fingerprint($record) }}">
        <nav class="wizard-steps" aria-label="Pasos del expediente" hidden>
            @foreach($sections as $section => $fields)
            <button type="button" data-go-step="{{ $loop->index }}"><span>{{ $loop->iteration }}</span>{{ $section === 'personales' ? 'Tus datos' : 'Salud y bienestar' }}</button>
            @endforeach
            <button type="button" data-go-step="{{ count($sections) }}"><span>{{ count($sections) + 1 }}</span>Revisar y guardar</button>
        </nav>
        <div class="wizard-status"><span id="wizard-step-label" role="status">Completa los campos obligatorios</span><span id="wizard-save-status">Los cambios se guardan al finalizar</span></div>
        @foreach($sections as $section => $fields)
        <fieldset class="wizard-panel" data-step-title="{{ $section === 'personales' ? 'Tus datos' : 'Salud y bienestar' }}">
            <legend>{{ config('dossier.section_labels.'.$section) }}</legend>
            <p class="wizard-panel-help">{{ $section === 'personales' ? 'Tu nombre ya está precargado. Revisa tus datos y agrega un contacto de emergencia.' : 'Elige una respuesta en cada pregunta. Si eliges Sí, te pediremos un breve detalle.' }} <strong>* Obligatorio</strong></p>
            <div class="{{ $section === 'personales' ? 'wizard-fields' : 'wizard-questions' }}">
            @foreach($fields as $field)
                @if(str_ends_with($field, '_detalle') || $field === 'q11_necesita_apoyo_otro') @continue @endif
                @if($field === 'contacto_emergencia_nombre')
                <div class="wizard-emergency wide-field" role="group" aria-labelledby="emergency-contact-title">
                    <h2 id="emergency-contact-title">DATOS DE CONTACTO DE EMERGENCIA</h2>
                    <div class="wizard-fields">
                @endif
                @php
                    $value = old('datos.'.$field, $record?->$field ?? ($defaults[$field] ?? null));
                    $binary = preg_match('/^q[1-7]_.*(?<!detalle)$/', $field) || $field === 'q9_acomp_psicologico';
                    $required = !in_array($field, ['q12_info_adicional']);
                    $label = $section === 'cuestionario' ? config('dossier_questions.'.$field) : config('dossier.labels.'.$field);
                @endphp
                <div class="wizard-field {{ $section === 'cuestionario' ? 'question-card' : '' }} {{ $field === 'domicilio' ? 'wide-field' : '' }}" data-review-field="{{ $field }}" data-review-label="{{ $label }}">
                    @if($section === 'cuestionario')<span class="question-number">Pregunta {{ (int) preg_replace('/^q(\d+).*/', '$1', $field) }}</span>@endif
                    @if($binary || in_array($field, ['q10_estado_emocional', 'q11_necesita_apoyo']))
                        <h2 id="label-{{ $field }}" class="question-label">{{ $label }} <span aria-label="obligatorio">*</span></h2>
                    @else
                        <label class="question-label" for="{{ $field }}">{{ $label }} @if($required)<span aria-label="obligatorio">*</span>@else<small>Opcional</small>@endif</label>
                    @endif
                    @if($binary)
                        <div class="answer-choices" role="group" aria-labelledby="label-{{ $field }}">
                        @foreach(['1' => 'Sí', '0' => 'No'] as $answer => $text)
                            <label class="answer-choice"><input type="radio" name="datos[{{ $field }}]" id="{{ $field }}-{{ $answer }}" value="{{ $answer }}" @checked($value !== null && (string)$value === (string)$answer) required><span>{{ $text }}</span></label>
                        @endforeach
                        </div>
                        @if($field !== 'q9_acomp_psicologico')
                            @php($detail = $field.'_detalle')
                            <div class="question-detail" data-detail-for="{{ $field }}">
                                <label for="{{ $detail }}">{{ config('dossier.labels.'.$detail) }} <span>*</span></label>
                                <textarea class="form-control" id="{{ $detail }}" name="datos[{{ $detail }}]" rows="3" maxlength="10000" placeholder="Escribe un breve detalle…">{{ old('datos.'.$detail, $record?->$detail) }}</textarea>
                                @error($detail)<p class="field-error">{{ $message }}</p>@enderror
                            </div>
                        @endif
                    @elseif($field === 'q10_estado_emocional')
                        <p class="field-help">Elige la opción que mejor describa cómo te sientes.</p>
                        <div class="answer-choices emotional-choices" role="group" aria-labelledby="label-{{ $field }}">
                        @foreach(['Muy desfavorable', 'Desfavorable', 'Favorable', 'Muy favorable'] as $choice)
                            <label class="answer-choice"><input type="radio" name="datos[{{ $field }}]" value="{{ $choice }}" @checked($value === $choice) required><span>{{ $choice }}</span></label>
                        @endforeach
                        </div>
                    @elseif($field === 'q11_necesita_apoyo')
                        @php($supports = is_array($value) ? $value : (json_decode($value ?: '[]', true) ?: []))
                        <p class="field-help">Puedes elegir más de uno. Si no necesitas apoyo, elige Ninguno.</p>
                        <div class="answer-choices support-choices" role="group" aria-labelledby="label-{{ $field }}">
                        @foreach(['Psicológico', 'De aprendizaje', 'Otro', 'Ninguno'] as $choice)
                            <label class="answer-choice"><input type="checkbox" name="datos[{{ $field }}][]" value="{{ $choice }}" @checked(in_array($choice, $supports, true))><span>{{ $choice }}</span></label>
                        @endforeach
                        </div>
                        <div class="question-detail" data-support-detail>
                            <label for="q11_necesita_apoyo_otro">¿Qué otro apoyo necesitas? <span>*</span></label>
                            <textarea id="q11_necesita_apoyo_otro" class="form-control" name="datos[q11_necesita_apoyo_otro]" rows="3">{{ old('datos.q11_necesita_apoyo_otro', $record?->q11_necesita_apoyo_otro) }}</textarea>
                            @error('q11_necesita_apoyo_otro')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                    @elseif(in_array($field, ['nombres', 'apellidos', 'contacto_emergencia_nombre']))
                        <input class="form-control" id="{{ $field }}" name="datos[{{ $field }}]" value="{{ $value }}" maxlength="255" required autocomplete="{{ ['nombres'=>'given-name','apellidos'=>'family-name'][$field] ?? 'off' }}">
                    @elseif(in_array($field, ['telefono', 'contacto_emergencia_telefono']))
                        <input class="form-control" id="{{ $field }}" name="datos[{{ $field }}]" type="tel" inputmode="numeric" value="{{ $value }}" pattern="[0-9]{10}" minlength="10" maxlength="10" title="Escribe exactamente 10 dígitos, sin espacios ni guiones" required>
                        <p class="field-help">10 dígitos, sin espacios ni guiones.</p>
                    @elseif($field === 'fecha_nacimiento')
                        <input class="form-control" id="{{ $field }}" name="datos[{{ $field }}]" type="date" value="{{ $value }}" max="{{ now()->toDateString() }}" required>
                        <label class="field-help" for="edad-calculada">Edad calculada</label><input class="form-control age-output" id="edad-calculada" readonly placeholder="Años cumplidos">
                    @elseif($field === 'licenciatura_id')
                        <select class="form-select" id="{{ $field }}" name="datos[{{ $field }}]" required><option value="">Selecciona tu licenciatura</option>@foreach($licenciaturas as $degree)<option value="{{ $degree->id }}" @selected((string)$value === (string)$degree->id)>{{ $degree->nombre }}</option>@endforeach</select>
                    @elseif(in_array($field, ['estado_civil', 'apnp_tipo_sangre', 'apnp_factor_rh']))
                        @php($options = ['estado_civil'=>['Soltero(a)','Casado(a)','Unión Libre','Otro'],'apnp_tipo_sangre'=>['O','A','B','AB'],'apnp_factor_rh'=>['Positivo (+)','Negativo (-)']][$field])
                        <select class="form-select" id="{{ $field }}" name="datos[{{ $field }}]" required><option value="">Selecciona una opción</option>@foreach($options as $choice)<option value="{{ $choice }}" @selected($value === $choice)>{{ $choice }}</option>@endforeach</select>
                    @else
                        @if($field === 'q8_red_apoyo')<p class="field-help">Puede ser tu familia, amistades, pareja o comunidad. Si no cuentas con una red, puedes indicarlo.</p>@endif
                        <textarea class="form-control" id="{{ $field }}" name="datos[{{ $field }}]" rows="{{ $section === 'cuestionario' ? 3 : 2 }}" maxlength="10000" @required($required)>{{ $value }}</textarea>
                    @endif
                    @error($field)<p class="field-error" role="alert">{{ $message }}</p>@enderror
                </div>
                @if($field === 'contacto_emergencia_telefono')
                    </div>
                </div>
                @endif
            @endforeach
            </div>
        </fieldset>
        @endforeach
        <section class="wizard-panel wizard-review" data-step-title="Revisar y guardar" hidden>
            <h2>Una última revisión</h2><p>Revisa tus respuestas. Puedes volver a cualquier paso y corregirlas antes de guardar.</p>
            <div id="wizard-review-content"></div>
        </section>
        @if(auth()->user()->rol_id !== 3)
        <div class="wizard-reason"><label for="motivo">Motivo de la actualización *</label><textarea class="form-control" id="motivo" name="motivo" rows="2" required maxlength="2000">{{ old('motivo') }}</textarea></div>
        @endif
        <footer class="wizard-footer">
            <p id="wizard-footer-note">Tu expediente se guarda al finalizar.</p>
            <div><button id="wizard-back" class="btn btn-outline-primary" type="button" hidden>Anterior</button><button id="wizard-next" class="btn btn-primary" type="button" hidden>Continuar</button><button id="guardar-expediente" class="btn btn-primary" type="submit">Guardar expediente</button></div>
        </footer>
    </form>
</div>
<script src="{{ asset('js/dossier-age.js') }}"></script>
<script src="{{ asset('js/dossier-wizard.js') }}"></script>
@endsection
