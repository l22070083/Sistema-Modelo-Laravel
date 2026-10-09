@extends('layout')
@section('content')
<h1>Bienvenido, {{ auth()->user()->nombre }}</h1>
@if(auth()->user()->rol_id===3)<div class="d-flex flex-wrap gap-3"><a class="btn btn-primary" href="{{ route('perfil') }}">Completar mi perfil</a><a class="btn btn-primary" href="{{ route('mi-expediente') }}">Mi expediente</a></div>@endif
@endsection
