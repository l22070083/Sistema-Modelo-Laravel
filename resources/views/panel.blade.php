@extends('layout')
@section('title', 'Panel de Control · Escuela Modelo')
@section('content')
<h1 class="school-heading">Escuela Modelo Valladolid</h1>
<section class="dashboard-hero"><h2>¡Bienvenido, {{ auth()->user()->nombre }}!</h2><p>Sistema de Atención Oportuna y Detección de Riesgos Estudiantiles</p><span class="hero-badge"><x-icon name="chart"/>Panel de Control Actualizado</span><small>Consulta los expedientes y el seguimiento de salud de tus alumnos.</small></section>
@if($canSeeHealth)
<div class="metric-grid">
@foreach(['sin_alarma'=>['heart','Sin dato de alarma','success'],'seguimiento'=>['alert','En Seguimiento','warning'],'prioritaria'=>['pulse','Atención Prioritaria','danger']] as $key=>$metric)
<article class="metric-card text-{{ $metric[2] }}"><x-icon :name="$metric[0]"/><strong>{{ $metrics[$key] }}</strong><span>{{ $metric[1] }}</span></article>
@endforeach
</div>
<div class="chart-grid">
<section class="dashboard-chart"><h2><x-icon name="pulse"/>Distribución Global de Atención (Salud)</h2><div class="chart-content">
@php($total=$metrics['sin_alarma']+$metrics['seguimiento']+$metrics['prioritaria'])
@if($total)
@php($stable=100*$metrics['sin_alarma']/$total)
@php($follow=$stable+100*$metrics['seguimiento']/$total)
<div class="health-donut" role="img" aria-label="Sin dato de alarma: {{ $metrics['sin_alarma'] }}, seguimiento: {{ $metrics['seguimiento'] }}, atención prioritaria: {{ $metrics['prioritaria'] }}" style="--stable:{{ $stable }}%;--follow:{{ $follow }}%"><span>{{ $total }}<small>expedientes</small></span></div>
<p class="chart-legend"><span class="dot dot-stable"></span>Sin alarma <span class="dot dot-follow"></span>Seguimiento <span class="dot dot-priority"></span>Prioritaria</p>
@else<p class="empty-chart">Aún no hay expedientes clasificados.</p>@endif
<p class="text-muted small">{{ $metrics['sin_expediente'] }} alumnos pendientes de expediente o clasificación.</p>
</div></section>
<section class="dashboard-chart categories-chart"><h2><x-icon name="grid"/>Clasificación de Atención Estudiantil</h2><div class="chart-content category-bars">
@foreach($categories as $category=>$count)<div><span>{{ $category }}</span><strong>{{ $count }}</strong><meter min="0" max="{{ max(1,array_sum($categories)) }}" value="{{ $count }}">{{ $count }}</meter></div>@endforeach
<p class="text-muted small">Un alumno puede pertenecer a varias categorías. Se considera su valoración institucional cuando existe.</p>
</div></section>
</div>
@elseif($canSeeStudents)<div class="alert alert-info">Tu panel muestra los datos personales autorizados. La clasificación de salud requiere permiso del administrador.</div>
@else<div class="card card-body mb-4"><h2 class="h5">Panel de coordinador</h2><p class="mb-0">El administrador debe asignar los permisos de consulta de tus secciones. Puedes revisar las solicitudes de alta en Notificaciones.</p></div>@endif
@if($canSeeStudents)
<section class="dashboard-list"><header><h2>Listado de Alumnos y Seguimiento Individual</h2><form method="get"><label class="visually-hidden" for="alumno-search">Buscar alumno</label><input id="alumno-search" class="form-control" name="q" value="{{ request('q') }}" placeholder="Escriba un nombre para filtrar..."><button class="btn btn-light">Buscar</button></form></header>
<div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>ID</th><th>Nombre Completo</th><th>Correo</th>@if($canSeeHealth)<th>Atención</th>@endif<th>Acciones</th></tr></thead><tbody>
@forelse($students as $student)<tr><td>{{ $student->id }}</td><td>{{ $student->nombre }} {{ $student->apellidos }}</td><td>{{ $student->email }}</td>@if($canSeeHealth)<td>{{ !$student->expediente_id?'Pendiente de expediente':($student->atencion_prioritaria?'Prioritaria':implode(', ',\App\Services\DossierRules::categories($student->categoria_manual?:$student->categoria_atencion))) }}</td>@endif<td><a class="btn btn-outline-primary btn-sm" href="{{ route('alumno.ver',$student->id) }}">Ver alumno</a></td></tr>@empty<tr><td colspan="5">No hay alumnos que coincidan.</td></tr>@endforelse
</tbody></table></div><div class="p-3">{{ $students->links() }}</div>
</section>
@endif
@endsection
