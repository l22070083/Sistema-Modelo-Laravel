@extends('layout')
@section('content')
<h1>Mi perfil</h1><form class="card card-body mb-3" method="post" action="{{ route('perfil') }}">@csrf @include('perfil-campos')<button class="btn btn-primary">Guardar perfil</button></form>
@if(config('services.microsoft.enabled'))
<form method="post" action="{{ route('microsoft.link') }}" class="card card-body">@csrf<h2 class="h5">Vincular Microsoft</h2><label for="local-password">Confirma tu contraseña local</label><input id="local-password" type="password" name="password" class="form-control mb-3" autocomplete="current-password" required><button class="btn btn-outline-primary">Vincular mi cuenta Microsoft</button></form>
@endif
@endsection
