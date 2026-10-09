@extends('layout')
@section('title', 'Panel de Control · Escuela Modelo')
@section('content')
<h1 class="school-heading">Escuela Modelo Valladolid</h1>
<section class="dashboard-hero"><h2>¡Bienvenido, {{ auth()->user()->nombre }}!</h2><p>Sistema de Gestión de Alumnos y Expedientes</p><span class="hero-badge"><x-icon name="chart"/>Panel de Control</span><small>Consulta los alumnos y sus expedientes.</small></section>
@if($canSeeStudents)
<div class="metric-grid">
@foreach(['alumnos'=>['users','Alumnos activos','primary'],'expedientes'=>['folder','Expedientes registrados','success'],'pendientes'=>['file','Pendientes de expediente','warning']] as $key=>$metric)
<article class="metric-card text-{{ $metric[2] }}"><x-icon :name="$metric[0]"/><strong>{{ $metrics[$key] }}</strong><span>{{ $metric[1] }}</span></article>
@endforeach
</div>
<section class="dashboard-list"><header><h2>Listado de Alumnos</h2><form method="get"><label class="visually-hidden" for="alumno-search">Buscar alumno</label><input id="alumno-search" class="form-control" name="q" value="{{ request('q') }}" placeholder="Escriba un nombre para filtrar..."><button class="btn btn-light">Buscar</button></form></header>
<x-table-scroll label="Listado de alumnos"><table class="table table-hover mb-0"><thead><tr><th>ID</th><th>Nombre Completo</th><th>Correo</th><th>Expediente</th><th>Acciones</th></tr></thead><tbody>
@forelse($students as $student)<tr><td>{{ $student->id }}</td><td>{{ $student->nombre }} {{ $student->apellidos }}</td><td>{{ $student->email }}</td><td>{{ $student->expediente_id?'Registrado':'Pendiente' }}</td><td><a class="btn btn-outline-primary btn-sm" href="{{ route('alumno.ver',$student->id) }}">Ver alumno</a></td></tr>@empty<tr><td colspan="5">No hay alumnos que coincidan.</td></tr>@endforelse
</tbody></table></x-table-scroll><div class="p-3">{{ $students->links() }}</div>
</section>
@else
<div class="card card-body mb-4"><h2 class="h5">Panel de coordinador</h2><p class="mb-0">El administrador debe asignar los permisos de consulta de tus secciones. Puedes revisar las solicitudes de alta en Notificaciones.</p></div>
@endif
@endsection
