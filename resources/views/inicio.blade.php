@extends('layout')
@section('content')
<h1>Bienvenido, {{ auth()->user()->nombre }}</h1>
@if(auth()->user()->rol_id===3)<div class="d-flex flex-wrap gap-3"><a class="btn btn-primary" href="{{ route('perfil') }}">Completar mi perfil</a><a class="btn btn-primary" href="{{ route('encuesta.responder') }}">Responder encuesta de salud</a><a class="btn btn-primary" href="{{ route('mi-expediente') }}">Mi expediente</a><a class="btn btn-outline-primary" href="{{ route('resultados') }}">Mis resultados</a></div>@endif
@endsection
