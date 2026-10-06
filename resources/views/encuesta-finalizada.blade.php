@extends('layout')

@section('title', 'Encuesta completada')

@section('content')

<style>
    :root {
        --survey-blue: #174a88;
        --survey-blue-dark: #10345f;
        --survey-blue-light: #eaf2ff;
        --survey-border: #dbe5f1;
        --survey-text: #26384e;
        --survey-muted: #718096;
    }

    .completed-page {
        min-height: 65vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 35px 15px;
    }

    .completed-card {
        width: 100%;
        max-width: 650px;
        background: #fff;
        border: 1px solid var(--survey-border);
        border-radius: 20px;
        padding: 45px 40px;
        text-align: center;
        box-shadow: 0 12px 35px rgba(31, 55, 86, .10);
    }

    .completed-icon {
        width: 76px;
        height: 76px;
        margin: 0 auto 22px;
        border-radius: 50%;
        background: var(--survey-blue-light);
        border: 1px solid #cbdcf5;
        color: var(--survey-blue);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 35px;
        font-weight: 700;
    }

    .completed-card h1 {
        color: var(--survey-blue-dark);
        font-size: 28px;
        font-weight: 700;
        margin: 0 0 12px;
    }

    .completed-card p {
        color: var(--survey-muted);
        font-size: 15px;
        line-height: 1.7;
        margin: 0 auto 28px;
        max-width: 500px;
    }

    .confirmation-box {
        background: #f8fbff;
        border: 1px solid var(--survey-border);
        border-radius: 12px;
        padding: 14px 18px;
        margin-bottom: 25px;
        color: var(--survey-text);
        font-size: 13px;
    }

    .btn-home {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        background: linear-gradient(135deg, var(--survey-blue), #2468b5);
        color: #fff;
        border: none;
        border-radius: 10px;
        padding: 12px 23px;
        font-weight: 600;
        text-decoration: none;
        box-shadow: 0 5px 13px rgba(23, 74, 136, .20);
        transition: .2s ease;
    }

    .btn-home:hover {
        background: linear-gradient(135deg, var(--survey-blue-dark), var(--survey-blue));
        color: #fff;
        transform: translateY(-1px);
    }

    @media (max-width: 576px) {
        .completed-card {
            padding: 35px 22px;
        }

        .completed-card h1 {
            font-size: 24px;
        }
    }
</style>

<div class="completed-page">


<div class="completed-card">

    <div class="completed-icon" aria-hidden="true">
        ✓
    </div>

    <h1>Encuesta completada</h1>

    <p>
        Se guardaron correctamente todas tus respuestas de salud.
        Gracias por completar la encuesta.
    </p>

    <div class="confirmation-box">
        <strong>✓ Respuestas registradas</strong><br>
        Tu información ha sido guardada correctamente en el sistema.
    </div>

    <a
        class="btn-home"
        href="{{ route('home') }}"
    >
        ← Volver al inicio
    </a>

</div>


</div>

@endsection
