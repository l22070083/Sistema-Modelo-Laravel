@extends('layout')
@section('content')
<h1>{{ isset($token) ? 'Nueva contraseña' : 'Recuperar contraseña' }}</h1>
<form class="card card-body" method="post" action="{{ isset($token) ? route('password.reset', $token) : route('password.request') }}">@csrf
@if(isset($token))
<label for="password">Nueva contraseña</label><input id="password" class="form-control mb-3" name="password" type="password" autocomplete="new-password" required>
<label for="confirmation">Confirma la contraseña</label><input id="confirmation" class="form-control mb-3" name="password_confirmation" type="password" autocomplete="new-password" required>
@else
<label for="email">Correo</label><input id="email" class="form-control mb-3" name="email" type="email" required>
@endif
<button class="btn btn-primary">Enviar</button></form>
@if(!isset($token))<form class="card card-body mt-3" method="post" action="{{ route('email.resend') }}">@csrf<h2 class="h5">Reenviar verificación</h2><label for="resend-email">Correo</label><input id="resend-email" class="form-control mb-3" name="email" type="email" required><button class="btn btn-secondary">Reenviar</button></form>@endif
@endsection
