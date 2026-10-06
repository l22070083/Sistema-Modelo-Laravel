@extends('layout')

@section('title', 'Catálogo: ' . ucfirst($catalog))

@section('content')
<<<<<<< HEAD
<h1>Catálogo: {{ ucfirst($catalog) }}</h1>
<form id="{{ $editing ? 'editar-registro' : 'nuevo-registro' }}" class="card card-body mb-4" method="post" action="{{ $editing ? route('catalogo.save', [$catalog,$editing->id]) : route('catalogo.save', $catalog) }}">@csrf
<h2 class="h5">{{ $editing ? 'Editar' : 'Crear' }}</h2><label for="nombre">Nombre</label><input name="nombre" id="nombre" class="form-control mb-3" value="{{ old('nombre',$editing?->nombre) }}" required maxlength="{{ $catalog==='genero'?50:100 }}">
@if($catalog!=='genero')<label for="estado">Estado</label><select name="estado" id="estado" class="form-select mb-3"><option value="1" @selected(old('estado',$editing?->estado)==1)>Activo</option><option value="0" @selected(old('estado',$editing?->estado)===0)>Inactivo</option></select>@endif
@if($catalog==='grupo')
<label for="licenciatura">Licenciatura</label><select name="licenciatura_id" id="licenciatura" class="form-select mb-3" required>@foreach($licenciaturas as $l)<option value="{{ $l->id }}" @selected(old('licenciatura_id',$editing?->licenciatura_id)==$l->id)>{{ $l->nombre }}</option>@endforeach</select>
<label for="periodo">Periodo</label><input id="periodo" name="periodo" class="form-control mb-3" value="{{ old('periodo',$editing?->periodo) }}" maxlength="20">
@endif<button class="btn btn-primary">Guardar</button></form>
<x-table-scroll label="Catálogo"><table class="table"><thead><tr><th>ID</th><th>Nombre</th><th>Estado</th><th>Acción</th></tr></thead><tbody>@foreach($rows as $row)<tr><td>{{ $row->id }}</td><td>{{ $row->nombre }}</td><td>{{ isset($row->estado)?($row->estado?'Activo':'Inactivo'):'—' }}</td><td><a href="{{ route('catalogo.edit',[$catalog,$row->id]) }}">Editar</a></td></tr>@endforeach</tbody></table></x-table-scroll>{{ $rows->links() }}
=======

