@extends('layout')
@section('content')
<h1>Encuestas de salud</h1>
@foreach($surveys->prepend(null) as $survey)
<form class="card card-body mb-3" action="{{ $survey ? route('encuesta.save',$survey->id) : route('encuesta.save') }}" method="post">@csrf<h2 class="h5">{{ $survey?'Editar encuesta':'Nueva encuesta' }}</h2>
<label>Título<input class="form-control" name="titulo" value="{{ $survey?->titulo }}" required maxlength="255"></label><label>Descripción<textarea class="form-control" name="descripcion">{{ $survey?->descripcion }}</textarea></label><label>Estado<select class="form-select" name="estado"><option value="1" @selected($survey?->estado==1)>Activa</option><option value="0" @selected($survey?->estado===0)>Inactiva</option></select></label><button class="btn btn-primary mt-3">Guardar encuesta</button></form>
@endforeach
<h2>Preguntas</h2>
@foreach($questions->prepend(null) as $question)
<form class="card card-body mb-3" action="{{ $question ? route('pregunta.save',$question->id) : route('pregunta.save') }}" method="post">@csrf
<label>Encuesta<select class="form-select" name="encuesta_id" required>@foreach($surveys->filter() as $s)<option value="{{ $s->id }}" @selected($question?->encuesta_id==$s->id)>{{ $s->titulo }}</option>@endforeach</select></label>
<label>Pregunta<textarea class="form-control" name="planteamiento" required>{{ $question?->planteamiento }}</textarea></label>
<label>Riesgo<select class="form-select" name="tipo_riesgo">@foreach(['bajo','medio','alto'] as $risk)<option @selected($question?->tipo_riesgo===$risk)>{{ $risk }}</option>@endforeach</select></label>
<label>Estado<select class="form-select" name="status"><option value="1" @selected($question?->status==1)>Activa</option><option value="0" @selected($question?->status===0)>Inactiva</option></select></label><button class="btn btn-primary mt-3">{{ $question?'Guardar pregunta':'Crear pregunta' }}</button></form>
@endforeach
@endsection
