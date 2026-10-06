@extends('layout')

@section('content')
<<<<<<< HEAD
<h1>Coordinador: {{ $coordinador->nombre }}</h1>
<form class="card card-body mb-3" action="{{ route('coordinador.save',$coordinador->id) }}" method="post">@csrf
@foreach(['nombre','apellidos','username','email'] as $field)<label for="{{ $field }}">{{ ucfirst($field) }}</label><input id="{{ $field }}" name="{{ $field }}" value="{{ old($field,$coordinador->$field) }}" class="form-control mb-3" @if($field!=='apellidos') required @endif>@endforeach
<label for="password">Nueva contraseña (opcional)</label><input id="password" name="password" type="password" class="form-control mb-3" autocomplete="new-password"><button class="btn btn-primary">Guardar datos</button></form>
<form method="post" action="{{ route('coordinador.status',$coordinador->id) }}" class="mb-3" onsubmit="return confirm('¿Cambiar el estado del coordinador?')">@csrf<input type="hidden" name="status" value="{{ $coordinador->status===10?0:10 }}"><button class="btn btn-warning">{{ $coordinador->status===10?'Dar de baja':'Reactivar' }}</button></form>
<form method="post" action="{{ route('coordinador.grupos',$coordinador->id) }}" class="card card-body mb-3">@csrf<h2 class="h5">Grupos asignados</h2>
@foreach($grupos as $group)<label class="choice-label"><input name="grupos[]" type="checkbox" value="{{ $group->id }}" @checked($group->coordinador_id==$coordinador->id) @disabled($group->coordinador_id && $group->coordinador_id!=$coordinador->id)> {{ $group->nombre }}{{ $group->coordinador_id && $group->coordinador_id!=$coordinador->id?' (asignado a otro coordinador)':'' }}</label>@endforeach<button class="btn btn-primary mt-3">Guardar grupos</button></form>
<form method="post" action="{{ route('coordinador.permisos',$coordinador->id) }}" class="card card-body">@csrf<h2 class="h5">Permisos por sección</h2>
@foreach(config('dossier.sections') as $section=>$fields)<div class="permission-row"><strong>{{ config('dossier.section_labels.'.$section,ucfirst($section)) }}</strong> <label class="choice-label"><input name="permisos[{{ $section }}][ver]" type="checkbox" value="1" @checked($permisos[$section]->puede_ver??false)> Consultar</label> <label class="choice-label"><input name="permisos[{{ $section }}][editar]" type="checkbox" value="1" @checked($permisos[$section]->puede_editar??false)> Editar</label></div>@endforeach
<label for="motivo">Motivo del cambio de permisos</label><textarea name="motivo" id="motivo" class="form-control my-3" maxlength="2000" required></textarea><button class="btn btn-primary">Guardar permisos</button></form>
=======

