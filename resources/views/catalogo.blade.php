@extends('layout')
@section('content')
<h1>Catálogo: {{ ucfirst($catalog) }}</h1>
<form class="card card-body mb-4" method="post" action="{{ $editing ? route('catalogo.save', [$catalog,$editing->id]) : route('catalogo.save', $catalog) }}">@csrf
<h2 class="h5">{{ $editing ? 'Editar' : 'Crear' }}</h2><label for="nombre">Nombre</label><input name="nombre" id="nombre" class="form-control mb-3" value="{{ old('nombre',$editing?->nombre) }}" required maxlength="{{ $catalog==='genero'?50:100 }}">
@if($catalog!=='genero')<label for="estado">Estado</label><select name="estado" id="estado" class="form-select mb-3"><option value="1" @selected(old('estado',$editing?->estado)==1)>Activo</option><option value="0" @selected(old('estado',$editing?->estado)===0)>Inactivo</option></select>@endif
@if($catalog==='grupo')
<label for="licenciatura">Licenciatura</label><select name="licenciatura_id" id="licenciatura" class="form-select mb-3" required>@foreach($licenciaturas as $l)<option value="{{ $l->id }}" @selected(old('licenciatura_id',$editing?->licenciatura_id)==$l->id)>{{ $l->nombre }}</option>@endforeach</select>
<label for="periodo">Periodo</label><input id="periodo" name="periodo" class="form-control mb-3" value="{{ old('periodo',$editing?->periodo) }}" maxlength="20">
@endif<button class="btn btn-primary">Guardar</button></form>
<table class="table"><thead><tr><th>ID</th><th>Nombre</th><th>Estado</th><th>Acción</th></tr></thead><tbody>@foreach($rows as $row)<tr><td>{{ $row->id }}</td><td>{{ $row->nombre }}</td><td>{{ isset($row->estado)?($row->estado?'Activo':'Inactivo'):'—' }}</td><td><a href="{{ route('catalogo.edit',[$catalog,$row->id]) }}">Editar</a></td></tr>@endforeach</tbody></table>{{ $rows->links() }}
@endsection
