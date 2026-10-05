@extends('layout')
@section('title', 'Coordinadores')
@section('content')
<h1>Coordinadores</h1>
<section class="card card-body mb-4"><h2 class="h5">Designar una cuenta registrada como coordinador</h2><p>Selecciona la cuenta local o Microsoft. Su acceso quedará limitado a las secciones que autorices después.</p>
<form method="post" action="{{ route('coordinadores.designar') }}" class="row g-3">@csrf
<div class="col-md-6"><label class="form-label" for="candidato">Cuenta registrada</label><select id="candidato" name="user_id" class="form-select" required><option value="">Selecciona una cuenta</option>@foreach($candidatos as $candidate)<option value="{{ $candidate->id }}">{{ $candidate->nombre }} {{ $candidate->apellidos }} — {{ $candidate->email }}</option>@endforeach</select></div>
<div class="col-md-6"><label for="motivo-designacion" class="form-label">Motivo de designación</label><textarea id="motivo-designacion" name="motivo" class="form-control" required maxlength="2000"></textarea></div>
<div><button class="btn btn-primary">Designar coordinador y configurar permisos</button></div>
</form></section>
<div class="card mb-4" id="nuevo-coordinador"><div class="card-body">
<h2 class="h5">Crear coordinador</h2>
<p>La cuenta quedará activa. Después podrás asignar sus grupos y permisos por sección.</p>
<form method="post" action="{{ route('coordinadores.create') }}" class="row g-3">@csrf
    @include('partials.account-fields')
    <div class="col-12"><button class="btn btn-primary">Crear coordinador y configurar permisos</button></div>
</form>
</div></div>
<x-table-scroll label="Listado de coordinadores"><table class="table"><thead><tr><th>Nombre</th><th>Usuario</th><th>Correo</th><th>Estado</th></tr></thead><tbody>
@forelse($coordinadores as $coordinador)
<tr><td><a href="{{ route('coordinador.edit',$coordinador->id) }}">{{ $coordinador->nombre }} {{ $coordinador->apellidos }}</a></td><td>{{ $coordinador->username }}</td><td>{{ $coordinador->email }}</td><td>{{ $coordinador->status === 10 ? 'Activo' : 'Inactivo' }}</td></tr>
@empty<tr><td colspan="4">No hay coordinadores registrados.</td></tr>@endforelse
</tbody></table></x-table-scroll>
{{ $coordinadores->links() }}
@endsection
