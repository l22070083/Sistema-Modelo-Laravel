@extends('layout')

@section('content')

<style>
    :root {
        --student-blue: #174a88;
        --student-blue-dark: #10345f;
        --student-blue-light: #eaf2ff;
        --student-border: #dbe5f1;
        --student-text: #26384e;
        --student-muted: #718096;
        --student-bg: #f4f7fb;
    }

    .student-page {
        background: var(--student-bg);
        min-height: 100vh;
        padding: 35px 16px 50px;
    }

    .student-container {
        max-width: 950px;
        margin: 0 auto;
    }

    /* ENCABEZADO */
    .student-header {
        background: linear-gradient(125deg, #10345f, #205ca2);
        border-radius: 18px;
        padding: 30px;
        color: white;
        margin-bottom: 25px;
        box-shadow: 0 8px 25px rgba(23, 74, 136, .15);
        position: relative;
        overflow: hidden;
    }

    .student-header::after {
        content: "";
        position: absolute;
        width: 190px;
        height: 190px;
        border: 25px solid rgba(255,255,255,.07);
        border-radius: 50%;
        right: -55px;
        top: -70px;
    }

    .student-header-content {
        display: flex;
        align-items: center;
        gap: 18px;
        position: relative;
        z-index: 1;
    }

    .student-avatar {
        width: 68px;
        height: 68px;
        border-radius: 17px;
        background: rgba(255,255,255,.15);
        border: 1px solid rgba(255,255,255,.25);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 31px;
        flex-shrink: 0;
    }

    .student-header h1 {
        font-size: clamp(1.5rem, 3vw, 2rem);
        font-weight: 750;
        margin: 0 0 5px;
    }

    .student-header p {
        margin: 0;
        color: #dbeafe;
        font-size: .95rem;
    }

    /* TARJETA PRINCIPAL */
    .student-card {
        background: white;
        border: 1px solid var(--student-border);
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 5px 20px rgba(31, 57, 90, .05);
    }

    .student-card-title {
        padding: 22px 28px;
        border-bottom: 1px solid var(--student-border);
    }

    .student-card-title h2 {
        margin: 0 0 4px;
        color: var(--student-blue-dark);
        font-size: 1.15rem;
        font-weight: 700;
    }

    .student-card-title p {
        margin: 0;
        color: var(--student-muted);
        font-size: .88rem;
    }

    .student-body {
        padding: 28px;
    }

    /* INFORMACIÓN */
    .student-info-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
        margin-bottom: 28px;
    }

    .student-info {
        border: 1px solid var(--student-border);
        background: #fbfdff;
        border-radius: 12px;
        padding: 17px 18px;
        transition: all .2s ease;
    }

    .student-info:hover {
        border-color: #a9c6eb;
        background: #f7faff;
        transform: translateY(-1px);
    }

    .student-info-label {
        color: var(--student-muted);
        font-size: .78rem;
        font-weight: 650;
        text-transform: uppercase;
        letter-spacing: .4px;
        margin-bottom: 6px;
    }

    .student-info-value {
        color: var(--student-text);
        font-size: .97rem;
        font-weight: 600;
        word-break: break-word;
    }

    /* EXPEDIENTE */
    .student-expediente {
        background: var(--student-blue-light);
        border: 1px solid #d5e5fb;
        border-radius: 14px;
        padding: 22px;
        margin-top: 8px;
    }

    .student-expediente h3 {
        color: var(--student-blue-dark);
        font-size: 1rem;
        font-weight: 700;
        margin: 0 0 5px;
    }

    .student-expediente p {
        color: #527093;
        font-size: .87rem;
        margin: 0 0 17px;
    }

    /* BOTONES */
    .student-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 24px;
        padding-top: 23px;
        border-top: 1px solid var(--student-border);
    }

    .student-actions .btn {
        min-height: 43px;
        padding: 10px 18px;
        border-radius: 9px;
        font-size: .9rem;
        font-weight: 650;
        transition: all .2s ease;
    }

    .student-btn-primary {
        background: var(--student-blue);
        border: 1px solid var(--student-blue);
        color: white;
    }

    .student-btn-primary:hover {
        background: var(--student-blue-dark);
        border-color: var(--student-blue-dark);
        color: white;
        transform: translateY(-1px);
    }

    .student-btn-outline {
        background: white;
        border: 1px solid #b9cde5;
        color: var(--student-blue);
    }

    .student-btn-outline:hover {
        background: var(--student-blue-light);
        border-color: #8fb3df;
        color: var(--student-blue-dark);
    }

    .student-footer {
        text-align: center;
        color: #8290a3;
        font-size: .82rem;
        padding-top: 20px;
    }

    @media (max-width: 650px) {
        .student-page {
            padding: 20px 10px 35px;
        }

        .student-header {
            padding: 23px 18px;
        }

        .student-header-content {
            align-items: flex-start;
        }

        .student-avatar {
            width: 55px;
            height: 55px;
            font-size: 25px;
        }

        .student-body {
            padding: 20px 17px;
        }

        .student-card-title {
            padding: 20px 17px;
        }

        .student-info-grid {
            grid-template-columns: 1fr;
        }

        .student-actions {
            flex-direction: column;
        }

        .student-actions .btn {
            width: 100%;
        }
    }
