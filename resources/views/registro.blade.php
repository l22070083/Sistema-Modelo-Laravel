@extends('layout')
@section('content')
<h1>Registro de alumno</h1>
<p>Tu cuenta permanecerá pendiente hasta que tu administrador o coordinador te dé de alta.</p>
<form method="post" action="{{ route('registro.submit') }}" class="card card-body">@csrf
@foreach(['nombre'=>'Nombre','apellidos'=>'Apellidos','username'=>'Usuario','email'=>'Correo'] as $field=>$label)
<label class="form-label" for="{{ $field }}">{{ $label }}</label><input class="form-control mb-3" name="{{ $field }}" id="{{ $field }}" type="{{ $field==='email'?'email':'text' }}" value="{{ old($field) }}" required maxlength="255">
@endforeach
@include('perfil-campos', ['user'=>null])
<label for="password">Contraseña</label><input class="form-control mb-3" id="password" name="password" type="password" required autocomplete="new-password">
<label for="password_confirmation">Confirma la contraseña</label><input class="form-control mb-3" id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password">
<button class="btn btn-primary">Registrarme</button>
</form>
@endsection
