@extends('layout')
@section('content')
<h1>{{ $survey->titulo }}</h1><p>{{ $survey->descripcion }}</p>
<p>Progreso: {{ $answered }} de {{ $questions->total() }} preguntas</p><p id="estado-guardado" role="status"></p>
<form action="{{ route('encuesta.responder',['id'=>$survey->id,'page'=>$questions->currentPage()]) }}" method="post">@csrf
@foreach($questions as $question)<fieldset class="card card-body mb-3"><legend class="h5">{{ $question->planteamiento }}</legend>@foreach(['Si','No'] as $answer)<label><input type="radio" class="respuesta" name="respuestas[{{ $question->id }}]" value="{{ $answer }}" data-pregunta="{{ $question->id }}" @checked(($previous[$question->id]??null)===$answer) required> {{ $answer }}</label>@endforeach</fieldset>@endforeach
<button class="btn btn-primary">{{ $questions->hasMorePages()?'Guardar y continuar':'Guardar y finalizar' }}</button></form><div class="mt-3">{{ $questions->links() }}</div>
<script>
let queue=Promise.resolve();document.querySelectorAll('.respuesta').forEach(input=>input.addEventListener('change',()=>{let question=input.dataset.pregunta,value=input.value;queue=queue.catch(()=>{}).then(async()=>{const state=document.getElementById('estado-guardado');state.textContent='Guardando…';try{const response=await fetch(@json(route('encuesta.autoguardado')),{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':@json(csrf_token())},body:JSON.stringify({pregunta_id:question,respuesta:value})});if(!response.ok)throw new Error();state.textContent='Respuesta guardada.';}catch(error){state.textContent='No se pudo guardar automáticamente. Usa Guardar y continuar.';}});}));
</script>
@endsection
