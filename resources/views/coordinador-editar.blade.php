@extends('layout')
@section('content')
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
@endsection
