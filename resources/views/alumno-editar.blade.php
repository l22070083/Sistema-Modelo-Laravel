@extends('layout')

@section('content')

<style>
    :root {
        --profile-blue: #174a88;
        --profile-blue-dark: #10345f;
        --profile-blue-light: #eaf2ff;
        --profile-border: #dbe5f1;
        --profile-text: #26384e;
        --profile-muted: #718096;
        --profile-bg: #f4f7fb;
    }

    .profile-page {
        background: var(--profile-bg);
        min-height: 100vh;
        padding: 35px 16px 50px;
    }

    .profile-container {
        max-width: 900px;
        margin: 0 auto;
    }

    /* ENCABEZADO */

    .profile-header {
        background: linear-gradient(125deg, #10345f, #205ca2);
        color: white;
        border-radius: 18px;
        padding: 30px;
        margin-bottom: 25px;
        box-shadow: 0 8px 25px rgba(23, 74, 136, .15);
        position: relative;
        overflow: hidden;
    }

    .profile-header::after {
        content: "";
        position: absolute;
        width: 190px;
        height: 190px;
        border: 25px solid rgba(255,255,255,.07);
        border-radius: 50%;
        right: -55px;
        top: -70px;
    }

    .profile-header-content {
        display: flex;
        align-items: center;
        gap: 18px;
        position: relative;
        z-index: 1;
    }

    .profile-icon {
        width: 62px;
        height: 62px;
        border-radius: 16px;
        background: rgba(255,255,255,.15);
        border: 1px solid rgba(255,255,255,.25);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 29px;
        flex-shrink: 0;
    }

    .profile-header h1 {
        margin: 0 0 5px;
        font-size: clamp(1.45rem, 3vw, 1.9rem);
        font-weight: 750;
    }

    .profile-header p {
        margin: 0;
        color: #dbeafe;
        font-size: .93rem;
    }

    /* TARJETA */

    .profile-card {
        background: white;
        border: 1px solid var(--profile-border);
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 5px 20px rgba(31, 57, 90, .05);
    }

    .profile-card-header {
        padding: 22px 28px;
        border-bottom: 1px solid var(--profile-border);
    }

    .profile-card-header h2 {
        margin: 0 0 5px;
        color: var(--profile-blue-dark);
        font-size: 1.12rem;
        font-weight: 700;
    }

    .profile-card-header p {
        margin: 0;
        color: var(--profile-muted);
        font-size: .88rem;
    }

    .profile-card-body {
        padding: 28px;
    }

    /* CONTENEDOR DE CAMPOS */

    .profile-fields {
        background: #fbfdff;
        border: 1px solid var(--profile-border);
        border-radius: 13px;
        padding: 23px;
    }

    /*
     * Estilos para los campos que vienen
     * desde perfil-campos.blade.php
     */

    .profile-fields label {
        color: #34465d;
        font-size: .9rem;
        font-weight: 650;
        margin-bottom: 7px;
    }

    .profile-fields .form-control,
    .profile-fields .form-select {
        min-height: 44px;
        border: 1px solid #d4deeb;
        border-radius: 9px;
        padding: 9px 12px;
        color: var(--profile-text);
        background: white;
        transition: border-color .2s, box-shadow .2s;
    }

    .profile-fields .form-control:focus,
    .profile-fields .form-select:focus {
        border-color: #4c8ce5;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, .13);
        outline: none;
    }

    .profile-fields textarea.form-control {
        min-height: 100px;
        resize: vertical;
    }

    /* INFORMACIÓN */

    .profile-info {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 15px 17px;
        margin-bottom: 23px;
        background: var(--profile-blue-light);
        border: 1px solid #d5e5fb;
        border-radius: 11px;
        color: #31557f;
        font-size: .88rem;
    }

    .profile-info-icon {
        font-size: 18px;
        line-height: 1;
    }

    .profile-info strong {
        color: var(--profile-blue-dark);
    }

    /* ACCIONES */

    .profile-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 28px;
        padding-top: 23px;
        border-top: 1px solid var(--profile-border);
    }

    .profile-actions .btn {
        min-height: 43px;
        padding: 10px 19px;
        border-radius: 9px;
        font-size: .9rem;
        font-weight: 650;
        transition: all .2s ease;
    }

    .profile-btn-primary {
        background: var(--profile-blue);
        border: 1px solid var(--profile-blue);
        color: white;
    }

    .profile-btn-primary:hover {
        background: var(--profile-blue-dark);
        border-color: var(--profile-blue-dark);
        color: white;
        transform: translateY(-1px);
    }

    .profile-btn-secondary {
        background: white;
        border: 1px solid #c5d5e8;
        color: var(--profile-blue);
    }

    .profile-btn-secondary:hover {
        background: var(--profile-blue-light);
        border-color: #91b4dd;
        color: var(--profile-blue-dark);
    }

    .profile-footer {
        text-align: center;
        color: #8290a3;
        font-size: .82rem;
        padding-top: 20px;
    }

    @media (max-width: 600px) {

        .profile-page {
            padding: 20px 10px 35px;
        }

        .profile-header {
            padding: 23px 18px;
        }

        .profile-header-content {
            align-items: flex-start;
        }

        .profile-icon {
            width: 54px;
            height: 54px;
            font-size: 25px;
        }

        .profile-card-body {
            padding: 18px;
        }

        .profile-card-header {
            padding: 20px 18px;
        }

        .profile-fields {
            padding: 17px;
        }

        .profile-actions {
            flex-direction: column-reverse;
            align-items: stretch;
        }

        .profile-actions .btn {
            width: 100%;
        }
    }
</style>

<div class="profile-page">

<div class="profile-container">

    {{-- ENCABEZADO --}}
    <header class="profile-header">

        <div class="profile-header-content">

            <div class="profile-icon">
                👤
            </div>

            <div>

                <h1>
                    Editar perfil del alumno
                </h1>

                <p>
                    Actualización de información personal del estudiante
                </p>

            </div>

        </div>

    </header>


    {{-- FORMULARIO --}}
    <form method="post" class="profile-card">

        @csrf

        <div class="profile-card-header">

            <h2>
                Información personal
            </h2>

            <p>
                Modifica los datos necesarios y guarda los cambios.
            </p>

        </div>


        <div class="profile-card-body">

            {{-- MENSAJE INFORMATIVO --}}
            <div class="profile-info">

                <div class="profile-info-icon">
                    ℹ️
                </div>

                <div>
                    <strong>Información importante</strong><br>

                    Verifica que los datos sean correctos antes de
                    guardar los cambios en el perfil del alumno.
                </div>

            </div>


            {{-- CAMPOS ORIGINALES --}}
            <div class="profile-fields">

                @include('perfil-campos')

            </div>


            {{-- ACCIONES --}}
            <div class="profile-actions">

                <a href="{{ url()->previous() }}"
                   class="btn profile-btn-secondary">

                    ← Regresar

                </a>

                <button type="submit"
                        class="btn profile-btn-primary">

                    💾 Guardar cambios

                </button>

            </div>

        </div>

    </form>


    <div class="profile-footer">
        Sistema de Tutorías Académicas · Gestión de estudiantes
    </div>

</div>


</div>

@endsection
