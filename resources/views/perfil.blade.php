@extends('layout')

@section('title', 'Mi Perfil')

@section('content')

<style>
    :root {
        --profile-blue: #174a88;
        --profile-blue-dark: #10345f;
        --profile-blue-light: #eaf2ff;
        --profile-border: #dbe5f1;
        --profile-bg: #f4f7fb;
        --profile-text: #26384e;
        --profile-muted: #718096;
    }

    .profile-page {
        background: var(--profile-bg);
        min-height: calc(100vh - 100px);
        padding: 30px 0 45px;
    }

    .profile-container {
        max-width: 950px;
        margin: 0 auto;
        padding: 0 20px;
    }

    .profile-header {
        background: linear-gradient(
            135deg,
            var(--profile-blue-dark),
            var(--profile-blue)
        );
        color: white;
        border-radius: 18px;
        padding: 30px;
        margin-bottom: 24px;
        box-shadow: 0 10px 30px rgba(23, 74, 136, .16);
    }

    .profile-header-content {
        display: flex;
        align-items: center;
        gap: 18px;
    }

    .profile-icon {
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

    .profile-header h1 {
        margin: 0 0 7px;
        font-size: 28px;
        font-weight: 700;
        letter-spacing: -.3px;
    }

    .profile-header p {
        margin: 0;
        color: rgba(255, 255, 255, .88);
        font-size: 14px;
        line-height: 1.6;
    }

    .profile-card {
        background: white;
        border: 1px solid var(--profile-border);
        border-radius: 18px;
        overflow: hidden;
        margin-bottom: 22px;
        box-shadow: 0 8px 25px rgba(30, 55, 90, .07);
    }

    .profile-card-header {
        padding: 20px 24px;
        border-bottom: 1px solid var(--profile-border);
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .card-header-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        background: var(--profile-blue-light);
        color: var(--profile-blue);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
    }

    .profile-card-header h2 {
        margin: 0;
        color: var(--profile-text);
        font-size: 18px;
        font-weight: 700;
    }

    .profile-card-body {
        padding: 24px;
    }

    .profile-fields {
        color: var(--profile-text);
    }

    .profile-card-body :where(label) {
        color: #53657a;
        font-size: 13px;
        font-weight: 700;
    }

    .profile-card-body :where(input, select, textarea) {
        border: 1px solid var(--profile-border);
        border-radius: 9px;
        color: var(--profile-text);
        box-shadow: none;
    }

    .profile-card-body :where(input, select, textarea):focus {
        border-color: var(--profile-blue);
        box-shadow: 0 0 0 3px rgba(23, 74, 136, .10);
    }

    .save-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        border: none;
        border-radius: 9px;
        padding: 10px 20px;
        background: var(--profile-blue);
        color: white;
        font-size: 14px;
        font-weight: 700;
        transition: all .18s ease;
        margin-top: 20px;
    }

    .save-button:hover {
        background: var(--profile-blue-dark);
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 5px 14px rgba(23, 74, 136, .2);
    }

    .microsoft-card {
        background: white;
        border: 1px solid var(--profile-border);
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 8px 25px rgba(30, 55, 90, .07);
    }

    .microsoft-header {
        padding: 20px 24px;
        border-bottom: 1px solid var(--profile-border);
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .microsoft-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        background: #f3f6fa;
        color: var(--profile-blue);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
    }

    .microsoft-header h2 {
        margin: 0;
        color: var(--profile-text);
        font-size: 18px;
        font-weight: 700;
    }

    .microsoft-body {
        padding: 24px;
    }

    .microsoft-description {
        color: var(--profile-muted);
        font-size: 14px;
        line-height: 1.6;
        margin-bottom: 20px;
    }

    .password-label {
        display: block;
        color: #53657a;
        font-size: 13px;
        font-weight: 700;
        margin-bottom: 7px;
    }

    .password-input {
        min-height: 42px;
        border: 1px solid var(--profile-border);
        border-radius: 9px;
        color: var(--profile-text);
        box-shadow: none;
        margin-bottom: 16px;
    }

    .password-input:focus {
        border-color: var(--profile-blue);
        box-shadow: 0 0 0 3px rgba(23, 74, 136, .10);
    }

    .microsoft-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        border: 1px solid var(--profile-blue);
        border-radius: 9px;
        padding: 9px 17px;
        background: white;
        color: var(--profile-blue);
        font-size: 14px;
        font-weight: 700;
        transition: all .18s ease;
    }

    .microsoft-button:hover {
        background: var(--profile-blue);
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 5px 14px rgba(23, 74, 136, .15);
    }

    .security-note {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        background: var(--profile-blue-light);
        border: 1px solid #cfe0f7;
        border-radius: 10px;
        padding: 12px 14px;
        margin-bottom: 20px;
        color: #53657a;
        font-size: 13px;
        line-height: 1.5;
    }

    .security-note-icon {
        color: var(--profile-blue);
        font-weight: 700;
    }

    @media (max-width: 768px) {
        .profile-page {
            padding: 20px 0 30px;
        }

        .profile-container {
            padding: 0 12px;
        }

        .profile-header {
            padding: 22px;
            border-radius: 14px;
        }

        .profile-header-content {
            align-items: flex-start;
        }

        .profile-icon {
            width: 48px;
            height: 48px;
            font-size: 22px;
        }

        .profile-header h1 {
            font-size: 23px;
        }

        .profile-card-header,
        .microsoft-header {
            padding: 17px;
        }

        .profile-card-body,
        .microsoft-body {
            padding: 18px;
        }
    }
