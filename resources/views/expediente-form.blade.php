@extends('layout')
@section('content')
<h1>{{ $record?'Editar':'Llenar' }} expediente</h1>
<form method="post" class="card card-body">@csrf<input type="hidden" name="version" value="{{ \App\Http\Controllers\DossierController::fingerprint($record) }}">
@foreach($sections as $section=>$fields)<fieldset class="mb-4"><legend>{{ ucfirst($section) }}</legend>
@foreach($fields as $field)
@php($value=old('datos.'.$field,$record?->$field))
<div class="mb-3"><label for="{{ $field }}" class="form-label">{{ config('dossier.labels.'.$field,$field) }}</label>
@if(preg_match('/^q[1-7]_.*(?<!detalle)$/',$field)||$field==='q9_acomp_psicologico')
<select class="form-select" name="datos[{{ $field }}]" id="{{ $field }}" required><option value="0" @selected($value==0)>No</option><option value="1" @selected($value==1)>Sí</option></select>
@elseif($field==='q11_necesita_apoyo')
@php($supports=is_array($value)?$value:(json_decode($value?:'[]',true)?:[]))
@foreach(['Psicológico','De aprendizaje','Otro','Ninguno'] as $choice)<label class="d-block"><input type="checkbox" name="datos[{{ $field }}][]" value="{{ $choice }}" @checked(in_array($choice,$supports,true))> {{ $choice }}</label>@endforeach
@elseif($field==='licenciatura_id')<select class="form-select" name="datos[{{ $field }}]" id="{{ $field }}" required>@foreach($licenciaturas as $l)<option value="{{ $l->id }}" @selected($value==$l->id)>{{ $l->nombre }}</option>@endforeach</select>
@elseif(in_array($field,['genero','estado_civil','apnp_tipo_sangre','apnp_factor_rh','q10_estado_emocional']))
@php($options=['genero'=>['Masculino','Femenino','Otro'],'estado_civil'=>['Soltero(a)','Casado(a)','Unión Libre','Otro'],'apnp_tipo_sangre'=>['O','A','B','AB'],'apnp_factor_rh'=>['Positivo (+)','Negativo (-)'],'q10_estado_emocional'=>['Muy desfavorable','Desfavorable','Favorable','Muy favorable']][$field])
<select class="form-select" name="datos[{{ $field }}]" id="{{ $field }}" required><option value="">Seleccionar</option>@foreach($options as $option)<option @selected($value===$option)>{{ $option }}</option>@endforeach</select>
@elseif($field==='fecha_nacimiento')<input class="form-control" name="datos[{{ $field }}]" id="{{ $field }}" type="date" value="{{ $value }}" required max="{{ date('Y-m-d') }}">
@else<textarea class="form-control" name="datos[{{ $field }}]" id="{{ $field }}">{{ $value }}</textarea>@endif
</div>
@endforeach</fieldset>@endforeach
@if(auth()->user()->rol_id!==3)<label for="motivo">Motivo de la actualización</label><textarea name="motivo" id="motivo" class="form-control mb-3" required maxlength="2000"></textarea>@endif
<div class="d-flex gap-2 mb-3" id="pasos-expediente" hidden><button class="btn btn-outline-primary" type="button" id="paso-anterior">Anterior</button><button class="btn btn-outline-primary" type="button" id="paso-siguiente">Siguiente</button><span id="paso-actual" role="status"></span></div>
<button class="btn btn-primary" id="guardar-expediente">Guardar expediente</button></form>
<script>
const form=document.querySelector('form.card'),steps=Array.from(form.querySelectorAll('fieldset'));let activeStep=0;
function showStep(index){activeStep=index;steps.forEach((step,i)=>step.hidden=i!==index);document.getElementById('paso-anterior').disabled=index===0;document.getElementById('paso-siguiente').hidden=index===steps.length-1;document.getElementById('guardar-expediente').hidden=index!==steps.length-1;document.getElementById('paso-actual').textContent='Paso '+(index+1)+' de '+steps.length;}
if(steps.length>1){document.getElementById('pasos-expediente').hidden=false;showStep(0);document.getElementById('paso-anterior').onclick=()=>showStep(activeStep-1);document.getElementById('paso-siguiente').onclick=()=>{const invalid=Array.from(steps[activeStep].querySelectorAll('input,select,textarea')).find(input=>!input.checkValidity());if(invalid){invalid.reportValidity();}else{showStep(activeStep+1);}};form.addEventListener('invalid',event=>{const index=steps.findIndex(step=>step.contains(event.target));if(index>=0){showStep(index);}},true);}
</script>
@endsection
