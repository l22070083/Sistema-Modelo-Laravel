<label for="matricula">Matrícula</label><input class="form-control mb-3" name="matricula" id="matricula" required pattern="[0-9]+" maxlength="50" value="{{ old('matricula', $user?->matricula) }}">
@foreach(['licenciatura_id'=>['Licenciatura',$licenciaturas], 'grupo_id'=>['Grupo',$grupos]] as $field=>$options)
<label for="{{ $field }}">{{ $options[0] }}</label><select class="form-select mb-3" name="{{ $field }}" id="{{ $field }}" @if($field!=='grupo_id') required @endif><option value="">Seleccionar</option>
@foreach($options[1] as $option)<option value="{{ $option->id }}" @if($field==='grupo_id') data-licenciatura="{{ $option->licenciatura_id }}" @endif @selected(old($field, $user?->$field)==$option->id)>{{ $option->nombre }}</option>@endforeach
</select>
@endforeach
<script>
document.getElementById('licenciatura_id').addEventListener('change',function(){let group=document.getElementById('grupo_id');group.value='';Array.from(group.options).forEach(o=>{o.hidden=o.value!==''&&o.dataset.licenciatura!==this.value;});});
</script>
