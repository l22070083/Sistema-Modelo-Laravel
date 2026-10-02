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
<div class="card mb-4"><div class="card-body">
<h2 class="h5">Dar de alta a un coordinador</h2>
<form method="post" action="{{ route('coordinadores.create') }}" class="row g-3">@csrf
@foreach(['nombre' => 'Nombre', 'apellidos' => 'Apellidos', 'username' => 'Usuario', 'email' => 'Correo'] as $field => $label)
    <div class="col-md-6"><label class="form-label" for="{{ $field }}">{{ $label }}</label>
    <input class="form-control" id="{{ $field }}" name="{{ $field }}" value="{{ old($field) }}" type="{{ $field === 'email' ? 'email' : 'text' }}" maxlength="255" @if($field !== 'apellidos') required @endif></div>
@endforeach
    <div class="col-md-6"><label class="form-label" for="password">Contraseña</label><input class="form-control" id="password" name="password" type="password" required autocomplete="new-password"><small>Al menos 8 caracteres, una mayúscula y un carácter especial.</small></div>
    <div class="col-12"><button class="btn btn-primary">Dar de alta</button></div>
</form>
</div></div>
<div class="table-responsive"><table class="table"><thead><tr><th>Nombre</th><th>Usuario</th><th>Correo</th><th>Estado</th></tr></thead><tbody>
@forelse($coordinadores as $coordinador)
<tr><td><a href="{{ route('coordinador.edit',$coordinador->id) }}">{{ $coordinador->nombre }} {{ $coordinador->apellidos }}</a></td><td>{{ $coordinador->username }}</td><td>{{ $coordinador->email }}</td><td>{{ $coordinador->status === 10 ? 'Activo' : 'Inactivo' }}</td></tr>
@empty<tr><td colspan="4">No hay coordinadores registrados.</td></tr>@endforelse
</tbody></table></div>
{{ $coordinadores->links() }}
@endsection
