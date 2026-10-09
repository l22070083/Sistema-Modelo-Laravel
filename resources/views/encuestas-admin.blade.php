@extends('layout')

@section('title', 'Encuestas de salud')

@section('content')

<style>
    :root {
        --survey-blue: #174a88;
        --survey-blue-dark: #10345f;
        --survey-blue-light: #eaf2ff;
        --survey-border: #dbe5f1;
        --survey-text: #26384e;
        --survey-muted: #718096;
        --survey-bg: #f4f7fb;
        --survey-success: #198754;
        --survey-danger: #dc3545;
    }

    .survey-page {
        color: var(--survey-text);
    }

    .survey-header {
        background: linear-gradient(135deg, var(--survey-blue-dark), var(--survey-blue));
        border-radius: 18px;
        padding: 28px 30px;
        color: white;
        margin-bottom: 25px;
        box-shadow: 0 10px 25px rgba(16, 52, 95, .15);
    }

    .survey-header h1 {
        margin: 0 0 7px;
        font-size: 28px;
        font-weight: 700;
    }

    .survey-header p {
        margin: 0;
        opacity: .9;
        font-size: 14px;
    }

    .section-title {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
        margin: 30px 0 15px;
    }

    .section-title h2 {
        margin: 0;
        color: var(--survey-blue-dark);
        font-size: 21px;
        font-weight: 700;
    }

    .section-title .badge-count {
        background: var(--survey-blue-light);
        color: var(--survey-blue);
        border: 1px solid #cbdcf5;
        border-radius: 30px;
        padding: 6px 12px;
        font-size: 12px;
        font-weight: 600;
    }

    .survey-card {
        background: #fff;
        border: 1px solid var(--survey-border);
        border-radius: 16px;
        margin-bottom: 18px;
        overflow: hidden;
        box-shadow: 0 5px 18px rgba(31, 55, 86, .06);
        transition: .2s ease;
    }

    .survey-card:hover {
        box-shadow: 0 9px 25px rgba(31, 55, 86, .1);
    }

    .survey-card-header {
        background: #f8fbff;
        border-bottom: 1px solid var(--survey-border);
        padding: 16px 20px;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .survey-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        background: var(--survey-blue-light);
        color: var(--survey-blue);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        font-weight: 700;
        flex-shrink: 0;
    }

    .survey-card-header h3 {
        margin: 0;
        font-size: 16px;
        font-weight: 700;
        color: var(--survey-blue-dark);
    }

    .survey-card-body {
        padding: 22px 20px;
    }

    .form-group {
        margin-bottom: 17px;
    }

    .form-group:last-child {
        margin-bottom: 0;
    }

    .form-label-custom {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: #425466;
        margin-bottom: 7px;
    }

    .survey-page .form-control,
    .survey-page .form-select {
        border: 1px solid var(--survey-border);
        border-radius: 9px;
        padding: 10px 12px;
        color: var(--survey-text);
        background-color: #fff;
        transition: border-color .2s ease, box-shadow .2s ease;
    }

    .survey-page textarea.form-control {
        min-height: 100px;
        resize: vertical;
    }

    .survey-page .form-control:focus,
    .survey-page .form-select:focus {
        border-color: var(--survey-blue);
        box-shadow: 0 0 0 3px rgba(23, 74, 136, .12);
    }

    .btn-survey {
        background: linear-gradient(135deg, var(--survey-blue), #2468b5);
        color: white;
        border: none;
        border-radius: 9px;
        padding: 10px 18px;
        font-weight: 600;
        box-shadow: 0 4px 10px rgba(23, 74, 136, .18);
        transition: .2s ease;
    }

    .btn-survey:hover {
        background: linear-gradient(135deg, var(--survey-blue-dark), var(--survey-blue));
        color: white;
        transform: translateY(-1px);
    }

    .status-help {
        display: block;
        margin-top: 5px;
        color: var(--survey-muted);
        font-size: 12px;
    }

    .info-box {
        background: var(--survey-blue-light);
        border: 1px solid #cbdcf5;
        border-radius: 12px;
        padding: 14px 16px;
        color: #35516f;
        font-size: 13px;
        margin-bottom: 22px;
    }

    .info-box strong {
        color: var(--survey-blue-dark);
    }

    @media (max-width: 768px) {
        .survey-header {
            padding: 22px;
        }

        .survey-header h1 {
            font-size: 23px;
        }

        .survey-card-body {
            padding: 18px;
        }

        .section-title {
            align-items: flex-start;
            flex-direction: column;
        }
    }
</style>

<div class="survey-page">


{{-- Encabezado --}}
<div class="survey-header">
    <h1>Encuestas de salud</h1>
    <p>
        Administra las encuestas institucionales y configura las preguntas
        utilizadas para la valoración de salud de los alumnos.
    </p>
</div>

{{-- Información --}}
<div class="info-box">
    <strong>Administración de encuestas:</strong>
    puedes crear nuevas encuestas, modificar las existentes y controlar
    cuáles se encuentran activas para su aplicación.
</div>

{{-- ENCUESTAS --}}
<div class="section-title">
    <h2>Encuestas</h2>
    <span class="badge-count">
        {{ $surveys->filter()->count() }} registradas
    </span>
</div>

@foreach($surveys->prepend(null) as $survey)

    <form
        id="{{ $survey ? 'encuesta-'.$survey->id : 'nueva-encuesta' }}"
        class="survey-card"
        action="{{ $survey ? route('encuesta.save', $survey->id) : route('encuesta.save') }}"
        method="post"
    >
        @csrf

        <div class="survey-card-header">
            <div class="survey-icon">
                {{ $survey ? '✎' : '+' }}
            </div>

            <h3>
                {{ $survey ? 'Editar encuesta' : 'Nueva encuesta' }}
            </h3>
        </div>

        <div class="survey-card-body">

            <div class="row g-3">

                <div class="col-md-8">
                    <div class="form-group">
                        <label class="form-label-custom" for="titulo-{{ $survey?->id ?? 'nueva' }}">
                            Título de la encuesta
                        </label>

                        <input
                            id="titulo-{{ $survey?->id ?? 'nueva' }}"
                            class="form-control"
                            name="titulo"
                            value="{{ $survey?->titulo }}"
                            required
                            maxlength="255"
                            placeholder="Ej. Encuesta de salud integral"
                        >
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label-custom" for="estado-{{ $survey?->id ?? 'nueva' }}">
                            Estado
                        </label>

                        <select
                            id="estado-{{ $survey?->id ?? 'nueva' }}"
                            class="form-select"
                            name="estado"
                        >
                            <option value="1" @selected($survey?->estado == 1)>
                                Activa
                            </option>

                            <option value="0" @selected($survey?->estado === 0)>
                                Inactiva
                            </option>
                        </select>

                        <small class="status-help">
                            Define si la encuesta puede utilizarse.
                        </small>
                    </div>
                </div>

                <div class="col-12">
                    <div class="form-group">
                        <label class="form-label-custom" for="descripcion-{{ $survey?->id ?? 'nueva' }}">
                            Descripción
                        </label>

                        <textarea
                            id="descripcion-{{ $survey?->id ?? 'nueva' }}"
                            class="form-control"
                            name="descripcion"
                            placeholder="Describe brevemente el objetivo de esta encuesta..."
                        >{{ $survey?->descripcion }}</textarea>
                    </div>
                </div>

            </div>

            <div class="mt-3">
                <button class="btn btn-survey" type="submit">
                    {{ $survey ? 'Guardar cambios' : 'Guardar encuesta' }}
                </button>
            </div>

        </div>
    </form>

@endforeach


{{-- PREGUNTAS --}}
<div class="section-title">
    <h2>Preguntas</h2>

    <span class="badge-count">
        {{ $questions->filter()->count() }} registradas
    </span>
</div>

@foreach($questions->prepend(null) as $question)

    <form
        id="{{ $question ? 'pregunta-'.$question->id : 'nueva-pregunta' }}"
        class="survey-card"
        action="{{ $question ? route('pregunta.save', $question->id) : route('pregunta.save') }}"
        method="post"
    >
        @csrf

        <div class="survey-card-header">
            <div class="survey-icon">
                {{ $question ? '✎' : '?' }}
            </div>

            <h3>
                {{ $question ? 'Editar pregunta' : 'Nueva pregunta' }}
            </h3>
        </div>

        <div class="survey-card-body">

            <div class="row g-3">

                {{-- Encuesta --}}
                <div class="col-12">
                    <div class="form-group">
                        <label
                            class="form-label-custom"
                            for="encuesta-{{ $question?->id ?? 'nueva' }}"
                        >
                            Encuesta
                        </label>

                        <select
                            id="encuesta-{{ $question?->id ?? 'nueva' }}"
                            class="form-select"
                            name="encuesta_id"
                            required
                        >
                            @foreach($surveys->filter() as $s)
                                <option
                                    value="{{ $s->id }}"
                                    @selected($question?->encuesta_id == $s->id)
                                >
                                    {{ $s->titulo }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Pregunta --}}
                <div class="col-12">
                    <div class="form-group">
                        <label
                            class="form-label-custom"
                            for="planteamiento-{{ $question?->id ?? 'nueva' }}"
                        >
                            Pregunta
                        </label>

                        <textarea
                            id="planteamiento-{{ $question?->id ?? 'nueva' }}"
                            class="form-control"
                            name="planteamiento"
                            required
                            placeholder="Escribe el planteamiento de la pregunta..."
                        >{{ $question?->planteamiento }}</textarea>
                    </div>
                </div>

                {{-- Estado --}}
                <div class="col-12">
                    <div class="form-group">
                        <label
                            class="form-label-custom"
                            for="status-{{ $question?->id ?? 'nueva' }}"
                        >
                            Estado de la pregunta
                        </label>

                        <select
                            id="status-{{ $question?->id ?? 'nueva' }}"
                            class="form-select"
                            name="status"
                        >
                            <option value="1" @selected($question?->status == 1)>
                                Activa
                            </option>

                            <option value="0" @selected($question?->status === 0)>
                                Inactiva
                            </option>
                        </select>
                    </div>
                </div>

            </div>

            <div class="mt-2">
                <button class="btn btn-survey" type="submit">
                    {{ $question ? 'Guardar pregunta' : 'Crear pregunta' }}
                </button>
            </div>

        </div>
    </form>

@endforeach


</div>

@endsection
