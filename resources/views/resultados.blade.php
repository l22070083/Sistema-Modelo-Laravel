@extends('layout')
@section('content')
<h1>Resultados y valoración de salud</h1>
<p>La clasificación se obtiene del expediente: salud física (1, 3, 4, 5), socioemocional (2, 9, 10 y apoyo psicológico en 11) y psicopedagógica (6, 7 y apoyo de aprendizaje en 11). El administrador o coordinador autorizado puede determinar la clasificación y la prioridad.</p>
@forelse($assessments as $row)
<article class="card card-body mb-3"><h2 class="h5">{{ $row->nombre }} {{ $row->apellidos }}</h2>
<p>{{ implode(', ',json_decode($row->categoria_manual?:$row->categoria_atencion,true)?:[]) }} — {{ $row->atencion_prioritaria?'Atención prioritaria':'Sin prioridad marcada' }}</p>
@if($row->categoria_manual)<p>Valoración institucional por {{ $row->valorado_por }}. Esta valoración prevalece sobre la automática.</p>@endif
@if(auth()->user()->rol_id!==3 && \App\Services\SectionAccess::can('clasificacion',true))<a href="{{ route('expediente.ver',$row->id) }}" class="btn btn-outline-primary">Valorar o modificar clasificación y riesgo</a>@endif
</article>
@empty<p>No hay expedientes con clasificación disponible.</p>@endforelse
@if($results->isNotEmpty())
<h2 class="h4">Puntajes históricos conservados</h2><x-table-scroll label="Resultados históricos de salud"><table class="table"><thead><tr><th>Alumno</th><th>Nivel registrado</th><th>Estado</th><th>Fecha</th></tr></thead><tbody>@foreach($results as $row)<tr><td>{{ $row->nombre }} {{ $row->apellidos }}</td><td>{{ $row->nivel_riesgo }}</td><td>{{ $row->nivel_riesgo<=3?'estable':($row->nivel_riesgo<=6?'seguimiento':'urgente') }}</td><td>{{ $row->fecha }}</td></tr>@endforeach</tbody></table></x-table-scroll>
@endif
@endsection
