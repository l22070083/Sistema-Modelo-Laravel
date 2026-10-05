@extends('layout')
@section('title', 'Crear alumno')
@section('content')
<h1>Crear alumno</h1>
<p>El alumno quedará activo y podrá iniciar sesión con su usuario y contraseña. Su expediente se podrá completar después.</p>
<form method="post" action="{{ route('alumno.create') }}" class="card card-body">@csrf
    <div class="row g-3">
        @include('partials.account-fields', ['requiredSurname' => true])
        <div class="col-12">@include('perfil-campos', ['user' => null])</div>
        <div class="col-12 form-actions">
            <button class="btn btn-primary">Crear alumno</button>
            <a class="btn btn-outline-secondary" href="{{ route('alumnos') }}">Volver a alumnos</a>
        </div>
    </div>
</form>
@endsection