<style>
    :root {
        --coord-blue: #174a88;
        --coord-blue-dark: #10345f;
        --coord-blue-light: #eaf2ff;
        --coord-border: #dbe5f1;
        --coord-text: #26384e;
        --coord-muted: #718096;
        --coord-bg: #f4f7fb;
        --coord-warning: #d99100;
    }

    .coord-page {
        background: var(--coord-bg);
        min-height: 100vh;
        padding: 30px 16px 50px;
    }

    .coord-container {
        max-width: 1050px;
        margin: 0 auto;
    }

    /* HEADER */

    .coord-header {
        background: linear-gradient(125deg, #10345f, #205ca2);
        color: white;
        border-radius: 18px;
        padding: 30px;
        margin-bottom: 25px;
        box-shadow: 0 8px 25px rgba(23, 74, 136, .15);
        position: relative;
        overflow: hidden;
    }

    .coord-header::after {
        content: "";
        position: absolute;
        width: 210px;
        height: 210px;
        border: 28px solid rgba(255,255,255,.07);
        border-radius: 50%;
        right: -60px;
        top: -80px;
    }

    .coord-header-content {
        display: flex;
        align-items: center;
        gap: 18px;
        position: relative;
        z-index: 1;
    }

    .coord-avatar {
        width: 65px;
        height: 65px;
        border-radius: 17px;
        background: rgba(255,255,255,.15);
        border: 1px solid rgba(255,255,255,.25);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 30px;
        flex-shrink: 0;
    }

    .coord-header h1 {
        margin: 0 0 5px;
        font-size: clamp(1.45rem, 3vw, 1.95rem);
        font-weight: 750;
    }

    .coord-header p {
        margin: 0;
        color: #dbeafe;
        font-size: .92rem;
    }

    /* GRID */

    .coord-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 20px;
    }

    .coord-card {
        background: white;
        border: 1px solid var(--coord-border);
        border-radius: 17px;
        overflow: hidden;
        box-shadow: 0 5px 20px rgba(31, 57, 90, .045);
    }

    .coord-card.full {
        grid-column: 1 / -1;
    }

    .coord-card-header {
        padding: 20px 23px;
        border-bottom: 1px solid var(--coord-border);
        display: flex;
        align-items: center;
        gap: 13px;
    }

    .coord-card-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: var(--coord-blue-light);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 19px;
        flex-shrink: 0;
    }

    .coord-card-header h2 {
        color: var(--coord-blue-dark);
        font-size: 1.02rem;
        font-weight: 700;
        margin: 0 0 3px;
    }

    .coord-card-header p {
        color: var(--coord-muted);
        font-size: .8rem;
        margin: 0;
    }

    .coord-card-body {
        padding: 23px;
    }

    /* FORMULARIOS */

    .coord-form-label {
        display: block;
        color: #34465d;
        font-size: .87rem;
        font-weight: 650;
        margin-bottom: 7px;
    }

    .coord-page .form-control {
        min-height: 44px;
        border: 1px solid #d4deeb;
        border-radius: 9px;
        color: var(--coord-text);
        padding: 9px 12px;
        transition: border-color .2s, box-shadow .2s;
    }

    .coord-page .form-control:focus {
        border-color: #4c8ce5;
        box-shadow: 0 0 0 3px rgba(59,130,246,.13);
        outline: none;
    }

    .coord-page textarea.form-control {
        min-height: 100px;
        resize: vertical;
    }

    /* BOTONES */

    .coord-page .btn {
        min-height: 42px;
        border-radius: 9px;
        padding: 9px 17px;
        font-size: .88rem;
        font-weight: 650;
        transition: all .2s ease;
    }

    .coord-btn-primary {
        background: var(--coord-blue);
        border: 1px solid var(--coord-blue);
        color: white;
    }

    .coord-btn-primary:hover {
        background: var(--coord-blue-dark);
        border-color: var(--coord-blue-dark);
        color: white;
        transform: translateY(-1px);
    }

    .coord-btn-warning {
        background: #fff7e6;
        border: 1px solid #f1d394;
        color: #a86c00;
    }

    .coord-btn-warning:hover {
        background: #ffedc2;
        color: #8b5800;
    }

    /* ESTADO */

    .coord-status {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
        background: #fbfdff;
        border: 1px solid var(--coord-border);
        border-radius: 11px;
        padding: 15px 17px;
    }

    .coord-status-label {
        font-size: .8rem;
        color: var(--coord-muted);
        margin-bottom: 3px;
    }

    .coord-status-value {
        font-size: .93rem;
        font-weight: 700;
        color: var(--coord-text);
    }

    .coord-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border-radius: 30px;
        padding: 6px 11px;
        font-size: .76rem;
        font-weight: 700;
    }

    .coord-badge.active {
        color: #18794e;
        background: #e8f7ef;
    }

    .coord-badge.inactive {
        color: #a33b3b;
        background: #fdecec;
    }

    .coord-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: currentColor;
    }

    /* GRUPOS */

    .groups-list {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
    }

    .group-option {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        padding: 13px 14px;
        border: 1px solid var(--coord-border);
        background: #fbfdff;
        border-radius: 10px;
        cursor: pointer;
        transition: all .2s ease;
    }

    .group-option:hover {
        background: #f3f8ff;
        border-color: #a9c6eb;
    }

    .group-option input {
        width: 17px;
        height: 17px;
        margin-top: 2px;
        accent-color: var(--coord-blue);
        flex-shrink: 0;
    }

    .group-name {
        color: var(--coord-text);
        font-size: .87rem;
        font-weight: 600;
    }

    .group-warning {
        display: block;
        color: #9a6a19;
        font-size: .72rem;
        margin-top: 3px;
    }

    /* PERMISOS */

    .permissions-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .permission-row {
        display: grid;
        grid-template-columns: minmax(180px, 1fr) 130px 130px;
        align-items: center;
        gap: 12px;
        padding: 13px 15px;
        border: 1px solid var(--coord-border);
        border-radius: 10px;
        background: #fbfdff;
    }

    .permission-section {
        color: var(--coord-text);
        font-size: .88rem;
        font-weight: 650;
    }

    .permission-check {
        display: flex;
        align-items: center;
        gap: 7px;
        color: #53677f;
        font-size: .82rem;
        cursor: pointer;
    }

    .permission-check input {
        width: 16px;
        height: 16px;
        accent-color: var(--coord-blue);
    }

    .permission-header {
        display: grid;
        grid-template-columns: minmax(180px, 1fr) 130px 130px;
        gap: 12px;
        padding: 0 15px 8px;
        color: var(--coord-muted);
        font-size: .73rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .3px;
    }

    /* MOTIVO */

    .motivo-box {
        margin-top: 22px;
        padding-top: 22px;
        border-top: 1px solid var(--coord-border);
    }

    /* FOOTER */

    .coord-footer {
        text-align: center;
        color: #8290a3;
        font-size: .82rem;
        padding-top: 22px;
    }

    @media (max-width: 800px) {
        .coord-grid {
            grid-template-columns: 1fr;
        }

        .coord-card.full {
            grid-column: auto;
        }
    }

    @media (max-width: 600px) {

        .coord-page {
            padding: 20px 10px 35px;
        }

        .coord-header {
            padding: 23px 18px;
        }

        .coord-header-content {
            align-items: flex-start;
        }

        .coord-avatar {
            width: 54px;
            height: 54px;
            font-size: 25px;
        }

        .coord-card-body {
            padding: 18px;
        }

        .coord-card-header {
            padding: 18px;
        }

        .groups-list {
            grid-template-columns: 1fr;
        }

        .permission-header {
            display: none;
        }

        .permission-row {
            grid-template-columns: 1fr 1fr;
        }

        .permission-section {
            grid-column: 1 / -1;
            padding-bottom: 5px;
            border-bottom: 1px solid var(--coord-border);
        }

        .coord-status {
            align-items: flex-start;
            flex-direction: column;
        }
    }
