@extends('layout')
@section('content')<h1>Editar perfil del alumno</h1><form method="post" class="card card-body">@csrf @include('perfil-campos')<button class="btn btn-primary">Guardar</button></form>@endsection
