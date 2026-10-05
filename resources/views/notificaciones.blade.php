@extends('layout')
@section('title', 'Notificaciones')
@section('content')
<h1>Notificaciones</h1>
<div class="card"><div class="card-body">
<h2 class="h5">Solicitudes de alta de alumnos ({{ $alumnos->total() }})</h2>
<p>Puedes dar de alta a un alumno, varios seleccionados o todos los pendientes de todas las páginas.</p>
<form id="seleccionados" action="{{ route('notificaciones.alta') }}" method="post">@csrf<input type="hidden" name="modo" value="seleccionados"></form>
<x-table-scroll label="Solicitudes de alta"><table class="table"><thead><tr><th><label><input type="checkbox" id="marcar-pagina"> Seleccionar página</label></th><th>Alumno</th><th>Correo</th><th>Matrícula</th><th>Acción</th></tr></thead><tbody>
@forelse($alumnos as $alumno)
<tr>
<td><input form="seleccionados" type="checkbox" class="seleccion-alumno" name="seleccion[]" value="{{ $alumno->id }}" aria-label="Seleccionar a {{ $alumno->nombre }}"></td>
<td>{{ $alumno->nombre }} {{ $alumno->apellidos }}</td><td>{{ $alumno->email }}</td><td>{{ $alumno->matricula }}</td>
<td><form action="{{ route('notificaciones.alta') }}" method="post" onsubmit="return confirm('¿Dar de alta a este alumno y avisarle por correo?')">@csrf<input type="hidden" name="modo" value="individual"><input type="hidden" name="seleccion[]" value="{{ $alumno->id }}"><button class="btn btn-success btn-sm">Dar de alta</button></form></td>
</tr>
@empty<tr><td colspan="5">No hay solicitudes de alta pendientes.</td></tr>@endforelse
</tbody></table></x-table-scroll>
{{ $alumnos->links() }}
<div class="approval-actions"><button form="seleccionados" class="btn btn-primary" @disabled(!$alumnos->total()) onclick="return confirm('¿Dar de alta a los seleccionados?')">Dar de alta a los seleccionados</button>
<form action="{{ route('notificaciones.alta') }}" method="post" onsubmit="return confirm('¿Dar de alta a TODOS los pendientes, incluyendo todas las páginas?')">@csrf<input type="hidden" name="modo" value="todos"><button class="btn btn-success" @disabled(!$alumnos->total())>Dar de alta a todos ({{ $alumnos->total() }})</button></form></div>
</div></div>
<script>document.getElementById('marcar-pagina').addEventListener('change', function () { document.querySelectorAll('.seleccion-alumno').forEach(input => input.checked = this.checked); });</script>
@endsection
