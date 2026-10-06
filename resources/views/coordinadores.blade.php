@extends('layout')

@section('title', 'Coordinadores')

@section('content')

<style>
    :root {
        --coord-blue: #174a88;
        --coord-blue-dark: #10345f;
        --coord-blue-light: #eaf2ff;
        --coord-border: #dbe5f1;
        --coord-text: #26384e;
        --coord-muted: #718096;
        --coord-bg: #f4f7fb;
    }

    .coordinadores-page {
        background: var(--coord-bg);
        min-height: 100vh;
        padding: 30px 16px 50px;
    }

    .coordinadores-container {
        max-width: 1100px;
        margin: 0 auto;
    }

    /* HEADER */

    .coordinadores-header {
        background: linear-gradient(125deg, #10345f, #205ca2);
        color: white;
        border-radius: 18px;
        padding: 30px;
        margin-bottom: 25px;
        box-shadow: 0 8px 25px rgba(23, 74, 136, .15);
        position: relative;
        overflow: hidden;
    }

    .coordinadores-header::after {
        content: "";
        position: absolute;
        width: 210px;
        height: 210px;
        border: 28px solid rgba(255,255,255,.07);
        border-radius: 50%;
        right: -60px;
        top: -80px;
    }

    .coordinadores-header-content {
        display: flex;
        align-items: center;
        gap: 18px;
        position: relative;
        z-index: 1;
    }

    .coordinadores-icon {
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

    .coordinadores-header h1 {
        margin: 0 0 5px;
        font-size: clamp(1.5rem, 3vw, 2rem);
        font-weight: 750;
    }

    .coordinadores-header p {
        margin: 0;
        color: #dbeafe;
        font-size: .93rem;
    }

    /* GRID */

    .coordinadores-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 20px;
        margin-bottom: 20px;
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
        display: flex;
        align-items: center;
        gap: 13px;
        padding: 20px 23px;
        border-bottom: 1px solid var(--coord-border);
    }

    .coord-card-icon {
        width: 41px;
        height: 41px;
        border-radius: 11px;
        background: var(--coord-blue-light);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 19px;
        flex-shrink: 0;
    }

    .coord-card-header h2 {
        margin: 0 0 3px;
        color: var(--coord-blue-dark);
        font-size: 1.02rem;
        font-weight: 700;
    }

    .coord-card-header p {
        margin: 0;
        color: var(--coord-muted);
        font-size: .81rem;
    }

    .coord-card-body {
        padding: 23px;
    }

    /* FORMULARIOS */

    .coord-label,
    .coordinadores-page .form-label {
        color: #34465d;
        font-size: .87rem;
        font-weight: 650;
        margin-bottom: 7px;
    }

    .coordinadores-page .form-control,
    .coordinadores-page .form-select {
        min-height: 44px;
        border: 1px solid #d4deeb;
        border-radius: 9px;
        color: var(--coord-text);
        padding: 9px 12px;
        transition: border-color .2s, box-shadow .2s;
    }

    .coordinadores-page textarea.form-control {
        min-height: 110px;
        resize: vertical;
    }

    .coordinadores-page .form-control:focus,
    .coordinadores-page .form-select:focus {
        border-color: #4c8ce5;
        box-shadow: 0 0 0 3px rgba(59,130,246,.13);
        outline: none;
    }

    .coord-help {
        display: block;
        color: var(--coord-muted);
        font-size: .76rem;
        margin-top: 6px;
    }

    /* BOTONES */

    .coordinadores-page .btn {
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

    /* AVISO */

    .coord-info {
        display: flex;
        gap: 11px;
        padding: 14px 16px;
        background: var(--coord-blue-light);
        border: 1px solid #d5e5fb;
        border-radius: 10px;
        color: #31557f;
        font-size: .84rem;
        margin-bottom: 20px;
    }

    .coord-info strong {
        color: var(--coord-blue-dark);
    }

    /* TABLA */

    .coord-table-wrapper {
        background: white;
        border: 1px solid var(--coord-border);
        border-radius: 17px;
        overflow: hidden;
        box-shadow: 0 5px 20px rgba(31,57,90,.045);
    }

    .coord-table-header {
        padding: 20px 23px;
        border-bottom: 1px solid var(--coord-border);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }

    .coord-table-header h2 {
        margin: 0 0 3px;
        color: var(--coord-blue-dark);
        font-size: 1.05rem;
        font-weight: 700;
    }

    .coord-table-header p {
        margin: 0;
        color: var(--coord-muted);
        font-size: .81rem;
    }

    .coord-table-wrapper .table {
        margin: 0;
    }

    .coord-table-wrapper thead th {
        background: #f5f8fc;
        color: #64748b;
        font-size: .74rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .35px;
        border-bottom: 1px solid var(--coord-border);
        padding: 14px 18px;
        white-space: nowrap;
    }

    .coord-table-wrapper tbody td {
        color: var(--coord-text);
        font-size: .87rem;
        padding: 15px 18px;
        vertical-align: middle;
        border-color: #edf2f7;
    }

    .coord-table-wrapper tbody tr {
        transition: background .15s ease;
    }

    .coord-table-wrapper tbody tr:hover {
        background: #f8fbff;
    }

    .coord-name {
        color: var(--coord-blue);
        font-weight: 700;
        text-decoration: none;
    }

    .coord-name:hover {
        color: var(--coord-blue-dark);
        text-decoration: underline;
    }

    .coord-email {
        color: #5d7088;
    }

    .coord-status {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 10px;
        border-radius: 30px;
        font-size: .74rem;
        font-weight: 700;
    }

    .coord-status.active {
        color: #18794e;
        background: #e8f7ef;
    }

    .coord-status.inactive {
        color: #a33b3b;
        background: #fdecec;
    }

    .coord-status-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: currentColor;
    }

    .coord-empty {
        text-align: center;
        padding: 45px 20px !important;
        color: var(--coord-muted) !important;
    }

    .coord-empty-icon {
        font-size: 32px;
        margin-bottom: 10px;
    }

    /* PAGINACIÓN */

    .coord-pagination {
        padding: 18px 20px;
        border-top: 1px solid var(--coord-border);
        background: #fbfdff;
    }

    .coord-pagination nav {
        display: flex;
        justify-content: center;
    }

    /* RESPONSIVE */

    @media (max-width: 800px) {
        .coordinadores-grid {
            grid-template-columns: 1fr;
        }

        .coord-card.full {
            grid-column: auto;
        }
    }

    @media (max-width: 650px) {

        .coordinadores-page {
            padding: 20px 10px 35px;
        }

        .coordinadores-header {
            padding: 23px 18px;
        }

        .coordinadores-header-content {
            align-items: flex-start;
        }

        .coordinadores-icon {
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

        .coord-table-header {
            padding: 18px;
        }

        .coord-table-wrapper .table {
            min-width: 650px;
        }

        .coord-table-wrapper {
            overflow-x: auto;
        }
    }
</style>

<div class="coordinadores-page">
    <div class="coordinadores-container">

        {{-- ENCABEZADO --}}
        <header class="coordinadores-header">
            <div class="coordinadores-header-content">
                <div class="coordinadores-icon">👨‍🏫</div>

                <div>
                    <h1>Coordinadores</h1>
                    <p>
                        Gestión de cuentas, designaciones y coordinadores
                        del Sistema de Tutorías Académicas
                    </p>
                </div>
            </div>
        </header>

        {{-- FORMULARIOS SUPERIORES --}}
        <div class="coordinadores-grid">

            {{-- DESIGNAR CUENTA --}}
            <section class="coord-card">

                <div class="coord-card-header">
                    <div class="coord-card-icon">🔗</div>

                    <div>
                        <h2>Designar cuenta registrada</h2>
                        <p>Convertir una cuenta existente en coordinador</p>
                    </div>
                </div>

                <div class="coord-card-body">

                    <div class="coord-info">
                        <div>ℹ️</div>

                        <div>
                            <strong>Nota:</strong>
                            el acceso quedará limitado a las secciones
                            que autorices posteriormente.
                        </div>
                    </div>

                    <form method="post" action="{{ route('coordinadores.designar') }}">
                        @csrf

                        <div class="mb-3">
                            <label class="coord-label" for="candidato">
                                Cuenta registrada
                            </label>

                            <select id="candidato"
                                    name="user_id"
                                    class="form-select"
                                    required>

                                <option value="">Selecciona una cuenta</option>

                                @foreach($candidatos as $candidate)
                                    <option value="{{ $candidate->id }}">
                                        {{ $candidate->nombre }}
                                        {{ $candidate->apellidos }}
                                        —
                                        {{ $candidate->email }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="motivo-designacion" class="coord-label">
                                Motivo de designación
                            </label>

                            <textarea id="motivo-designacion"
                                      name="motivo"
                                      class="form-control"
                                      required
                                      maxlength="2000"
                                      placeholder="Indica el motivo de la designación..."></textarea>
                        </div>

                        <button class="btn coord-btn-primary w-100">
                            ✓ Designar coordinador y configurar permisos
                        </button>
                    </form>

                </div>
            </section>

            {{-- DAR DE ALTA --}}
            <section class="coord-card" id="nuevo-coordinador">

                <div class="coord-card-header">
                    <div class="coord-card-icon">➕</div>

                    <div>
                        <h2>Crear coordinador</h2>
                        <p>La cuenta quedará activa. Después podrás asignar sus grupos y permisos por sección.</p>
                    </div>
                </div>

                <div class="coord-card-body">

                    <form method="post" action="{{ route('coordinadores.create') }}">
                        @csrf

                        <div class="row g-3">

                            {{-- Campos de cuenta actuales del proyecto --}}
                            @include('partials.account-fields')

                            <div class="col-12">
                                <button class="btn coord-btn-primary w-100">
                                    + Crear coordinador y configurar permisos
                                </button>
                            </div>

                        </div>
                    </form>

                </div>
            </section>

        </div>

        {{-- LISTADO --}}
        <section class="coord-table-wrapper">

            <div class="coord-table-header">
                <div>
                    <h2>Coordinadores registrados</h2>
                    <p>
                        Consulta el estado y administra las cuentas
                        registradas.
                    </p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table">

                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Usuario</th>
                            <th>Correo</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse($coordinadores as $coordinador)

                            <tr>
                                <td>
                                    <a class="coord-name"
                                       href="{{ route('coordinador.edit', $coordinador->id) }}">
                                        {{ $coordinador->nombre }}
                                        {{ $coordinador->apellidos }}
                                    </a>
                                </td>

                                <td>{{ $coordinador->username }}</td>

                                <td class="coord-email">{{ $coordinador->email }}</td>

                                <td>
                                    @if($coordinador->status === 10)
                                        <span class="coord-status active">
                                            <span class="coord-status-dot"></span>
                                            Activo
                                        </span>
                                    @else
                                        <span class="coord-status inactive">
                                            <span class="coord-status-dot"></span>
                                            Inactivo
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <a class="btn btn-outline-primary btn-sm mb-2" href="{{ route('coordinador.edit', $coordinador->id) }}">Ver / Actualizar</a>
                                    @include('partials.account-delete', ['account' => $coordinador, 'deleteRoute' => 'coordinador.destroy'])
                                    <form method="post" action="{{ route('coordinador.status', $coordinador->id) }}" onsubmit="return confirm('¿Confirmas el cambio de estado de este coordinador? La baja bloqueará su acceso y liberará sus grupos.');">
                                        @csrf
                                        <input type="hidden" name="status" value="{{ $coordinador->status === 10 ? 0 : 10 }}">
                                        <button class="btn btn-sm {{ $coordinador->status === 10 ? 'btn-outline-danger' : 'btn-outline-success' }}">{{ $coordinador->status === 10 ? 'Dar de baja' : 'Reactivar' }}</button>
                                    </form>
                                </td>
                            </tr>

                        @empty

                            <tr>
                                <td colspan="5" class="coord-empty">
                                    <div class="coord-empty-icon">👨‍🏫</div>
                                    No hay coordinadores registrados.
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>
            </div>

            {{-- PAGINACIÓN --}}
            @if($coordinadores->hasPages())
                <div class="coord-pagination">
                    {{ $coordinadores->links() }}
                </div>
            @endif

        </section>

        <div class="text-center text-muted small mt-4">
            Sistema de Tutorías Académicas · Gestión de coordinadores
        </div>

    </div>
</div>

@endsection