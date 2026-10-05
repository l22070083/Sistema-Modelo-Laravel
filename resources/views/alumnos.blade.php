@extends('layout')
@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
    <h1 class="mb-0">Alumnos</h1>
    @if(auth()->user()->rol_id === \App\Models\User::ADMIN)<a class="btn btn-primary" href="{{ route('alumno.create') }}">Crear alumno</a>@endif
</div>
<x-table-scroll label="Listado de alumnos"><table class="table"><thead><tr><th>Nombre</th><th>Usuario</th><th>Matrícula</th><th>Acción</th></tr></thead><tbody>@forelse($rows as $student)<tr><td>{{ $student->nombre }} {{ $student->apellidos }}</td><td>{{ $student->username }}</td><td>{{ $student->matricula }}</td><td><a href="{{ route('alumno.ver',$student->id) }}">Ver</a></td></tr>@empty<tr><td colspan="4">No hay alumnos registrados.</td></tr>@endforelse</tbody></table></x-table-scroll>{{ $rows->links() }}
@endsection