</style>

<div class="profile-page">

```
<div class="profile-container">

    {{-- Encabezado --}}
    <div class="profile-header">

        <div class="profile-header-content">

            <div class="profile-icon">
                👤
            </div>

            <div>
                <h1>Mi perfil</h1>

                <p>
                    Administra y actualiza la información de tu cuenta
                    dentro del sistema de tutorías.
                </p>
            </div>

        </div>

    </div>

    {{-- Información del perfil --}}
    <div class="profile-card">

        <div class="profile-card-header">

            <div class="card-header-icon">
                ✎
            </div>

            <h2>
                Información personal
            </h2>

        </div>

        <div class="profile-card-body">

            <form
                method="post"
                action="{{ route('perfil') }}"
            >

                @csrf

                <div class="profile-fields">
                    @include('perfil-campos')
                </div>

                <button
                    type="submit"
                    class="save-button"
                >
                    ✓ Guardar perfil
                </button>

            </form>

        </div>

    </div>

    {{-- Vinculación Microsoft --}}
    @if(config('services.microsoft.enabled'))

        <div class="microsoft-card">

            <div class="microsoft-header">

                <div class="microsoft-icon">
                    ⊞
                </div>

                <h2>
                    Vincular cuenta Microsoft
                </h2>

            </div>

            <div class="microsoft-body">

                <div class="security-note">

                    <span class="security-note-icon">
                        🔒
                    </span>

                    <span>
                        Para proteger tu cuenta, confirma primero tu
                        contraseña local antes de realizar la vinculación
                        con Microsoft.
                    </span>

                </div>

                <form
                    method="post"
                    action="{{ route('microsoft.link') }}"
                >

                    @csrf

                    <label
                        for="local-password"
                        class="password-label"
                    >
                        Confirma tu contraseña local
                    </label>

                    <input
                        id="local-password"
                        type="password"
                        name="password"
                        class="form-control password-input"
                        autocomplete="current-password"
                        required
                    >

                    <button
                        type="submit"
                        class="microsoft-button"
                    >
                        <span>⊞</span>
                        Vincular mi cuenta Microsoft
                    </button>

                </form>

            </div>

        </div>

    @endif

</div>
```

</div>

@endsection
