@extends('layout')

@section('title', 'Administradores')

@section('content')

<style>
    :root {
        --admin-blue: #174a88;
        --admin-blue-dark: #10345f;
        --admin-blue-light: #eaf2ff;
        --admin-border: #dbe5f1;
        --admin-text: #26384e;
        --admin-muted: #718096;
        --admin-bg: #f4f7fb;
    }

    .admins-page {
        background: var(--admin-bg);
        min-height: 100vh;
        padding: 30px 16px 50px;
    }

    .admins-container {
        max-width: 1100px;
        margin: 0 auto;
    }

    /* HEADER */

    .admins-header {
        background: linear-gradient(125deg, #10345f, #205ca2);
        color: white;
        border-radius: 18px;
        padding: 30px;
        margin-bottom: 25px;
        box-shadow: 0 8px 25px rgba(23, 74, 136, .15);
        position: relative;
        overflow: hidden;
    }

    .admins-header::after {
        content: "";
        position: absolute;
        width: 210px;
        height: 210px;
        border: 28px solid rgba(255,255,255,.07);
        border-radius: 50%;
        right: -60px;
        top: -80px;
    }

    .admins-header-content {
        display: flex;
        align-items: center;
        gap: 18px;
        position: relative;
        z-index: 1;
    }

    .admins-icon {
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

    .admins-header h1 {
        margin: 0 0 5px;
        font-size: clamp(1.5rem, 3vw, 2rem);
        font-weight: 750;
    }

    .admins-header p {
        margin: 0;
        color: #dbeafe;
        font-size: .93rem;
    }

    /* TARJETAS */

    .admin-card {
        background: white;
        border: 1px solid var(--admin-border);
        border-radius: 17px;
        overflow: hidden;
        box-shadow: 0 5px 20px rgba(31, 57, 90, .045);
        margin-bottom: 20px;
    }

    .admin-card-header {
        display: flex;
        align-items: center;
        gap: 13px;
        padding: 20px 23px;
        border-bottom: 1px solid var(--admin-border);
    }

    .admin-card-icon {
        width: 41px;
        height: 41px;
        border-radius: 11px;
        background: var(--admin-blue-light);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 19px;
        flex-shrink: 0;
    }

    .admin-card-header h2 {
        margin: 0 0 3px;
        color: var(--admin-blue-dark);
        font-size: 1.02rem;
        font-weight: 700;
    }

    .admin-card-header p {
        margin: 0;
        color: var(--admin-muted);
        font-size: .81rem;
    }

    .admin-card-body {
        padding: 23px;
    }

    /* FORMULARIOS */

    .admins-page .form-label {
        color: #34465d;
        font-size: .87rem;
        font-weight: 650;
        margin-bottom: 7px;
    }

    .admins-page .form-control,
    .admins-page .form-select {
        min-height: 44px;
        border: 1px solid #d4deeb;
        border-radius: 9px;
        color: var(--admin-text);
        padding: 9px 12px;
        transition: border-color .2s, box-shadow .2s;
    }

    .admins-page .form-control:focus,
    .admins-page .form-select:focus {
        border-color: #4c8ce5;
        box-shadow: 0 0 0 3px rgba(59,130,246,.13);
        outline: none;
    }

    .admins-page small,
    .admins-page .form-text {
        color: var(--admin-muted);
        font-size: .76rem;
    }

    /* BOTONES */

    .admins-page .btn {
        min-height: 42px;
        border-radius: 9px;
        padding: 9px 17px;
        font-size: .88rem;
        font-weight: 650;
        transition: all .2s ease;
    }

    .admin-btn-primary {
        background: var(--admin-blue);
        border: 1px solid var(--admin-blue);
        color: white;
    }

    .admin-btn-primary:hover {
        background: var(--admin-blue-dark);
        border-color: var(--admin-blue-dark);
        color: white;
        transform: translateY(-1px);
    }

    /* AVISO */

    .admin-info {
        display: flex;
        gap: 11px;
        padding: 14px 16px;
        background: var(--admin-blue-light);
        border: 1px solid #d5e5fb;
        border-radius: 10px;
        color: #31557f;
        font-size: .84rem;
        margin-bottom: 20px;
    }

    .admin-info strong {
        color: var(--admin-blue-dark);
    }

    /* TABLA */

    .admin-table-card {
        background: white;
        border: 1px solid var(--admin-border);
        border-radius: 17px;
        overflow: hidden;
        box-shadow: 0 5px 20px rgba(31,57,90,.045);
    }

    .admin-table-header {
        padding: 20px 23px;
        border-bottom: 1px solid var(--admin-border);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }

    .admin-table-header h2 {
        margin: 0 0 3px;
        color: var(--admin-blue-dark);
        font-size: 1.05rem;
        font-weight: 700;
    }

    .admin-table-header p {
        margin: 0;
        color: var(--admin-muted);
        font-size: .81rem;
    }

    .admin-count {
        background: var(--admin-blue-light);
        color: var(--admin-blue);
        padding: 6px 12px;
        border-radius: 20px;
        font-size: .75rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .admin-table-card .table {
        margin: 0;
    }

    .admin-table-card thead th {
        background: #f5f8fc;
        color: #64748b;
        font-size: .74rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .35px;
        border-bottom: 1px solid var(--admin-border);
        padding: 14px 18px;
        white-space: nowrap;
    }

    .admin-table-card tbody td {
        color: var(--admin-text);
        font-size: .87rem;
        padding: 15px 18px;
        vertical-align: middle;
        border-color: #edf2f7;
    }

    .admin-table-card tbody tr {
        transition: background .15s ease;
    }

    .admin-table-card tbody tr:hover {
        background: #f8fbff;
    }

    .admin-name {
        color: var(--admin-blue-dark);
        font-weight: 700;
    }

    .admin-email {
        color: #5d7088;
    }

    .admin-status {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 10px;
        border-radius: 30px;
        font-size: .74rem;
        font-weight: 700;
    }

    .admin-status.active {
        color: #18794e;
        background: #e8f7ef;
    }

    .admin-status.inactive {
        color: #a33b3b;
        background: #fdecec;
    }

    .admin-status-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: currentColor;
    }

    .admin-empty {
        text-align: center;
        padding: 45px 20px !important;
        color: var(--admin-muted) !important;
    }

    .admin-empty-icon {
        font-size: 32px;
        margin-bottom: 10px;
    }

    /* PAGINACIÓN */

    .admin-pagination {
        padding: 18px 20px;
        border-top: 1px solid var(--admin-border);
        background: #fbfdff;
    }

    .admin-pagination nav {
        display: flex;
        justify-content: center;
    }

    /* RESPONSIVE */

    @media (max-width: 650px) {

        .admins-page {
            padding: 20px 10px 35px;
        }

        .admins-header {
            padding: 23px 18px;
        }

        .admins-header-content {
            align-items: flex-start;
        }

        .admins-icon {
            width: 54px;
            height: 54px;
            font-size: 25px;
        }

        .admin-card-body,
        .admin-card-header,
        .admin-table-header {
            padding: 18px;
        }

        .admin-table-card .table {
            min-width: 650px;
        }

        .admin-table-card {
            overflow-x: auto;
        }
    }
</style>

<div class="admins-page">
    <div class="admins-container">

        {{-- ENCABEZADO --}}
        <header class="admins-header">
            <div class="admins-header-content">
                <div class="admins-icon">🛡️</div>

                <div>
                    <h1>Administradores</h1>
                    <p>
                        Gestión de cuentas con acceso total al sistema
                        de tutorías.
                    </p>
                </div>
            </div>
        </header>

        {{-- CREAR ADMINISTRADOR --}}
        <section class="admin-card" id="nuevo-administrador">

            <div class="admin-card-header">
                <div class="admin-card-icon">➕</div>

                <div>
                    <h2>Crear administrador</h2>
                    <p>Dar de alta una nueva cuenta de administrador</p>
                </div>
            </div>

            <div class="admin-card-body">

                <div class="admin-info">
                    <div>ℹ️</div>

                    <div>
                        <strong>Nota:</strong>
                        la cuenta quedará activa y tendrá acceso a todas
                        las secciones y a la administración de usuarios.
                    </div>
                </div>

                <form method="post"
                      action="{{ route('administradores.create') }}"
                      class="row g-3">

                    @csrf

                    @include('partials.account-fields')

                    <div class="col-12">
                        <button class="btn admin-btn-primary">
                            + Crear administrador
                        </button>
                    </div>

                </form>

            </div>

        </section>

        {{-- LISTADO --}}
        <section class="admin-table-card">

            <div class="admin-table-header">

                <div>
                    <h2>Administradores registrados</h2>
                    <p>Consulta el estado de las cuentas registradas.</p>
                </div>

                <span class="admin-count">
                    {{ $administradores->total() }} registros
                </span>

            </div>

            <x-table-scroll label="Listado de administradores">

                <table class="table">

                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Usuario</th>
                            <th>Correo</th>
                            <th>Estado</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse($administradores as $administrator)

                            <tr>
                                <td class="admin-name">
                                    {{ $administrator->nombre }}
                                    {{ $administrator->apellidos }}
                                </td>

                                <td>{{ $administrator->username }}</td>

                                <td class="admin-email">{{ $administrator->email }}</td>

                                <td>
                                    @if($administrator->status === \App\Models\User::ACTIVE)
                                        <span class="admin-status active">
                                            <span class="admin-status-dot"></span>
                                            Activo
                                        </span>
                                    @else
                                        <span class="admin-status inactive">
                                            <span class="admin-status-dot"></span>
                                            Inactivo
                                        </span>
                                    @endif
                                </td>
                            </tr>

                        @empty

                            <tr>
                                <td colspan="4" class="admin-empty">
                                    <div class="admin-empty-icon">🛡️</div>
                                    No hay administradores registrados.
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </x-table-scroll>

            {{-- PAGINACIÓN --}}
            @if($administradores->hasPages())
                <div class="admin-pagination">
                    {{ $administradores->links() }}
                </div>
            @endif

        </section>

        <div class="text-center text-muted small mt-4">
            Sistema de Tutorías Académicas · Gestión de administradores
        </div>

    </div>
</div>

@endsection