</style>

<div class="student-page">


<div class="student-container">

    {{-- ENCABEZADO --}}
    <header class="student-header">

        <div class="student-header-content">

            <div class="student-avatar">
                🎓
            </div>

            <div>
                <h1>
                    {{ $student->nombre }} {{ $student->apellidos }}
                </h1>

                <p>
                    Perfil del estudiante · Sistema de Tutorías Académicas
                </p>
            </div>

        </div>

    </header>


    {{-- INFORMACIÓN DEL ALUMNO --}}
    <section class="student-card">

        <div class="student-card-title">

            <h2>Información del estudiante</h2>

            <p>
                Datos generales registrados en el sistema.
            </p>

        </div>


        <div class="student-body">

            <div class="student-info-grid">

                <div class="student-info">

                    <div class="student-info-label">
                        Nombre completo
                    </div>

                    <div class="student-info-value">
                        {{ $student->nombre }} {{ $student->apellidos }}
                    </div>

                </div>


                <div class="student-info">

                    <div class="student-info-label">
                        Matrícula
                    </div>

                    <div class="student-info-value">
                        {{ $student->matricula }}
                    </div>

                </div>


                <div class="student-info">

                    <div class="student-info-label">
                        Correo electrónico
                    </div>

                    <div class="student-info-value">
                        {{ $student->email }}
                    </div>

                </div>


                @if(isset($student->username))

                    <div class="student-info">

                        <div class="student-info-label">
                            Usuario
                        </div>

                        <div class="student-info-value">
                            {{ $student->username }}
                        </div>

                    </div>

                @endif

            </div>


            {{-- EXPEDIENTE --}}
            @if(
                \App\Services\SectionAccess::can('personales', true) &&
                \App\Services\SectionAccess::can('cuestionario', true)
            )

                <div class="student-expediente">

                    <h3>
                        📋 Expediente estudiantil
                    </h3>

                    <p>
                        Consulta o actualiza la información correspondiente
                        al expediente académico y personal del estudiante.
                    </p>

                    <a class="btn student-btn-primary"
                       href="{{ route('expediente.crear', $student->id) }}">

                        {{ isset($record) && $record ? 'Editar expediente' : 'Crear o editar expediente' }}

                    </a>

                </div>

            @endif


            {{-- ACCIONES --}}
            <div class="student-actions">
                @if(auth()->user()->rol_id === 1)
                    @include('partials.account-delete', ['account' => $student, 'deleteRoute' => 'alumno.destroy'])
                @endif

                @if(auth()->user()->rol_id === 1)

                    <a class="btn student-btn-primary"
                       href="{{ route('alumno.editar', $student->id) }}">

                        ✏️ Editar perfil

                    </a>

                @endif

                <a class="btn student-btn-outline"
                   href="{{ url()->previous() }}">

                    ← Regresar

                </a>

            </div>

        </div>

    </section>


    <div class="student-footer">
        Sistema de Tutorías Académicas · Perfil del estudiante
    </div>

</div>


</div>

@endsection
