@extends('layout')

@section('title', $survey->titulo)

@section('content')

<style>
    :root {
        --survey-blue: #174a88;
        --survey-blue-dark: #10345f;
        --survey-blue-light: #eaf2ff;
        --survey-border: #dbe5f1;
        --survey-text: #26384e;
        --survey-muted: #718096;
        --survey-success: #198754;
        --survey-danger: #dc3545;
        --survey-bg: #f4f7fb;
    }

    .survey-answer-page {
        color: var(--survey-text);
    }

    /* Encabezado */
    .survey-answer-header {
        background: linear-gradient(135deg, var(--survey-blue-dark), var(--survey-blue));
        border-radius: 18px;
        padding: 28px 30px;
        color: white;
        margin-bottom: 22px;
        box-shadow: 0 10px 25px rgba(16, 52, 95, .15);
    }

    .survey-answer-header h1 {
        margin: 0 0 8px;
        font-size: 27px;
        font-weight: 700;
    }

    .survey-answer-header p {
        margin: 0;
        opacity: .9;
        font-size: 14px;
        line-height: 1.6;
    }

    /* Progreso */
    .progress-card {
        background: white;
        border: 1px solid var(--survey-border);
        border-radius: 15px;
        padding: 19px 21px;
        margin-bottom: 22px;
        box-shadow: 0 5px 18px rgba(31, 55, 86, .06);
    }

    .progress-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 15px;
        margin-bottom: 10px;
    }

    .progress-label {
        font-size: 14px;
        font-weight: 700;
        color: var(--survey-blue-dark);
    }

    .progress-count {
        font-size: 13px;
        color: var(--survey-muted);
        font-weight: 600;
    }

    .progress {
        height: 9px;
        border-radius: 20px;
        background: #edf2f7;
        overflow: hidden;
    }

    .progress-bar {
        background: linear-gradient(90deg, var(--survey-blue-dark), #2b73c2);
        border-radius: 20px;
        transition: width .3s ease;
    }

    /* Estado de guardado */
    .save-status {
        min-height: 22px;
        margin: 0 0 18px;
        padding: 10px 14px;
        border-radius: 9px;
        background: var(--survey-blue-light);
        color: var(--survey-blue);
        border: 1px solid #cbdcf5;
        font-size: 13px;
        font-weight: 600;
    }

    /* Pregunta */
    .question-card {
        background: white;
        border: 1px solid var(--survey-border);
        border-radius: 16px;
        margin-bottom: 17px;
        padding: 0;
        overflow: hidden;
        box-shadow: 0 5px 18px rgba(31, 55, 86, .06);
        transition: .2s ease;
    }

    .question-card:focus-within {
        border-color: #a9c4e7;
        box-shadow: 0 7px 22px rgba(23, 74, 136, .1);
    }

    .question-number {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 31px;
        height: 31px;
        border-radius: 9px;
        background: var(--survey-blue-light);
        color: var(--survey-blue);
        font-size: 13px;
        font-weight: 700;
        flex-shrink: 0;
    }

    .question-content {
        padding: 20px;
    }

    .question-title {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        margin: 0 0 18px;
        font-size: 16px;
        line-height: 1.55;
        font-weight: 700;
        color: var(--survey-blue-dark);
    }

    /* Opciones */
    .answer-options {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }

    .answer-option {
        position: relative;
        cursor: pointer;
        margin: 0;
    }

    .answer-option input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .answer-box {
        display: flex;
        align-items: center;
        gap: 10px;
        min-height: 48px;
        padding: 11px 14px;
        border: 1px solid var(--survey-border);
        border-radius: 10px;
        background: #fff;
        color: var(--survey-text);
        font-size: 14px;
        font-weight: 600;
        transition: all .2s ease;
    }

    .answer-circle {
        width: 19px;
        height: 19px;
        border: 2px solid #a9b7c7;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .answer-option input:checked + .answer-box {
        background: var(--survey-blue-light);
        border-color: var(--survey-blue);
        color: var(--survey-blue-dark);
    }

    .answer-option input:checked + .answer-box .answer-circle {
        border-color: var(--survey-blue);
    }

    .answer-option input:checked + .answer-box .answer-circle::after {
        content: "";
        width: 9px;
        height: 9px;
        background: var(--survey-blue);
        border-radius: 50%;
    }

    .answer-box:hover {
        border-color: #9db9db;
        background: #f8fbff;
    }

    /* Botón */
    .submit-area {
        background: white;
        border: 1px solid var(--survey-border);
        border-radius: 15px;
        padding: 18px 20px;
        margin-top: 22px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
        box-shadow: 0 5px 18px rgba(31, 55, 86, .06);
    }

    .submit-info {
        color: var(--survey-muted);
        font-size: 13px;
    }

    .btn-survey {
        background: linear-gradient(135deg, var(--survey-blue), #2468b5);
        color: white;
        border: none;
        border-radius: 9px;
        padding: 11px 20px;
        font-weight: 600;
        box-shadow: 0 4px 10px rgba(23, 74, 136, .18);
        transition: .2s ease;
        white-space: nowrap;
    }

    .btn-survey:hover {
        background: linear-gradient(135deg, var(--survey-blue-dark), var(--survey-blue));
        color: white;
        transform: translateY(-1px);
    }

    /* Paginación */
    .pagination-container {
        margin-top: 20px;
        padding: 15px;
        background: white;
        border: 1px solid var(--survey-border);
        border-radius: 12px;
    }

    .pagination-container nav {
        margin: 0;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .survey-answer-header {
            padding: 22px;
        }

        .survey-answer-header h1 {
            font-size: 23px;
        }

        .progress-top {
            align-items: flex-start;
            flex-direction: column;
            gap: 4px;
        }

        .answer-options {
            grid-template-columns: 1fr;
        }

        .submit-area {
            align-items: stretch;
            flex-direction: column;
        }

        .btn-survey {
            width: 100%;
        }
    }
</style>

<div class="survey-answer-page">

    {{-- Encabezado --}}
    <div class="survey-answer-header">
        <h1>{{ $survey->titulo }}</h1>

        @if($survey->descripcion)
            <p>{{ $survey->descripcion }}</p>
        @else
            <p>Responde las siguientes preguntas de acuerdo con tu situación actual.</p>
        @endif
    </div>

    {{-- Progreso --}}
    @php
        $totalQuestions = $questions->total();
        $progress = $totalQuestions > 0
            ? round(($answered / $totalQuestions) * 100)
            : 0;
    @endphp

    <div class="progress-card">
        <div class="progress-top">
            <span class="progress-label">
                Progreso de la encuesta
            </span>

            <span class="progress-count">
                {{ $answered }} de {{ $totalQuestions }} preguntas respondidas
            </span>
        </div>

        <div
            class="progress"
            role="progressbar"
            aria-valuenow="{{ $progress }}"
            aria-valuemin="0"
            aria-valuemax="100"
        >
            <div
                class="progress-bar"
                style="width: {{ $progress }}%"
            ></div>
        </div>
    </div>

    {{-- Estado del guardado automático --}}
    <p
        id="estado-guardado"
        class="save-status"
        role="status"
        aria-live="polite"
    ></p>

    {{-- Formulario --}}
    <form
        action="{{ route('encuesta.responder', ['id' => $survey->id, 'page' => $questions->currentPage()]) }}"
        method="post"
    >
        @csrf

        @foreach($questions as $index => $question)

            <fieldset class="question-card">
                <div class="question-content">

                    <legend class="question-title">
                        <span class="question-number">
                            {{ $questions->firstItem() + $index }}
                        </span>

                        <span>
                            {{ $question->planteamiento }}
                        </span>
                    </legend>

                    <div class="answer-options">

                        @foreach(['Si', 'No'] as $answer)

                            <label class="answer-option">

                                <input
                                    type="radio"
                                    class="respuesta"
                                    name="respuestas[{{ $question->id }}]"
                                    value="{{ $answer }}"
                                    data-pregunta="{{ $question->id }}"
                                    @checked(($previous[$question->id] ?? null) === $answer)
                                    required
                                >

                                <span class="answer-box">
                                    <span class="answer-circle"></span>
                                    <span>{{ $answer }}</span>
                                </span>

                            </label>

                        @endforeach

                    </div>

                </div>
            </fieldset>

        @endforeach

        {{-- Acciones --}}
        <div class="submit-area">

            <div class="submit-info">
                Las respuestas se guardan automáticamente al seleccionarlas.
            </div>

            <button class="btn btn-survey" type="submit">
                {{ $questions->hasMorePages() ? 'Guardar y continuar' : 'Guardar y finalizar' }}
            </button>

        </div>

    </form>

    {{-- Paginación --}}
    @if($questions->hasPages())
        <div class="pagination-container">
            {{ $questions->links() }}
        </div>
    @endif

</div>

<script>
    let queue = Promise.resolve();

    document.querySelectorAll('.respuesta').forEach(input => {
        input.addEventListener('change', () => {

            const question = input.dataset.pregunta;
            const value = input.value;

            queue = queue
                .catch(() => {})
                .then(async () => {

                    const state = document.getElementById('estado-guardado');

                    state.textContent = 'Guardando respuesta…';

                    try {

                        const response = await fetch(
                            @json(route('encuesta.autoguardado')),
                            {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': @json(csrf_token())
                                },
                                body: JSON.stringify({
                                    pregunta_id: question,
                                    respuesta: value
                                })
                            }
                        );

                        if (!response.ok) {
                            throw new Error();
                        }

                        state.textContent = '✓ Respuesta guardada automáticamente.';

                    } catch (error) {

                        state.textContent =
                            'No se pudo guardar automáticamente. Usa "Guardar y continuar".';
                    }
                });
        });
    });
</script>

@endsection