<style>
    :root {
        --catalog-blue: #174a88;
        --catalog-blue-dark: #10345f;
        --catalog-blue-light: #eaf2ff;
        --catalog-border: #dbe5f1;
        --catalog-text: #26384e;
        --catalog-muted: #718096;
    }

    .catalog-page {
        color: var(--catalog-text);
    }

    /* Encabezado */
    .catalog-header {
        background: linear-gradient(135deg, var(--catalog-blue-dark), var(--catalog-blue));
        border-radius: 18px;
        padding: 28px 30px;
        color: #fff;
        margin-bottom: 22px;
        box-shadow: 0 10px 25px rgba(16, 52, 95, .15);
    }

    .catalog-header h1 {
        margin: 0 0 7px;
        font-size: 28px;
        font-weight: 700;
    }

    .catalog-header p {
        margin: 0;
        opacity: .9;
        font-size: 14px;
    }

    /* Tarjeta del formulario */
    .catalog-card {
        background: #fff;
        border: 1px solid var(--catalog-border);
        border-radius: 17px;
        overflow: hidden;
        margin-bottom: 25px;
        box-shadow: 0 7px 22px rgba(31, 55, 86, .07);
    }

    .catalog-card-header {
        background: #f8fbff;
        border-bottom: 1px solid var(--catalog-border);
        padding: 18px 22px;
        display: flex;
        align-items: center;
        gap: 13px;
    }

    .catalog-icon {
        width: 42px;
        height: 42px;
        border-radius: 11px;
        background: var(--catalog-blue-light);
        color: var(--catalog-blue);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        font-weight: 700;
    }

    .catalog-card-header h2 {
        margin: 0;
        color: var(--catalog-blue-dark);
        font-size: 18px;
        font-weight: 700;
    }

    .catalog-card-header span {
        display: block;
        margin-top: 3px;
        color: var(--catalog-muted);
        font-size: 12px;
    }

    .catalog-card-body {
        padding: 23px 22px;
    }

    /* Campos */
    .catalog-label {
        display: block;
        color: #425466;
        font-size: 13px;
        font-weight: 600;
        margin-bottom: 7px;
    }

    .catalog-page .form-control,
    .catalog-page .form-select {
        border: 1px solid var(--catalog-border);
        border-radius: 9px;
        padding: 10px 12px;
        color: var(--catalog-text);
        transition: border-color .2s ease, box-shadow .2s ease;
    }

    .catalog-page .form-control:focus,
    .catalog-page .form-select:focus {
        border-color: var(--catalog-blue);
        box-shadow: 0 0 0 3px rgba(23, 74, 136, .12);
    }

    /* Botón */
    .btn-catalog {
        background: linear-gradient(135deg, var(--catalog-blue), #2468b5);
        color: #fff;
        border: none;
        border-radius: 9px;
        padding: 10px 20px;
        font-weight: 600;
        box-shadow: 0 4px 10px rgba(23, 74, 136, .18);
        transition: .2s ease;
    }

    .btn-catalog:hover {
        background: linear-gradient(135deg, var(--catalog-blue-dark), var(--catalog-blue));
        color: #fff;
        transform: translateY(-1px);
    }

    /* Tabla */
    .table-card {
        background: #fff;
        border: 1px solid var(--catalog-border);
        border-radius: 17px;
        overflow: hidden;
        box-shadow: 0 7px 22px rgba(31, 55, 86, .07);
    }

    .table-card-header {
        background: #f8fbff;
        border-bottom: 1px solid var(--catalog-border);
        padding: 17px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
    }

    .table-card-header h2 {
        margin: 0;
        color: var(--catalog-blue-dark);
        font-size: 17px;
        font-weight: 700;
    }

    .record-count {
        background: var(--catalog-blue-light);
        color: var(--catalog-blue);
        border: 1px solid #cbdcf5;
        border-radius: 20px;
        padding: 5px 11px;
        font-size: 11px;
        font-weight: 700;
    }

    .catalog-table {
        margin: 0;
        vertical-align: middle;
    }

    .catalog-table thead th {
        background: var(--catalog-blue);
        color: #fff;
        border: none;
        padding: 13px 15px;
        font-size: 12px;
        font-weight: 600;
        white-space: nowrap;
    }

    .catalog-table tbody td {
        padding: 13px 15px;
        border-color: #edf2f7;
        font-size: 13px;
    }

    .catalog-table tbody tr:hover {
        background: #f8fbff;
    }

    .id-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 34px;
        padding: 4px 8px;
        border-radius: 7px;
        background: #f1f5f9;
        color: #526274;
        font-size: 11px;
        font-weight: 700;
    }

    .status-badge {
        display: inline-block;
        border-radius: 20px;
        padding: 5px 10px;
        font-size: 11px;
        font-weight: 700;
    }

    .status-active {
        background: #e8f7ee;
        color: #157347;
    }

    .status-inactive {
        background: #fdeaea;
        color: #b02a37;
    }

    .status-neutral {
        background: #f1f5f9;
        color: #64748b;
    }

    .edit-link {
        display: inline-block;
        color: var(--catalog-blue);
        background: var(--catalog-blue-light);
        border: 1px solid #cbdcf5;
        border-radius: 7px;
        padding: 6px 11px;
        font-size: 12px;
        font-weight: 600;
        text-decoration: none;
        transition: .2s ease;
    }

    .edit-link:hover {
        background: var(--catalog-blue);
        color: #fff;
    }

    /* Paginación */
    .pagination-wrapper {
        padding: 15px 18px;
        border-top: 1px solid var(--catalog-border);
    }

    .pagination-wrapper nav {
        margin: 0;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .catalog-header {
            padding: 22px;
        }

        .catalog-header h1 {
            font-size: 23px;
        }

        .catalog-card-body {
            padding: 19px;
        }

        .table-card {
            border-radius: 13px;
        }
    }
</style>

<div class="catalog-page">


{{-- Encabezado --}}
<div class="catalog-header">
    <h1>
        Catálogo: {{ ucfirst($catalog) }}
    </h1>

    <p>
        Administra los registros disponibles en este catálogo institucional.
    </p>
</div>