</style>

<div class="coord-page">


<div class="coord-container">

    {{-- ENCABEZADO --}}
    <header class="coord-header">

        <div class="coord-header-content">

            <div class="coord-avatar">
                👨‍🏫
            </div>

            <div>

                <h1>
                    Coordinador: {{ $coordinador->nombre }}
                </h1>

                <p>
                    Administración de datos, grupos y permisos
                </p>

            </div>

        </div>

    </header>


    <div class="coord-grid">


        {{-- DATOS DEL COORDINADOR --}}
        <section class="coord-card">

            <div class="coord-card-header">

                <div class="coord-card-icon">
                    👤
                </div>

                <div>

                    <h2>
                        Datos del coordinador
                    </h2>

                    <p>
                        Información de acceso y contacto
                    </p>

                </div>

            </div>


            <div class="coord-card-body">

                <form action="{{ route('coordinador.save', $coordinador->id) }}"
                      method="post">

                    @csrf

                    @foreach(['nombre','apellidos','username','email'] as $field)

                        <div class="mb-3">

                            <label for="{{ $field }}"
                                   class="coord-form-label">

                                {{ ucfirst($field) }}

                            </label>

                            <input id="{{ $field }}"
                                   name="{{ $field }}"
                                   value="{{ old($field, $coordinador->$field) }}"
                                   class="form-control"
                                   @if($field != 'apellidos') required @endif>

                        </div>

                    @endforeach


                    <div class="mb-3">

                        <label for="password"
                               class="coord-form-label">

                            Nueva contraseña

                            <span class="fw-normal text-muted">
                                (opcional)
                            </span>

                        </label>

                        <input id="password"
                               name="password"
                               type="password"
                               class="form-control"
                               autocomplete="new-password">

                    </div>


                    <button class="btn coord-btn-primary w-100">

                        💾 Guardar datos

                    </button>

                </form>

            </div>

        </section>


        {{-- ESTADO --}}
        <section class="coord-card">

            <div class="coord-card-header">

                <div class="coord-card-icon">
                    ⚡
                </div>

                <div>

                    <h2>
                        Estado de la cuenta
                    </h2>

                    <p>
                        Control de acceso del coordinador
                    </p>

                </div>

            </div>


            <div class="coord-card-body">

                <div class="coord-status">

                    <div>

                        <div class="coord-status-label">
                            Estado actual
                        </div>

                        @if($coordinador->status === 10)

                            <div class="coord-badge active">
                                <span class="coord-dot"></span>
                                Activo
                            </div>

                        @else

                            <div class="coord-badge inactive">
                                <span class="coord-dot"></span>
                                Inactivo
                            </div>

                        @endif

                    </div>


                    <form method="post"
                          action="{{ route('coordinador.status', $coordinador->id) }}"
                          onsubmit="return confirm('¿Cambiar el estado del coordinador?')">

                        @csrf

                        <input type="hidden"
                               name="status"
                               value="{{ $coordinador->status === 10 ? 0 : 10 }}">

                        <button class="btn coord-btn-warning">

                            {{ $coordinador->status === 10
                                ? 'Dar de baja'
                                : 'Reactivar'
                            }}

                        </button>

                    </form>

                </div>

                <div class="mt-3 text-muted small">

                    Cambiar el estado puede afectar el acceso del
                    coordinador al sistema.

                </div>

            </div>

        </section>


        {{-- GRUPOS --}}
        <section class="coord-card full">

            <div class="coord-card-header">

                <div class="coord-card-icon">
                    👥
                </div>

                <div>

                    <h2>
                        Grupos asignados
                    </h2>

                    <p>
                        Selecciona los grupos que estarán bajo responsabilidad
                        de este coordinador.
                    </p>

                </div>

            </div>


            <div class="coord-card-body">

                <form method="post"
                      action="{{ route('coordinador.grupos', $coordinador->id) }}">

                    @csrf

                    <div class="groups-list">

                        @foreach($grupos as $group)

                            <label class="group-option">

                                <input name="grupos[]"
                                       type="checkbox"
                                       value="{{ $group->id }}"
                                       @checked($group->coordinador_id == $coordinador->id)
                                       @disabled(
                                            $group->coordinador_id &&
                                            $group->coordinador_id != $coordinador->id
                                       )>

                                <span>

                                    <span class="group-name">
                                        {{ $group->nombre }}
                                    </span>

                                    @if(
                                        $group->coordinador_id &&
                                        $group->coordinador_id != $coordinador->id
                                    )

                                        <span class="group-warning">
                                            ⚠ Asignado a otro coordinador
                                        </span>

                                    @endif

                                </span>

                            </label>

                        @endforeach

                    </div>


                    <button class="btn coord-btn-primary mt-3">

                        💾 Guardar grupos

                    </button>

                </form>

            </div>

        </section>


        {{-- PERMISOS --}}
        <section class="coord-card full">

            <div class="coord-card-header">

                <div class="coord-card-icon">
                    🔐
                </div>

                <div>

                    <h2>
                        Permisos por sección
                    </h2>

                    <p>
                        Define qué información puede consultar o modificar
                        el coordinador.
                    </p>

                </div>

            </div>


            <div class="coord-card-body">

                <form method="post"
                      action="{{ route('coordinador.permisos', $coordinador->id) }}">

                    @csrf


                    <div class="permission-header">

                        <div>
                            Sección
                        </div>

                        <div>
                            Consultar
                        </div>

                        <div>
                            Editar
                        </div>

                    </div>


                    <div class="permissions-list">

                        @foreach(config('dossier.sections') as $section => $fields)

                            <div class="permission-row">

                                <div class="permission-section">

                                    {{ ucfirst(str_replace('_', ' ', $section)) }}

                                </div>


                                <label class="permission-check">

                                    <input name="permisos[{{ $section }}][ver]"
                                           type="checkbox"
                                           value="1"
                                           @checked($permisos[$section]->puede_ver ?? false)>

                                    Puede consultar

                                </label>


                                <label class="permission-check">

                                    <input name="permisos[{{ $section }}][editar]"
                                           type="checkbox"
                                           value="1"
                                           @checked($permisos[$section]->puede_editar ?? false)>

                                    Puede editar

                                </label>

                            </div>

                        @endforeach

                    </div>


                    <div class="motivo-box">

                        <label for="motivo"
                               class="coord-form-label">

                            Motivo del cambio de permisos

                        </label>

                        <textarea name="motivo"
                                  id="motivo"
                                  class="form-control"
                                  maxlength="2000"
                                  required
                                  placeholder="Describe el motivo por el cual se modifican los permisos..."></textarea>

                    </div>


                    <button class="btn coord-btn-primary mt-3">

                        🔐 Guardar permisos

                    </button>

                </form>

            </div>

        </section>

    </div>


    <div class="coord-footer">
        Sistema de Tutorías Académicas · Administración de coordinadores
    </div>

</div>

</div>

>>>>>>> c285146 (agregando nuevas vistas de acuerdo con los colores de la escuela dentro del panel administrativo)
@endsection