{{-- Formulario --}}
<div class="catalog-card">

    <div class="catalog-card-header">

        <div class="catalog-icon">
            {{ $editing ? '✎' : '+' }}
        </div>

        <div>
            <h2>
                {{ $editing ? 'Editar registro' : 'Crear registro' }}
            </h2>

            <span>
                {{ $editing
                    ? 'Modifica la información del registro seleccionado.'
                    : 'Completa los datos para agregar un nuevo registro.'
                }}
            </span>
        </div>

    </div>

    <div class="catalog-card-body">

        <form
            method="post"
            action="{{
                $editing
                    ? route('catalogo.save', [$catalog, $editing->id])
                    : route('catalogo.save', $catalog)
            }}"
        >
            @csrf

            <div class="row g-3">

                {{-- Nombre --}}
                <div class="{{ $catalog === 'grupo' ? 'col-md-6' : 'col-md-8' }}">

                    <label
                        for="nombre"
                        class="catalog-label"
                    >
                        Nombre
                    </label>

                    <input
                        name="nombre"
                        id="nombre"
                        class="form-control"
                        value="{{ old('nombre', $editing?->nombre) }}"
                        required
                        maxlength="{{ $catalog === 'genero' ? 50 : 100 }}"
                        placeholder="Ingresa el nombre"
                    >

                </div>


                {{-- Estado --}}
                @if($catalog !== 'genero')

                    <div class="{{ $catalog === 'grupo' ? 'col-md-6' : 'col-md-4' }}">

                        <label
                            for="estado"
                            class="catalog-label"
                        >
                            Estado
                        </label>

                        <select
                            name="estado"
                            id="estado"
                            class="form-select"
                        >
                            <option
                                value="1"
                                @selected(old('estado', $editing?->estado) == 1)
                            >
                                Activo
                            </option>

                            <option
                                value="0"
                                @selected(old('estado', $editing?->estado) === 0)
                            >
                                Inactivo
                            </option>
                        </select>

                    </div>

                @endif


                {{-- Campos exclusivos de grupo --}}
                @if($catalog === 'grupo')

                    <div class="col-md-6">

                        <label
                            for="licenciatura"
                            class="catalog-label"
                        >
                            Licenciatura
                        </label>

                        <select
                            name="licenciatura_id"
                            id="licenciatura"
                            class="form-select"
                            required
                        >
                            @foreach($licenciaturas as $l)

                                <option
                                    value="{{ $l->id }}"
                                    @selected(old('licenciatura_id', $editing?->licenciatura_id) == $l->id)
                                >
                                    {{ $l->nombre }}
                                </option>

                            @endforeach
                        </select>

                    </div>


                    <div class="col-md-6">

                        <label
                            for="periodo"
                            class="catalog-label"
                        >
                            Periodo
                        </label>

                        <input
                            id="periodo"
                            name="periodo"
                            class="form-control"
                            value="{{ old('periodo', $editing?->periodo) }}"
                            maxlength="20"
                            placeholder="Ej. Enero-Junio 2026"
                        >

                    </div>

                @endif

            </div>


            <div class="mt-4">

                <button
                    class="btn btn-catalog"
                    type="submit"
                >
                    {{ $editing ? 'Guardar cambios' : 'Guardar registro' }}
                </button>

            </div>

        </form>

    </div>

</div>


{{-- Tabla --}}
<div class="table-card">

    <div class="table-card-header">

        <h2>
            Registros del catálogo
        </h2>

        <span class="record-count">
            {{ $rows->total() }} registros
        </span>

    </div>

    <div class="table-responsive">

        <table class="table catalog-table">

            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Estado</th>
                    <th>Acción</th>
                </tr>
            </thead>

            <tbody>

                @forelse($rows as $row)

                    <tr>

                        <td>
                            <span class="id-badge">
                                #{{ $row->id }}
                            </span>
                        </td>

                        <td>
                            <strong>
                                {{ $row->nombre }}
                            </strong>
                        </td>

                        <td>

                            @if(isset($row->estado))

                                @if($row->estado)
                                    <span class="status-badge status-active">
                                        Activo
                                    </span>
                                @else
                                    <span class="status-badge status-inactive">
                                        Inactivo
                                    </span>
                                @endif

                            @else

                                <span class="status-badge status-neutral">
                                    —
                                </span>

                            @endif

                        </td>

                        <td>
                            <a
                                class="edit-link"
                                href="{{ route('catalogo.edit', [$catalog, $row->id]) }}"
                            >
                                Editar
                            </a>
                        </td>

                    </tr>

                @empty

                    <tr>
                        <td
                            colspan="4"
                            class="text-center py-4"
                        >
                            <span class="text-muted">
                                No hay registros disponibles en este catálogo.
                            </span>
                        </td>
                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>


    @if($rows->hasPages())

        <div class="pagination-wrapper">
            {{ $rows->links() }}
        </div>

    @endif

</div>


</div>

>>>>>>> c285146 (agregando nuevas vistas de acuerdo con los colores de la escuela dentro del panel administrativo)
@endsection
